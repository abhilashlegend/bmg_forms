<?php
/**
 * @package Bmg Forms
 */
/*
Plugin Name: Bmg Forms
Description: add and manage forms on your site
Version: 1.0.2
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
		2.1 - bmg forms shortcode 
	

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
add_action('admin_init', 'bmg_forms_register_options');

// 1.8
// generate form 
add_action('wp_ajax_bmg_generate_form','bmg_generate_form'); //admin user

// 1.9
// update form 
add_action('wp_ajax_bmg_forms_update_form','bmg_forms_update_form'); //admin user


//1.10
// Delete Field and Drop column
add_action('wp_ajax_bmg_forms_delete_field','bmg_forms_delete_field'); //admin user

function boot_session() {
  session_start();
}
add_action('init', 'boot_session');

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
	add_shortcode('bmg_forms','bmg_forms_shortcode');
}

// 2.1 
// hint: contact us shortcode

function bmg_forms_shortcode($args, $content="") {
	global $wpdb;
	$form_id = $args['id'];
	$output = '';
	$error_message = [];
	$aria_state = [];
	$form_fields = [];
	$field_id = [];
	$success = false;
	$form_meta_table = $wpdb->prefix . 'bmg_forms_meta';
	$captcha = bmg_forms_create_captcha();
	$form_name = getformname($form_id);
	$dir = "wp-content/uploads/bmg-forms";
	$image_path = null;

	if(isset($form_id)) {
		$result = $wpdb->get_results("SELECT * FROM $form_meta_table WHERE form_id=$form_id ORDER BY field_order ASC");

		$form_items = count($result);

		if($form_items == 0) {
			$output = "<div> No form to display</div>";
		}

		$output .= '<div role="form">
					<form method="post" action="" enctype="multipart/form-data"  novalidate>';

		for($i = 0; $i < $form_items; $i++){

			// Header tags
			if($result[$i]->type == "header") {
				$output .= '<'. $result[$i]->subtype . ' class="' . $result[$i]->classname . '">' . $result[$i]->label . '</' . $result[$i]->subtype . '>';
			}

			// paragraph
			if($result[$i]->type == "paragraph") {
				$output .= '<'. $result[$i]->subtype . ' class="' . $result[$i]->classname . '">' . $result[$i]->label . '</' . $result[$i]->subtype . '>';
			}

			// Text Field 
			if($result[$i]->type == "text") {
				if($result[$i]->required) {
					$field_required = 'true';
					$rspan = '<span aria-label="required">*</span>';
					if(empty(trim(esc_attr($_POST[$result[$i]->name])))){
						$error_message[$result[$i]->name] = "Please enter " . $result[$i]->label;
					}
				} else {
					$field_required = 'false';
					$rspan = '';
				}
				if($result[$i]->subtype == "email" && isset($_POST[$result[$i]->name])){
					if (!filter_var($_POST[$result[$i]->name], FILTER_VALIDATE_EMAIL) && !empty($_POST[$result[$i]->name])) {
						$error_message[$result[$i]->name] = 'Please enter a valid email address';
					}	
				}
				$form_fields[] = $result[$i]->name;
				$field_id[$result[$i]->name] = $result[$i]->id;
				if($result[$i]->maxlength > 0) {
					$field_max_length = $maxlength;
				} else {
					$field_max_length = '';
				}
				if(isset($_POST[$result[$i]->name])){
					$field_value = $_POST[$result[$i]->name];
				} else {
					$field_value = $result[$i]->value;
				}
				$output .= '<div class="form-group">';
				$output .=	'<label for="' . $result[$i]->name . '">'. $rspan . ' ' . $result[$i]->label . '</label>';
				$output .=	'<input type="' . $result[$i]->subtype . '" name="' . $result[$i]->name . '" id="' . $result[$i]->name . '" aria-required="' . $field_required . '" aria-invalid="" aria-describedby="' . $result[$i]->name . '-error" placeholder="' . $result[$i]->placeholder . '" value="' . $field_value . '" class="' . $result[$i]->classname . '" maxlength="' . $field_max_length . '" />';
				$output .= '<small id="' . $result[$i]->name . '-help" class="form-text text-muted">' .  $result[$i]->description . '</small>';
				$output .= '</div>';
			}


			// Number input
			if($result[$i]->type == "number") {
				if($result[$i]->required) {
					$field_required = 'true';
					$rspan = '<span aria-label="required">*</span>';
					if(empty(trim(esc_attr($_POST[$result[$i]->name])))){
						$error_message[$result[$i]->name] = "Please enter " . $result[$i]->label;
					}
				} else {
					$field_required = 'false';
					$rspan = '';
				}
				
				
				if($result[$i]->maxlength > 0) {
					$field_max_length = $maxlength;
				} else {
					$field_max_length = '';
				}
				if(isset($_POST[$result[$i]->name])){
					$field_value = $_POST[$result[$i]->name];
				} else {
					$field_value = $result[$i]->value;
				}
				$form_fields[] = $result[$i]->name;
				$field_id[$result[$i]->name] = $result[$i]->id;
				$output .= '<div class="form-group">';
				$output .=	'<label for="' . $result[$i]->name . '">'. $rspan . ' ' . $result[$i]->label . '</label>';
				$output .=	'<input type="number" name="' . $result[$i]->name . '" id="' . $result[$i]->name . '" aria-required="' . $field_required . '" aria-invalid="" aria-describedby="' . $result[$i]->name . '-error" placeholder="' . $result[$i]->placeholder . '" value="' . $field_value . '" class="' . $result[$i]->classname . '" maxlength="' . $field_max_length . '" min="' . $result[$i]->min . '" max="' . $result[$i]->max . '" step="' . $result[$i]->step . '" />';
				$output .= '<small id="' . $result[$i]->name . '-help" class="form-text text-muted">' .  $result[$i]->description . '</small>';
				$output .= '</div>';
			}

			// Textarea
			if($result[$i]->type == "textarea") {
				if($result[$i]->required) {
					$field_required = 'true';
					$rspan = '<span aria-label="required">*</span>';
					if(empty(trim(esc_attr($_POST[$result[$i]->name])))){
						$error_message[$result[$i]->name] = "Please enter " . $result[$i]->label;
					}
				} else {
					$field_required = 'false';
					$rspan = '';
				}
				if(isset($_POST[$result[$i]->name])){
					$field_value = $_POST[$result[$i]->name];
				} else {
					$field_value = $result[$i]->value;
				}
				
				$form_fields[] = $result[$i]->name;
				$field_id[$result[$i]->name] = $result[$i]->id;
				if($result[$i]->subtype == "tinymce") {

					$output .= '<div class="form-group">';
					$output .=	'<label for="' . $result[$i]->name . '">'. $rspan . ' ' . $result[$i]->label . '</label>';

					$output .= '<textarea name="' . $result[$i]->name . '" cols="40" rows="' . $result[$i]->rows . '" class="tinymce-enabled ' . $result[$i]->classname . '" id="' . $result[$i]->name . '" aria-required="' . $field_required . '" aria-invalid="" aria-describedby="' . $result[$i]->name . '-error" placeholder="' . $result[$i]->placeholder . '">' . $field_value . '</textarea>';
					$output .= '<small id="' . $result[$i]->name . '-help" class="form-text text-muted">' .  $result[$i]->description . '</small>';
					$output .= '</div>';
				} else { 
					$output .= '<div class="form-group">';
					$output .=	'<label for="' . $result[$i]->name . '">'. $rspan . ' ' . $result[$i]->label . '</label>';
					$output .= '<textarea name="' . $result[$i]->name . '" cols="40" rows="' . $result[$i]->rows . '" class="' . $result[$i]->classname . '" id="' . $result[$i]->name . '" aria-required="' . $field_required . '" aria-invalid="" aria-describedby="' . $result[$i]->name . '-error" placeholder="' . $result[$i]->placeholder . '">' . $field_value . '</textarea>';
					$output .= '<small id="' . $result[$i]->name . '-help" class="form-text text-muted">' .  $result[$i]->description . '</small>';
					$output .= '</div>';

				}
					
			}


			// Select field
			if($result[$i]->type == "select") {
				if($result[$i]->required) {
					$field_required = 'true';
					$rspan = '<span aria-label="required">*</span>';
					if(empty(trim(esc_attr($_POST[$result[$i]->name])))){
						$error_message[$result[$i]->name] = "Please enter " . $result[$i]->label;
					}
				} else {
					$field_required = 'false';
					$rspan = '';
				}

				$form_fields[] = $result[$i]->name;
				$option_values = unserialize($result[$i]->sub_values);
				$option_items = count($option_values);
				$field_id[$result[$i]->name] = $result[$i]->id;
				$output .= '<div class="form-group">';
				$output .=	'<label for="' . $result[$i]->name . '">'. $rspan . ' ' . $result[$i]->label . '</label>';
				$output .= '<select name="' . $result[$i]->name . '" id="' . $result[$i]->name . '" aria-required="' . $field_required . '" aria-invalid="false" class="' . $result[$i]->classname . '">';
					for($j = 0; $j < $option_items; $j++) {
						if(isset($_POST[$result[$i]->name]) && $_POST[$result[$i]->name] == $option_values[$j]->value){
							$option_selected =  'selected="true"'; 
						} 
						else if($option_values[$j]->selected == 1){
							$option_selected =  'selected="true"'; 
						} else {
							$option_selected =  ''; 
						}
						
						$output .= '<option value="' . $option_values[$j]->value . '" ' . $option_selected . '>' . $option_values[$j]->label . '</option>';
					}
				$output .= '</select>';
				$output .= '<small id="' . $result[$i]->name . '-help" class="form-text text-muted">' .  $result[$i]->description . '</small>';
				$output .= '</div>';	
			}	


			// Radio Group
			if($result[$i]->type == "radio-group") {
				if($result[$i]->required) {
					$field_required = 'true';
					$rspan = '<span aria-label="required">*</span>';
					if(empty(trim(esc_attr($_POST[$result[$i]->name])))){
						$error_message[$result[$i]->name] = "Please enter " . $result[$i]->label;
					}
				} else {
					$field_required = 'false';
					$rspan = '';
				}
				if($result[$i]->inline) {
					$form_inline = 'form-check-inline';
				}
				else {
					$form_inline = '';
				}
				$form_fields[] = $result[$i]->name;
				$field_id[$result[$i]->name] = $result[$i]->id;
				$option_values = unserialize($result[$i]->sub_values);
				$option_items = count($option_values);
				$output .= '<div class="form-group">';
				$output .=	'<label for="' . $result[$i]->name . '">'. $rspan . ' ' . $result[$i]->label . '</label>';
				$output .= '<small id="' . $result[$i]->name . '-help" class="form-text text-muted">' .  $result[$i]->description . '</small>';
				for($j = 0; $j < $option_items; $j++) {
						if($option_values[$j]->selected == 1 && empty($_POST[$result[$i]->name])){
							$option_selected =  'checked'; 
						} else {
							$option_selected =  ''; 
						}
						$output .= '<div class="form-check ' . $form_inline . '">';
						$output .= '<input class="form-check-input ' . $result[$i]->classname . '" type="radio" name="' . $result[$i]->name . '" id="' . $result[$i]->name . $j . '" value="' . $option_values[$j]->value . '" ' . $option_selected;

									 if ($option_values[$j]->value == $_POST[$result[$i]->name]){
									 	$output .=  'checked'; 
									
									 } 
								
						$output .= '/>';	

  						$output .= '<label class="form-check-label" for="' . $result[$i]->name . $j . '">';
    					$output .= $option_values[$j]->label;
  						$output .= '</label>';
  						$output .= '</div>';	
				}
				
				$output .= '</div>';	
			}	


			// Checkbox Group
			if($result[$i]->type == "checkbox-group") {
				if($result[$i]->required) {
					$field_required = 'true';
					$rspan = '<span aria-label="required">*</span>';
					if(empty(trim(esc_attr($_POST[$result[$i]->name])))){
						$error_message[$result[$i]->name] = "Please enter " . $result[$i]->label;
					}
				} else {
					$field_required = 'false';
					$rspan = '';
				}
				if($result[$i]->inline) {
					$form_inline = 'form-check-inline';
				}
				else {
					$form_inline = '';
				}
				$form_fields[] = $result[$i]->name;
				$field_id[$result[$i]->name] = $result[$i]->id;
				$option_values = unserialize($result[$i]->sub_values);
				$option_items = count($option_values);
				$output .= '<div class="form-group">';
				$output .=	'<label for="' . $result[$i]->name . '">'. $rspan . ' ' . $result[$i]->label . '</label>';
				$output .= '<small id="' . $result[$i]->name . '-help" class="form-text text-muted">' .  $result[$i]->description . '</small>';
				
				for($j = 0; $j < $option_items; $j++) {

						if($option_values[$j]->selected == 1 && empty($_POST[$result[$i]->name])){
							$option_selected =  'checked'; 
						}
						 else {
							$option_selected =  ''; 
						}
						
						$output .= '<div class="form-check ' . $form_inline . '">';
						$output .= '<input class="form-check-input ' . $result[$i]->classname . '" type="checkbox" name="' . $result[$i]->name . '[]" id="' . $result[$i]->name . $j . '" value="' . $option_values[$j]->value . '" ' .  $option_selected; 
							if(is_array($_POST[$result[$i]->name])) { 
									 if (in_array($option_values[$j]->value, $_POST[$result[$i]->name])){
									 	$output .=  'checked'; 
									
									 } 
									}
						$output .= '/>';
  						$output .= '<label class="form-check-label" for="' . $result[$i]->name . $j . '">';
    					$output .= $option_values[$j]->label;
  						$output .= '</label>';
  						$output .= '</div>';			
				}
				
				$output .= '</div>';	
			}	

			// Date Field
			if($result[$i]->type == "date") {
				if($result[$i]->required) {
					$field_required = 'true';
					$rspan = '<span aria-label="required">*</span>';
					if(empty(trim(esc_attr($_POST[$result[$i]->name])))){
						$error_message[$result[$i]->name] = "Please enter " . $result[$i]->label;
					}
				} else {
					$field_required = 'false';
					$rspan = '';
				}
				if(isset($_POST[$result[$i]->name])){
					$field_value = $_POST[$result[$i]->name];
				} else {
					$field_value = $result[$i]->value;
				}
				$form_fields[] = $result[$i]->name;
				$field_id[$result[$i]->name] = $result[$i]->id;
				$output .= '<div class="form-group">';
				$output .=	'<label for="' . $result[$i]->name . '">'. $rspan . ' ' . $result[$i]->label . '</label>';
				$output .=	'<input type="date" name="' . $result[$i]->name . '" id="' . $result[$i]->name . '" aria-required="' . $field_required . '" aria-invalid="" aria-describedby="' . $result[$i]->name . '-error" placeholder="' . $result[$i]->placeholder . '" value="' . $field_value . '" class="' . $result[$i]->classname . '" maxlength="' . $field_max_length . '" />';
				$output .= '<small id="' . $result[$i]->name . '-help" class="form-text text-muted">' .  $result[$i]->description . '</small>';	

				$output .= '</div>';
			}

			// File Upload field
			if($result[$i]->type == "file") {
				if($result[$i]->required) {
					$field_required = 'true';
					$rspan = '<span aria-label="required">*</span>';
					if(empty(trim(esc_attr($_FILES[$result[$i]->name])))){
						$error_message[$result[$i]->name] = "Please upload " . $result[$i]->label;
					}
				} else {
					$field_required = 'false';
					$rspan = '';
				}
				if($result[$i]->multiple) {
					$field_multiple = 'multiple="true"';
					$field_arr = '[]';
				} else {
					$field_multiple = '';
					$field_arr = '';
				}

				if(isset($_FILES[$result[$i]->name]) && $_FILES[$result[$i]->name]['size'] > 0 && count($error_message) == 0)
				{
					$path = $_FILES[$result[$i]->name]['name'];	
					$ext = pathinfo($path, PATHINFO_EXTENSION);
					$file_name = uniqid();
					$target_file 	= $dir . '/' . $file_name  . '.' . $ext;
					move_uploaded_file($_FILES[$result[$i]->name]["tmp_name"], $target_file);
					$uploads = wp_upload_dir();
						$image_path = 	$uploads['baseurl'] . "/bmg-forms/" . $file_name . '.' . $ext;
					$_POST[$result[$i]->name] = $image_path;
				}
				$form_fields[] = $result[$i]->name;
				$field_id[$result[$i]->name] = $result[$i]->id;
				$output .= '<div class="form-group">';
				$output .=	'<label for="' . $result[$i]->name . '">'. $rspan . ' ' . $result[$i]->label . '</label>';
				$output .= '<input placeholder="' . $result[$i]->placeholder . '" class="" name="' . $result[$i]->name . $field_arr . '" ' . $field_multiple . ' type="file" id="' . $result[$i]->name . '" title="' . $result[$i]->description . '">';
				$output .= '<small id="' . $result[$i]->name . '-help" class="form-text text-muted">' .  $result[$i]->description . '</small>';	
				$output .= '</div>';	
			}


			// Hidden Input
			if($result[$i]->type == "hidden") {
				$output .=	'<input type="hidden" name="' . $result[$i]->name . '" id="' . $result[$i]->name . '"  value="' . $result[$i]->value . '"  />';
				$form_fields[] = $result[$i]->name;
				$field_id[$result[$i]->name] = $result[$i]->id;
			}


			// Button
			if($result[$i]->type == "button") {
				if($result[$i]->subtype == "submit"){
					$output .= '<div class="form-group">';
					$output .= '<label for="bmg_security"><span aria-label="required">*</span> Security Code</label><div class="clearfix"></div>';
					$output .= '<input type="text" class="form-control bmg-captcha-field" id="bmg_security" name="bmg_security" placeholder="Enter code" required aria-required="true" aria-describedby="bmg_security-error" />';
					
					$output .= $captcha['image'];
					$output .= '<div class="clearfix"></div>';
					$output .= '</div>';	
					$output .= '<input type="hidden" name="bmg_code" id="bmg_code" value="';
				$output .= $captcha['word']; 
				$output .= '" />';
				if(empty(trim(esc_attr($_POST['bmg_security'])))){
						$error_message['bmg_security'] = "Please enter security code";
					}
				}
				if(!empty(trim(esc_attr($_POST['bmg_security']))) && $_POST['bmg_security'] != $_POST['bmg_code']) {
					$error_message['bmg_security'] = "Security code does not match";
				}	
				$output .= '<div class="form-group">';
				$output .= '<input type="' . $result[$i]->subtype . '" name="' . $result[$i]->name . '" value="' . $result[$i]->label . '" class="' . $result[$i]->classname . '">';	
				$output .= '</div>';	
			}

		}


		$output .= '</form>
					</div>';

			// Display error messages		
			if(count($error_message) && count($_POST)) {
			
			$error_output = '<div class="info-box warning bmg-input-error" role="alert">
			<h3><strong>Error submitting form:</strong></h3>
			<ul class="bmg-list-errors" title="The following errors have been reported">'; 
			foreach($error_message as $key => $error) {
				$error_output .= '<li><a href="#' . $key .'" id="' . $key . '-error">'  . $error . "</a></li>";
			}
			$error_output .= '</ul></div>';
			echo $error_output;
			} else {
				$error_output = '';	
				echo $error_output;
			}		

			// If there is no error store data and send mail. 	
			if(count($_POST) && count($error_message) == 0) {
			
				$form_table =  $wpdb->prefix . 'bmg_forms_' . $form_name . $form_id;
				
				$sql = "SHOW COLUMNS FROM $form_table";
				$result = $wpdb->get_results($sql);
				$count = 0;
				$fields = [];
				$table_data = [];
				$replace_logic_arr = [];
				$default_body = '<table>';

				for($i = 0; $i < count($form_fields); $i++) { 
					$fields[$count] = $result[$count + 1]->Field;
					$field_name  = $fields[$count];
					$table_data[$field_name] = $_POST[$field_name];
					$replace_logic_arr['['.$field_name.']'] = $_POST[$field_name];
					 
					$default_body .= '<tr><td>' . $field_name . '<td><td>' . $_POST[$field_name] . '</td></tr>'; 
					$count++;
				}

				$default_body .= '</table>';
	
				$success = $wpdb->insert(
								$form_table, $table_data
						   );

				$mail_table_name = $wpdb->prefix . 'bmg_forms_mails';
	 			$config = $wpdb->get_results("SELECT * FROM $mail_table_name WHERE form_id = $form_id");

	 			if(count($config) > 0){
	 				$toadmin = $config[0]->to_user;
	 				$rsubject = $config[0]->subject;
	 				$mail_subject = str_replace(array_keys($replace_logic_arr), array_values($replace_logic_arr), $rsubject);
	 				$headers = array('Content-Type: text/html; charset=UTF-8');
	 				$rbody = $config[0]->message_body;
	 				if($config[0]->from_user){
	 					$body = 'From ' . $config[0]->from_user;
	 				}
	 				
	 				$body .= str_replace(array_keys($replace_logic_arr), array_values($replace_logic_arr), $rbody);
	 				$admin_mail = wp_mail( $toadmin, $mail_subject, $body, $headers );

	 			} else {
	 				$toadmin = get_option('bmg_forms_admin_email');
					$mail_subject = $form_name . ' Submission';
					$headers = array('Content-Type: text/html; charset=UTF-8');
					$admin_mail = wp_mail( $toadmin, $mail_subject, $default_body, $headers );
	 			}
				
				$_SESSION['form_submit'] = 'true';
			}

			if($success) {
				$success_output = '<div class="bmg-success-box" role="alert"><Strong>Message successfully sent!</strong>
						</div>';
				echo $success_output;
				foreach($form_fields as $field) {
					unset($_POST[$field]);
				}			
			}

	} else {
		$output = "<div> Invalid shortcode </div>";
	}
	
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


		/* Edit Form */
		add_submenu_page (
			null,
			__( 'Edit Form', 'bmg-edit-form'),
			__( 'Edit Form', 'bmg-edit-form'),
			'manage_options',
			'bmg_edit_form',
			'bmg_edit_form_markup'
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


		/* Mail Config page */
		add_submenu_page (
			null,
			__( 'Mail Configuration', 'bmg-mail-config' ),
			__( 'Mail Configuration', 'bmg-mail-config'),
			'manage_options',
			'bmg_mail_config',
			'bmg_mail_config_markup'
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

		wp_register_script('bmg-forms-tinymce-public', plugins_url('js/public/tinymce.min.js', __FILE__), array('jquery'), false, true);

		wp_register_script('bmg-forms-app-public', plugins_url('js/public/bmg-app.js', __FILE__), array('jquery'), false, true);

		wp_enqueue_style('bmg-forms-css-public');
		wp_enqueue_script('bmg-forms-tinymce-public');	
		wp_enqueue_script('bmg-forms-app-public');

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
	$table_name2 = $wpdb->prefix . 'bmg_forms_mails';	

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		form_name varchar(100) NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";
	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS $table_name2 (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		form_id mediumint(9) NOT NULL,
		to_user varchar(100) NOT NULL,
		from_user varchar(100),
		subject varchar(100),
		additional_headers text,
		message_body text,
		PRIMARY KEY  (id),
		FOREIGN KEY  (form_id) REFERENCES  $table_name(id)
	) $charset_collate;";
	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS $table_name1 (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		form_id mediumint(9) NOT NULL,
		field_order mediumint(9),
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
		ON DELETE CASCADE
	) $charset_collate;";
	$wpdb->query($sql);


	$upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/bmg-forms';
    if (! is_dir($upload_dir)) {
       mkdir( $upload_dir, 0700 );
    }

}

// 5.2
// hint: remove all tables on uninstall
function bmg_forms_uninstall_plugin() {

	// remove custom tables
	bmg_forms_remove_plugin_tables();

	//Remove plugin options
	bmg_forms_remove_options();

	//Remove upload folder and files
	bmg_forms_remove_uploads();

}


function bmg_forms_remove_uploads() {
	$upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/bmg-forms';
	$dirPath = $upload_dir;
	if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            rmdir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

function bmg_forms_remove_plugin_tables() {
	//setup return variable
	$tables_removed = false;
	
	global $wpdb;
	try {
				$charset_collate = $wpdb->get_charset_collate();
				$table_name = $wpdb->prefix . 'bmg_forms';
				$table_name1 = $wpdb->prefix . 'bmg_forms_meta';
				$table_name2 = $wpdb->prefix . 'bmg_forms_mails';


				$result = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id");

				if($result) {
					foreach($result as $row) {
						$form_table = $wpdb->prefix . 'bmg_forms_' . $row->form_name . $row->id;
						$delete_table_sql = "DROP TABLE IF EXISTS  $form_table;";
						$tables_removed = $wpdb->query($delete_table_sql);
					}
				}

				

				$sql = "SET FOREIGN_KEY_CHECKS=0;";
				$wpdb->query($sql);

				$sql = "SET FOREIGN_KEY_CHECKS=0;";
				$wpdb->query($sql);
			
				$sql = "DROP TABLE IF EXISTS  $table_name, $table_name1, $table_name2;";
				$tables_removed = $wpdb->query($sql);
		}
		catch (Exception $e) {
			
			$wpdb->show_errors();
		}

	return $tables_removed;	
}


function bmg_forms_update_form() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'bmg_forms';
	$table_name1 = $wpdb->prefix . 'bmg_forms_meta';
	$form_name = $_POST['formname'];
	$form_id = $_POST['formid'];
	$form_data = json_decode(stripcslashes($_POST['formdata']));

	if(isset($form_name) && isset($form_data) && isset($form_id)) {

			$form_table =  $wpdb->prefix . 'bmg_forms_' . $form_name . $form_id;

			$fields = count($form_data);
			$field_order = 0;
			for($i = 0; $i < $fields; $i++){

			$field_order++;
				foreach ($form_data[$i] as $field_name => $value) {

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
						$id = NULL;

						foreach ($form_data[$i] as $field_name => $value) {


							if($field_name == "colname"){
								$col_name = $value;

							}
							if($field_name == "id") {
								$id = $value;
							}
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
								$name = str_replace("-", "_", $name);

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
						if(isset($id)) { 

							$update_result = $wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'required' => $required,
									'label' 	=> $label,
									'description' => $description,
									'placeholder'		 => $placeholder,
									'classname' => $class,
									'name' => $name,
									'access' => $access,
									'maxlength' => $maxlength,
									'value' => $default_value,
									'subtype' => $subtype
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%d','%s','%s','%s','%s','%s','%d','%d','%s','%s'), //data format
				                array('%d') //where format
				        	 );



							$chg_stmt = "ALTER TABLE $form_table CHANGE $col_name $name varchar(100)";
							$wpdb->query($chg_stmt);


					    } else {
					    		

					    		$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,maxlength,value, subtype) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s,%s)",$form_id, $field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $maxlength, $default_value, $subtype);
    							$wpdb->query($sql);	

    							$add_stmt = "ALTER TABLE $form_table ADD $name varchar(100)";
								$wpdb->query($add_stmt);

					    }						

					} /* Text field end */



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
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
							if($field_name == "colname"){
								$col_name = $value;
							}
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' text';
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

						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'required' => $required,
									'label' 	=> $label,
									'description' => $description,
									'placeholder'		 => $placeholder,
									'classname' => $class,
									'name' => $name,
									'access' => $access,
									'maxlength' => $maxlength,
									'value' => $default_value,
									'subtype' => $subtype,
									'rows' => $rows
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%d','%s','%s','%s','%s','%s','%d','%d','%s','%s','%d'), //data format
				                array('%d') //where format
				        	);

				        	$chg_stmt = "ALTER TABLE $form_table CHANGE $col_name $name text";
							$wpdb->query($chg_stmt);

						} else {
								$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,maxlength,value, subtype, rows) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s,%s,%d)",$form_id, $field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $maxlength, $default_value, $subtype, $rows);
    							$wpdb->query($sql);

    							$add_stmt = "ALTER TABLE $form_table ADD $name text";
								$wpdb->query($add_stmt);	
						}
					
					
				} /* ENd of text area */


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
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
							if($field_name == "colname"){
								$col_name = $value;
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
								$name = str_replace("-", "_", $name);
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

						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'required' => $required,
									'label' 	=> $label,
									'description' => $description,
									'placeholder'		 => $placeholder,
									'classname' => $class,
									'name' => $name,
									'access' => $access,
									'value' => $default_value,
									'min' => $min,
									'max' => $max,
									'step' => $step
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%d','%s','%s','%s','%s','%s','%d','%s','%d','%d','%d'), //data format
				                array('%d') //where format
				        	);
				        	$chg_stmt = "ALTER TABLE $form_table CHANGE $col_name $name varchar(30)";
							$wpdb->query($chg_stmt);
						} else {
							$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,value,min,max,step) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%s,%d,%d,%d)",$form_id, $field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $default_value, $min, $max, $step);
    						$wpdb->query($sql);	

    						$add_stmt = "ALTER TABLE $form_table ADD $name varchar(30)";
								$wpdb->query($add_stmt);
						}	
				
				 } /* End of number field */


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
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
							if($field_name == "colname"){
								$col_name = $value;
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' varchar(100)';
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

						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'required' => $required,
									'label' 	=> $label,
									'description' => $description,
									'placeholder'		 => $placeholder,
									'classname' => $class,
									'name' => $name,
									'access' => $access,
									'multiple' => $multiple,
									'sub_values' => $options
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%d','%s','%s','%s','%s','%s','%d','%d','%s'), //data format
				                array('%d') //where format
				        	);	
				        	$chg_stmt = "ALTER TABLE $form_table CHANGE $col_name $name varchar(100)";
							$wpdb->query($chg_stmt);
						} else {
							$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,multiple,sub_values) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s)",$form_id, $field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $multiple, $options);
    						$wpdb->query($sql);		

    						$add_stmt = "ALTER TABLE $form_table ADD $name varchar(100)";
							$wpdb->query($add_stmt);	
						}		 	
					
					
				} /* End of select field */


				/* Radion Group */
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
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
							if($field_name == "colname"){
								$col_name = $value;
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
							if($field_name == "inline"){
								$inline = $value;
							}
							if($field_name == "className"){
								$class = $value;
							}
							if($field_name == "name"){
								$name = $value;
								$name = str_replace("-", "_", $name);
							
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

						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'required' => $required,
									'label' 	=> $label,
									'description' => $description,
									'inline'		 => $inline,
									'classname' => $class,
									'name' => $name,
									'access' => $access,
									'other' => $other,
									'sub_values' => $options
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%d','%s','%s','%s','%s','%s','%d','%d','%s'), //data format
				                array('%d') //where format
				        	);	
				        	$chg_stmt = "ALTER TABLE $form_table CHANGE $col_name $name varchar(100)";
							$wpdb->query($chg_stmt);
						} else {
							$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,inline,classname,name,access,other,sub_values) VALUES (%d,%d,%s,%d,%s,%s,%d,%s,%s,%d,%d,%s)",$form_id, $field_order, $field_type, $required, $label, $description, $inline, $class, $name, $access, $other, $options);
    						$wpdb->query($sql);
    						$add_stmt = "ALTER TABLE $form_table ADD $name varchar(100)";
							$wpdb->query($add_stmt);	
						} 	 	
						
					
				} /* End of radio group */


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
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
							if($field_name == "colname"){
								$col_name = $value;
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
								$name = str_replace("-", "_", $name);
							
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

						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'required' => $required,
									'label' 	=> $label,
									'description' => $description,
									'toggle' => $toggle,
									'inline'		 => $inline,
									'classname' => $class,
									'name' => $name,
									'access' => $access,
									'other' => $other,
									'sub_values' => $options
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%d','%s','%s','%d','%d','%s','%s','%d','%d','%s'), //data format
				                array('%d') //where format
				        	);	

				        	$chg_stmt = "ALTER TABLE $form_table CHANGE $col_name $name text";
							$wpdb->query($chg_stmt);
						} else {
							$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id, field_order,type,required,label,description,toggle,inline,classname,name,access,other,sub_values) VALUES (%d,%d,%s,%d,%s,%s,%d,%d,%s,%s,%d,%d,%s)",$form_id, $field_order, $field_type, $required, $label, $description, $toggle, $inline, $class, $name, $access, $other, $options);
    						$wpdb->query($sql);	

    						$add_stmt = "ALTER TABLE $form_table ADD $name varchar(100)";
							$wpdb->query($add_stmt);

						}		 	
					
					
				} /* ENd of checkbox group */

				/* Hidden Input */	
				if($value === "hidden" && $field_name === "type") {
					$field_type = "hidden";
					$name = "";
					$default_value = "";
					$access = false;
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
							if($field_name == "colname"){
								$col_name = $value;
							}
							if($field_name == "name"){
								$name = $value;
								$name = str_replace("-", "_", $name);
							
							}
							if($field_name == "value"){
								$default_value = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
						}

						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'name' => $name,
									'access' => $access,
									'value' => $default_value
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%s','%d','%s'), //data format
				                array('%d') //where format
				        	);	

				        	$chg_stmt = "ALTER TABLE $form_table CHANGE $col_name $name varchar(30)";
							$wpdb->query($chg_stmt);
						} else {
							$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,name,access,value) VALUES (%d,%d,%s,%s,%d,%s)",$form_id, $field_order, $field_type, $name, $access, $default_value);
    						$wpdb->query($sql);	

							$add_stmt = "ALTER TABLE $form_table ADD $name varchar(30)";
							$wpdb->query($add_stmt);
						}		 	
					
				} /* ENd of input hidden */



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
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
							if($field_name == "colname"){
								$col_name = $value;
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
								$name = str_replace("-", "_", $name);
							
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "value"){
								$default_value = $value;
							}
						}	
						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'required' => $required,
									'label' => $label,
									'description' => $description,
									'placeholder' => $placeholder,
									'classname' => $class,
									'name' => $name,
									'access' => $access,
									'value' => $default_value
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%d','%s','%s','%s','%s','%s','%d','%s'), //data format
				                array('%d') //where format
				        	);	

				        	$chg_stmt = "ALTER TABLE $form_table CHANGE $col_name $name varchar(50)";
							$wpdb->query($chg_stmt);
						} else {
							$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,value) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%s)",$form_id, $field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $default_value);
    						$wpdb->query($sql);	

    						$add_stmt = "ALTER TABLE $form_table ADD $name varchar(50)";
							$wpdb->query($add_stmt);
						}	 	
				} /* End of date field */


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
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
							if($field_name == "colname"){
								$col_name = $value;
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
								$name = str_replace("-", "_", $name);
	
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
						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id,
									'field_order' => $field_order, 
									'type' => $field_type,
									'required' => $required,
									'label' => $label,
									'description' => $description,
									'placeholder' => $placeholder,
									'classname' => $class,
									'name' => $name,
									'access' => $access,
									'subtype' => $subtype,
									'multiple' => $multiple
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%d','%s','%s','%s','%s','%s','%d','%s','%d'), //data format
				                array('%d') //where format
				        	);	

				        	$chg_stmt = "ALTER TABLE $form_table CHANGE $col_name $name varchar(250)";
							$wpdb->query($chg_stmt);
						} else {
							$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,subtype,multiple) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%s,%d)",$form_id, $field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $subtype, $multiple);
    						$wpdb->query($sql);	

    						$add_stmt = "ALTER TABLE $form_table ADD $name varchar(250)";
							$wpdb->query($add_stmt);		
						}	 	
					
				
				} /* ENd of file upload */


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
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
							if($field_name == "required"){
								$required = $value;
							}
							if($field_name == "colname"){
								$col_name = $value;
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
								$name = str_replace("-", "_", $name);
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

						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'required' => $required,
									'label' => $label,
									'description' => $description,
									'placeholder' => $placeholder,
									'classname' => $class,
									'name' => $name,
									'access' => $access,
									'requirevalidoption' => $requirevalidoption,
									'sub_values' => $options
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%d','%s','%s','%s','%s','%s','%d','%d','%s'), //data format
				                array('%d') //where format
				        	);	

				        	$chg_stmt = "ALTER TABLE $form_table CHANGE $col_name $name varchar(100)";
							$wpdb->query($chg_stmt);
						} else {
							$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id, field_order,type,required,label,description,placeholder,classname,name,access,requirevalidoption,sub_values) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s)",$form_id, $field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $requirevalidoption, $options);
    						$wpdb->query($sql);	

    						$add_stmt = "ALTER TABLE $form_table ADD $name varchar(100)";
							$wpdb->query($add_stmt);
						}		 	
					
					
				} /* End of Autocomplete */


				/* Header Tag Input */	
				if($value === "header" && $field_name === "type") {
					$field_type = "header";
					$subtype = "";
					$label = "";
					$class = "";
					$access = false;
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
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

						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'subtype' => $subtype,
									'label' => $label,
									'classname' => $class,
									'access' => $access
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%s','%s','%s','%d'), //data format
				                array('%d') //where format
				        	);	
						} else {
							$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id, field_order,type,subtype,label,classname,access) VALUES (%d,%d,%s,%s,%s,%s,%d)",$form_id, $field_order, $field_type, $subtype, $label, $class, $access);
    						$wpdb->query($sql);	
						}		 	

				} /* ENd of header */


				/* Paragraph Tag Input */	
				if($value === "paragraph" && $field_name === "type") {
					$field_type = "paragraph";
					$subtype = "";
					$label = "";
					$class = "";
					$access = false;
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
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

						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'subtype' => $subtype,
									'label' => $label,
									'classname' => $class,
									'access' => $access
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%s','%s','%s','%d'), //data format
				                array('%d') //where format
				        	);	
						} else {
							$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,subtype,label,classname,access) VALUES (%d,%d,%s,%s,%s,%s,%d)",$form_id, $field_order, $field_type, $subtype, $label, $class, $access);
    						$wpdb->query($sql);			
						}		 	
					
					
				} /* End of paragraph field */


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
					$id = NULL;
						foreach ($form_data[$i] as $field_name => $value) {
							if($field_name == "id") {
								$id = $value;
							}
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

						if(isset($id)) {
							$wpdb->update(
			                $table_name1, //table
				               array( 
									'form_id' => $form_id, 
									'field_order' => $field_order,
									'type' => $field_type,
									'label' => $label,
									'classname' => $class,
									'name' => $name,
									'access' => $access,
									'style' => $style,
									'value' => $default_value,
									'subtype' => $subtype
								), //data
				                array('id' => $id), //where
				                array('%d','%d','%s','%s','%s','%s','%d','%s','%s','%s'), //data format
				                array('%d') //where format
				        	);	
						} else {
							$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,label,classname,name,access,style,value,subtype) VALUES (%d,%d,%s,%s,%s,%s,%d,%s,%s,%s)",$form_id, $field_order, $field_type, $label, $class, $name, $access, $style, $default_value, $subtype);
    						$wpdb->query($sql);
						}
			
				} /* ENd of button field */



				}
			}

			echo "Form has been updated";			
	}	

		//print_r($form_data);
					  
}

