/**
 * @projectDescription  Boot Scalar Javascript/jQuery using yepnope.js
 * @author              Craig Dietrich
 * @version             Cantaloupe 1.0
 */

/**
 * Get paths to script and style directories
 */
var script_uri = document.getElementsByTagName("script")[0].src;
var base_uri = 'http://'+script_uri.replace('http://','').split('/').slice(0,-2).join('/');
var arbors_uri = base_uri.substr(0, base_uri.lastIndexOf('/'));
var views_uri = arbors_uri.substr(0, arbors_uri.lastIndexOf('/'));
var modules_uri = views_uri+'/modules';
var widgets_uri = views_uri+'/widgets';

/**
 * Not an easy way to wait for a CSS file to load (even with yepnope doing a good job in Firefox)
 * This function takes a test, for example, that the width of an element has changed, and runs a callback
 */
function when(tester_func, callback) {
	var timeout = 50;
	var passed = tester_func();
	if ('undefined'!=typeof(passed) && passed) {
		callback();
		return;
	}
	setTimeout(function() {
		when(tester_func, callback)
	}, timeout);
}


/*
 * $.fn.slotmanager_create_slot
 * Create a slot and attach to a tag
 * @param obj options, required 'url_attributes' 
 */	

$.fn.slotmanager_create_slot = function(width, options) {

	$tag = $(this); 
	if ($tag.hasClass('inline')) return;
	$tag.data( 'slot', $('<div class="slot"></div>') );
	var url = null;
	
	// Get URL
	
	var url = null;
	for (var k in options['url_attributes']) {;
		if ('undefined'==typeof($tag.attr(options['url_attributes'][k]))) continue;
		if ($tag.attr(options['url_attributes'][k]).length>0) {
			url = $tag.attr(options['url_attributes'][k]);
			break;
		}
	}
	if (!url) return;
	
	// Seperate seek hash if present
	
	var annotation_url = null;
	var uri_components = url.split('#');
	
	// TODO: Special case for hypercities #, until we correctly variable-ify #'s
	if (uri_components.length>1 && uri_components[0].toLowerCase().indexOf('hypercities')!=-1) {
		// keep URL as it is
	} else if (uri_components.length>1) {
		var url = uri_components[0];
		annotation_url = uri_components[1];	
		//if (annotation_url && annotation_url.indexOf('://')==-1) annotation_url = dirname(document.location.href)+'/'+annotation_url;	
		// modified by Erik below to remove duplicated 'annotations/' in url
		if (annotation_url && annotation_url.indexOf('://')==-1) annotation_url = scalarapi.model.urlPrefix+annotation_url;	
	}

	// Metadata resource
	var resource = $tag.attr('resource');		
	
	// Create media element object
	
	var opts = {};
	opts.width = width; 
	opts.player_dir = $('link#approot').attr('href')+'static/players/';
	opts.base_dir = scalarapi.model.urlPrefix;
	opts.seek = annotation_url;
	opts.chromeless = true;
	//if (opts.seek && opts.seek.length) alert('[Test mode] Asking to seek: '+opts.seek);		
	$tag.data('path', url);
	$tag.data('meta', resource);
	$tag.mediaelement(opts);
	
	// Insert media element's embed markup
	
	if (!$tag.data('mediaelement')) return false;  // mediaelement rejected the file
	$tag.data('slot').html( $tag.data('mediaelement').getEmbedObject() );

	return $tag.data('slot');

}


/**
 * Boot the interface
 */   
