
function toggle(source) {

    checkboxes = document.getElementsByClassName('wooproof_page_location');
    for (var i = 0, n = checkboxes.length; i < n; i++) {
        if (source.checked) {
            //jQuery(checkboxes[i]).attr('disabled', true);

        } else {
            //jQuery(checkboxes[i]).removeAttr('disabled');
        }
        checkboxes[i].checked = source.checked;
    }
}

function reset_to_defaults (form){
    if(confirm('Do you really want to reset the settings to defaults?')){
        form.submit();
    }
}

var deactivation_link = ".wp-list-table.plugins tbody tr[data-plugin='"+ ig_wooproof.plugin_id +"'] span.deactivate a";
if (document.querySelector(deactivation_link) !== null) {
    deactivation_href = document.querySelector(deactivation_link).href;
} else {
    deactivation_href = "";
}

var theDialog = jQuery("#wooproof-delete-dialog").dialog({
    autoOpen  : false,
    modal     : true,
    title     : "Quick Feedback",
    width     : 460,
//    height    : auto,
    buttons   : {
              'Skip and deactivate' : function() {
                  jQuery(":button:contains('Skip and deactivate'), :button:contains('Submit and deactivate')").prop("disabled", true).addClass("ui-state-disabled");
                  var data = {
                            'action': 'wooproof_deactivation_feedback',
                             delete_data: jQuery('form#wooproof-deactivation-feedback-form input[name=delete_data]').is(':checked')?'Yes':"No",
                             deactivation_reason: jQuery('input[name=deactivation_reason]:checked').val(),
                    };

                    jQuery.post(ajaxurl, data, function(response) {
                            window.location.href = deactivation_href;
                    });
                  //window.location = $(this).data("deactivation_href")
                  //$(this).dialog('close');
              },
              /*'Delete it also' : function() {
                  alert('no clicked');
                  $(this).dialog('close');
              }*/
                }
});

//Open the dialog box when the button is clicked.
jQuery(deactivation_link).click(function(e) {
    e.preventDefault();
    jQuery("#wooproof-delete-dialog").dialog("open");
});



jQuery("form#wooproof-deactivation-feedback-form :input").change(function() {
    jQuery(".ui-dialog-buttonpane .ui-button-text").text('Submit and deactivate');
});