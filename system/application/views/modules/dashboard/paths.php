<?$this->template->add_js(path_from_file(__FILE__).'tablesorter/jquery.tablesorter.min.js')?>
<?$this->template->add_css(path_from_file(__FILE__).'tablesorter/style.css')?>

		<script>
		$(document).ready(function() {
			$(".tablesorter").tablesorter({ 
        		headers: { 
        			0: {sorter: false }, 
        			1: {sorter: false }, // hidden column
            		8: {sorter: false }
        		}
   			}); 
   			
   			$(window).resize(function() { resizeList(); });
   			resizeList();
   			
		});	
		
		function resizeList() {
    		$('.table_wrapper').height(Math.max(200, $(window).height() - ($('.table_wrapper').offset().top + 100))+'px'); // magic number to get list height right
		}
		
		function pathsGetContainerOf(the_link) {
			var $the_link = $(the_link);
			var $the_row = $the_link.parent().parent();
			var version_segment = $the_row.attr('class');
			var version_segment = version_segment.substr(version_segment.indexOf('version_id_'));
			var version_segment = jQuery.trim(version_segment);
			var version_id = parseInt(version_segment.replace('version_id_',''));
			if (!$the_link.data('is_open')) {
				$the_link.data('orig_html', $the_link.html());
				var data = {action:'get_container_of',version_id:version_id}
				$.get('api/get_container_of', data, function(data) {
						if (data.length == 0) {
							$the_row.after('<tr class="container_of_wrapper" id="container_of_row_'+version_id+'"><td>&nbsp;</td><td class="odd" colspan="8">No items</td></tr>');
						} else {
						   	var $row = $('<tr class="container_of_wrapper" id="container_of_row_'+version_id+'"><td colspan="8" style="padding:0px 0px 0px 0px;"><table style="width:100%;" cellspacing="0" cellpadding="0"></table></td></tr>');
						   	var $header = ('<tr><th></th><th style=\"display:none;\">ID</th><th>Order</th><th>URI</th><th>Title</th><th>Description</th></tr>');
						   	$row.find('table').html($header);
						   	$the_row.after($row);			
						    for (var j in data) {
						    	var $data_row = $('<tr class="bottom_border container_of_row"></tr>');
						    	$data_row.html('<td style="white-space:nowrap;width:40px;"></td>');
								$data_row.append('<td property="id" style="display:none;">'+data[j]['versions'][0]['version_id']+"</td>");
								$data_row.append('<td style="white-space:nowrap;width:60px;" property="sort_number">'+data[j].sort_number+"</td>");
								$data_row.append('<td property="slug"><a href="<?=confirm_slash(base_url()).@confirm_slash($book->slug)?>'+data[j].slug+'">'+data[j].slug+"</a></td>");
								$data_row.append('<td property="title">'+data[j]['versions'][0]['title']+'</td>');
								$data_row.append('<td class="excerpt" property="description"><span class="full">'+data[j]['versions'][0]['description']+'</span><span class="clip">'+create_excerpt(data[j]['versions'][0]['description'],8)+'</span></td>');
								$row.find('table').find('tr:last').after($data_row);
						
						    }
						    /*
						    var $option_row = $('<tr class="bottom_border"></tr>');
						    $option_row.html('<td style="white-space:nowrap;width:40px;"></td>');
							$option_row.append('<td colspan="4"><a href="javascript:;" onclick="edit_path_order('+version_id+', this);">edit order</a></td>');
							$row.find('table').find('tr:last').after($option_row);
							*/						    			    
						}
						$the_link.html($the_link.data('orig_html'));
						$the_link.blur();
						$the_link.data('is_open',true);							
				});
			} else {
				$('#container_of_row_'+version_id).remove();
				$the_link.data('is_open',false);
				$the_link.blur();
			}
		}
		function deleteContent() {
			var items_to_delete = $('tr[typeof="pages"]').find("input[type='checkbox']:checked");
			var content_ids_to_delete = new Array;
			// Get content
			for (var j = 0; j < items_to_delete.length; j++) {
				var $to_delete = $(items_to_delete[j]);
				content_ids_to_delete.push(parseInt($to_delete.attr('name').replace('content_id_','')));
			}
			if (content_ids_to_delete.length==0) {
				alert('Please select items to delete');
				return false;
			}
			// Confirm
			str = 'Are you sure you wish to DELETE ';
			if (content_ids_to_delete.length > 0) str += toWords(content_ids_to_delete.length) + 'content (will remove relationships and page)';
			str+='?';
			if (!confirm(str)) return false;
			// Do delete
			var data = {};
			data.action = 'delete_content';
			data.content_ids = content_ids_to_delete.join(','); 
			$.post('api/delete_content', data, function(data) {
			  for (var j = 0; j < data.content.length; j++) {
			  	 var content_id = data.content[j];
			  	 $('#path_row_'+content_id).remove();
			  }		  
			  var str = '';
			  if (data.content.length > 0) str += ucwords(toWords(data.content.length))+'content';
			  str += ' deleted';
			  alert(str);
			});	
		}	
		function edit_path_order(version_id, the_link) {
			var $the_link = $(the_link);
			$the_link.blur();
			$the_row = $('#pathTable').find('#path_version_id_'+version_id);
			var container_of_rows = $('#pathTable').find('#container_of_row_'+version_id).find('table').find('.container_of_row');			
			if (!$the_link.data('is_open')) {
				$the_link.data('is_open',true);
				container_of_rows.each(function() {
					var $this = $(this);
					var $sn = $this.find("td[property='sort_number']");
					var the_value = $sn.html();
					$sn.html('<input style="width:40px;" type="text" name="" value="'+the_value+'" />');
				});
				$the_link.html('<b>Save order</b>');
			} else {
				$the_link.data('is_open',false);
				// Get the sort numbers and content IDs
				var order = new Array;
				container_of_rows.each(function() {
					var $this = $(this);
					var version_id = $this.find("td[property='id']").html();
					var $sn = $this.find("td[property='sort_number']");
					var the_value = $sn.find('input:first').val();
					order.push({sort_number:the_value, child_version_id:version_id});
					$sn.html(the_value);
				});	
				// Order based on sort numbers
				order.sort(pathOrderFunc);
				// Clean up for sending
				var to_send = new Array;
				for (var j = 0; j < order.length; j++) {
					to_send.push(order[j].child_version_id);
				}		
				// Save
				$.post('api/save_path_order', {parent_version_id:version_id, child_version_ids:to_send}, function(data) {
					$the_link.html('Edit order');
					alert('Order has been saved')
				});
			}
		};
		function pathOrderFunc(a,b) {
		    var x = parseInt(a.sort_number);
		    var y = parseInt(b.sort_number);
		    return ((x < y) ? -1 : ((x > y) ? 1 : 0));			
		}		
		</script>
		
		<div class="table_wrapper">
		<table cellspacing="0" cellpadding="0" class="tablesorter">
			<thead>
				<tr class="head">
					<th></th>
					<th style="display:none;">ID</th>
					<th>Live?</th>
					<th>URI</th>
					<th>Title</th>
					<th>Description</th>
					<th>Content</th>
					<th>Created</th>
					<th>Contents</th>
				</tr>
			</thead>
			<tbody>
