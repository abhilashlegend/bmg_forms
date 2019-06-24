jQuery($ => {
  const fbEditor = document.getElementById("bmg-forms-build-wrap");
  var options = {
      disabledActionButtons: ['save','data'],
      disableFields: ['autocomplete'],  
      actionButtons: [{
        id: 'saveData',
        className: 'btn savebtn',
        label: 'Generate Form',
        type: 'button',
        events: {
          click: function() {
          generateForm();
        }
      }
      }]
    };
  const formBuilder = $(fbEditor).formBuilder(options);
  let formData;
  let formName;
  function generateForm() {
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
         "errordisplay": errorDisplay,
         "captcha": enable_captcha
       }

      layoutOptions = JSON.stringify(layoutOptions);
      console.log(layoutOptions);
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
              alert("Form created successfully");

            console.log(this.responseText);
            window.location.replace("admin.php?page=bmg-forms");
          } 
          };
      xhttp.open("POST", "admin-ajax.php?action=bmg_generate_form",true);
      xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhttp.send("formname=" + formName + "&formdata=" + formData + "&layout=" + layoutOptions);  
      }
  }

  $("#form-layout").change(function() {
     var str = "";
    $( "#form-layout option:selected" ).each(function() {
      str = $( this ).val();
    });
    if(str == "grid"){
      $('.bmg-grid-opt-row').css('display','table-row');
    } else {
      $('.bmg-grid-opt-row').css('display','none');
    }
     if(str == "horizontal"){
      $('#hide-labels').prop("checked", false);
      $('#hide-labels').prop("disabled", true);
    } else {
      $('#hide-labels').prop("disabled", false);
    }
  });
 
});


 