function bmg_forms_delete_field() {
	global $wpdb;
	$table_name1 = $wpdb->prefix . 'bmg_forms_meta';
	$form_name = $_POST['formname'];
	$form_id = $_POST['formid'];
	$field_id = $_POST['fieldid'];
	$field_name = $_POST['fieldname'];
	$table_name = $wpdb->prefix . 'bmg_forms_' . $form_name . $form_id;

	$del_field_sql = "DELETE FROM $table_name1 WHERE id=$field_id";
	$wpdb->query($del_field_sql);

	$del_col_sql = "ALTER TABLE $table_name DROP $field_name;";
	$wpdb->query($del_col_sql);	


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

		$form_table =  $wpdb->prefix . 'bmg_forms_' . $form_name . $form_id;
		$table_fields = [];

		$fields = count($form_data);
		$field_order = 0;
		for($i = 0; $i < $fields; $i++){
			$field_order++;
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' varchar(100)';
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
								 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,maxlength,value, subtype) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s,%s)",$form_id,$field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $maxlength, $default_value, $subtype);
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' text';
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
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,maxlength,value, subtype, rows) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s,%s,%d)",$form_id, $field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $maxlength, $default_value, $subtype, $rows);
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' varchar(30)';
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
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,value,min,max,step) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%s,%d,%d,%d)",$form_id, $field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $default_value, $min, $max, $step);
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' varchar(100)';
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
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,multiple,sub_values) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s)",$form_id, $field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $multiple, $options);
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' varchar(100)';
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
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,inline,classname,name,access,other,sub_values) VALUES (%d,%d,%s,%d,%s,%s,%d,%s,%s,%d,%d,%s)",$form_id, $field_order, $field_type, $required, $label, $description, $inline, $class, $name, $access, $other, $options);
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' text';
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
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,toggle,inline,classname,name,access,other,sub_values) VALUES (%d,%d,%s,%d,%s,%s,%d,%d,%s,%s,%d,%d,%s)",$form_id, $field_order, $field_type, $required, $label, $description, $toggle, $inline, $class, $name, $access, $other, $options);
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' varchar(30)';
							}
							if($field_name == "value"){
								$default_value = $value;
							}
							if($field_name == "access"){
								$access = $value;
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,name,access,value) VALUES (%d,%d,%s,%s,%d,%s)",$form_id, $field_order, $field_type, $name, $access, $default_value);
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' varchar(50)';
							}
							if($field_name == "access"){
								$access = $value;
							}
							if($field_name == "value"){
								$default_value = $value;
							}
						}		 	
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,value) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%s)",$form_id, $field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $default_value);
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' varchar(250)';
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
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,subtype,multiple) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%s,%d)",$form_id,$field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $subtype, $multiple);
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
								$name = str_replace("-", "_", $name);
								$table_fields[] = $name . ' varchar(100)';
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
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,required,label,description,placeholder,classname,name,access,requirevalidoption,sub_values) VALUES (%d,%d,%s,%d,%s,%s,%s,%s,%s,%d,%d,%s)",$form_id,$field_order, $field_type, $required, $label, $description, $placeholder, $class, $name, $access, $requirevalidoption, $options);
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
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,subtype,label,classname,access) VALUES (%d,%d,%s,%s,%s,%s,%d)",$form_id, $field_order, $field_type, $subtype, $label, $class, $access);
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
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,subtype,label,classname,access) VALUES (%d,%d,%s,%s,%s,%s,%d)",$form_id,$field_order, $field_type, $subtype, $label, $class, $access);
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
					$sql = $wpdb->prepare("INSERT INTO $table_name1 (form_id,field_order,type,label,classname,name,access,style,value,subtype) VALUES (%d,%d,%s,%s,%s,%s,%d,%s,%s,%s)",$form_id, $field_order, $field_type, $label, $class, $name, $access, $style, $default_value, $subtype);
    				$wpdb->query($sql);	
					
				}

			}
		}
		
		$table_col_names = implode(', ', $table_fields);
	$sql = "CREATE TABLE IF NOT EXISTS $form_table  (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					$table_col_names,
					PRIMARY KEY  (id)
				) $charset_collate;";
				$wpdb->query($sql);

				echo "form successfully created";
	
	}

	 wp_die();

}

