<?php
// get the default values for our options
	$options = bmg_forms_get_current_options();

?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( get_admin_page_title() ); ?></h1>
	<hr class='wp-header-end' />
	<form method="post" action="options.php">
		<?php
			// outputs a unique nounce for our plugin options
			settings_fields('bmg_forms_settings');
			// generates a unique hidden field with our form handling url
			@do_settings_sections('bmg-forms');

			echo('<table class="form-table">
			
				<tbody>
					<tr>
						<th scope="row"><label for="bmg_forms_admin_email">Admin Email </label></th>
						<td>
							<input type="text" name="bmg_forms_admin_email" value="'. $options['bmg_forms_admin_email'] .'" class="" />
							<p class="description" id="bmg_forms_admin_email-description">The email used to get data when forms are filled .</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bmg_table_row_limit">Table Rows Limit</label></th>
						<td>
							<input type="number" name="bmg_table_row_limit" value="'. $options['bmg_table_row_limit'] .'" class="" />
							<p class="description" id="bmg_table_row_limitt-description">The number of rows to display per page or pagination limit .</p>
						</td>
					</tr>
			
				</tbody>
				
			</table>');

		?>
		
        <?php @submit_button(); ?>
	</form>

</div>