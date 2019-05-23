<?php 

function bmg_ticket_settings() {

	//If plugin settings don't exist, then create them
	if(false == get_option('bmg_ticket_settings')) {
		add_option('bmg_ticket_settings');
	}
	//Define (at least) one section for our fields
	add_settings_section(
		//Unique identifier for the section
		'bmg_ticket_settings_section',
		//Section Title
		__('BMG ticket Settings Section','bmg-ticket'),
		//Callback for an optional description
		'bmg_ticket_settings_section_callback',
		//Admin page to add section to
		'bmg-ticket'
	);

	add_settings_field(
		//Unique identifier for field
		'bmg_ticket_settings_custom_text',
		//Field Title
		__('Main site url','bmg-ticket'),
		//Callback for field markup
		'bmg_ticket_settings_custom_text_callback',
		//Page to go on
		'bmg-ticket',
		//Section to go in
		'bmg_ticket_settings_section'
	);

	add_settings_field(
		//Unique identifier for field
		'bmg_ticket_agent_username',
		//Field Title
		__('Agent username','bmg-ticket'),
		//Callback for field markup
		'bmg_ticket_agent_username_callback',
		//Page to go on
		'bmg-ticket',
		//Section to go in
		'bmg_ticket_settings_section'
	);

	add_settings_field(
		//Unique identifier for field
		'bmg_ticket_agent_password',
		//Field Title
		__('Agent password','bmg-ticket'),
		//Callback for field markup
		'bmg_ticket_agent_password_callback',
		//Page to go on
		'bmg-ticket',
		//Section to go in
		'bmg_ticket_settings_section'
	);
	


	register_setting(
		'bmg_ticket_settings',
		'bmg_ticket_settings'
	);


}



add_action('admin_init','bmg_ticket_settings');



function bmg_ticket_settings_custom_text_callback() {
	$options = get_option('bmg_ticket_settings');

	$main_site = '';
	if(isset($options['site_url'])) {
		$main_site = esc_html($options['site_url']);


	}

	

	echo '<input type="url" id="site_url" name="bmg_ticket_settings[site_url]" value="' . $main_site . '" />';
}


function bmg_ticket_agent_username_callback() {
	$options = get_option('bmg_ticket_settings');

	$agent_username = '';
	if(isset($options['agent_username'])) {
		$agent_username = esc_html($options['agent_username']);
	}

	echo '<input type="text" id="agent_username" name="bmg_ticket_settings[agent_username]" value="' . $agent_username . '" />';
}

function bmg_ticket_agent_password_callback() {
	$options = get_option('bmg_ticket_settings');

	$agent_password = '';
	if(isset($options['agent_password'])) {
		$agent_password = esc_html($options['agent_password']);
	}

	echo '<input type="password" id="agent_password" name="bmg_ticket_settings[agent_password]" value="' . $agent_password . '" />';
}


function custom_admin_js() {

	$options = get_option('bmg_ticket_settings');

	$main_site = '';
	if(isset($options['site_url'])) {
		$main_site = esc_html($options['site_url']);


	}

	?>
    
   <script type="text/javascript" async>;
   var main_site = "<?php echo $main_site; ?>";
   var arr = main_site.split("");
   var indexes = [];
   for(var i = 0; i < arr.length; i++) {
   	if(arr[i] === "/") {
   		indexes.push(i);
   	}
   }
   var count = 0;
   for(var j = 0; j < indexes.length; j++) {
   	console.log(indexes[j]);
   	 arr.splice(indexes[j] + count,0,"\\");
   	 count++;
   }
  cust_url = arr.join("") + "\/wp-json\/";
   console.log(indexes);
   console.log(cust_url);
   (function (a, b, c, e, f, g) {if (a.wpasGadget) {return};e = 'script';g = b.createElement(e);e = b.getElementsByTagName(e)[0];g.async = 1;g.src = c;e.parentNode.insertBefore(g, e);})(window, document, '<?php echo $main_site; ?>/wp-content/plugins/awesome-support-remote-tickets-V1-3-0/assets/public/js/gadget-dist.js');var wpasData = {"gadgetID":"96","url": cust_url }</script>
    	<?php
}
//add_action('admin_footer', 'custom_admin_js');


function CallAPI($method, $url, $data = false)
{
	$options = get_option('bmg_ticket_settings');

	if(isset($options['agent_username'])) {
		$agent_username = esc_html($options['agent_username']);
	}
	if(isset($options['agent_password'])) {
		$agent_password = esc_html($options['agent_password']);
	}

	

    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, $agent_username . ":" . $agent_password);

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return json_decode($result);
}
