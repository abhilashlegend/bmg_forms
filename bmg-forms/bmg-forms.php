<?php
/**
 * @package Bmg Forms
 */
/*
Plugin Name: Bmg Forms
Description: add and manage forms on your site
Version: 1.0.0
Author: Abhilash
License: GPLv2 or later
Text Domain: bmg-forms
*/

/* !0. TABLE OF CONTENTS */

/* 
	1. Hooks
		1.1 - Register our Plugin Menu
		1.2 - registers all our custom shortcodes on init
		1.3 - load external files to public website

	2. ShortCodes
		2.0 - register shortcode
		2.1 - contact us shortcode 
		2.2 - volunteer form shortcode
		2.3 - feedback form shortcode

	3. Filters
		3.1 - admin menus

    4. External Scripts
    	4.1 - loads external files into PUBLIC website 
    	4.2 - loads external files into admin

    5. Actions
    	5.1 - create all tables related to plugin
    	5.2 - remove all tables on uninstall

    6. Helpers

    7. Custom Post types

    8. Admin Pages
    	 8.1 - forms page
    	 8.2 - Submissions page
    	 	8.2.1 - Submission detail page
    	 8.3 - new form

    9. Settings

*/
    

    //Include shortcodes
foreach ( glob( plugin_dir_path( __FILE__ ) . 'lib/*.php' ) as $file ) {

    include_once $file;
}

/* 1. Hooks */
	
/* 1.1 Register our Plugin Menu */
add_action('admin_menu','bmg_forms_settings_page');    

// 1.2 
// hint: registers all our custom shortcodes on init
add_action('init','bmg_forms_register_shortcodes');

// 1.3
// hint: load external files to public website 

add_action('wp_enqueue_scripts', 'bmg_forms_public_scripts',99);

// 1.4
//hint: load external files in wordpress admin
add_action('admin_enqueue_scripts', 'bmg_forms_admin_scripts');

// 1.5
// hint: create tables for forms
register_activation_hook( __FILE__, 'bmg_forms_plugin_create_db');

// 1.6
// hint: remove tables on uninstall
register_uninstall_hook( __FILE__, 'bmg_forms_uninstall_plugin');

// 1.7
// register plugin options
add_action('admin_init', 'bmg_register_options');

// 1.8
// generate form 
add_action('wp_ajax_bmg_generate_form','bmg_generate_form'); //admin user

// add_action( 'wp_mail_failed', 'onMailError', 10, 1 );
function onMailError( $wp_error ) {
    echo "<pre>";
    print_r($wp_error);
    echo "</pre>";
}

/* 2. Shortcodes */

//2.0
//hint: register shortcode
function bmg_forms_register_shortcodes() {
	add_shortcode('bmg_contact_us_form','bmg_contact_us_form_shortcode');
	add_shortcode('bmg_volunteer_form','bmg_volunteer_form_shortcode');
	add_shortcode('bmg_feedback_form', 'bmg_feedback_form_shortcode');
	add_shortcode('bmg_service_inquiry_form','bmg_service_inquiry_form_shortcode');
}

// 2.1 
// hint: contact us shortcode

function bmg_contact_us_form_shortcode($args, $content="") {
	global $wpdb;
	$table_name = $wpdb->prefix . 'bmg_contact_us';
	$error_message = [];
	$aria_state = [];
	$success = false;
	$full_name = $email = $phone = $website = $subject = $comments = '';
	

	

	if(isset($_POST['bmg_submit']) && empty($_SESSION['form_submit'])) {
	
		$full_name = trim(esc_attr($_POST['bmg-full-name']));
		$email = trim(esc_attr($_POST['bmg-email']));
		$phone = trim(esc_attr($_POST['bmg-phone']));
		$website = trim(esc_attr($_POST['bmg-website']));
		$subject = trim(esc_attr($_POST['bmg-subject']));
		$comments = trim(esc_attr($_POST['bmg-comments']));
		$token_id = stripslashes( $_POST['token'] );

		if(empty($full_name)) {
			$error_message['bmg-full-name'] = 'Please enter your full name'; 
			$aria_state['full_name'] = 'true';
		}
		if(empty($email)) {
			$error_message['bmg-email'] = 'Please enter your email address';
			$aria_state['email'] = 'true';
		}
		if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
			$error_message['bmg-email'] = 'Please enter a valid email address';
			$aria_state['email'] = 'true';
		}
		if(empty($comments)) {
			$error_message['bmg-comments'] = 'Please enter comments';
			$aria_state['comments'] = 'true';
		}


		// If there is no error 
		if(count($error_message) == 0 && !get_transient( 'token_' . $token_id )) {
			
			 $table_name = $wpdb->prefix . 'bmg_contact_us';
				$sql = "SHOW COLUMNS FROM $table_name";
				$result = $wpdb->get_results($sql);
				$count = 0;
				$fields = [];
				$table_data = [];
				$form_fields = [$full_name, $email, $phone, $website, $subject, $comments];

				for($i = 0; $i < count($form_fields); $i++) { 
					$fields[$count] = $result[$count + 1]->Field;
					$field_name  = $fields[$count];
					$table_data[$field_name] = $form_fields[$count];
					$count++;
				}
				
				$success = $wpdb->insert(
								$table_name, $table_data, array('%s','%s','%s','%s','%s','%s')
						   );

				set_transient( 'token_' . $token_id, 'dummy-content', 60 );
					$wpdb->show_errors();
					print_r( $wpdb->queries);


			$toadmin = get_option('bmg_forms_admin_email');


			$mail_subject = 'Contact Us Submission from' . $first_name . 'regarding' . $subject;
			$body = $comments;
			$headers = array('Content-Type: text/html; charset=UTF-8');
			 
			$admin_mail = wp_mail( $toadmin, $mail_subject, $body, $headers );

			$to_customer = $email;
			$customer_subject = 'My Blind Spot Contact Submission Confirmation';
			$customer_body = "Greetings <br />,
								Here's a copy of your contact request to My Blind Spot
							<table>
								<tr>
								 	<th>Full Name</th>
								 	<td>" . $full_name . "</td>
								 </tr>
								 <tr>
								 	<th> Subject</th>
								 	<td>" . $subject . "</td>
								 </tr>
								 <tr>
									<th> Comment </th>
									<td>" . $comments . "</td>
							  	  </tr>
							  </table>
							  ";

			$customer_mail = wp_mail( $to_customer, $customer_subject, $customer_body, $headers );	

			if($admin_mail == false) {
				$mail_error = "Failed to send your message. Please try later or contact the administrator by another method.";
			}
			$_SESSION['form_submit'] = 'true';			  
		} else {
			$_SESSION['form_submit'] = NULL;
		}

	}
	$token_id = md5( uniqid( "", true ) );
	if(count($error_message)) {
			$output = '<div class="info-box warning bmg-input-error" role="alert">
			<h3><strong>Error submitting form:</strong></h3>
			<ul class="bmg-list-errors" title="The following errors have been reported">'; 
			foreach($error_message as $key => $error) {
				$output .= '<li><a href="#' . $key .'" id="' . $key . '-error">'  . $error . "</a></li>";
			}
			$output .= '</ul></div>';
	} else {
		$output = '';	
	}
	if($success) {
		$output = '<div class="bmg-success-box" role="alert"><Strong>Message successfully sent!</strong>
		</div>';
	} 


	$output .= '<div role="form">
				<form method="post" action="" novalidate>
					<div class="form-group">
						<label for="bmg-full-name">Full Name: (required)</label>
						<input type="text" name="bmg-full-name" id="bmg-full-name" aria-required="true" aria-invalid="' . $aria_state['full_name'] . '" aria-describedby="bmg-full-name-error" placeholder="Full Name" value="' . $full_name . '" class="form-control" />';

				$output .=	'</div>

					<div class="form-group">
						<label for="bmg-email">Email: (required)</label>
						<input type="text" name="bmg-email" id="bmg-email" aria-required="true" aria-invalid="' . $aria_state['email'] . '" placeholder="Email" aria-describedby="bmg-email-error" class="form-control" value="' . $email . '" />';

				$output .=	'</div>

					<div class="form-group">
						<label for="bmg-phone">Phone:</label>
						<input type="text" name="bmg-phone" id="bmg-phone" aria-invalid="false" placeholder="Phone" class="form-control" value="' . $phone . '" />
					</div>

					<div class="form-group">
						<label for="bmg-website">Website:</label>
						<input type="text" name="bmg-website" id="bmg-website" aria-invalid="false" placeholder="Website" value="' . $website . '" class="form-control" />
					</div>
	
					<div class="form-group">
						<label for="bmg-subject">Subject: (required)</label>
						<select name="bmg-subject" id="bmg-subject" aria-required="true" aria-invalid="false" class="form-control">
							<option value="Free site Quick Look">Free site Quick Look</option>
							<option value="MBS JAWS for Quickbooks Questions">MBS JAWS for Quickbooks Questions</option>
							<option value="Accessibility consulting quote or questions">Accessibility consulting quote or questions</option>
							<option value="Ask MBS to speak">Ask MBS to speak</option>
							<option value="MBS Advocacy">MBS Advocacy</option>
							<option value="General question">General question</option>
							<option value="Training or workshop questions">Training or workshop questions</option>
							<option value="Website/app design or development questions">Website/app design or development questions</option>
							<option value="Hire me?">Hire me?</option>
						</select>';
						

					$output .= '</div>

					<div class="form-group">
						<label for="bmg-comments">Comments: (required)</label>
						<textarea name="bmg-comments" cols="40" rows="10" class="form-control" id="bmg-comments" aria-required="true" aria-invalid="' . $aria_state['comments'] . '" aria-describedby="bmg-comments-error" placeholder="Comments">' . $comments . '</textarea>';
						
				$output .=	'</div>

					<div class="form-group">
					 <input type="hidden" name="token" value="' . $token_id . '" />
						<input type="submit" name="bmg_submit" value="Submit" class="bmg-submit btn-mbs">
					</div>';
						if($mail_error) {
							$output .= '<div role="alert" class="bmg-forms-mail-error">' . $mail_error . '</div>';
						}
					
				$output .= '</form>
	';

	return $output;

}