function bmg_forms_remove_options() {

	$options_removed = false;
	
	try {
	
		// get plugin option settings
		$options = bmg_forms_get_options_settings();
		
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
function bmg_forms_get_current_options() {
	
	// setup our return variable
	$current_options = array();
	
	try {
	
		// build our current options associative array
		$current_options = array(
			'bmg_forms_admin_email' => bmg_forms_get_option('bmg_forms_admin_email'), 
			'bmg_table_row_limit' => bmg_forms_get_option('bmg_table_row_limit'),
		);
	
	} catch( Exception $e ) {
		
		// php error
	
	}
	
	// return current options
	return $current_options;
	
}

// 6.2
// hint: get's an array of plugin option data (group and settings) so as to save it all in one place
function bmg_forms_get_options_settings() {
	
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

// 6.3
// hint: returns the requested page option value or it's default
function bmg_forms_get_option( $option_name ) {
	
	// setup return variable
	$option_value = '';	
	
	
	try {
		
		// get default option values
		$defaults = bmg_forms_get_default_options();
		
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

// 6.4
// hint: returns default option values as an associative array
function bmg_forms_get_default_options() {
	
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

// 6.5
//hint: create captcha
function bmg_forms_create_captcha($data = '', $img_path = '', $img_url = '', $font_path = '') {
			
		$defaults = array(
		'word' 			=> 	'', 
		'img_path' 		=>   plugin_dir_path( __FILE__ ) . 'captcha/', 
		'img_url' 		=> 	plugins_url( 'captcha/', __FILE__ ),
		'img_width' 	=> '150', 
		'img_height' 	=> '45', 
		'font_path' 	=> '',
		'expiration' 	=> 7200
		);		
		
		foreach ($defaults as $key => $val)	{
				
			if ( ! is_array($data)) {
				if ( ! isset($$key) OR $$key == '') {
					$$key = $val;
				
				}
			}
			else {			
				$$key = ( ! isset($data[$key])) ? $val : $data[$key];			
				
			}
		}	
		
		if ($defaults['img_path'] == '' OR $defaults['img_url'] == '') {
			return FALSE;
		}
		if ( ! @is_dir($defaults['img_path'])) {
			return FALSE;
		}
		
		if ( ! is_writable($defaults['img_path'])) {
			return FALSE;
		}			
	
		if ( ! extension_loaded('gd')) {
			return FALSE;
		}		
		
		// -----------------------------------
		// Remove old images	
		// -----------------------------------
				
		list($usec, $sec) = explode(" ", microtime());
		$now = ((float)$usec + (float)$sec);
				
		$current_dir = @opendir($defaults['img_path']);
		
		while($filename = @readdir($current_dir)) {
			if ($filename != "." and $filename != ".." and $filename != "index.html") {
				$name = str_replace(".jpg", "", $filename);	
				if (((double)$name + $expiration) < $now) {
					@unlink($img_path.$filename);
				}
			}
		}		
		@closedir($current_dir);
	
		// -----------------------------------
		// Do we have a "word" yet?
		// -----------------------------------
		
	   if ($word == '') {
			$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$str = '';
			for ($i = 0; $i < 4; $i++) {
				$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
			}		
			$word = $str;
	   }
		
		// -----------------------------------
		// Determine angle and position	
		// -----------------------------------
		
		$length	= strlen($word);
		$angle	= ($length >= 6) ? rand(-($length-6), ($length-6)) : 0;
		$x_axis	= rand(6, (360/$length)-16);			
		$y_axis = ($angle >= 0 ) ? rand($img_height, $img_width) : rand(6, $img_height);
		
		// -----------------------------------
		// Create image
		// -----------------------------------
				
		// PHP.net recommends imagecreatetruecolor(), but it isn't always available
		if (function_exists('imagecreatetruecolor')) {
			$im = imagecreatetruecolor($img_width, $img_height);
		}
		else {
			$im = imagecreate($img_width, $img_height);
		}
				
		// -----------------------------------
		//  Assign colors
		// -----------------------------------
		
		$bg_color		= imagecolorallocate ($im, 255, 255, 255);
		$border_color	= imagecolorallocate ($im, 153, 102, 102);
		$text_color		= imagecolorallocate ($im, 100, 0, 0);
		$grid_color		= imagecolorallocate($im, 255, 182, 182);
		$shadow_color	= imagecolorallocate($im, 255, 240, 240);
	
		// -----------------------------------
		//  Create the rectangle
		// -----------------------------------
		
		ImageFilledRectangle($im, 0, 0, $img_width, $img_height, $bg_color);
		
		// -----------------------------------
		//  Create the spiral pattern
		// -----------------------------------
		
		$theta		= 1;
		$thetac		= 7;
		$radius		= 16;
		$circles	= 20;
		$points		= 32;
	
		for ($i = 0; $i < ($circles * $points) - 1; $i++) {
			$theta = $theta + $thetac;
			$rad = $radius * ($i / $points );
			$x = ($rad * cos($theta)) + $x_axis;
			$y = ($rad * sin($theta)) + $y_axis;
			$theta = $theta + $thetac;
			$rad1 = $radius * (($i + 1) / $points);
			$x1 = ($rad1 * cos($theta)) + $x_axis;
			$y1 = ($rad1 * sin($theta )) + $y_axis;
			imageline($im, $x, $y, $x1, $y1, $grid_color);
			$theta = $theta - $thetac;
		}
	
		// -----------------------------------
		//  Write the text
		// -----------------------------------
		
		$use_font = ($font_path != '' AND file_exists($font_path) AND function_exists('imagettftext')) ? TRUE : FALSE;
			
		if ($use_font == FALSE) {
			$font_size =7;
			$x = rand(0, $img_width/($length/2));
			$y = 0;
		}
		else {
			$font_size	= 16;
			$x = rand(0, $img_width/($length/1.5));
			$y = $font_size+2;
		}		
		for ($i = 0; $i < strlen($word); $i++) {
			if ($use_font == FALSE) {
				$y = rand(0 , $img_height/2);
				imagestring($im, $font_size, $x, $y, substr($word, $i, 1), $text_color);
				$x += ($font_size*2);
			}
			else {		
				$y = rand($img_height/2, $img_height-3);
				imagettftext($im, $font_size, $angle, $x, $y, $text_color, $font_path, substr($word, $i, 1));
				$x += $font_size;
			}
		}		
	
		// -----------------------------------
		//  Create the border
		// -----------------------------------
	
		imagerectangle($im, 0, 0, $img_width-1, $img_height-1, $border_color);		
	
		// -----------------------------------
		//  Generate the image
		// -----------------------------------
		
		$img_name = $now.'.jpg';		
		
		ImageJPEG($im, $img_path.$img_name);		
		$img = "<img src=\"$img_url$img_name\" width=\"$img_width\" height=\"$img_height\" style=\"border:0;\" class=\"bmg-security-img\" alt=\" \" />";
		ImageDestroy($im);
		return array('word' => $word, 'time' => $now, 'image' => $img);
}


// 6.6
function getformname($id) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'bmg_forms';
	$form_name = $wpdb->get_var(
		$wpdb->prepare(
			"
				SELECT form_name 
				FROM $table_name
				WHERE id=%d	
			",
			$id
		)
	);
	return $form_name;
}

function getbmgformstables() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'bmg_forms';
	$sql = "SELECT * FROM $table_name";
	$result = $wpdb->get_results($sql);
	return $result;
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
	
	$options = bmg_forms_get_current_options();

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

/* 8.5 mail config page */
function bmg_mail_config_markup() {
	//Double check user capabilities
	if( !current_user_can('manage_options')) {
		return;
	}

	include( plugin_dir_path( __FILE__ ) . './templates/admin/mail-config.php');
}


/* 8.6 mail config page */
function bmg_edit_form_markup() {
	//Double check user capabilities
	if( !current_user_can('manage_options')) {
		return;
	}

	include( plugin_dir_path( __FILE__ ) . './templates/admin/edit-form.php');
}


// 9.1
// hint: registers all our plugin options
function bmg_forms_register_options() {
		
	// get plugin options settings
	
	$options = bmg_forms_get_options_settings();
	
	// loop over settings
	foreach( $options['settings'] as $setting ):
	
		// register this setting
		register_setting($options['group'], $setting);
	
	endforeach;
	
}


?>