jQuery(document).ready(function() {

var tid;
/* Post new ticket */
var postForm = jQuery( '#post-form' );
 
var jsonData = function( form ) {
    var arrData = form.serializeArray(),
        objData = {};
     
    jQuery.each( arrData, function( index, elem ) {
        objData[elem.name] = elem.value;
    });
     
    return JSON.stringify( objData );
};
 
postForm.on( 'submit', function( e ) {
    e.preventDefault();
     
   jQuery.ajax({
        type:"POST",
        url: js_config.ajax_url + "/wp-json/wpas-api/v1/tickets",
        //method: 'POST',
        dataType : 'json',
        data: jsonData( postForm ),
        //crossDomain: true,
        contentType: 'application/json',
        beforeSend: function ( xhr ) {
              xhr.setRequestHeader ("Authorization", "Basic " + btoa("agentuser" + ":" + "123456789"));
        },
        success: function( data ) {
             tb_remove();
             alert("New ticket added");
            console.log("Succes" + data );
        },
        error: function( error ) {
            tb_remove();
            alert("Error: Could not add ticket");
            console.log("Error");
            console.log( error );
        }
    }); 
    });


/* Open edit ticket modal */
    jQuery(function() {
        editTicket = jQuery( "#bmg-ticket-edit-modal" ).dialog({
        autoOpen: false,
        height: 450,
        width: 600,
        modal: true,
        title: "Edit Ticket"
    });


   jQuery(document).on("click", ".editTicket", function () {
                tid = jQuery(this).data('id');
                var title = jQuery(this).data('title');
                var content = jQuery(this).data('content');
                console.log(tid + ' ' + title + ' ' + content);
                
                jQuery("#ticket-title").val( title );
                jQuery("#ticket-content").val (content)
                
                editTicket.dialog( "open" );
            });
        });        

/* Close thickbox */
    jQuery(function() {
        jQuery('.cancel-btn').click(function() {
            tb_remove();
        });

    });

    jQuery('.button-cancel').click(function() {
            editTicket.dialog( "close" );
        });


    var updateForm = jQuery( '#update-form' );


    updateForm.on( 'submit', function( e ) {
    e.preventDefault();
     
   jQuery.ajax({
        type:"POST",
        url: js_config.ajax_url + "/wp-json/wpas-api/v1/tickets/" + tid,
        //method: 'POST',
        dataType : 'json',
        data: jsonData( updateForm ),
        //crossDomain: true,
        contentType: 'application/json',
        beforeSend: function ( xhr ) {
              xhr.setRequestHeader ("Authorization", "Basic " + btoa("agentuser" + ":" + "123456789"));
        },
        success: function( data ) {
            editTicket.dialog( "close" );
            console.log("Succes" + data );   
            alert("Ticket has been updated");
        },
        error: function( error ) {
            editTicket.dialog( "close" );
            alert("Error: Could not update");
            console.log( error );
        }
    }); 
    });

});