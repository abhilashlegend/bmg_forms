<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( get_admin_page_title() ); ?></h1>
	<div id="titlediv">
		<div id="titlewrap">
				<label class="screen-reader-text" id="title-prompt-text" for="title">Enter form name</label>
			<input type="text" name="post_title" class="bmg-forms-new-form" value="" id="bmg-form-name" spellcheck="true" autocomplete="off" placeholder="Enter Form Name">
		</div>
	</div>
	<ul class="nav nav-tabs" id="myTab" role="tablist">
	  <li class="nav-item active">
	  	<a class="nav-link  active" id="form-tab" data-toggle="tab" href="#form" role="tab" aria-controls="form" aria-selected="true">Form</a>
	  </li>
	  <li class="nav-item">
	  	<a class="nav-link" id="layout-tab" data-toggle="tab" href="#layout" role="tab" aria-controls="layout" aria-selected="false">Settings</a>
	  </li>
	</ul>
	<div class="tab-content" id="myTabContent">
	  <div class="tab-pane active" id="form" role="tabpanel" aria-labelledby="form-tab">
	  	<div id="bmg-forms-build-wrap">
		</div>
	  </div>

	  <div class="tab-pane" id="layout" role="tabpanel" aria-labelledby="layout-tab">
	  		<h2> Form Layout settings</h2>
			<form name="bmg-forms-layout-form">
				<table class="layout-setting-table">
					<tbody>
						<tr>
							<th scope="row">
								 <label for="form-layout">Fields Layout</label>
							</th>
							<td>
								 	<select class="form-control" id="form-layout">
										<option value="vertical">Vertical</option>
										<option value="horizontal">Horizontal</option>
										<option value="inline">Inline</option>
										<option value="grid">Grid</option>
							      	</select>
							</td>
						</tr>
						<tr class="bmg-grid-opt-row">
							<th scope="row">
								<label for="form-grid-option">Grid Columns</label>
							</th>
							<td>
								<select class="form-control" id="form-grid-option" name="form-grid-option">
									<option value="2">Two</option>
									<option value="3">Three</option>
									<option value="4">Four</option>
						      	</select>
						     </td>
						 </tr>
						<tr>
							<th scope="row">
								<label for="hide-labels">Hide Labels</label>
							</th>
							<td>
								<input type="checkbox" id="hide-labels" name="hide-labels" value="1" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="form-grid-option">Buttons Alignment</label>
							</th>
							<td>
								<select class="form-control" id="button-align" name="button-align">
									<option value="left">Left</option>
									<option value="center">Center</option>
									<option value="right">Right</option>
						      	</select>
						     </td>
						 </tr>
						 <tr>
							<th scope="row">
								<label for="form-grid-option">Error Display</label>
							</th>
							<td>
								<label for="Inline">
									<input type="radio" name="errors" id="error-inline" value="inline" />
									Inline
								</label>
								<label for="top">
									<input type="radio" name="errors" id="error-top" value="top" checked />
									Top
								</label>
						     </td>
						 </tr>
						 <tr>
							<th scope="row">
								<label for="form-captcha">Captcha</label>
							</th>
							<td>
								<input type="checkbox" id="form-captcha" name="form-captcha" value="1" />
						     </td>
						 </tr>
					</tbody>
				</table>
				
			</form>
	  </div>
	  
	</div>
	
	
</div>
