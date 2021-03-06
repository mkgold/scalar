<?php
/**
 * @projectDescription		Base controller class to handle database and login tasks useful for all controllers
 * @author					Craig Dietrich
 * @version					2.2
 */

abstract class MY_Controller extends Controller {

	protected $data = array();

	public function MY_Controller() {

		parent::__construct();

		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		
		// GET vars, note that this requires 'uri_protocol' to be 'PATH_INFO' in config.php
		parse_str($_SERVER['QUERY_STRING'], $_GET);		
		
		$this->load->library( 'session' );	
		$this->load->helper( 'url' );
		$this->load->helper( 'html' );
		$this->load->helper( 'string' );
		$this->load->helper( 'language' );
		$this->load->helper( 'array' );	
		$this->load->helper( 'directory' );	
		$this->config->load( 'rdf' );
		$this->config->load( 'local_settings' );	
		$this->data['recaptcha_public_key'] = ($this->config->item('recaptcha_public_key')) ? $this->config->item('recaptcha_public_key') : '';
		
		// Models
		$this->load->model( 'user_model', 'users' );   // Interact with user database
		$this->load->model( 'login_model', 'login' );  // Handle login session		
		
		// Language (default set in config/config.php)
		$lang = (isset($_REQUEST['lang']) && file_exists(APPPATH.'language/'.$_REQUEST['lang'])) ? $_REQUEST['lang'] : null;
		$this->lang->load('content', $lang);
		
		// Database
		// TODO: I believe this opens two different connections to the same database
		$this->load->database();
		$this->load->library('RDF_Store', 'rdf_store');

		// Initalize view data
		$this->data['app_root'] = base_url().'system/application/';
		$this->data['ns'] = $this->config->item('namespaces');

		// Authentication
		try {
			if ($this->login->do_logout()) {
				header('Location: '.$this->redirect_url());
				exit;				
			} elseif ($this->login->do_login()) {			
				header('Location: '.$this->redirect_url());
				exit;
			}			
			$this->set_login_params();
		} catch (Exception $e) {
			$this->data['login_error'] =  $e->getMessage();
		}			
		

	}
	
	/**
	 * Set information about the logged-in user such as the books they are attached to
	 * @requires	$this->login
	 * @requires	$this->data
	 * @return 		null
	 */
	
	protected function set_login_params() {
		
		$this->data['login']          = $this->login->get();
		$this->data['login_is_super'] = (isset($this->data['login']->is_super) && $this->data['login']->is_super) ? true : false;
		$this->data['login_books']    = (isset($this->data['login']->user_id)) ? $this->login->get_books($this->data['login']->user_id) : array();
		$this->data['login_book_ids'] = $this->login->get_book_ids($this->data['login_books']);		
		
	}
	
	/**
	 * Set information about whether a logged-in user can edit a book or manage content
	 * @requires	$this->data
	 * @return 		null
	 */	

	protected function set_user_book_perms() {

		$this->data['user_level'] = null;
		// Admin
		if ($this->data['login_is_super']) {
			$this->data['user_level'] = 'Author';
		// Book author
		} elseif (!empty($this->data['book']) && in_array($this->data['book']->book_id, $this->data['login_book_ids'])) {
			$user_level = array_get_node('book_id', $this->data['book']->book_id, $this->data['login_books']);
			$this->data['user_level'] = ucwords($user_level['value']['relationship']);
		}	
		
	}
	
	/**
	 * Add information about a page's creator to the page array
	 * @requires	$this->data
	 * @return 		null
	 */	
	
	protected function set_page_user_fields() {

		// Add user fields for the current page and its versions
		$this->data['page'] = $this->add_user_fields($this->data['page']);
		
		// Add user fields for replies on the current page (for the reply popup box)
		if (isset($this->data['page']->versions) && !empty($this->data['page']->versions)) {
			for ($j = 0; $j < count($this->data['page']->versions); $j++) {
				if (!isset($this->data['page']->versions[$j]->has_replies) || empty($this->data['page']->versions[$j]->has_replies)) continue;
				for ($k = 0; $k < count($this->data['page']->versions[$j]->has_replies); $k++) {
					$this->data['page']->versions[$j]->has_replies[$k] = $this->add_user_fields($this->data['page']->versions[$j]->has_replies[$k]); 
				}
			}
		}
		
	}
	
	/**
	 * Add information about a creator to the passed node
	 * @param	obj $node
	 * @return  obj $node
	 */	
	
	protected function add_user_fields($node) {

		if (empty($node)) return $node;
		$anon = 'Anonymous';        // TODO: from config
		$invalid = 'Unknown User';  // TODO: from config

		// Page
		$fullname = null;
		if (!empty($node->user)) {
			$user = $this->users->get_by_user_id($node->user);
			$fullname = (!empty($user)) ? trim($user->fullname) : $invalid;
			if (isset($this->data['base_uri'])) {
				$node->homepage = confirm_slash($this->data['base_uri']).'users/'.$node->user;
			}			
		}
		$node->fullname = (!empty($fullname)) ? $fullname : $anon;
		// Versions
		if (isset($node->versions)) {
			for ($j = 0; $j < count($node->versions); $j++) {
				$fullname = null;
				if (!empty($node->versions[$j]->user)) {
					$user = $this->users->get_by_user_id($node->versions[$j]->user);
					$fullname = (!empty($user)) ? trim($user->fullname) : $invalid;
					if (isset($this->data['base_uri'])) {
						$node->versions[$j]->homepage = confirm_slash($this->data['base_uri']).'users/'.$node->versions[$j]->user;
					}						
				}
				$node->versions[$j]->fullname = (!empty($fullname)) ? $fullname : $anon;							
			}
		}
		
		return $node;
		
	}	
	
	/**
	 * Test a user level against logged-in status
	 * @param 	int $book_id
	 * @param	str $level 
	 * @return 	bool
	 */	
	
	protected function login_is_book_admin($level='Author') {

		if ($this->users->is_a(strtolower($this->data['user_level']), $level)) return true;
		return false;

	}		
	
	/**
	 * Protect a book against a user level
	 * @param 	int $book_id
	 * @param	str	$level
	 * @return 	null
	 */	
	
	protected function protect_book($level='Author') {
		
		if (!$this->login_is_book_admin($level)) $this->kickout();

	}

	/**
	 * Redirect the page to the base URL
	 * @return 	null
	 */

	protected function kickout() {
		
		header('Location: '.base_url());
		exit;
		
	}		
	
	/**
	 * Redirect the page to login
	 * @return 	null
	 */	
	
	protected function require_login($msg='') {
		
		$uri = (confirm_slash(base_url())).'system/login?redirect_url='.urlencode($this->redirect_url());
		if (!empty($msg)) $uri .= '&msg='.$msg;
		header('Location: '.$uri);
		exit;
		
	}

	/**
	 * Return a redirect URL
	 * @return 	str 	URI
	 */  
	
   	protected function redirect_url() {
   		
   		if (isset($_REQUEST{'redirect_url'}) && !empty($_REQUEST['redirect_url'])) return urldecode(trim($_REQUEST{'redirect_url'}));
    	return base_url(); 
   		
   	}		
	
} 

?>