<?
		if (!empty($current_book_paths)) {
	  		foreach ($current_book_paths as $row) {		
				$slug = $row->slug;
				$version = $row->versions[0];
				$desc_excerpt = create_excerpt($version->description);
				if (strlen($version->description) == strlen($desc_excerpt)) $desc_excerpt = null;
				$content_excerpt = create_excerpt($version->content);
				if (strlen($version->content) == strlen($content_excerpt)) $content_excerpt = null;				
				echo '<tr typeof="pages" id="path_row_'.$row->content_id.'" class="bottom_border version content_id_'.$row->content_id.' version_id_'.$version->version_id.' '.(($row->is_live)?'':'not_live').'">';
				echo '<td style="white-space:nowrap;">';
				echo '<input type="checkbox" name="content_id_'.$row->content_id.'" value="1">&nbsp; <a href="javascript:;" onclick="edit_row($(this).parents(\'tr\'));" class="generic_button">Edit</a>';
				echo '</td>';
				echo '<td style="display:none" property="id">'.$row->content_id.'</td>';
				echo '<td class="editable boolean" property="is_live" style="text-align:center;width:65px;">'.$row->is_live."</td>\n";
				echo '<td property="slug">';
				echo '<a href="'.confirm_slash(base_url()).confirm_slash($book->slug).$slug.'">'.$slug.'</a>';
				echo '</td>';
				echo '<td style="width:200px;">'.htmlspecialchars($version->title).'</td>'."\n";
				if ($desc_excerpt) {
					echo '<td><span class="full">'.htmlspecialchars($version->description).'</span><span class="clip">'.htmlspecialchars($desc_excerpt).'</span></td>'."\n";
				} else {
					echo '<td>'.htmlspecialchars($version->description).'</td>';
				}
				if ($content_excerpt) {
					echo '<td><span class="full">'.htmlspecialchars($version->content).'</span><span class="clip">'.htmlspecialchars($content_excerpt).'</span></td>'."\n";
				} else {
					echo '<td>'.htmlspecialchars($version->content).'</td>';
				}
				echo '<td style="white-space:nowrap;">'.((!empty($row->created)&&$row->created!='0000-00-00 00:00:00')? date( 'M j, Y g:i A', strtotime($row->created)):'').'</td>'."\n";
				echo '<td style="white-space:nowrap;text-align:center;"><a href="javascript:;" onclick="pathsGetContainerOf(this)" class="generic_button">View</a></td>';
				echo "</tr>\n";													
			}
		}
?>
			</tbody>
		</table>
		</div>
		
		<br />
		
		<form onsubmit="deleteContent();return false;">
		<input type="submit" value="Delete selected content" class="generic_button large" />
		&nbsp; <small>Caution: will delete checked path relationship <b>and the path page</b></small>
		</form>		