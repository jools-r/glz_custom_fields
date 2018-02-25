$(function() {
textpattern.Relay.register('txpAsyncForm.success', glzResetRadio);

    // creating a global object to store variables, functions etc.
    if (GLZ_CUSTOM_FIELDS == undefined) {
        var GLZ_CUSTOM_FIELDS;
        GLZ_CUSTOM_FIELDS = {};
        GLZ_CUSTOM_FIELDS.special_custom_types  = ["date-picker", "time-picker"];
        GLZ_CUSTOM_FIELDS.no_value_custom_types = ["text_input", "textarea"];
        GLZ_CUSTOM_FIELDS.messages = {
            'textarea' : "Each value on a separate line<br>One {default} value allowed",
            'script'   : "File name in your custom scripts path",
            'configure': "Configure custom scripts path"
        }
    }

    // CUSTOM FIELD PREFS PANE
    // init toggle custom field "value" field:
    // – disable for special custom types / types that have no_value
    // – show path field for custom scripts
    glzToggleTypeLink();
    if ( $.inArray($("select#custom_set_type :selected").attr("value"), [].concat(GLZ_CUSTOM_FIELDS.special_custom_types, GLZ_CUSTOM_FIELDS.no_value_custom_types)) != -1 ) {
        glzCustomFieldValueOff();
    } else if ( $("select#custom_set_type :selected").attr("value") == "custom-script" ) {
        glzCustomFieldValuePath();
    }
    // update custom field "value" field if type changed
    $("select#custom_set_type").change( function() {
        glzToggleTypeLink();
        if ( $.inArray($("select#custom_set_type :selected").attr("value"), [].concat(GLZ_CUSTOM_FIELDS.special_custom_types, GLZ_CUSTOM_FIELDS.no_value_custom_types)) != -1 ) {
            glzCustomFieldValueOff();
        } else if ( $("select#custom_set_type :selected").attr("value") == "custom-script" ) {
            glzCustomFieldValuePath();
        } else {
            glzCustomFieldValueOn();
        }
    });

    // WRITE TAB: Add a reset link to all radio custom fields

    // add radio field reset button
    glzResetRadio();

    // reset all radio buttons if "reset" is clicked (also after async save)
    $(".txp-layout").on("click", ".glz-custom-field-reset", function() {
        // abort if disabled (= previously clicked)
        if ($(this).hasClass('disabled')) return false;
        // get this radio button group name
        custom_field_to_reset = $(this).attr("name");

        // reset our radio input(s)
        $("input[name=" + custom_field_to_reset + "]").prop("checked", false);
        // set "reset" button to disabled
        $(this).addClass("disabled");

        // add hidden input with empty value and same ID to save as empty to the db (only if not already there)
        if($(this).siblings(".txp-form-radio-reset").length === 0) {
            $(this).after("<input type=\"hidden\" class=\"txp-form-radio-reset\" value=\"\" name=\""+ custom_field_to_reset +"\" />");
        }
        return false;
    });

    // revert reset state if radio button subsequently clicked (also after async save)
    $(".txp-layout").on("click",".glz-custom-radio-field .radio", function() {
        custom_field_to_reanimate = $(this).attr("name");
        $this_reset_button = $(".glz-custom-field-reset[name=" + custom_field_to_reanimate + "]");
        // if "reset" button currently disabled
        if ($this_reset_button.hasClass("disabled")) {
            // remove input with empty value
            $("input[type=hidden][name=" + custom_field_to_reanimate + "]").remove();
            // revert disabled status of "reset" button
            $this_reset_button.removeClass("disabled");
        }
    });

    // ### RE-USABLE FUNCTIONS ###

    // add reset button to radio fields
    function glzResetRadio() {
        // if there are radio fields
        if ($(".glz-custom-radio-field").length > 0) {
            // loop over each set
            $(".glz-custom-radio-field").each(function() {
                custom_field_to_reset = $(this).find("input:first").attr("name");
                $(this).find("label:first").after(" <span class=\"small\"><a href=\"#\" class=\"glz-custom-field-reset\" name=\"" + custom_field_to_reset +"\">Reset</a></span>");
                // if none of the radio buttons are checked on load, set "reset" button to disabled
                if(!$("input:radio[name=" + custom_field_to_reset + "]").is(":checked")) {
                    $(".glz-custom-field-reset[name=" + custom_field_to_reset + "]").addClass("disabled");
                }
            });
        }
    }
    // hide/disable custom field "value" input/textarea
    function glzCustomFieldValueOff() {
    if ($("textarea#value").length) {
        GLZ_CUSTOM_FIELDS.textarea_value = $("textarea#value").html();
        $("textarea#value + br + span.information").html('');
        $("textarea#value").remove();
    }
    if (!$("input#value").length)
        $(".glz-cf-value .txp-form-field-value").prepend('<input type="text" id="value" name="value" />');
        $("input#value").attr('value', "no value allowed").attr('disabled', true);
        $("input#value + br + span.information").html('');
    }
    // show custom field "value" input + messages
    function glzCustomFieldValueOn() {
        if ( $("input#value").length ) {
            $("input#value").remove();
        }
        if ( !$("textarea#value").length ) {
            $(".glz-cf-value .txp-form-field-value").prepend('<textarea id="value" name="value"></textarea>');
            $("textarea#value + br + span.information").html(GLZ_CUSTOM_FIELDS.messages['textarea']);
        }
        if ( GLZ_CUSTOM_FIELDS.textarea_value ) {
            $("textarea#value").html(GLZ_CUSTOM_FIELDS.textarea_value);
        }
    }
    // remove custom field "value" input + messages
    // show path input + messages
    function glzCustomFieldValuePath() {
        if ($("textarea#value").length) {
            $("textarea#value + br + span.information").html('');
            $("textarea#value").remove();
        }
        if (!$("input#value").length) {
            $(".glz-cf-value .txp-form-field-value").prepend('<input type="text" id="value" name="value" />');
        }
        if ( $.inArray($("input#value").attr('value'), ["", "no value allowed"]) != -1 ) {
            $("input#value").attr('value', "");
            $("input#value").attr('disabled', false);
            $("input#value + br + span.information").html(GLZ_CUSTOM_FIELDS.messages['script']);
        }
    }
    // remove/show messages based on type drop-down value
    function glzToggleTypeLink() {
        $("select#custom_set_type").parent().find('br, span').remove();
        if ( $.inArray($("select#custom_set_type :selected").attr("value"), [].concat(GLZ_CUSTOM_FIELDS.special_custom_types, ["multi-select", "custom-script"])) != -1 ) {
            $("select#custom_set_type").after("<br><span class=\"information\"><a href=\"http://"+window.location.host+window.location.pathname+"?event=prefs#prefs_group_glz_custom_f\">"+GLZ_CUSTOM_FIELDS.messages['configure']+"</a></span>");
        }
    }

});
