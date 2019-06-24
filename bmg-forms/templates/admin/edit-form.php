<?php
	global $wpdb;
	$form_id = $_GET['form_id'];

	 $table_name = $wpdb->prefix . 'bmg_forms';
	 $sql = "SELECT * FROM $table_name WHERE id = $form_id";
	 $form = $wpdb->get_results($sql);

	 $form_meta_table = $wpdb->prefix . 'bmg_forms_meta';
	 $form_meta = $wpdb->get_results("SELECT * FROM $form_meta_table WHERE form_id=$form_id ORDER BY field_order ASC");

	 $form_layout_table = $wpdb->prefix . 'bmg_forms_settings';
	 $form_layout = $wpdb->get_results("SELECT * FROM $form_layout_table WHERE form_id=$form_id ORDER BY id ASC");

	 
	 $field_layout = $form_layout[0]->field_layout;
	 $grid_columns = $form_layout[0]->grid_columns;
	 $hide_labels = (boolean)$form_layout[0]->hide_labels;
	 $buttons_alignment = $form_layout[0]->buttons_alignment;
	 $error_display = $form_layout[0]->error_display;
	 $enable_captcha =  (boolean)$form_layout[0]->captcha;

	 $form_items = count($form_meta);

	 for($i = 0; $i < $form_items; $i++){

	 	//$data = json_encode((array)$form_meta);
	 	if($form_meta[$i]->required == "1"){
	 		$form_meta[$i]->required = true;
		 } else {
		 	unset($form_meta[$i]->required);
		 }
		 if($form_meta[$i]->value == null) {
		 	unset($form_meta[$i]->value);
		 }
		 if($form_meta[$i]->style == null) {
		 	unset($form_meta[$i]->style);
		 }
		 if($form_meta[$i]->min == null) {
		 	unset($form_meta[$i]->min);
		 }
		 if($form_meta[$i]->max == null) {
		 	unset($form_meta[$i]->max);
		 }
		 if($form_meta[$i]->step == null) {
		 	unset($form_meta[$i]->step);
		 }
		 if($form_meta[$i]->rows == null) {
		 	unset($form_meta[$i]->rows);
		 }
		 if($form_meta[$i]->placeholder == null) {
		 	unset($form_meta[$i]->placeholder);
		 }
		 if($form_meta[$i]->subtype == null) {
		 	unset($form_meta[$i]->subtype);
		 }
		 if($form_meta[$i]->maxlength == null) {
		 	unset($form_meta[$i]->maxlength);
		 }
		 if($form_meta[$i]->description == null) {
		 	unset($form_meta[$i]->description);
		 }
		$form_meta[$i]->colname = $form_meta[$i]->name; 
	 	$form_meta[$i]->className = $form_meta[$i]->classname;
	 	unset($form_meta[$i]->classname);
	 	unset($form_meta[$i]->access);
	 	$form_meta[$i]->values = unserialize($form_meta[$i]->sub_values);
	 	unset($form_meta[$i]->sub_values);
	 	unset($form_meta[$i]->other);


	 }

	 $data = json_encode((array)$form_meta);