$(window).ready(function() {
	
	// Trash button
  	$('.hide_page_link').click(function() {
  		var uri = document.location.href;
  		if (uri.indexOf('?')!=-1) uri = uri.substr(0, uri.indexOf('?'));
  		if (uri.indexOf('#')!=-1) uri = uri.substr(0, uri.indexOf('#'));
  		document.location.href = uri + '?action=removed';
  		return false;
  	});
	
	yepnope([
	         
		  // Scalar API
		  {load: [base_uri+'/js/jquery.rdfquery.rules.min-1.0.js',
		          base_uri+'/js/jquery.RDFa.js',
		          widgets_uri+'/cookie/jquery.cookie.js',
		          widgets_uri+'/api/scalarapi.js'], complete:function() {
			  
				/**
				 * Get raw JSON (for including directly into Scalar API?)
				 */			 
				var rdf = $(document.body).RDFa();
				var rdf_json = rdf.dump();
				console.log('------- RDFa JSON ----------------------------');
				console.log(rdf_json);
				
				scalarapi.model.urlPrefix = document.location.href.split('/').slice(0,5).join('/')+'/';
				
				scalarapi.model.parseNodes(rdf_json);
				
				//$('<div id="header"><a href="'+scalarapi.model.urlPrefix+'index?template=cantaloupe_dev">Home</a></div>').prependTo('article');

				//console.log(JSON.stringify(rdf_json));
				/**
				 * Navigating the RDFa using jquery.RDFa.js' methods if needed
				 */
				console.log('------- Current page from RDFa ---------------');
				console.log( 'current page title: '+rdf.predicate('http://purl.org/dc/terms/title') );
				console.log( 'current page description: '+rdf.predicate('http://purl.org/dc/terms/description') );
				console.log( 'current page content: '+rdf.predicate('http://rdfs.org/sioc/ns#content') );
				console.log('------- Relationships from RDFa  -------------');
				// Tags
				var rel = rdf.relations('in').nodes_by_type();
				for (var uri in rel) console.log('has tag: '+rdf.predicate(rel[uri], 'http://purl.org/dc/terms/title'));
				var rel = rdf.relations('out').nodes_by_type();
				for (var uri in rel) console.log('tag of: '+rdf.predicate(rel[uri], 'http://purl.org/dc/terms/title'));
				// Paths
				var rel = rdf.relations('in').nodes_by_type('index');
				for (var uri in rel) console.log('has path: '+rdf.predicate(rel[uri], 'http://purl.org/dc/terms/title')+" at '"+rdf.types(rel[uri])+"'");
				var rel = rdf.relations('out').nodes_by_type('index');
				for (var uri in rel) console.log('path of: '+rdf.predicate(rel[uri], 'http://purl.org/dc/terms/title')+" at '"+rdf.types(rel[uri])+"'");
				// Annotations	
				var rel = rdf.relations('in').nodes_by_type('t');
				for (var uri in rel) console.log('has annotation: '+rdf.predicate(rel[uri], 'http://purl.org/dc/terms/title')+" at '"+rdf.types(rel[uri])+"'");
				var rel = rdf.relations('out').nodes_by_type('t');
				for (var uri in rel) console.log('annotation of: '+rdf.predicate(rel[uri], 'http://purl.org/dc/terms/title')+" at '"+rdf.types(rel[uri])+"'");	
				// Comments	
				var rel = rdf.relations('in').nodes_by_type('datetime');
				for (var uri in rel) console.log('has reply: '+rdf.predicate(rel[uri], 'http://purl.org/dc/terms/title')+" at '"+rdf.types(rel[uri])+"'");
				var rel = rdf.relations('out').nodes_by_type('datetime');
				for (var uri in rel) console.log('reply of: '+rdf.predicate(rel[uri], 'http://purl.org/dc/terms/title')+" at '"+rdf.types(rel[uri])+"'");
				// References
				var rel = rdf.predicates('http://purl.org/dc/terms/isReferencedBy');
				for (var uri in rel) console.log('is referenced by: '+rdf.predicate(rel[uri].value, 'http://purl.org/dc/terms/title'));
				var rel = rdf.predicates('http://purl.org/dc/terms/references');
				for (var uri in rel) console.log('references: '+rdf.predicate(rel[uri].value, 'http://purl.org/dc/terms/title'));	
						  
		  }},   
		  
		  // Background visualization layer
		  {load: [widgets_uri+'/vis/scalarvis2.css',
		          widgets_uri+'/vis/jquery.scalarvis2.js'], complete:function() {  
			  	// TODO: Background visualization initialization here
		  }},  		  
		  
		  // Mediaelement
		  {load: [widgets_uri+'/mediaelement/AC_QuickTime.js',
		          widgets_uri+'/mediaelement/flowplayer.js',
		          widgets_uri+'/mediaelement/jquery.annotate.js',
		          widgets_uri+'/mediaelement/froogaloop.min.js',
		          widgets_uri+'/mediaelement/mediaelement.css',
		          widgets_uri+'/mediaelement/annotation.css',
		          widgets_uri+'/mediaelement/jquery.mediaelement.js',
		          widgets_uri+'/mediaelement/jquery.jplayer.min.js'], complete:function() {
			  	// TODO
		  }},
		  
		  // Slot managers
		  {load: [widgets_uri+'/slotmanager/jquery.texteo.js',
		          widgets_uri+'/slotmanager/texteo.css',
		          widgets_uri+'/slotmanager/jquery.inlineslotmanager.js'], complete:function() {
			  	// TODO
			  	
			  	/*
			  	// add initial drop cap
			  	$('[property="sioc:content"] p,div').eq(0).each(function () {
				      var el = $(this),
				      text = el.html(),
		              first = text.slice(0, 1),
				      rest = text.slice(1);
				      $(this).parent().prepend("<span class='dropcap'>" + first + "</span>");
				      el.html(rest);
				});
				*/
			  	
			  	$('[property="sioc:content"] p,div').addClass('body_copy').wrap('<div class="paragraph_wrapper"></div>');
			  	
				$('a').each(function() {
					if ($(this).attr('resource')) {
					
						var slot ;
						/*if (Math.random() < .75) {
							slot = $(this).slotmanager_create_slot(300 + (Math.round((Math.random() * 5)) * 50), {url_attributes: ['href', 'src']});
						} else {*/
						//}
						/*if (!$(this).parent('p,div').parent().hasClass('paragraph_wrapper')) {
							$(this).parent('p,div').wrapAll('<div class="paragraph_wrapper"></div>');
						}*/
						
						
						var factor = Math.random();
						
						//if (factor > .75) {
							// large, right
							slot = $(this).slotmanager_create_slot(620, {url_attributes: ['href', 'src']});
							slot.addClass('right');
							$(this).parent('p,div').before(slot);
						
						/*} else if (factor > .5) {
							// full width
							slot = $(this).slotmanager_create_slot(1040, {url_attributes: ['href', 'src']});
							$(this).parent('p,div').parent().before(slot);
						} else if (factor > .25) {
							// smaller, right
							slot = $(this).slotmanager_create_slot(412, {url_attributes: ['href', 'src']});
							slot.addClass('right');
							$(this).parent('p,div').before(slot);
						} else {
							slot = $(this).slotmanager_create_slot(206, {url_attributes: ['href', 'src']});
							slot.addClass('left');
							$(this).parent('p,div').before(slot);
						}*/
						
						
							

						
						/*if ($(this).parent('li').length > 0) {
							$(this).parent('li').before(slot);
						} else if ($(this).prev('br').length > 0) {
							$(this).prev('br').before(slot);
						} else {
							$(this).parent().prepend(slot);
						}*/
					}
				});		
				
				/*$('.paragraph_wrapper').each(function() {
					$(this).find('.slot').eq(0).css({'clear':'both', 'margin-top':'24px'});
				});
				$('.paragraph_wrapper').prepend('<div class="float_spacer"></div>');	  */	
				
				// rewrite Scalar hyperlinks to point to the cantaloupe_dev template
				$('a').each(function() {
					var href = $(this).attr('href');
					if ((href.indexOf(scalarapi.model.urlPrefix.substr(0, scalarapi.model.urlPrefix.length-1)) != -1) || (href.indexOf('http://') == -1)) {
						if (href.split('?').length > 1) {
							href += '&template=cantaloupe_dev';
						} else {
							href += '?template=cantaloupe_dev';
						}
						$(this).attr('href', href);
					}
				});
				
				$('#book-title').parent().wrap('<div id="header"></div>');
		  }},

		  // Content preview
		  {load: [widgets_uri+'/contentpreview/jquery.scalarcontentpreview.js'], complete:function() {
			  	// TODO: content preview
		  }},
		  
		  // Maximize + comments
		  {load: [widgets_uri+'/maximize/maximize.css',
		          widgets_uri+'/maximize/jquery.scalarmaximize.js',
		          '//www.google.com/recaptcha/api/js/recaptcha_ajax.js',
		          widgets_uri+'/replies/replies.js'], complete:function() {
				$('.reply_link').click(function() {
			    	commentFormDisplayForm();
			    	return false;
			    });
				if (document.location.hash.indexOf('comment')!=-1) commentFormDisplayForm();
		  }},
		  
		  // Live annotations
		  {load: [widgets_uri+'/liveannotations/jquery.scalarliveannotations.js'], complete:function() {
			$('body').bind('show_annotation', function(event, annotation, mediaelement) {
				if (!mediaelement.isPlaying()) return;
				$('<div></div>').appendTo('body').live_annotation({
					annotation:annotation, 
					mediaelement:mediaelement, 
					mode:(($("script[src*='vertslotmanager.js']").length) ? 'vert' : 'horiz'), 
					content_wrapper_id:'content_wrapper', 
					content_id:'content'
				});
			});	
			$('body').bind('hide_annotation', function(event) {});	
		  }}
  	  
	]);  // !yepnope
	
});

