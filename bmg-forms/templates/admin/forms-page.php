<?php
namespace bmg_forms\lib;
global $wpdb;

$table_name = $wpdb->prefix . 'bmg_forms';

$meta_table = $wpdb->prefix . 'bmg_forms_meta';


$result = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id");
		$items = $wpdb->num_rows;
					$rows_limit = get_option('bmg_table_row_limit');
					$p = new pagination;
					$limit = '';
					if($items > 0 && $result) {
						
						$p->items($items);
						$p->limit($rows_limit); // Limit entries per page
						$p->target("admin.php?page=bmg-forms"); 
						$p->currentPage(isset($_GET['paging'])); // Gets and validates the current page
						$p->calculate(); // Calculates what to show
						$p->parameterName('paging');
						$p->adjacents(1); //No. of page away from the current page
								 
						if(!isset($_GET['paging'])) {
							$p->page = 1;
						} else {
							$p->page = $_GET['paging'];
						}
						 
						//Query for limit paging
						$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;
						 
				}

				if(isset($_POST['delete'])) {
					$row_array = $_POST['post'];
					$row_ids = implode(', ', $row_array);

					//$form_name = getformname($row_array);
					foreach($row_array as $f) {
						$form_name = getformname($f);
						$form_table = $wpdb->prefix . 'bmg_forms_' . $form_name . $f;
						$delete_table_sql = "DROP TABLE IF EXISTS  $form_table;";
						$tables_removed = $wpdb->query($delete_table_sql);
						
					}
					$delete_meta_sql = "DELETE from $meta_table WHERE form_id IN ($row_ids)";
					$delete_meta = $wpdb->query($delete_meta_sql);
					$delete_sql = "DELETE from $table_name WHERE id IN ($row_ids)";
					$delete_result = $wpdb->query($delete_sql);
					if($delete_result) {
							$message = count($row_array) . " form(s) deleted.";
					} 
				}


				$query = "SELECT * FROM $table_name ORDER BY id $limit"; 
				$result = $wpdb->get_results($query);


?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( get_admin_page_title() ); ?></h1>
	<?php
		if(isset($message)) {
		?>
			<div id="message" class="updated notice is-dismissible"><p><?php echo $message; ?> </p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
	<?php } ?>
<form method="post" name="bmg_frm">
<div class="tablenav top">
	<div class="alignleft bulkactions">
		<input type="submit" id="doaction" name="delete" class="button" value="Delete">
	</div>
	<div class="tablenav-pages">
				<?php
		 if($items > 0) { ?>
                    <div class="">
                        <nav class=''>
                            <?php  echo $p->show();  // Echo out the list of paging. ?>
                        </nav>
                    </div>
                    <?php } ?>
				
			</div>
			<br class="clear" />
	</div>
	<table class="wp-list-table widefat fixed striped posts">
		<thead>
			<tr>
				<td class="manage-column column-cb check-column">
					<label class="screen-reader-text" for="cb-select-all-1">Select All</label>
					<input id="cb-select-all-1" type="checkbox">
				</td>
				<th class="manage-column column-title column-primary">
					<b>Form</b>
				</th>
				<th class="manage-column column-title column-primary">
					<b>Shortcode</b>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php
			if($result) {
	foreach($result as $row) {
						?>
			<tr>
				<th scope="row" class="check-column">	
				
					<label class="screen-reader-text" for="cb-select-<?php echo $row->id; ?>">Select <?php echo $row->form_name; ?></label>									
					<input id="cb-select-<?php echo $row->id; ?>" type="checkbox" name="post[]" value="<?php echo $row->id; ?>">
			
					</th>
				<td>
					<?php
						echo $row->form_name;
					?>
				</td>
				<td>
					[bmg_forms <?php echo 'Id="' . $row->id . '"'; ?>]
					<a href="<?php  echo esc_html( admin_url('admin.php?page=bmg_mail_config&form_id=' ) ) . $row->id; ?>" class="btn btn-primary btn-sm pull-right">Mail Config</a>
					<a href="<?php  echo esc_html( admin_url('admin.php?page=bmg_edit_form&form_id=' ) ) . $row->id; ?>" class="btn btn-success btn-sm pull-right mr-2">Edit</a>
				</td>
				
			</tr>
			<?php
				}
			} else {?>
				<tr>
					<td colspan="3" class="text-center">No Forms created yet.</td>
				</tr>

			<?php
			}	
			?>		
		</tbody>
	</table>
</form>
</div> <!-- End of wrap -->
