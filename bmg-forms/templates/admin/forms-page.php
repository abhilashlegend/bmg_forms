<?php
namespace bmg_forms\lib;
global $wpdb;

$table_name = $wpdb->prefix . 'bmg_forms';


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


				$query = "SELECT * FROM $table_name ORDER BY id $limit"; 
				$result = $wpdb->get_results($query);


?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( get_admin_page_title() ); ?></h1>
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
	<table class="wp-list-table widefat fixed striped posts">
		<thead>
			<tr>
				<th class="manage-column column-title column-primary">
					<b>Form</b>
				</th>
				<th class="manage-column column-title column-primary">
					<b>Shortcode</b>
				</th>
			</tr>
		<thead>
		<tbody>
			<?php
			if($result) {
	foreach($result as $row) {
						?>
			<tr>
				<td>
					<?php
						echo $row->form_name;
					?>
				</td>
				<td>
					[bmg_forms <?php echo 'Id="' . $row->id . '"'; ?>]
				</td>
			</tr>
			<?php
				}
			}	
			?>		
		</tbody>
	</table>
</div> <!-- End of wrap -->