?>
<script type="text/javascript">

	
	jQuery(function($) {

		var layoutOpt = "";
    $( "#form-layout option:selected" ).each(function() {
      layoutOpt = $( this ).val();
    });
    if(layoutOpt == "grid"){
      $('.bmg-grid-opt-row').css('display','table-row');
    } else {
      $('.bmg-grid-opt-row').css('display','none');
    }

    if(layoutOpt == "horizontal"){
    	$('#hide-labels').prop("checked", false);
    	$('#hide-labels').prop("disabled", true);
    }
		
  var fbTemplate = document.getElementById('bmg-forms-edit-wrap'),
    options = {
      typeUserAttrs: {
       		text: {
       			 id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    },
			    colname: {
			    	label: 'column name',
			    	value: '',
			    	readonly: true
			    }
       		},
       		"radio-group": {
       			id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    },
			    colname: {
			    	label: 'column name',
			    	value: '',
			    	readonly: true
			    }
       		},
       		"date": {
       			id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    },
			    colname: {
			    	label: 'column name',
			    	value: '',
			    	readonly: true
			    }
       		},
       		"textarea": {
       			id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    },
			    colname: {
			    	label: 'column name',
			    	value: '',
			    	readonly: true
			    }
       		},
       		"select": {
       			id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    },
			    colname: {
			    	label: 'column name',
			    	value: '',
			    	readonly: true
			    }
       		},
       		"number": {
       			id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    },
			    colname: {
			    	label: 'column name',
			    	value: '',
			    	readonly: true
			    }
       		},
       		"paragraph": {
       			id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    }
       		},
       		"hidden": {
       			id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    },
			    colname: {
			    	label: 'column name',
			    	value: '',
			    	readonly: true
			    }
       		},
       		"header": {
       			id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    }
       		},
       		"file": {
       			id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    },
			    colname: {
			    	label: 'column name',
			    	value: '',
			    	readonly: true
			    }
       		},
       		"checkbox-group" : {
       			id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    },
			    colname: {
			    	label: 'column name',
			    	value: '',
			    	readonly: true
			    }	
       		},
       		"button" : {
       			id: {
			      label: 'id',
			      value: '',
			      readonly: true
			    }	
       		}
       },	
      formData: '<?php echo $data; ?>',
      disabledActionButtons: ['save','clear','data'],
      disableFields: ['autocomplete'],
      actionButtons: [{
        id: 'updateData',
        className: 'btn savebtn',
        label: 'Update Form',
        type: 'button',
        events: {
          click: function() {
          updateForm();
        }
      }
      }]
       
    };
  const formBuilder = $(fbTemplate).formBuilder(options);
  let formData;
  let formName;

  function updateForm() {
      var formName = document.getElementById('bmg-form-name').value;
      var formId = document.getElementById('bmg-form-id').value;
      var formData = formBuilder.actions.getData('json', true)
      var formName = document.getElementById('bmg-form-name').value;
     var fieldLayout = document.getElementById('form-layout').value;
     var gridColumns;
     var hideLabels = false;
     var enable_captcha = false;
      if(fieldLayout == "grid"){
        gridColumns = document.getElementById('form-grid-option').value;
      }
      if(document.getElementById("hide-labels").checked == true){
        hideLabels = true;
      }
      if(document.getElementById("form-captcha").checked == true){
        enable_captcha = true;
      }
       var buttonAlignment = document.getElementById('button-align').value;
       var errorDisplay;
       if(document.getElementById("error-inline").checked){
         errorDisplay = "inline";
       } else {
         errorDisplay = "error-top";
       }
       var layoutOptions = {
         "fieldlayout": fieldLayout,
         "gridcolumns": gridColumns,
         "hidelabels": hideLabels,
         "buttonalignment": buttonAlignment,
         "errordisplay": errorDisplay
       }

      layoutOptions = JSON.stringify(layoutOptions);
      console.log(layoutOptions);
      if(formData.length === 2){
        alert("Please construct the form");
        return false;
      }
      if(formName !== "" && formData.length > 2){
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
             alert("Form updated successfully");

            console.log(this.responseText);
            window.location.replace("admin.php?page=bmg-forms");
          } 
          };
      xhttp.open("POST", "admin-ajax.php?action=bmg_forms_update_form",true);
      xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhttp.send("formname=" + formName + "&formdata=" + formData + "&formid=" + formId + "&layout=" + layoutOptions);  
      }
  }

  jQuery('#bmg-forms-edit-wrap').on('click touchstart', '.delete-confirm', e => {
  	formName = document.getElementById('bmg-form-name').value;
      formId = document.getElementById('bmg-form-id').value;
	 const deleteID = jQuery(event.target)
      .parents('.form-field:eq(0)').attr('id');
      const fId = '#id-'+deleteID;
      const recId = jQuery('#id-'+deleteID).val();
      const fieldName = jQuery('#name-'+deleteID).val();
      var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
             alert("Field Deleted");

            console.log(this.responseText);
   
          } 
         };
      xhttp.open("POST", "admin-ajax.php?action=bmg_forms_delete_field",true);
      xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhttp.send("formname=" + formName + "&fieldid=" + recId + "&formid=" + formId + "&fieldname=" + fieldName);

 	console.log(deleteID);
 	console.log(recId);
 	  })
 


})

