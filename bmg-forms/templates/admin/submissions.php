<?php
namespace bmg_forms\lib;
global $wpdb;
$message = NULL;


$table_list = getbmgformstables();

$enable_detail = false;
// $_SESSION["table_name"] = $wpdb->prefix . 'bmg_contact_us';
if($_SESSION["table_name"] == NULL){
	if($table_list){
		$_SESSION["table_name"] = $wpdb->prefix . 'bmg_forms_' . $table_list[0]->form_name . $table_list[0]->id;	
	}
	
}
if(isset($_POST['bmg-forms'])) {
	$_SESSION["table_name"] = $_POST['bmg-forms'];
}

$table_name = $_SESSION["table_name"];

$result = $wpdb->get_results("SELECT * FROM $table_name");
$items = $wpdb->num_rows;
					$rows_limit = get_option('bmg_table_row_limit');

					$p = new pagination;
					$limit = '';
					if($items > 0 && $result) {
						
						$p->items($items);
						$p->limit($rows_limit); // Limit entries per page
						$p->target("admin.php?page=bmg-submissions"); 
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

				// Delete row(s)
				if(isset($_POST['delete'])) {
					$row_array = $_POST['post'];
					$row_ids = implode(', ', $row_array);
					$delete_sql = "DELETE from $table_name WHERE id IN ($row_ids)";
					$delete_result = $wpdb->query($delete_sql);
					if($delete_result) {
							$message = count($row_array) . " submission deleted.";
					}
				}

?>


<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( get_admin_page_title() ); ?></h1>
		<?php
		if(isset($message)) {
		?>
			<div id="message" class="updated notice is-dismissible"><p><?php echo $message; ?> </p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
	<?php } ?>
		<form method="post" name="submission_frm">
		<div class="tablenav top">
			<div class="alignleft bulkactions">

				<input type="submit" id="doaction" name="delete" class="button" value="Delete">
				<label for="bmg-forms">Select Form</label>
				<select name="bmg-forms" onchange="submission_frm.submit();">
					<option value=''>SELECT</option>
					<?php
						
						foreach ($table_list as $key => $table) {
							$selected = '';
							if($table_name ==  $wpdb->prefix . 'bmg_forms_' . $table->form_name . $table->id){
								$selected = "selected='selected'";	
							}
							 echo "<option value=" . $wpdb->prefix . 'bmg_forms_' . $table->form_name . $table->id . " " . $selected . ">" . $table->form_name . "</option>";    	
						}
					?>
				</select>
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
		
		<table class='wp-list-table widefat fixed striped posts' width='100%'>

			<thead>
			<tr>
				<td class="manage-column column-cb check-column">
					<label class="screen-reader-text" for="cb-select-all-1">Select All</label>
					<input id="cb-select-all-1" type="checkbox">
				</td>
				<?php
				
				$sql = "SHOW COLUMNS FROM $table_name";
				$result = $wpdb->get_results($sql);
				$count = 0;
				$fields = [];
				if($result) { 
				foreach($result as $row){
					if($count == 10){
						$enable_detail = true;
						break;
					} else {
						$fields[$count] = $result[$count]->Field;
						echo "<th> <b>" . $fields[$count] . "</b></th>";
						
					} 
						
$count++;

					 ?>
				
				<?php
				}
			} else {
				echo "<th class='text-center'><b> Please select Form </th>";
			}	
				?>
				
			</tr>
		</thead>
		<tbody>
				<?php
					$tab_col_names = implode(', ', $fields);
				 $table_data = $wpdb->get_results("SELECT $tab_col_names FROM $table_name $limit");
				 if($table_data) {
							foreach($table_data as $i => $value) {

				?>
				<tr 
					<?php
						if($enable_detail) { ?>
							style='cursor: pointer;' onclick='window.location="<?php  echo esc_html( admin_url('admin.php?page=bmg_submission_detail&table=' . $table_name . '&Id=' ) ) . $value->id; ?>"' 
<?php
						} ?>
					
				>				
					<th scope="row" class="check-column">	
				
												
					<input id="cb-select-<?php echo $value->id; ?>" type="checkbox" name="post[]" value="<?php echo $value->id; ?>">
			
					</th>
					<?php
						foreach($value as $d) { 
					?>
					
			   <td>
				<?php
				if(filter_var($d, FILTER_VALIDATE_URL)) { 
				echo '<a href="' . $d . '">Download file</a>';
				} else {
					echo $d;
				}

				?>
			   </td>
			   <?php 
				}

			 ?>
				

				
		<?php	}	?>			
			</tr>
		<?php } else {  ?>
			<tr>
				<?php

					if($count == 0) {
						echo "<td> </td>";
					}
				?>
				<td colspan="<?php echo $count + 1; ?>" class="text-center"> No Records </td>
			</tr>
		<?php } ?>	
		</tbody>
		
		</table>
	</form>
</div> <!-- End of wrap -->