// 2.2
//hint: volunteer form shortcode

function bmg_volunteer_form_shortcode($args, $content="") {
	global $wpdb;
	$table_name = $wpdb->prefix . 'bmg_volunteer';
	$us_states = ['Alabama','Alaska','Arizona','Arkansas','California','Colorado','Connecticut','Delaware','Florida','Georgia','Hawaii','Idaho','Illinois','Indiana','Iowa','Kansas','Kentucky','Louisiana','Maine','Maryland','Massachusetts','Michigan','Minnesota','Mississippi','Missouri','Montana','Nebraska','Nevada','New Hampshire','New Jersey','New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon','Pennsylvania','Rhode Island','South Carolina','South Dakota','Tennessee','Texas','Utah','Vermont','Virginia','Washington','West Virginia','Wisconsin','Wyoming'];
	$error_message = [];
	$aria_state = [];
	$success = false;
	$first_name = $middle_name = $last_name = $current_add = $city = $state = $zip = $phone = $email = $birthdate = $education = $current_employer = $position = $special_skills = $times_available = $ex_position_1 = $ex_position_2 = $agency_1 = $agency_2 = $date_1 = $date_2 = $age = $hear = $why_volunteer = $crime = $crime_reason = $ref_name_1 = $ref_name_2 = $ref_name_3 = $ref_relationship_1 = $ref_relationship_2 = $ref_relationship_3 = $ref_contact_1 = $ref_contact_2 = $ref_contact_3 = $ref_email_1 = $ref_email_2 = $ref_email_3 = $e_contact_name = $e_relationship = $e_phone_no = $e_cell_phone = "";
	$interests = [];

	if(isset($_POST['bmg_submit']) && empty($_SESSION['form_submit'])) {
		$first_name = trim(esc_attr($_POST['bmg-first-name']));
		$middle_name = trim(esc_attr($_POST['bmg-middle-initial']));
		$last_name = trim(esc_attr($_POST['bmg-last-name']));
		$current_add = trim(esc_attr($_POST['bmg-current-address']));
		$city = trim(esc_attr($_POST['bmg-city']));
		$state = trim(esc_attr($_POST['bmg-state']));
		$zip = trim(esc_attr($_POST['bmg-zip']));
		$phone = trim(esc_attr($_POST['bmg-phone']));
		$email = trim(esc_attr($_POST['bmg-email']));
		$birthdate = trim(esc_attr($_POST['bmg-birthday']));
		$education = trim(esc_attr($_POST['bmg-education']));
		$current_employer = trim(esc_attr($_POST['bmg-current-employer']));
		$position = trim(esc_attr($_POST['bmg-position']));
		$special_skills = trim(esc_attr($_POST['bmg-special-talent']));
		$interests = $_POST['bmg-interests'];
		$times = trim(esc_attr($_POST['bmg-available']));
		$ex_position_1 = trim(esc_attr($_POST['bmg-position-1']));
		$agency_1 = trim(esc_attr($_POST['bmg-agency-1']));
		$date_1 = trim(esc_attr($_POST['bmg-date-1']));
		$ex_position_2 = trim(esc_attr($_POST['bmg-position-2']));
		$agency_2 = trim(esc_attr($_POST['bmg-agency-2']));
		$date_2 = trim(esc_attr($_POST['bmg-date-2']));
		$age = $_POST['bmg-age'];
		$hear = trim(esc_attr($_POST['bmg-hear']));
		$why_volunteer = trim(esc_attr($_POST['bmg-why-volunteer']));
		$crime = trim(esc_attr($_POST['bmg-crime']));
		$crime_reason = trim(esc_attr($_POST['bmg-crime-why']));
		$ref_name_1 = trim(esc_attr($_POST['bmg-ref-name-1']));
		$ref_relationship_1 = trim(esc_attr($_POST['bmg-rel-1']));
		$ref_contact_1 = trim(esc_attr($_POST['bmg-contact-1']));
		$ref_email_1 = trim(esc_attr($_POST['bmg-contact-email-1']));

		$ref_name_2 = trim(esc_attr($_POST['bmg-ref-name-2']));
		$ref_relationship_2 = trim(esc_attr($_POST['bmg-rel-2']));
		$ref_contact_2 = trim(esc_attr($_POST['bmg-contact-2']));
		$ref_email_2 = trim(esc_attr($_POST['bmg-contact-email-2']));

		$ref_name_3 = trim(esc_attr($_POST['bmg-ref-name-3']));
		$ref_relationship_3 = trim(esc_attr($_POST['bmg-rel-3']));
		$ref_contact_3 = trim(esc_attr($_POST['bmg-contact-3']));
		$ref_email_3 = trim(esc_attr($_POST['bmg-contact-email-3']));

		$e_contact_name = trim(esc_attr($_POST['bmg-ec-name']));
		$e_relationship = trim(esc_attr($_POST['bmg-ec-relationship']));
		$e_phone_no = trim(esc_attr($_POST['bmg-ec-phone-number']));
		$e_cell_phone = trim(esc_attr($_POST['bmg-ec-cell-number']));	

	

		if(empty($first_name)) {
			$error_message['bmg-first-name'] = 'Please enter your first name'; 
			$aria_state['bmg_first_name'] = 'true';
		}

		if(empty($last_name)) {
			$error_message['bmg-last-name'] = 'Please enter your last name'; 
			$aria_state['bmg_last_name'] = 'true';
		}

		if(empty($phone)) {
			$error_message['bmg-phone'] = 'Please enter your phone number'; 
			$aria_state['bmg_phone'] = 'true';
		}

		if(!preg_match('/^[0-9]{10}+$/', $phone) && !empty($phone)) {
			$error_message['bmg-phone'] = 'Please enter your phone number correctly'; 
			$aria_state['bmg_phone'] = 'true';
		}

		if(empty($email)) {
			$error_message['bmg-email'] = 'Please enter your email address'; 
			$aria_state['bmg_email'] = 'true';
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
			$error_message['bmg-email'] = 'Please enter a valid email address';
			$aria_state['bmg_email'] = 'true';
		}

		if(!preg_match('/^[0-9]{10}+$/', $ref_contact_1) && !empty($ref_contact_1)) {
			$error_message['bmg-contact-1'] = 'Please enter reference one contact number correctly'; 
			$aria_state['ref_contact_1'] = 'true';
		}

		if (!filter_var($ref_email_1, FILTER_VALIDATE_EMAIL) && !empty($ref_email_1)) {
			$error_message['bmg-contact-email-1'] = 'Please enter reference one email address correctly';
			$aria_state['bmg_contact_email_1'] = 'true';
		}

		if(!preg_match('/^[0-9]{10}+$/', $ref_contact_2) && !empty($ref_contact_2)) {
			$error_message['bmg-contact-2'] = 'Please enter reference two contact number correctly'; 
			$aria_state['ref_contact_2'] = 'true';
		}

		if (!filter_var($ref_email_2, FILTER_VALIDATE_EMAIL) && !empty($ref_email_2)) {
			$error_message['bmg-contact-email-2'] = 'Please enter reference two email address correctly';
			$aria_state['bmg_contact_email_2'] = 'true';
		}

		if(!preg_match('/^[0-9]{10}+$/', $ref_contact_3) && !empty($ref_contact_3)) {
			$error_message['bmg-contact-3'] = 'Please enter reference three contact number correctly'; 
			$aria_state['ref_contact_3'] = 'true';
		}

		if (!filter_var($ref_email_3, FILTER_VALIDATE_EMAIL) && !empty($ref_email_3)) {
			$error_message['bmg-contact-email-3'] = 'Please enter reference three email address correctly';
			$aria_state['bmg_contact_email_3'] = 'true';
		}

		if(!preg_match('/^[0-9]{10}+$/', $e_phone_no) && !empty($e_phone_no)) {
			$error_message['bmg-ec-phone-number'] = 'Please enter emergency contact phone number correctly'; 
			$aria_state['e_phone_no'] = 'true';
		}

		if(!preg_match('/^[0-9]{10}+$/', $e_cell_phone) && !empty($e_cell_phone)) {
			$error_message['bmg-ec-cell-number'] = 'Please enter emergency cell phone number correctly'; 
			$aria_state['e_cell_phone_no'] = 'true';
		}

		// If there is no error 
		if(count($error_message) == 0 && !get_transient( 'token_' . $token_id )) {
				$table_name = $wpdb->prefix . 'bmg_volunteer';
				$sql = "SHOW COLUMNS FROM $table_name";
				$result = $wpdb->get_results($sql);
				$count = 0;
				$fields = [];
				$table_data = [];
				$form_fields = [$first_name , $middle_name , $last_name , $current_add , $city , $state, $zip ,$phone ,$email ,$birthdate ,$education ,$current_employer ,$position ,$special_skills , $interests , $times_available ,$ex_position_1 , $agency_1, $date_1,  $ex_position_2  ,$agency_2 , $date_2 , $age , $hear , $why_volunteer , $crime , $crime_reason ,
					$ref_name_1 , $ref_relationship_1 , $ref_contact_1 , $ref_email_1 , $ref_name_2 , $ref_relationship_2 , $ref_contact_2 , $ref_email_2 , $ref_name_3 , $ref_relationship_3 , $ref_contact_3 , $ref_email_3 , $e_contact_name , $e_relationship , $e_phone_no , $e_cell_phone ];

				for($i = 0; $i < count($form_fields); $i++) { 
					$fields[$count] = $result[$count + 1]->Field;
					$field_name  = $fields[$count];
					$table_data[$field_name] = $form_fields[$count];
					$count++;
				}
				
				$success = $wpdb->insert(
								$table_name, $table_data, array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')
						   );

				set_transient( 'token_' . $token_id, 'dummy-content', 60 );
					$wpdb->show_errors();
					print_r( $wpdb->queries);


			$toadmin = get_option('bmg_forms_admin_email');
			$mail_subject = 'Volunteer Registration from' . $first_name;
			$body = "Greetings <br />,
								Here's a copy of your contact request to My Blind Spot
							<table>
								<tr>
								 	<th>Volunteer Full Name</th>
								 	<td>" . $first_name . ' ' . $middle_name . ' ' . $last_name . "</td>
								 </tr>
								 <tr>
								 	<th> Address </th>
								 	<td>" . $current_add . ' ' . $city . ' ' . $state .  ' Zip ' . $zip . "</td>
								 </tr>
								 <tr>
									<th> Phone </th>
									<td>" . $phone . "</td>
							  	  </tr>
							  	 <tr>
							  	 	<th> Email </th>
							  	 	<td>" . $email . "</td>
							  	 </tr>
							  	 <tr>
							  	 	<th> Birthday </th>
							  	 	<td>" . $birthdate . "</td>
							  	 </tr>
							  	 <tr>
							  	 	<th> Education </th>
							  	 	<td>" . $education . "</td>
							  	 </tr>
							  	 <tr>
							  	 	<th> Current Employer </th>
							  	 	<td>" . $current_employer . "</td>
							  	 </tr>
							  	 <tr>
							  	 	<th> Position </th>
							  	 	<td>" . $position . "</td>
							  	 </tr>
							  	 <tr>
							  	 	<th> Special Talents / Skills </th>
							  	 	<td>" . $special_skills . "</td>
							  	 </tr>
							  	 <tr>
							  	 	<th>Interests </th>
							  	 	<td>";
							  	 	$prefix = ' ';
							  	 	foreach($interests as $interest){
							  	 		$body .= $prefix . " " . $interest;
							  	 		$prefix = ', ';
							  	 	} 
							  	 	$body .= "</td>
							  	 	</tr>
							  	 	<tr>
							  	 		<th>Days and times available</th>
							  	 		<td>" . $times . "</td>
									</tr>
									<tr>
										<th>Volunteer Experience (most recent)</th>
										<td>Experience 1: <br />
										  Position: " . $ex_position_1 . " Agency: " . $agency_1 . " Date: " . $date_1 . "<br />
										  Experience 2: <br />
										  Position: " . $ex_position_2 . " Agency: " .
										  	$agency_2 . " Date: " . $date_2 . "</td>
									</tr>
									<tr>
										<th>Volunteer age > 18 ? </th>
										<td>" . $age . "</td>
									</tr>
									<tr>
										<th>How did you hear about our Volunteer Services Department? </th>
										<td>" . $hear . "</td>
									</tr>
									<tr>
										<th>Why do you want to volunteer with My Blind Spot?</th>
										<td>" . $why_volunteer . "</td>
									</tr>
									<tr>
										<th>Have you ever been convicted of a crime (including probations before judgement), or are there any pending criminal charges awaiting a hearing in a court of law? Do not list any criminal charges for which records have been expunged.
										</th>
										<td>" . $crime . "</td>
									</tr>
									<tr>
										<th>If yes, please describe all convictions when they occurred, the acts and circumstances involved, and information pertaining to rehabilitation: 
										</th>
										<td>" . $crime_reason . "</td>
									</tr>
									<tr>
										<th>References:</th>
										<td>Reference one: <br />
										Name: " . $ref_name_1 . " Relationship: " . $ref_relationship_1 . " Contact Number: " . $ref_contact_1 . " Email: " . $ref_email_1 . "<br />
										Reference two: <br /> 
										Name: " . $ref_name_2 . " Relationship: " . $ref_relationship_2 . " Contact Number: " . $ref_contact_2 . " Email: " . $ref_email_2 . "<br /> 
										Reference three: <br />
										Name: " . $ref_name_3 . " Relationship: " . $ref_relationship_3 . " Contact Number: " . $ref_contact_3 . " Email: " . $ref_email_3 . "<br /> 
										</td>
									</tr>
									<tr>
										<th>Emergency contact: </th>
										<td> Contact Name: " .  $e_contact_name . " Relationship: " . $e_relationship . " Home Phone Number: " . $e_phone_no . " Cell Phone Number: " . $e_cell_phone_no . "</td>
									</tr>
							  </table>
							  ";
			$headers = array('Content-Type: text/html; charset=UTF-8');
			 
			$admin_mail = wp_mail( $toadmin, $mail_subject, $body, $headers );

			if($admin_mail == false) {
				$mail_error = "Failed to send your message. Please try later or contact the administrator by another method.";
			}
			$_SESSION['form_submit'] = 'true';			  
			} else {
				$_SESSION['form_submit'] = NULL;
			}
					

	}

	$token_id = md5( uniqid( "", true ) );
	if(count($error_message)) {
			$output = '<div class="info-box warning bmg-input-error" role="alert">
			<h3><strong>Error submitting form:</strong></h3>
			<ul class="bmg-list-errors" title="The following errors have been reported">'; 
			foreach($error_message as $key => $error) {
				
				$output .= '<li><a href="#' . $key .'" id="' . $key . '-error">'  . $error . "</a></li>";
			}
			$output .= '</ul></div>';
	} else {
		$output = '';	
	}
	if($success) {
		$output = '<div class="bmg-success-box" role="alert"><Strong>Message successfully sent!</strong>
		</div>';
	} 
	$output .= '<div role="form">
				<form method="post" action="" novalidate>
					<div class="row">
						<div class="col-sm-3">
							<div class="form-group">
								<label for="bmg-first-name">First Name: </label>
								<input type="text" name="bmg-first-name" value="' . $first_name . '" size="40" class="form-control" id="bmg-first-name" aria-required="true" aria-invalid="' . $aria_state['bmg_first_name'] . '" placeholder="First Name" aria-describedby="bmg-first-name-error" />';
								
						$output .=	'</div>
						</div>

						<div class="col-sm-3 col-md-2">
							<div class="form-group">
								<label for="bmg-middle-initial">Middle Initial: </label>
								<input type="text" name="bmg-middle-initial" value="' . $middle_name . '" size="40" class="form-control" id="bmg-middle-initial" aria-invalid="false" placeholder="Middle Initial">
							</div>
						</div>

						<div class="col-sm-3">
							<div class="form-group">
								<label for="bmg-last-name">Last Name: </label>
								<input type="text" name="bmg-last-name" value="' . $last_name . '" size="40" class="form-control" id="bmg-last-name" aria-required="true" aria-invalid="' . $aria_state['bmg_last_name'] . '" placeholder="Last Name" aria-describedby="bmg-last-name-error" />';

						$output .= 	'</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-4">
							<div class="form-group">
								<label for="bmg-current-address">Current Address: </label>
								<input type="text" name="bmg-current-address" value="' . $current_add . '" size="40" class="form-control" id="bmg-current-address" aria-invalid="false" placeholder="Current Address" />
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-city">City: </label>
								<input type="text" name="bmg-city" value="' . $city . '" size="40" class="form-control" id="bmg-city" aria-invalid="false" placeholder="City" />
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-state">State: </label>
								<select name="bmg-state" class="form-control" id="bmg-state" aria-invalid="false">';
									foreach($us_states as $state_item){
										$selected = '';
										if($state == $state_item){
											$selected = 'selected="selected"';
										}
										$output .= '<option value="' . $state_item . '"' . $selected . ' >' . $state_item . '</option>';
									}
							$output .=	'</select>
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-zip">Zip: </label>
								<input type="text" name="bmg-zip" value="' . $zip . '" size="40" class="form-control" id="bmg-zip" aria-invalid="false" placeholder="Zip" />
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-3">
							<div class="form-group">
								<label for="bmg-phone">Phone: </label>
								<input type="text" name="bmg-phone" value="' . $phone . '" size="40" class="form-control" id="bmg-phone" aria-required="true" aria-invalid="' . $aria_state['bmg_phone'] . '" placeholder="Phone" aria-describedby="bmg-phone-error">';	
						$output .=	'</div>
						</div>

						<div class="col-sm-3">
							<div class="form-group">
								<label for="bmg-email">Email</label>
								<input type="email" name="bmg-email" value="' . $email . '" size="40" class="form-control" id="bmg-email" aria-required="true" aria-invalid="' . $aria_state['bmg_email'] . '" placeholder="Email" aria-describedby="bmg-email-error" />';
						$output .=	'</div>
						</div>

						<div class="col-sm-3">
							<div class="form-group">
								<label for="bmg-birthday">Birthday</label>
								<input type="text" name="bmg-birthday" value="' . $birthdate . '" size="40" class="form-control" id="bmg-birthday" aria-invalid="false" placeholder="Format: (MM/DD/YYYY)" />
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-8">
							<div class="form-group">
 								<label for="bmg-education">Education: </label>
 								<textarea name="bmg-education" cols="40" rows="10" class="form-control" id="bmg-education" aria-invalid="false" placeholder="Education">' . $education . '</textarea>
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-4">
							<div class="form-group">
								<label for="bmg-current-employer">Current Employer: </label>
								<input type="text" name="bmg-current-employer" value="' . $current_employer . '" size="40" class="form-control" id="bmg-current-employer" aria-invalid="false" placeholder="Current Employer" />
							</div>
						</div>

						<div class="col-sm-4">
							<div class="form-group">
								<label for="bmg-position">Position: </label>
								<input type="text" name="bmg-position" value="' . $position . '" size="40" class="form-control" id="bmg-position" aria-invalid="false" placeholder="Position" />
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-8">
							<div class="form-group">
								<label for="bmg-special-talent">Special Talents / Skills that you feel would benifit our organization: </label>
								<textarea name="bmg-special-talent" cols="40" rows="10" class="form-control" id="bmg-special-talent" aria-invalid="false" placeholder="Special Talents/Skills">' . $special_skills . '</textarea>
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-8">
							<div class="form-group" role="group" aria-label="Interest: Please tell us which areas you are interested in volunteering">
								<label>Interest: Please tell us which areas you are interested in volunteering</label> 
								<br />
								<label>
									<input type="checkbox" name="bmg-interests[]" value="Administration"'; 
									if(is_array($interests)) { 
									 if (in_array("Administration", $interests)){
									 	$output .= "checked='checked'";
									 } 
									}
									$output .= '/>
									Administrator
								</label> <br />
								<label>
									<input type="checkbox" name="bmg-interests[]" value="Communication"';
									if(is_array($interests)) { 
										if (in_array("Communication", $interests)){
									 		$output .= "checked='checked'";
									 	} 
									}
									 $output .= '/>
									Communication
								</label> <br />
								<label>
									<input type="checkbox" name="bmg-interests[]" value="Education"';
									if(is_array($interests)) { 
										if (in_array("Education", $interests)){
										 	$output .= "checked='checked'";
										 } 
									}
									 $output .= '/>
									Education
								</label> <br />
								<label>
									<input type="checkbox" name="bmg-interests[]" value="Events/Fundraising"';
									if(is_array($interests)) { 
										if (in_array("Events/Fundraising", $interests)){
										 	$output .= "checked='checked'";
										 }
									}
									 $ouput .= '/>
									Events / Fundraising
								</label> <br />
								<label>
									<input type="checkbox" name="bmg-interests[]" value="Grant writing"';
									if(is_array($interests)) { 
										if (in_array("Grant writing", $interests)){
										 	$output .= "checked='checked'";
										 }
									}
									 $output .= '/>
									Grant Writing
								</label> <br />
								<label>
									<input type="checkbox" name="bmg-interests[]" value="Web accessibility testing"';
									if(is_array($interests)) { 
										if (in_array("Web accessibility testing", $interests)){
										 	$output .= "checked='checked'";
										 }
									}
									 $output .= '/>
									Web Accessibility Testing
								</label> <br />
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-8">
							<div class="form-group">
								<label for="bmg-available">Please indicate days and times available: 
								</label>
								<textarea name="bmg-available" cols="40" rows="10" class="form-control" id="bmg-available" aria-invalid="false" placeholder="Days and times available">' . $times . '</textarea>
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-8">
							<h3> Volunteer Experience: <span>(list most recent)</span> </h3>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-4">
							<div class="form-group">
								<label for="bmg-position-1">Position: </label>
								<input type="text" name="bmg-position-1" value="' . $ex_position_1 . '" size="40" class="form-control" id="bmg-position-1" aria-invalid="false" placeholder="Position" />
							</div>
						</div> 

						<div class="col-sm-4">
							<div class="form-group">
								<label for="bmg-agency-1">Agency: </label>
								<input type="text" name="bmg-agency-1" value="' . $agency_1 . '" size="40" class="form-control" id="bmg-agency-1" aria-invalid="false" placeholder="Agency" />
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-date-1">Date: </label>
								<input type="text" name="bmg-date-1" value="' . $date_1 . '" size="40" class="form-control" id="bmg-date-1" aria-invalid="false" placeholder="Date" />
							</div>
						</div>
					</div> <!-- End of row -->	

					<div class="row">
						<div class="col-sm-4">
							<div class="form-group">
								<label for="bmg-position-2">Position: </label>
								<input type="text" name="bmg-position-2" value="' . $ex_position_2 . '" size="40" class="form-control" id="bmg-position-2" aria-invalid="false" placeholder="Position" />
							</div>
						</div> 

						<div class="col-sm-4">
							<div class="form-group">
								<label for="bmg-agency-2">Agency: </label>
								<input type="text" name="bmg-agency-2" value="' . $agency_2 . '" size="40" class="form-control" id="bmg-agency-2" aria-invalid="false" placeholder="Agency" />
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-date-2">Date: </label>
								<input type="text" name="bmg-date-2" value="' . $date_2 . '" size="40" class="form-control" id="bmg-date-2" aria-invalid="false" placeholder="Date" />
							</div>
						</div>
					</div> <!-- End of row -->	

					<div class="row">
						<div class="col-sm-8" role="radiogroup" aria-labelledby="radio_age_group">
							<label for="bmg-age" id="radio_age_group">Are you at least 18 years of age?</label> <br />
							<label>
								<input type="radio" name="bmg-age" value="yes"';
									if ($age == "yes"){
									 	$output .= "checked='checked'";
									 } else {
									 	$output .= "checked='checked'";
									 }
								 $output .= '/>
								Yes
							</label> <br />
							<label>
								<input type="radio" name="bmg-age" value="no"';
									if ($age == "no"){
									 	$output .= "checked='checked'";
									 }
								 $output .= '/>
								No
							</label> 
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-8">
							<div class="form-group">

								<label for="bmg-hear">How did you hear about our Volunteer Services Department?: </label>
								<textarea name="bmg-hear" cols="40" rows="10" class="form-control" id="bmg-hear" aria-invalid="false" placeholder="How did you hear about our Volunteer Services Department?">' . $hear . '</textarea>
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-8">
							<div class="form-group">
								<label for="bmg-why-volunteer">Why do you want to volunteer with My Blind Spot?: </label>
								<textarea name="bmg-why-volunteer" cols="40" rows="10" class="form-control" id="bmg-why-volunteer" aria-invalid="false" placeholder="Why do you want to volunteer with My Blind Spot?">' . $why_volunteer . '</textarea>
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-8">
							<div class="form-group" role="radiogroup" aria-labelledby="radio_crime_group">
								<label for="bmg-crime" id="radio_crime_group">
									Have you ever been convicted of a crime (including probations before judgement), or are there any pending criminal charges awaiting a hearing in a court of law? Do not list any criminal charges for which records have been expunged.
								</label>
								<label>
									<input type="radio" name="bmg-crime" value="Yes"';
										if($crime == "Yes") {
											$output .= 'checked="checked"';
										}	
									$output .= ' />
									Yes
								</label> <br />
								<label>
									<input type="radio" name="bmg-crime" value="No"';
										if($crime == "No") {
											$output .= 'checked="checked"';
										} else {
											$output .= 'checked="checked"';
										}
									$output .= ' />
									No
								</label>
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-8">
							<div class="form-group">
								<label for="bmg-crime-why">
									If yes, please describe all convictions when they occurred, the acts and circumstances involved, and information pertaining to rehabilitation: 
								</label>
								<textarea name="bmg-crime-why" id="bmg-crime-why" aria-invalid="false" placeholder="If yes, please describe all convictions when they occurred, the acts and circumstances involved, and information pertaining to rehabilitation" class="form-control">' . $crime_reason . '</textarea>
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<h3> References: Please provide at least three (3) references who can attest to your character skills, responsibility, and dependability. Family references are not permitted.
						</h3>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-ref-name-1">Name: </label>
								<input type="text" name="bmg-ref-name-1" value="' . $ref_name_1 . '" size="40" class="form-control" id="bmg-ref-name-1" aria-invalid="false" placeholder="Name">
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-rel-1">Relationship: </label>
								<input type="text" name="bmg-rel-1" value="' . $ref_relationship_1 . '" size="40" class="form-control" id="bmg-rel-1" aria-invalid="false" placeholder="Relationship">
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-contact-1">Contact Number: </label>
								<input type="text" name="bmg-contact-1" value="' . $ref_contact_1 . '" size="40" class="form-control" id="bmg-contact-1" aria-invalid="' . $aria_state['ref_contact_1'] . '" placeholder="Contact Number">';	

						$output .=	'</div>
						</div>

						<div class="col-sm-3">
							<div class="form-group">
								<label for="bmg-contact-email-1">Email: </label>
								<input type="email" name="bmg-contact-email-1" value="' . $ref_email_1 . '" size="40" class="form-control" id="bmg-contact-email-1" aria-invalid="' . $aria_state['bmg_contact_email_1'] . '" placeholder="Email" aria-describedby="bmg-contact-email-1-error">';
								
						$output .=	'</div>
						</div>
					</div> <!-- End of row -->

					<!-- Reference 2 -->
					<div class="row">
						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-ref-name-2">Name: </label>
								<input type="text" name="bmg-ref-name-2" value="' . $ref_name_2 . '" size="40" class="form-control" id="bmg-ref-name-2" aria-invalid="false" placeholder="Name">
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-rel-2">Relationship: </label>
								<input type="text" name="bmg-rel-2" value="' . $ref_relationship_2 . '" size="40" class="form-control" id="bmg-rel-2" aria-invalid="false" placeholder="Relationship">
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-contact-2">Contact Number: </label>
								<input type="text" name="bmg-contact-2" value="' . $ref_contact_2 . '" size="40" class="form-control" id="bmg-contact-2" aria-invalid="' . $aria_state['ref_contact_2'] . '" placeholder="Contact Number" aria-describedby="bmg-contact-2-error">';
						$output .=	'</div>
						</div>

						<div class="col-sm-3">
							<div class="form-group">
								<label for="bmg-contact-email-2">Email: </label>
								<input type="email" name="bmg-contact-email-2" value="' . $ref_email_2 . '" size="40" class="form-control" id="bmg-contact-email-2" aria-invalid="' . $aria_state['bmg_contact_email_2'] . '" placeholder="Email" aria-describedby="bmg-contact-email-2-error">';
								
						$output .=	'</div>
						</div>
					</div> <!-- End of row -->

					<!-- Reference 3 -->
					<div class="row">
						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-ref-name-3">Name: </label>
								<input type="text" name="bmg-ref-name-3" value="' . $ref_name_3 . '" size="40" class="form-control" id="bmg-ref-name-3" aria-invalid="false" placeholder="Name">
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-rel-3">Relationship: </label>
								<input type="text" name="bmg-rel-3" value="' . $ref_relationship_3 . '" size="40" class="form-control" id="bmg-rel-3" aria-invalid="false" placeholder="Relationship">
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								<label for="bmg-contact-3">Contact Number: </label>
								<input type="text" name="bmg-contact-3" value="' . $ref_contact_3 . '" size="40" class="form-control" id="bmg-contact-3" aria-invalid="' . $aria_state['ref_contact_3'] . '" placeholder="Contact Number" aria-describedby="bmg-contact-3-error">';
						$output .=	'</div>
						</div>

						<div class="col-sm-3">
							<div class="form-group">
								<label for="bmg-contact-email-3">Email: </label>
								<input type="email" name="bmg-contact-email-3" value="' . $ref_email_3 . '" size="40" class="form-control" id="bmg-contact-email-3" aria-invalid="' . $aria_state['bmg_contact_email_3'] . '" placeholder="Email" aria-describedby="bmg-contact-email-3-error" >';
								
							$output .= '</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-8">
							<h3>In the event of an emergency, please list the person you would want notified:</h3>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-4">
							<div class="form-group">
								<label for="bmg-ec-name">Emergency Contact Name: </label>
								<input type="text" name="bmg-ec-name" value="' . $e_contact_name . '" size="40" class="form-control" id="bmg-ec-name" aria-invalid="false" placeholder="Emergency Contact Name">
							</div>
						</div>

						<div class="col-sm-4">
							<div class="form-group">
								<label for="bmg-ec-relationship">Relationship: </label>
								<input type="text" name="bmg-ec-relationship" value="' . $e_relationship . '" size="40" class="form-control" id="bmg-ec-relationship" aria-invalid="false" placeholder="Relationship">
							</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-4">
							<div class="form-group">
								<label for="bmg-ec-phone-number">Home Phone Number: </label>
									<input type="text" name="bmg-ec-phone-number" value="' . $e_phone_no . '" size="40" class="form-control" id="bmg-ec-phone-number" aria-invalid="'  . $aria_state['e_phone_no'] . '" placeholder="Home Phone Number" aria-describedby="bmg-ec-phone-number-error">';
									
						$output .=	'</div>
						</div>

						<div class="col-sm-4">
							<div class="form-group">
							<label for="bmg-ec-cell-number">Cell Phone Number:</label>
							<input type="text" name="bmg-ec-cell-number" value="' . $e_cell_phone . '" size="40" class="form-control" id="bmg-ec-cell-number" aria-invalid="' . $aria_state['e_phone_no'] . '" placeholder="Cell Phone Number" aria-describedby="bmg-ec-phone-number-error">';
							
							$output .= '</div>
						</div>
					</div> <!-- End of row -->

					<div class="row">
						<div class="col-sm-8">
							 <input type="hidden" name="token" value="' . $token_id . '" />
							<input type="submit" name="bmg_submit" value="Submit" class="btn-mbs" /></div>
					</div>

					<div class="row">
						<div class="col-sm-8"><br />';
							if($mail_error) {
							$output .= '<div role="alert" class="bmg-forms-mail-error">' . $mail_error . '</div>';
							}
					$output .=	'</div>
					</div>
				</form>
			   </div>';

	return $output;
}

