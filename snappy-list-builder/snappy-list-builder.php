<?php

/*
Plugin Name: Snappy List Builder
Plugin URI: http://wordpressplugincourse.com/plugins/snappy-list-builder
Description: The ultimate email list building plugin for Wordpress. Capture new subscribers. Reward subscribers with a custom download upon opt-in. Build unlimited lists. Import and export subscribers easily with .csv
Version: 1.0
Author: Abhilash N
Author URI: http://abhilashwebdeveloper.com
License: GPL2
License URIL https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: snappy-list-builder
*/

/* 0. Table of contents */

/* 
	1. Hooks
		1.1 - registers all our custom shortcodes
		1.2 - register custom admin column headers
		1.3 - register custom admin column data

	2. Shortcodes
		2.1 - slb_register_shortcodes()
		2.2 - slb_form_shortcode()

	3. Filters

	4. External Scripts

	5. Actions

	6. Helpers

	7. Custom post types

	8. Admin Pages

	9. Settings

*/

/* 1. HOOKS */

// 1.1 
// hint: registers all our custom shortcodes on init
add_action('init', 'slb_register_shortcodes');

// 1.2
// hint: register custom admin column headers
add_filter('manage_edit-slb_subscriber_columns','slb_subscriber_column_headers');
add_filter('manage_edit-slb_list_columns','slb_list_column_headers');

// 1.3
// hint: register custom admin column data
add_filter('manage_slb_subscriber_posts_custom_column', 'slb_subscriber_column_data', 1, 2);
add_action('admin_head-edit.php', 'slb_register_custom_admin_titles');
add_filter('manage_slb_list_posts_custom_column','slb_list_column_data', 1, 2);

/* 2. Shortcodes */

// 2.1
function slb_register_shortcodes() {
	add_shortcode('slb_form', 'slb_form_shortcode');
}

// 2.2
function slb_form_shortcode($args, $content="") {
	// setup our output variable - the form html
	$output = ' 
		<div class="slb">
			<form id="slb_form" name="slb_form" class="slb-form" method="post">
				<p class="slb-input-container">
					<label>Your Name</label>
					<input type="text" name="slb_fname" placeholder="First Name" />
					<input type="text" name="slb_lname" placeholder="Last Name" />
				</p>

				<p class="slb-input-container">
					<label>Your Email</label>
					<input type="email" name="slb_email" placeholder="ex. you@example.com" />
				</p>';

				/* Include content in our form html if content is passed into the function */
				if(strlen($content)) {
					$output .= '<div class="slb-content">' . wpautop($content) . '</div>'; 
				}

				$output .= '<p class="slb-input-container">
					<input type="submit" name="slb_submit" value="Sign Me Up!" />
				</p>
			</form>
		</div>
	';

	// Return our results/html
	return $output;
}

/* 3. Filters */
// 3.1
function slb_subscriber_column_headers($columns) {

	// creating custom column header data
	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __('Subscriber Name'),
		'email' => __('Email Address'),
	);

	// returning new columns
	return $columns;
}

// 3.2
function slb_subscriber_column_data($column, $post_id) {

	// setup our return text
	$output = '';

	switch($column) {

		case 'title':
			// get the custom name data
			$fname = get_field('slb_fname', $post_id);
			$lname = get_field('slb_lname', $post_id);
			$output .= $fname . ' ' . $lname;
			break;
		case 'email':
			// get the custom email data
			$email = get_field('slb_email', $post_id);
			$output .= $email;
			break;
	}

	// echo the output
	echo $output; 
}

// 3.2.2
// hint: registers special custom admin title columns
function slb_register_custom_admin_titles() {
	add_filter(
		'the_title',
		'slb_custom_admin_titles',
		99,
		2
	);
}


// 3.2.3
// hint: handles custom admin title "title" column data for post types without titles
function slb_custom_admin_titles($title, $post_id) {
	global $post;

	$output = $title;

	if(isset($post->post_type)){
		switch ($post->post_type) {
			case 'slb_subscriber':
				$fname = get_field('slb_fname', $post_id);
				$lname = get_field('slb_lname', $post_id);
				$output = $fname . '  ' . $lname;
				break;			
		}
	}
	return $output;
}

// 3.3
function slb_list_column_headers($columns) {

	// creating custom column header data
	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => ('List Name'),
	);

	// returning new columns
	return $columns;
}

// 3.4
function slb_list_column_data($column, $post_id) {

	// setup our return text
	$output = '';

	switch ( $column ) {
		case 'example':
			// code
			break;
	}

	echo $output;
}

/* 4. External Scripts */


/* 5. Actions */


/* 6. Helpers */


/* 7. Custom Post types */


/* 8. Admin Pages */


/* 9. Settings */

