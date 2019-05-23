<?php

function bmg_ticket_admin_styles_scripts() {
	wp_enqueue_script(
		'bmg-ticket',
		plugins_url('bmg-ticket') . '/private/js/bmg-ticket.js',
		[],
		time()
	);

	wp_enqueue_style("jquery-ui-css", "https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css");
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-dialog');

	$options = get_option('bmg_ticket_settings');
	$main_site = '';
	$agent_username = '';
	$agent_password = '';

	if(isset($options['site_url'])) {
		$main_site = esc_html($options['site_url']);
	}

	if(isset($options['agent_username'])) {
		$agent_username = esc_html($options['agent_username']);
	}

	if(isset($options['agent_password'])) {
		$agent_password = esc_html($options['agent_password']);
	}

	wp_localize_script( 'bmg-ticket', 'js_config', array(
        'ajax_url'	=> esc_url_raw ( $main_site ),
        'ajax_nonce'	=> wp_create_nonce( 'wp_rest' ),
        'username' => $username,
        'password' => $password,
    	));
}

add_action( 'admin_enqueue_scripts', 'bmg_ticket_admin_styles_scripts' );