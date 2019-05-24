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
		toggle boolean DEFAULT 0,
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
	$table_name1 = $wpdb->prefix . 'bmg_forms_meta';
	$form_name = $_POST['formname'];
	$form_data = json_decode(stripcslashes($_POST['formdata']));
	$col = [];
	$val = [];
	if(isset($form_name) && isset($form_data)) {
		$wpdb->insert( 
			$table_name, 
			array( 
				'form_name' => $form_name, 
			),
			array('%s') 
		);
		$form_id = $wpdb->insert_id;

		$fields = count($form_data);
		
		for($i = 0; $i < $fields; $i++){
			$counter = 0;
			foreach ($form_data[$i] as $field_name => $value) {
				// echo $field_name . " " . $value . " | ";
			
				/*if($field_name == "values"){
					$table_data["sub_values"] = serialize($value);
				} else {
					
					$table_data[$field_name] =  $value;	
				}*/
				/* Text Field */	
				if($value === "text" && $field_name == "type") {
					$field_type = "text";
					$subtype = "";
					$required = false;
					$label = "";
					$description = "";
					$placeholder = "";
					$class = "";
					$name = "";
					$default_value = "";
					$access = false;
					$maxlength = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "subtype") {
								$subtype = $value;
							}
							if($field_name == "required"){
								$required = $value;
							}
							if($field_name == "label") {
								$label = $value;
							}
							if($field_name == "description"){
								$description = $value;
							}
							if($field_name == "placeholder"){
								$placeholder = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "name"){
								$name = $value;
							}
							if($field_name == "value"){
								$default_value = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "maxlength"){
								$maxlength = $value;
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,required,label,description,placeholder,classname,name,access,maxlength,value, subtype) VALUES (%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s,%s)",$form_id, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $maxlength, $default_value, $subtype);
    				$wpdb->query($sql);	
					
				}



				/* Text Area */	
				if($value === "textarea" && $field_name == "type") {
					$field_type = "textarea";
					$subtype = "";
					$required = false;
					$label = "";
					$description = "";
					$placeholder = "";
					$class = "";
					$name = "";
					$default_value = "";
					$access = false;
					$maxlength = NULL;
					$rows = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "subtype") {
								$subtype = $value;
							}
							if($field_name == "required"){
								$required = $value;
							}
							if($field_name == "label") {
								$label = $value;
							}
							if($field_name == "description"){
								$description = $value;
							}
							if($field_name == "placeholder"){
								$placeholder = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "name"){
								$name = $value;
							}
							if($field_name == "value"){
								$default_value = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "maxlength"){
								$maxlength = $value;
							}
							if($field_name == "rows") {
								$rows = $value;	
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,required,label,description,placeholder,classname,name,access,maxlength,value, subtype, rows) VALUES (%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s,%s,%d)",$form_id, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $maxlength, $default_value, $subtype, $rows);
    				$wpdb->query($sql);	
					
				}


				/* Number Field */	
				if($value === "number" && $field_name == "type") {
					$field_type = "number";
					$required = false;
					$label = "";
					$description = "";
					$placeholder = "";
					$class = "";
					$name = "";
					$default_value = "";
					$access = false;
					$min = NULL;
					$max = NULL;
					$step = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							
							if($field_name == "required"){
								$required = $value;
							}
							if($field_name == "label") {
								$label = $value;
							}
							if($field_name == "description"){
								$description = $value;
							}
							if($field_name == "placeholder"){
								$placeholder = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "name"){
								$name = $value;
							}
							if($field_name == "value"){
								$default_value = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "min"){
								$min = $value;
							}
							if($field_name == "max") {
								$max = $value;	
							}
							if($field_name == "step") {
								$step = $value;	
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,required,label,description,placeholder,classname,name,access,value,min,max,step) VALUES (%d,%s,%d,%s,%s,%s,%s,%s,%d,%s,%d,%d,%d)",$form_id, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $default_value, $min, $max, $step);
    				$wpdb->query($sql);	
				
				}


				/* Select */	
				if($value === "select" && $field_name == "type") {
					$field_type = "select";
					$required = false;
					$label = "";
					$description = "";
					$placeholder = "";
					$class = "";
					$name = "";
					$access = false;
					$multiple = false;
					$options = [];
						foreach ($form_data[$i] as $field_name => $value) {
							
							if($field_name == "required"){
								$required = $value;
							}
							if($field_name == "label") {
								$label = $value;
							}
							if($field_name == "description"){
								$description = $value;
							}
							if($field_name == "placeholder"){
								$placeholder = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "name"){
								$name = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "multiple"){
								$multiple = $value;
							}
							if($field_name == "values") {
								$options = serialize($value);	
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,required,label,description,placeholder,classname,name,access,multiple,sub_values) VALUES (%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s)",$form_id, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $multiple, $options);
    				$wpdb->query($sql);	
					
				}


				/* Radio Group */	
				if($value === "radio-group" && $field_name == "type") {
					$field_type = "radio-group";
					$required = false;
					$label = "";
					$description = "";
					$inline = false;
					$class = "";
					$name = "";
					$access = false;
					$other = true;
					$options = [];
						foreach ($form_data[$i] as $field_name => $value) {
							
							if($field_name == "required"){
								$required = $value;
							}
							if($field_name == "label") {
								$label = $value;
							}
							if($field_name == "description"){
								$description = $value;
							}
							if($field_name == "inline"){
								$inline = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "name"){
								$name = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "other"){
								$other = $value;
							}
							if($field_name == "values") {
								$options = serialize($value);	
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,required,label,description,inline,classname,name,access,other,sub_values) VALUES (%d,%s,%d,%s,%s,%d,%s,%s,%d,%d,%s)",$form_id, $field_type, $required, $label, $description, $inline, $class, $name, $access, $other, $options);
    				$wpdb->query($sql);	
					
				}



				/* Checkbox Group */	
				if($value === "checkbox-group" && $field_name == "type") {
					$field_type = "checkbox-group";
					$required = false;
					$label = "";
					$description = "";
					$toggle = false;
					$inline = false;
					$class = "";
					$name = "";
					$access = false;
					$other = true;
					$options = [];
						foreach ($form_data[$i] as $field_name => $value) {
							
							if($field_name == "required"){
								$required = $value;
							}
							if($field_name == "label") {
								$label = $value;
							}
							if($field_name == "description"){
								$description = $value;
							}
							if($field_name == "toggle"){
								$toggle = $value;
							}
							if($field_name == "inline"){
								$inline = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "name"){
								$name = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "other"){
								$other = $value;
							}
							if($field_name == "values") {
								$options = serialize($value);	
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,required,label,description,toggle,inline,classname,name,access,other,sub_values) VALUES (%d,%s,%d,%s,%s,%d,%d,%s,%s,%d,%d,%s)",$form_id, $field_type, $required, $label, $description, $toggle, $inline, $class, $name, $access, $other, $options);
    				$wpdb->query($sql);	
					
				}


				/* Hidden Input */	
				if($value === "hidden" && $field_name === "type") {
					$field_type = "hidden";
					$name = "";
					$default_value = "";
					$access = false;
						foreach ($form_data[$i] as $field_name => $value) {
							
							if($field_name == "name"){
								$name = $value;
							}
							if($field_name == "value"){
								$default_value = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,name,access,value) VALUES (%d,%s,%s,%d,%s)",$form_id, $field_type, $name, $access, $default_value);
    				$wpdb->query($sql);	
				
				}



				/* Date Field */	
				if($value === "date" && $field_name == "type") {
					$field_type = "date";
					$required = false;
					$label = "";
					$description = "";
					$placeholder = "";
					$class = "";
					$name = "";
					$access = false;
					$default_value = "";
						foreach ($form_data[$i] as $field_name => $value) {
							
							if($field_name == "required"){
								$required = $value;
							}
							if($field_name == "label") {
								$label = $value;
							}
							if($field_name == "description"){
								$description = $value;
							}
							if($field_name == "placeholder"){
								$placeholder = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "name"){
								$name = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "value"){
								$default_value = $value;
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,required,label,description,placeholder,classname,name,access,value) VALUES (%d,%s,%d,%s,%s,%s,%s,%s,%d,%s)",$form_id, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $default_value);
    				$wpdb->query($sql);	
					
				}



				/* File Upload */	
				if($value === "file" && $field_name == "type") {
					$field_type = "file";
					$required = false;
					$label = "";
					$description = "";
					$placeholder = "";
					$class = "";
					$name = "";
					$access = false;
					$subtype = "";
					$multiple = false;
						foreach ($form_data[$i] as $field_name => $value) {
							
							if($field_name == "required"){
								$required = $value;
							}
							if($field_name == "label") {
								$label = $value;
							}
							if($field_name == "description"){
								$description = $value;
							}
							if($field_name == "placeholder"){
								$placeholder = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "name"){
								$name = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "subtype"){
								$subtype = $value;
							}
							if($multiple == "multiple"){
								$multiple = $value;
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,required,label,description,placeholder,classname,name,access,subtype,multiple) VALUES (%d,%s,%d,%s,%s,%s,%s,%s,%d,%s,%d)",$form_id, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $subtype, $multiple);
    				$wpdb->query($sql);	
				
				}



				/* Autocomple select input */	
				if($value === "autocomplete" && $field_name == "type") {
					$field_type = "autocomplete";
					$required = false;
					$label = "";
					$description = "";
					$placeholder = "";
					$class = "";
					$name = "";
					$access = false;
					$requirevalidoption = false;
					$options = [];
						foreach ($form_data[$i] as $field_name => $value) {
							
							if($field_name == "required"){
								$required = $value;
							}
							if($field_name == "label") {
								$label = $value;
							}
							if($field_name == "description"){
								$description = $value;
							}
							if($field_name == "placeholder"){
								$placeholder = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "name"){
								$name = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "requireValidOption"){
								$requirevalidoption = $value;
							}
							if($field_name == "values") {
								$options = serialize($value);	
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,required,label,description,placeholder,classname,name,access,requirevalidoption,sub_values) VALUES (%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s)",$form_id, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $requirevalidoption, $options);
    				$wpdb->query($sql);	
					
				}



				/* Header Tag Input */	
				if($value === "header" && $field_name === "type") {
					$field_type = "header";
					$subtype = "";
					$label = "";
					$class = "";
					$access = false;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "subtype"){
								$subtype = $value;
							}
							if($field_name == "label"){
								$label = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,subtype,label,classname,access) VALUES (%d,%s,%s,%s,%s,%d)",$form_id, $field_type, $subtype, $label, $class, $access);
    				$wpdb->query($sql);	
					
				}


				/* Paragraph Tag Input */	
				if($value === "paragraph" && $field_name === "type") {
					$field_type = "paragraph";
					$subtype = "";
					$label = "";
					$class = "";
					$access = false;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "subtype"){
								$subtype = $value;
							}
							if($field_name == "label"){
								$label = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,subtype,label,classname,access) VALUES (%d,%s,%s,%s,%s,%d)",$form_id, $field_type, $subtype, $label, $class, $access);
    				$wpdb->query($sql);	
					
				}



				/* Button */	
				if($value === "button" && $field_name === "type") {
					$field_type = "button";
					$subtype = "";
					$label = "";
					$class = "";
					$name = "";
					$default_value = "";
					$access = false;
					$style = "";
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "subtype") {
								$subtype = $value;
							}
							if($field_name == "label") {
								$label = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "name"){
								$name = $value;
							}
							if($field_name == "value"){
								$default_value = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "style"){
								$style = $value;
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,type,label,classname,name,access,style,value,subtype) VALUES (%d,%s,%s,%s,%s,%d,%s,%s,%s)",$form_id, $field_type, $label, $class, $name, $access, $style, $default_value, $subtype);
    				$wpdb->query($sql);	
					
				}

				echo "form successfully created";				


			}
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