// 2.3
// hint: feedback form shortcode
function bmg_feedback_form_shortcode($args, $content="") {
	global $wpdb;
	$table_name = $wpdb->prefix . 'bmg_feedback';
	$error_message = [];
	$aria_state = [];
	$success = false;
	$name = $email =  $comments = '';
	

	

	if(isset($_POST['bmg_submit']) && empty($_SESSION['form_submit'])) {
	
		$name = trim(esc_attr($_POST['bmg-name']));
		$email = trim(esc_attr($_POST['bmg-email']));
		$comments = trim(esc_attr($_POST['bmg-comments']));
		$token_id = stripslashes( $_POST['token'] );

		if(empty($name)) {
			$error_message['bmg-name'] = 'Please enter your name'; 
			$aria_state['name'] = 'true';
		}
		if(empty($email)) {
			$error_message['bmg-email'] = 'Please enter your email address';
			$aria_state['email'] = 'true';
		}
		if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
			$error_message['bmg-email'] = 'Please enter a valid email address';
			$aria_state['email'] = 'true';
		}
		if(empty($comments)) {
			$error_message['bmg-comments'] = 'Please enter comments';
			$aria_state['comments'] = 'true';
		}


		// If there is no error 
		if(count($error_message) == 0 && !get_transient( 'token_' . $token_id )) {
			
			 $table_name = $wpdb->prefix . 'bmg_feedback';
				$sql = "SHOW COLUMNS FROM $table_name";
				$result = $wpdb->get_results($sql);
				$count = 0;
				$fields = [];
				$table_data = [];
				$form_fields = [$name, $email, $comments];

				for($i = 0; $i < count($form_fields); $i++) { 
					$fields[$count] = $result[$count + 1]->Field;
					$field_name  = $fields[$count];
					$table_data[$field_name] = $form_fields[$count];
					$count++;
				}
				
				$success = $wpdb->insert(
								$table_name, $table_data, array('%s','%s','%s')
						   );

				set_transient( 'token_' . $token_id, 'dummy-content', 60 );
					$wpdb->show_errors();
					print_r( $wpdb->queries);


			$toadmin = get_option('bmg_forms_admin_email');
			$mail_subject = 'Feedback from' . $name;
			$body = $comments;
			$headers = array('Content-Type: text/html; charset=UTF-8');
			 
			$admin_mail = wp_mail( $toadmin, $mail_subject, $body, $headers );

			$to_customer = $email;
			$customer_subject = 'My Blind Spot Feedback Submission Confirmation';
			$customer_body = "Greetings <br />,
								Here's a copy of your contact request to My Blind Spot
							<table>
								<tr>
								 	<th>Name</th>
								 	<td>" . $name . "</td>
								 </tr>
								 <tr>
								 	<th> Subject</th>
								 	<td>" . $email . "</td>
								 </tr>
								 <tr>
									<th> Comment </th>
									<td>" . $comments . "</td>
							  	  </tr>
							  </table>
							  ";

			$customer_mail = wp_mail( $to_customer, $customer_subject, $customer_body, $headers );	

			if($admin_mail == false) {
				$mail_error = "Failed to send your message. Please try later or contact the administrator by another method.";
			}
			$_SESSION['form_submit'] = 'true';			  
		} else {
			$_SESSION['form_submit'] = NULL;
		}

	}
	$token_id = md5( uniqid( "", true ) );
	if(count($error_message)) {
			$output = '<div class="info-box warning bmg-input-error" role="alert">
			<h3><strong>Error submitting form:</strong></h3>
			<ul class="bmg-list-errors" title="The following errors have been reported">'; 
			foreach($error_message as $key => $error) {
				
				$output .= '<li><a href="#' . $key .'" id="' . $key . '-error">'  . $error . "</a></li>";
			}
			$output .= '</ul></div>';
	} else {
		$output = '';	
	}
	if($success) {
		$output = '<div class="bmg-success-box" role="alert"><Strong>Message successfully sent!</strong>
		</div>';
	}
	$output .= '<div role="form">
				<form method="post" action="" novalidate>
					<div class="form-group">
						<label for="bmg-name">Name: (required)</label>
						<input type="text" name="bmg-name" id="bmg-name" aria-required="true" aria-invalid="' . $aria_state['name'] . '" aria-describedby="bmg-name-error" placeholder="Name" value="' . $name . '" class="form-control" />';
				$output .=	'</div>

					<div class="form-group">
						<label for="bmg-email">Email: (required)</label>
						<input type="text" name="bmg-email" id="bmg-email" aria-required="true" aria-invalid="' . $aria_state['email'] . '" placeholder="Email" aria-describedby="bmg-email-error" class="form-control" value="' . $email . '" />';

				$output .=	'</div>

					<div class="form-group">
						<label for="bmg-comments">Comments: (required)</label>
						<textarea name="bmg-comments" cols="40" rows="10" class="form-control" id="bmg-comments" aria-required="true" aria-invalid="' . $aria_state['comments'] . '" aria-describedby="bmg-comments-error" placeholder="Comments">' . $comments . '</textarea>';
						
				$output .=	'</div>

					<div class="form-group">
					 <input type="hidden" name="token" value="' . $token_id . '" />
						<input type="submit" name="bmg_submit" value="Submit" class="bmg-submit btn-mbs">
					</div>';
						if($mail_error) {
							$output .= '<div role="alert" class="bmg-forms-mail-error">' . $mail_error . '</div>';
						}
					
				$output .= '</form>
	';

	return $output;

}

