<?php
/**
 * @projectDescription		Helpful methods
 * @author					Craig Dietrich
 * @version					1.0
 */

class Resource_model extends Model {

	var $resource_table = 'resources';

    public function __construct() {
    	
        parent::__construct();
        
    }
    
    public function get($field='') {
    	
    	$this->db->select('value');
    	$this->db->from($this->resource_table);
    	$this->db->where('field', $field);
    	$query = $this->db->get();
    	if ($query->num_rows==0) return null;
    	$result = $query->result();
    	return $result[0]->value;
    	
    }
    
    public function page_views() {
    	
		return array('plain' => 'Single column',
					 'text' => 'Text emphasis',
					 'media' => 'Media emphasis',
					 'split' => 'Split emphasis',
					 'par' => 'Media per paragraph', 
					 'vis' => 'Visualization: Radial',
					 'visindex' => 'Visualization: Index',
					 'vispath' => 'Visualization: Paths',
					 'vismedia' => 'Visualization: Media',
					 'vistag' => 'Visualization: Tags',
					 'versions' => 'History editor',
					 'history' => 'History browser',
					 'meta' => 'Metadata',
					 'rdf' => 'RDF');  	
    	
    }
    
    public function file_views() {
    	
    	return array('file' => 'File',
					 'versions' => 'History editor', 
					 'meta' => 'Metadata',
					 'rdf' => 'RDF',
					 'vis' => 'Visualization: Radial',								
					 'visindex' => 'Visualization: Index',
					 'vispath' => 'Visualization: Paths',
					 'vismedia' => 'Visualization: Media',
					 'vistag' => 'Visualization: Tags',
					 'manage_annotations' => 'Annotation editor');  
    	
    }
    
}
?>
