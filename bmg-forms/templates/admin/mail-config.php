<?php
	$error_message = [];
	$aria_state = [];
	$success = false;
	$toadmin = get_option('bmg_forms_admin_email');
	$from = $subject = $headers = $body;

	if(isset($_POST['savebtn'])) {
		$to = trim(esc_attr($_POST['to']));
		$from = trim(esc_attr($_POST['from']));
		$subject = trim(esc_attr($_POST['subject']));
		$headers = trim(esc_attr($_POST['headers']));
		$body = trim(esc_attr($_POST['body']));

		if(empty($to)) {
			$error_message['to'] = "To field is required";
			$aria_state['to'] = 'true';
		}
		if (!filter_var($to, FILTER_VALIDATE_EMAIL) && !empty($to)) {
			$error_message['to'] = 'Please enter a valid to email address';
			$aria_state['to'] = 'true';
		}
		if(empty($from)) {
			$error_message['from'] = "From field is required";
			$aria_state['from'] = 'true';
		}
		if(!filter_var($from, FILTER_VALIDATE_EMAIL) && !empty($from)) {
			$error_message['from'] = 'Please enter a valid from email address';
			$aria_state['from'] = 'true';
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
			 $table_name = $wpdb->prefix . 'bmg_contact_us';
			 $wpdb->insert( 
				$table_name, 
				array( 
					'form_id' => $form_id, 
					'to' => $to,
					'from_user' => $from,
					'subject' 	=> $subject,
					'additional_headers' => $headers,
					'message_body'		 => $body
				),
				array('%d','%s','%s','%s','%s','%s') 
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
	<form method="post">
		<table>
			<tr>
				<td>To: </td>
				<td><input type="text" id="to" name="to" value="<?php echo $toadmin; ?>" aria-required="true" aria-invalid="' . $aria_state['to'] . '" /></td>
			</tr>
			<tr>
				<td>From: </td>
				<td><input type="text" name="from" id="from" value="<?php echo $from; ?>" aria-required="true" aria-invalid="' . $aria_state['from'] . '" /></td>
			</tr>
			<tr>
				<td>Subject: </td>
				<td><input type="text" name="subject" id="subject" value="<?php echo $subject; ?>" aria-required="true" aria-invalid="' . $aria_state['subject'] . '">
			</td>
			<tr>
				<td>Additional Headers: </td>
				<td><textarea name="headers" id="headers"><?php echo $headers; ?></textarea></td>
			</tr>
			<tr>
				<td>Body: </td>
				<td><textarea name="body" id="body"><?php echo $body; ?></textarea></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" name="savebtn" id="savebtn" value="Save" class="btn btn-primary" /></td>
			</tr>
		</table>
	</form>

</div> <!-- End of wrap -->