// 2.4 
// hint: service inquiry form 
function bmg_service_inquiry_form_shortcode($args, $content="") {
	global $wpdb;
	$table_name = $wpdb->prefix . 'bmg_service_inquiry';
	$error_message = [];
	$aria_state = [];
	$success = false;
	$name = $email = $phone = $website = $service_request = $comments = '';
	

	

	if(isset($_POST['bmg_submit']) && empty($_SESSION['form_submit'])) {
	
		$name = trim(esc_attr($_POST['bmg-name']));
		$email = trim(esc_attr($_POST['bmg-email']));
		$phone = trim(esc_attr($_POST['bmg-phone']));
		$website = trim(esc_attr($_POST['bmg-website']));
		$subject = trim(esc_attr($_POST['bmg-service-request']));
		$comments = trim(esc_attr($_POST['bmg-comments']));
		$token_id = stripslashes( $_POST['token'] );

		if(empty($name)) {
			$error_message['bmg-name'] = 'Please enter your name'; 
			$aria_state['name'] = 'true';
		}
		if(empty($email)) {
			$error_message['bmg-email'] = 'Please enter your email address';
			$aria_state['email'] = 'true';
		}
		if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
			$error_message['bmg-email'] = 'Please enter a valid email address';
			$aria_state['email'] = 'true';
		}
		if(empty($comments)) {
			$error_message['bmg-comments'] = 'Please enter comments';
			$aria_state['comments'] = 'true';
		}


		// If there is no error 
		if(count($error_message) == 0 && !get_transient( 'token_' . $token_id )) {
			
				$sql = "SHOW COLUMNS FROM $table_name";
				$result = $wpdb->get_results($sql);
				$count = 0;
				$fields = [];
				$table_data = [];
				$form_fields = [$name, $email, $phone, $website, $service_request , $comments];

				for($i = 0; $i < count($form_fields); $i++) { 
					$fields[$count] = $result[$count + 1]->Field;
					$field_name  = $fields[$count];
					$table_data[$field_name] = $form_fields[$count];
					$count++;
				}
			

				$success = $wpdb->insert(
								$table_name, $table_data, array('%s','%s','%s','%s','%s','%s')
						   );

				set_transient( 'token_' . $token_id, 'dummy-content', 60 );
					$wpdb->show_errors();
					print_r( $wpdb->queries);


			$toadmin = get_option('bmg_forms_admin_email');
			$mail_subject = 'Service Request Submission from' . $name . 'regarding' . $service_request;
			$body = $comments;
			$headers = array('Content-Type: text/html; charset=UTF-8');
			 
			$admin_mail = wp_mail( $toadmin, $mail_subject, $body, $headers );

			$to_customer = $email;
			$customer_subject = 'My Blind Spot Service Request Submission Confirmation';
			$customer_body = "Greetings <br />,
								Here's a copy of your service request to My Blind Spot
							<table>
								<tr>
								 	<th>Full Name</th>
								 	<td>" . $name . "</td>
								 </tr>
								 <tr>
								 	<th> Subject</th>
								 	<td>" . $subject . "</td>
								 </tr>
								 <tr>
									<th> Comment </th>
									<td>" . $comments . "</td>
							  	  </tr>
							  </table>
							  ";

			$customer_mail = wp_mail( $to_customer, $customer_subject, $customer_body, $headers );	

			if($admin_mail == false) {
				$mail_error = "Failed to send your message. Please try later or contact the administrator by another method.";
			}
			$_SESSION['form_submit'] = 'true';			  
		} else {
			$_SESSION['form_submit'] = NULL;
		}

	}
	$token_id = md5( uniqid( "", true ) );
	if(count($error_message)) {
			$output = '<div class="info-box warning bmg-input-error" role="alert">
			<h3><strong>Error submitting form:</strong></h3>
			<ul class="bmg-list-errors" title="The following errors have been reported">'; 
			foreach($error_message as $key => $error) {
				
				$output .= '<li><a href="#' . $key .'" id="' . $key . '-error">'  . $error . "</a></li>";
			}
			$output .= '</ul></div>';
	} else {
		$output = '';	
	}
	if($success) {
		$output = '<div class="bmg-success-box" role="alert"><Strong>Message successfully sent!</strong>
		</div>';
	}
	$output .= '<div role="form">
				<form method="post" action="" novalidate>
					<div class="form-group">
						<label for="bmg-name">Name: (required)</label>
						<input type="text" name="bmg-name" id="bmg-name" aria-required="true" aria-invalid="' . $aria_state['name'] . '" aria-describedby="bmg-name-error" placeholder="Name" value="' . $name . '" class="form-control" />';
				$output .=	'</div>

					<div class="form-group">
						<label for="bmg-email">Email: (required)</label>
						<input type="text" name="bmg-email" id="bmg-email" aria-required="true" aria-invalid="' . $aria_state['email'] . '" placeholder="Email" aria-describedby="bmg-email-error" class="form-control" value="' . $email . '" />';
				$output .=	'</div>

					<div class="form-group">
						<label for="bmg-phone">Phone:</label>
						<input type="text" name="bmg-phone" id="bmg-phone" aria-invalid="false" placeholder="Phone" class="form-control" value="' . $phone . '" />
					</div>

					<div class="form-group">
						<label for="bmg-website">Website:</label>
						<input type="text" name="bmg-website" id="bmg-website" aria-invalid="false" placeholder="Website" value="' . $website . '" class="form-control" />
					</div>
	
					<div class="form-group">
						<label for="bmg-service-request">Service Request:</label>
						<select name="bmg-service-request" id="bmg-service-request" aria-required="true" aria-invalid="false" class="form-control">
							<option value=""></option>
							<option value="Accessiblity for Executive Teams">Accessiblity for Executive Teams</option>
							<option value="Motivational Speaking and Outreach">Motivational Speaking and Outreach</option>
							<option value="Accessibility Governance and Program Management">Accessibility Governance and Program Management</option>
							<option value="Accessibility Evaluations">Accessibility Evaluations</option>
							<option value="Quickbooks™ Training or workshop questions">Quickbooks™ Training or workshop questions</option>
						</select>';
						

					$output .= '</div>

					<div class="form-group">
						<label for="bmg-comments">Comments: (required)</label>
						<textarea name="bmg-comments" cols="40" rows="10" class="form-control" id="bmg-comments" aria-required="true" aria-invalid="' . $aria_state['comments'] . '" aria-describedby="bmg-comments-error" placeholder="Comments">' . $comments . '</textarea>';
				$output .=	'</div>

					<div class="form-group">
					 <input type="hidden" name="token" value="' . $token_id . '" />
						<input type="submit" name="bmg_submit" value="Submit" class="bmg-submit btn-mbs">
					</div>';
						if($mail_error) {
							$output .= '<div role="alert" class="bmg-forms-mail-error">' . $mail_error . '</div>';
						}
					
				$output .= '</form>
	';

	return $output;

}


