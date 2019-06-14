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
      formName = document.getElementById('bmg-form-name').value;
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
      xhttp.send("formname=" + formName + "&formdata=" + formData);  
      }
  }



  
 
});