/*
document.addEventListener('fieldRemoved', function(){
	 const deleteID = jQuery(event.target)
      .parents('.form-field:eq(0)').attr('id');
 console.log(deleteID);
 //console.log(event.currentTarget);	
 //console.log(jQuery('.fld-id').val());
});
*/

 
</script>
<style>
	.copy-button { display: none !important;  }
</style>

<div class="wrap">
	<h1 class="wp-heading-inline">Edit Form</h1>
	<div id="titlediv">
		<div id="titlewrap">
				<label class="screen-reader-text" id="title-prompt-text" for="title">Enter form name</label>
			<input type="text" name="post_title" readonly="readonly" class="bmg-forms-new-form" value="<?php echo $form[0]->form_name; ?>" id="bmg-form-name" spellcheck="true" autocomplete="off" placeholder="Enter Form Name">

			<input type="hidden" name="post_id" value="<?php echo $form_id; ?>" id="bmg-form-id" />
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
			<div id="bmg-forms-edit-wrap">
		
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
										<option value="vertical" <?php echo $selected = $field_layout == "vertical" ? "selected" : ""; ?>>Vertical</option>
										<option value="horizontal" <?php echo $selected = $field_layout == "horizontal" ? "selected" : ""; ?>>Horizontal</option>
										<option value="inline" <?php echo $selected = $field_layout == "inline" ? "selected" : ""; ?>>Inline</option>
										<option value="grid" <?php echo $selected = $field_layout == "grid" ? "selected" : ""; ?>>Grid</option>
							      	</select>
							</td>
						</tr>
						<tr class="bmg-grid-opt-row">
							<th scope="row">
								<label for="form-grid-option">Grid Columns</label>
							</th>
							<td>
								<select class="form-control" id="form-grid-option" name="form-grid-option">
									<option value="2" <?php echo $selected = $grid_columns == "2" ? "selected" : ""; ?>>Two</option>
									<option value="3" <?php echo $selected = $grid_columns == "3" ? "selected" : ""; ?>>Three</option>
									<option value="4" <?php echo $selected = $grid_columns == "4" ? "selected" : ""; ?>>Four</option>
						      	</select>
						     </td>
						 </tr>
						<tr>
							<th scope="row">
								<label for="hide-labels">Hide Labels</label>
							</th>
							<td>
								<input type="checkbox" id="hide-labels" <?php echo $checked = $hide_labels == true ? "checked" : ""; ?> name="hide-labels" value="1" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="form-grid-option">Buttons Alignment</label>
							</th>
							<td>
								<select class="form-control" id="button-align" name="button-align">
									<option value="left" <?php echo $selected = $buttons_alignment == "left" ? "selected" : ""; ?>>Left</option>
									<option value="center"  <?php echo $selected = $buttons_alignment == "center" ? "selected" : ""; ?>>Center</option>
									<option value="right"  <?php echo $selected = $buttons_alignment == "right" ? "selected" : ""; ?>>Right</option>
						      	</select>
						     </td>
						 </tr>
						 <tr>
							<th scope="row">
								<label for="form-grid-option">Error Display</label>
							</th>
							<td>
								<label for="Inline">
									<input type="radio" name="errors" id="error-inline" value="inline" <?php echo $checked = $error_display == "inline" ? "checked" : ""; ?> />
									Inline
								</label>
								<label for="top">
									<input type="radio" name="errors" id="error-top" value="top"  <?php echo $checked = $error_display == "top" ? "checked" : ""; ?> />
									Top
								</label>
						     </td>
						 </tr>
						 <tr>
							<th scope="row">
								<label for="form-captcha">Captcha</label>
							</th>
							<td>
								<input type="checkbox" id="form-captcha" <?php echo $checked = $enable_captcha == true ? "checked" : ""; ?> name="form-captcha" value="1" />
						     </td>
						 </tr>	
					</tbody>
				</table>
				
			</form>
	  </div>
	</div>
	
</div>