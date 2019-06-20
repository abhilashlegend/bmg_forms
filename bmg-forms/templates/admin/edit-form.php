<?php
	global $wpdb;
	$form_id = $_GET['form_id'];

	 $table_name = $wpdb->prefix . 'bmg_forms';
	 $sql = "SELECT * FROM $table_name WHERE id = $form_id";
	 $form = $wpdb->get_results($sql);

	 $form_meta_table = $wpdb->prefix . 'bmg_forms_meta';
	 $form_meta = $wpdb->get_results("SELECT * FROM $form_meta_table WHERE form_id=$form_id ORDER BY field_order ASC");

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
      formName = document.getElementById('bmg-form-name').value;
      formId = document.getElementById('bmg-form-id').value;
      formData = formBuilder.actions.getData('json', true)
      if(formName === ""){
        alert("Please enter form name");
        return false;
      }
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
      xhttp.send("formname=" + formName + "&formdata=" + formData + "&formid=" + formId);  
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
	<div id="bmg-forms-edit-wrap">
		
	</div>
</div>