/* 3. Filters */

/* 3.1 - admin menus */
	
	function bmg_forms_settings_page() {
		/* Main menu / Plugin Configuration Menu */
		add_menu_page(
			'BMG Forms',
			'BMG Forms',
			'manage_options',
			'bmg-forms',
			'bmg_forms_page_markup',
			'dashicons-email',
			100
		);

		/* Settings page */
		add_submenu_page (
			'bmg-forms',
			__( 'New Form', 'bmg-new-form' ),
			__( 'New Form', 'bmg-new-form'),
			'manage_options',
			'bmg-new-form',
			'bmg_new_form_markup'
		);


		/* View Submissions */
		add_submenu_page (
			'bmg-forms',
			__( 'Submissions', 'bmg-submissions' ),
			__( 'Submissions', 'bmg-submissions'),
			'manage_options',
			'bmg-submissions',
			'bmg_submissions_markup'
		);

		/* View Submission Detail */
		add_submenu_page (
			null,
			__( 'Submissions Detail', 'bmg-submission-detail' ),
			__( 'Submissions Detail', 'bmg-submissions-detail'),
			'manage_options',
			'bmg_submission_detail',
			'bmg_submission_detail_markup'
		);

		/* Settings page */
		add_submenu_page (
			'bmg-forms',
			__( 'Settings', 'bmg-settings' ),
			__( 'Settings', 'bmg-settings'),
			'manage_options',
			'bmg-settings',
			'bmg_settings'
		);

			
		

	}



