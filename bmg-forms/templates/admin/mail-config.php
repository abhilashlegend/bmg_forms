<?php
	global $wpdb;
	$form_id = $_GET['form_id'];
	$error_message = [];
	$aria_state = [];
	$success = false;
	$toadmin = get_option('bmg_forms_admin_email');
	$id = $from = $subject = $headers = $body;

	 $table_name = $wpdb->prefix . 'bmg_forms_mails';
	 $config = $wpdb->get_results("SELECT * FROM $table_name WHERE form_id = $form_id");

	 if(count($config) > 0){
	 	$id = $config[0]->id;
	 	$to = $config[0]->to_user;
	 	$from = $config[0]->from_user;
	 	$subject = $config[0]->subject;
	 	$body = $config[0]->message_body;
	 }
	 


	if(isset($_POST['savebtn'])) {
		$to = trim(esc_attr($_POST['to']));
		$from = trim(esc_attr($_POST['from']));
		$subject = trim(esc_attr($_POST['subject']));
		$body = trim(esc_attr($_POST['body']));

		if(empty($to)) {
			$error_message['to'] = "To field is required";
			$aria_state['to'] = 'true';
		}
		if(empty($subject)){
			$error_message['subject'] = "Subject field is required";
			$aria_state['subject'] = 'true';
		}
		if(empty($body)){
			$error_message['body'] = "Body field is required";
			$aria_state['body'] = 'true';
		}

		if(count($error_message) == 0) {
			 $table_name = $wpdb->prefix . 'bmg_forms_mails';
			 $wpdb->insert( 
				$table_name, 
				array( 
					'form_id' => $form_id, 
					'to_user' => $to,
					'from_user' => $from,
					'subject' 	=> $subject,
					'message_body'		 => $body
				),
				array('%d','%s','%s','%s','%s') 
			);
		}
	}


	// Update 
	if(isset($_POST['updatebtn'])) {
		$id = trim(esc_attr($_POST['id']));
		$to = trim(esc_attr($_POST['to']));
		$from = trim(esc_attr($_POST['from']));
		$subject = trim(esc_attr($_POST['subject']));
		$body = trim(esc_attr($_POST['body']));

		if(empty($to)) {
			$error_message['to'] = "To field is required";
			$aria_state['to'] = 'true';
		}
		if(empty($subject)){
			$error_message['subject'] = "Subject field is required";
			$aria_state['subject'] = 'true';
		}
		if(empty($body)){
			$error_message['body'] = "Body field is required";
			$aria_state['body'] = 'true';
		}

		if(count($error_message) == 0) {
			 $table_name = $wpdb->prefix . 'bmg_forms_mails';

			 $wpdb->update(
                $table_name, //table
               array( 
					'form_id' => $form_id, 
					'to_user' => $to,
					'from_user' => $from,
					'subject' 	=> $subject,
					'message_body'		 => $body
				), //data
                array('id' => $id), //where
                array('%d','%s','%s','%s','%s'), //data format
                array('%d') //where format
        	 );
		}
	}
?>

<div class="wrap">
	<h1 class="wp-heading-inline">Mail Configuration</h1>
	<?php
		if(count($error_message)) {
			echo '<div class="info-box warning bmg-input-error" role="alert">
			<h3><strong>Error submitting form:</strong></h3>
			<ul class="bmg-list-errors" title="The following errors have been reported">'; 
			foreach($error_message as $key => $error) {
				echo '<li><a href="#' . $key .'" id="' . $key . '-error">'  . $error . "</a></li>";
			}
			echo '</ul></div>';
		}

	?>
	<div>
	Note: Use below mail tags to incorporate into mail template. Here the mail tags will be replaced by the value entered during form submission.
	</div>
	<form method="post">
		<table>
			<tr>
				<td>Form Fields: </td>
				<td>
					<?php
							if(isset($form_id)) {

								$form_meta_table = $wpdb->prefix . 'bmg_forms_meta';	



								$result = $wpdb->get_results("SELECT * FROM $form_meta_table WHERE form_id=$form_id AND type NOT IN ('button','paragraph','header') ORDER BY id ASC");

								$form_items = count($result);

								for($i = 0; $i < $form_items; $i++){
									echo '[' . $result[$i]->name . '] &nbsp;';	
								}
							}	

					?>
				</td>
			</tr>
			<tr>
				<td>To: </td>
				<td><input type="text" id="to" name="to" value="<?php echo $toadmin; ?>" aria-required="true" class="regular-text code" readonly aria-invalid="' . $aria_state['to'] . '" /></td>
			</tr>
			<tr>
				<td>From: </td>
				<td><input type="text" name="from" id="from" value="<?php echo $from; ?>" aria-required="true" class="regular-text code" aria-invalid="' . $aria_state['from'] . '" /></td>
			</tr>
			<tr>
				<td>Subject: </td>
				<td><input type="text" name="subject" id="subject" value="<?php echo $subject; ?>" aria-required="true" class="regular-text code" aria-invalid="' . $aria_state['subject'] . '">
			</td>
			
			<tr>
				<td>Body: </td>
				<td><textarea name="body" class="large-text code" rows="5" cols="50" id="body"><?php echo $body; ?></textarea></td>
			</tr>
			<tr>
				<td></td>
				<td>
					<?php
						if(count($config) > 0) { 
					?>
					<input type="hidden" name="id" value="<?php echo $id; ?>">
					<input type="submit" name="updatebtn" id="updatebtn" value="Update" class="btn 
					btn-success" />
					<?php 
					} else {  
					?>
					<input type="submit" name="savebtn" id="savebtn" value="Save" class="btn btn-primary" />
					<?php 
					}
					?>

				</td>
			</tr>
		</table>
	</form>

</div> <!-- End of wrap -->