<div class="wrap">
	
	<hr class='wp-header-end' />
	<?php
		$options = get_option('bmg_ticket_settings');

		$main_site = '';
	if(isset($options['site_url'])) {
		$main_site = esc_html($options['site_url']);


	}
	?>
	<form method="post" action="options.php">
		<?php
			// outputs a unique nounce for our plugin options
			settings_fields('bmg_ticket_settings');
			// generates a unique hidden field with our form handling url
			@do_settings_sections('bmg-ticket');

		?>
		
        <?php @submit_button(); ?>
	</form>
</div>