/* 4. External Scripts */

// 4.1 
// hint: loads external files into PUBLIC website 
	function bmg_forms_public_scripts() {	
		wp_register_style('bmg-forms-css-public', plugins_url('css/public/bmg-forms.css', __FILE__));
		wp_enqueue_style('bmg-forms-css-public');	

		

	}

// 4.2
// hint: loads external files into admin
	function bmg_forms_admin_scripts() {
		wp_register_style('bmg-forms-admin-css', plugins_url('css/private/bmg-forms.css', __FILE__));
		wp_register_script('bmg-jquery-ui-private', plugins_url('js/private/jquery-ui.min.js', __FILE__), array('jquery'));
		wp_register_script('bmg-forms-form-builder-private', plugins_url('js/private/form-builder.min.js', __FILE__), array('jquery'));
		wp_register_script('bmg-forms-app-private', plugins_url('js/private/app.js', __FILE__), array('bmg-forms-form-builder-private'), false, true);

		wp_enqueue_style('bmg-forms-admin-css');
		wp_enqueue_script('bmg-jquery-ui-private');
		wp_enqueue_script('bmg-forms-form-builder-private');
		wp_enqueue_script('bmg-forms-app-private');
	}


/* 5. Actions */

// 5.1
// hint: create all tables related to plugin
function bmg_forms_plugin_create_db() {
	// Create DB Here
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'bmg_forms';
	$table_name1 = $wpdb->prefix . 'bmg_forms_meta';
	

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		form_name varchar(100) NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";
	$wpdb->query($sql);


	$sql = "CREATE TABLE IF NOT EXISTS $table_name1 (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		form_id mediumint(9) NOT NULL,
		type varchar(30) NOT NULL,
		required boolean DEFAULT 0,
		label text,
		description text,
		placeholder varchar(100),
		classname varchar(100),
		name varchar(30),
		access boolean DEFAULT 0,
		value text,
		subtype varchar(30),
		maxlength integer,
		sub_values BLOB,
		requirevalidoption boolean DEFAULT 0,
		style varchar(30),
		other boolean DEFAULT 0,
		multiple boolean DEFAULT 0,
		min integer,
		max integer,
		step integer,
		rows integer,
		toogle boolean DEFAULT 0,
		inline boolean DEFAULT 0,
		PRIMARY KEY  (id),
		FOREIGN KEY  (form_id) REFERENCES  $table_name(id)
	) $charset_collate;";
	$wpdb->query($sql);

}

