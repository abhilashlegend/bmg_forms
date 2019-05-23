<?php 
//If the file is called directly abort
if(!defined('WPINC')) {
	die;
}

function bmg_ticket_settings_page() {
	add_menu_page(
		'BMG ticket',
		'BMG ticket',
		'manage_options',
		'bmg-ticket',
		'bmg_ticket_settings_page_markup',
		'dashicons-tickets-alt',
		100
	);

	add_submenu_page (
	'bmg-ticket',
	__( 'My Tickets', 'bmg-ticket' ),
	__( 'My Tickets', 'bmg-ticket'),
	'manage_options',
	'bmg-my-tickets',
	'bmg_my_tickets_markup'
);

}

add_action('admin_menu','bmg_ticket_settings_page');

function bmg_ticket_settings_page_markup() {

	//Double check user capabilities
	if( !current_user_can('manage_options')) {
		return;
	}

	include( plugin_dir_path( __FILE__ ) . '../templates/admin/setting-page.php');
?>
	
<?php
}

function bmg_my_tickets_markup() {
	//Double check user capabilities
	if( !current_user_can('manage_options')) {
		return;
	}



	?>
	<?php add_thickbox(); ?>

<div id="bmg-ticket-new-modal" style="display:none;">
	<form name="post-form" id="post-form">
     <table class="form-table">
     	<tr valign="top">
     		<th scope="row">Title</th>
       		 <td><input type="text" name="title" class="form-control"  /></td>
     	</tr>
     	<tr valign="top">
     		<th scope="row">Content</th>
       		 <td><textarea type="text"  style="width:100%;" rows="10" name="content"></textarea></td>
     	</tr>
     	<tr>
     		<td></td>
			<td> <input type="submit" class="button button-primary" name="submit" value="Submit ticket" /> &nbsp; <input type="button" class="button button-secondary cancel-btn" name="cancel" value="Cancel"> </td>
     	</tr>
     </table>
 </form>
</div>

<div id="bmg-ticket-edit-modal" style="display:none;">
	<form name="post-form" id="update-form">
     <table class="form-table">
     	<tr valign="top">
     		<th scope="row">Title</th>
       		 <td>
       		 	<input type="text" id="ticket-title" name="title" class="form-control"  />
       		 </td>
     	</tr>
     	<tr valign="top">
     		<th scope="row">Content</th>
       		 <td><textarea type="text" id="ticket-content"  style="width:100%;" rows="10" name="content"></textarea></td>
     	</tr>
     	<tr>
     		<td></td>
			<td> <input type="submit" class="button button-primary" name="submit" value="Update ticket" /> &nbsp; <input type="button" class="button button-secondary button-cancel" name="cancel" value="Cancel"> </td>
     	</tr>
     </table>
 </form>
</div>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( get_admin_page_title() ); ?></h1>
		<a class="button button-primary thickbox alignright" href="#TB_inline?width=600&height=370&inlineId=bmg-ticket-new-modal" title="Add Ticket">New Ticket</a>
		<div class="clearfix"></div>
		<br />
		<table class='wp-list-table widefat fixed striped posts' width='100%'>
			<tr>
				<th>Title</th>
				<th>Content</th>
				<th>Status</th>
				<th>Date</th>
				<th>Action</th>
			</tr>
			<?php
				$options = get_option('bmg_ticket_settings');

				$main_site = '';
				if(isset($options['site_url'])) {
					$main_site = esc_html($options['site_url']);
				}



				$tickets_url = $main_site . "/wp-json/wpas-api/v1/tickets";


				$json = CallAPI("GET", $tickets_url, $data = false);
				
				foreach($json as $obj) {
				 echo "<tr>";
				 foreach($obj->title as $title) {
				 	echo "<td>" . $title . "</td>";
				 }
				 foreach($obj->content as $content) {
				 	echo "<td>" . $content . "</td>";
				 	break;
				 }
				 echo "<td>" . $obj->status . "</td>";
				 echo "<td>" . $obj->date . "</td>";
				 echo "<td> <a class='editTicket alignright' data-id='" . $obj->id . "' data-title='" . $title . "' data-content='" . strip_tags($content) . "' href='#' title='Edit Ticket'>Edit</a></td>"; 
				 echo "</tr>";

					
					
					
					//echo "<td>" . $obj->title . "</td>";
				}

			?>
		</table>
	</div>
<?php
}



?>