// 5.2
// hint: remove all tables on uninstall
function bmg_forms_uninstall_plugin() {

	// remove custom tables
	bmg_forms_remove_plugin_tables();

	//Remove plugin options
	bmg_forms_remove_options();

}

function bmg_forms_remove_plugin_tables() {
	//setup return variable
	$tables_removed = false;
	
	global $wpdb;
	try {
				$charset_collate = $wpdb->get_charset_collate();
				$table_name = $wpdb->prefix . 'bmg_forms';
				$table_name1 = $wpdb->prefix . 'bmg_forms_meta';
				

				$sql = "SET FOREIGN_KEY_CHECKS=0;";
				$wpdb->query($sql);

				$sql = "SET FOREIGN_KEY_CHECKS=0;";
				$wpdb->query($sql);
			
				$sql = "DROP TABLE IF EXISTS  $table_name, $table_name1;";
				$tables_removed = $wpdb->query($sql);
		}
		catch (Exception $e) {
			
			$wpdb->show_errors();
		}

	return $tables_removed;	
}


function bmg_generate_form() {
	$result = false;
	global $wpdb;
	$table_name = $wpdb->prefix . 'bmg_forms';
	$form_name = $_POST['formname'];
	$form_data = json_decode(stripcslashes($_POST['formdata']));
	if(isset($form_name) && isset($form_data)) {
		/*$wpdb->insert( 
			$table_name, 
			array( 
				'form_name' => $form_name
			),
			array('%s') 
		);*/



	}
	$fields = count($form_data);
	for($i = 0; $i < $fields; $i++){
		foreach ($form_data[$i] as $key => $value) {
			echo $key . " " . $value;
		}
	}
	 wp_die();

}

function bmg_forms_remove_options() {

	$options_removed = false;
	
	try {
	
		// get plugin option settings
		$options = bmg_get_options_settings();
		
		// loop over all the settings
		foreach( $options['settings'] as &$setting ):
			
			// unregister the setting
			unregister_setting( $options['group'], $setting );
		
		endforeach;
	
	} catch( Exception $e ) {
		
		// php error
		
	}
	
	// return result
	return $options_removed;

}




/* 6. Helpers */

// 6.1
// hint: get's the current options and returns values in associative array
function bmg_get_current_options() {
	
	// setup our return variable
	$current_options = array();
	
	try {
	
		// build our current options associative array
		$current_options = array(
			'bmg_forms_admin_email' => bmg_get_option('bmg_forms_admin_email'), 
			'bmg_table_row_limit' => bmg_get_option('bmg_table_row_limit'),
		);
	
	} catch( Exception $e ) {
		
		// php error
	
	}
	
	// return current options
	return $current_options;
	
}

// 6.2
// hint: get's an array of plugin option data (group and settings) so as to save it all in one place
function bmg_get_options_settings() {
	
	// setup our return data
	$settings = array( 
		'group'=>'bmg_forms_settings',
		'settings'=>array(
			'bmg_forms_admin_email',		
			'bmg_table_row_limit',
		),
	);
	
	// return option data
	return $settings;
	
}

// 6.20
// hint: returns the requested page option value or it's default
function bmg_get_option( $option_name ) {
	
	// setup return variable
	$option_value = '';	
	
	
	try {
		
		// get default option values
		$defaults = bmg_get_default_options();
		
		// get the requested option
		switch( $option_name ) {
			case 'bmg_table_row_limit':
				// reward page id
				$option_value = (get_option('bmg_table_row_limit')) ? get_option('bmg_table_row_limit') : $defaults['bmg_table_row_limit'];
				break;
			case 'bmg_forms_admin_email':
				// reward page id
				$option_value = (get_option('bmg_forms_admin_email')) ? get_option('bmg_forms_admin_email') : $defaults['bmg_forms_admin_email'];
				break;
		}
		
	} catch( Exception $e) {
		
		// php error
		
	}
	
	// return option value or it's default
	return $option_value;
	
}

// 6.19
// hint: returns default option values as an associative array
function bmg_get_default_options() {
	
	$defaults = array();
	
	try {
		
		
		
		
		// setup defaults array
		$defaults = array(
			'bmg_forms_admin_email' => '',
			'bmg_table_row_limit'=>10,
		);
	
	} catch( Exception $e) {
		
		// php error
		
	}
	
	// return defaults
	return $defaults;
	
	
}





/* 8. Admin Pages */

/* 8.1 Plugin Forms page */
function bmg_forms_page_markup() {

	//Double check user capabilities
	if( !current_user_can('manage_options')) {
		return;
	}

	include( plugin_dir_path( __FILE__ ) . './templates/admin/forms-page.php');

}

/* 8.2 Submissions page */
function bmg_submissions_markup() {
	//Double check user capabilities
	if( !current_user_can('manage_options')) {
		return;
	}

	include( plugin_dir_path( __FILE__ ) . './templates/admin/submissions.php');
}

/* 8.2.1 Submission detail page */

function bmg_submission_detail_markup() {
	//Double check user capabilities
	if( !current_user_can('manage_options')) {
		return;
	}

	include( plugin_dir_path( __FILE__ ) . './templates/admin/submission-detail.php');
}

/* 8.3 Settings page */
function bmg_settings() {
	
	$options = bmg_get_current_options();

	if( !current_user_can('manage_options')) {
		return;
	}

	include( plugin_dir_path( __FILE__ ) . './templates/admin/settings.php');
			
}

/* 8.4 New Form page */

function bmg_new_form_markup() {
	//Double check user capabilities
	if( !current_user_can('manage_options')) {
		return;
	}

	include( plugin_dir_path( __FILE__ ) . './templates/admin/new-form.php');
}




// 9.1
// hint: registers all our plugin options
function bmg_register_options() {
		
	// get plugin options settings
	
	$options = bmg_get_options_settings();
	
	// loop over settings
	foreach( $options['settings'] as $setting ):
	
		// register this setting
		register_setting($options['group'], $setting);
	
	endforeach;
	
}


?>