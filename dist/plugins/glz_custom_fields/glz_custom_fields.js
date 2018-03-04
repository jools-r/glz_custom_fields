$(function() {
// Ensure glzResetRadio() function persists after article asynchronous save
textpattern.Relay.register('txpAsyncForm.success', glzResetRadio);

    // Init: global object to store variables
    var GLZ_CF;
    GLZ_CF = {};
    GLZ_CF.special_custom_types  = ["date-picker", "time-picker"];
    GLZ_CF.no_value_custom_types = ["text_input", "textarea"];

    // Init: re-used jQuery objects
    $glz_value_field = $(".edit-custom-set-value");
    $glz_value_instructions = $glz_value_field.find(".txp-form-field-instructions");
    $glz_select_instructions = $('.edit-custom-set-type').find(".txp-form-field-instructions");

    // Init: get and store instruction strings, clear up message holders
    GLZ_CF.messages = {
        'textarea'         : $('.glz-custom-textarea-msg').html(),
        'configure'        : $glz_select_instructions.text(),
        'customscriptpath' : $('.glz-custom-script-msg').text()
    }
    $('.glz-custom-script-msg').remove();
    $('.glz-custom-textarea-msg').remove();


    // CUSTOM FIELD EDIT PANE
    // -----------------------

    // Toggle 'hint' for "select" custom field type dropdown
    glzToggleTypeLink();

    // Change custom field "value" field state
    // – Disabled for special custom types / types that have no preset values
    // – Textarea for custom types with multiple values (default at start)
    // – Path input field for custom scripts name
    if ( $.inArray($("select#custom_set_type :selected").attr("value"), [].concat(GLZ_CF.special_custom_types, GLZ_CF.no_value_custom_types)) != -1 ) {
        glzCustomFieldValueOff();
    } else if ( $("select#custom_set_type :selected").attr("value") == "custom-script" ) {
        glzCustomFieldValuePath();
    }

    // Update custom field "value" field if type drop-down is changed
    $("select#custom_set_type").change( function() {
        glzToggleTypeLink();
        if ( $.inArray($("select#custom_set_type :selected").attr("value"), [].concat(GLZ_CF.special_custom_types, GLZ_CF.no_value_custom_types)) != -1 ) {
            glzCustomFieldValueOff();
        } else if ( $("select#custom_set_type :selected").attr("value") == "custom-script" ) {
            glzCustomFieldValuePath();
        } else {
            glzCustomFieldValueOn();
        }
    });


    // WRITE TAB:
    // ---------

    // Add a reset link to all radio custom fields
    glzResetRadio();

    // reset all radio buttons if "reset" is clicked (also after async save)
    $(".txp-layout").on("click", ".glz-custom-field-reset", function() {
        // Abort if disabled (= previously clicked)
        if ($(this).hasClass('disabled')) return false;
        // Get this radio button group name
        var custom_field_to_reset = $(this).attr("name");

        // Reset radio input(s)
        $("input[name=" + custom_field_to_reset + "]").prop("checked", false);
        // Set "reset" button to disabled
        $(this).addClass("disabled");

        // Add hidden input with empty value and same ID to save as empty to the db (only if not already there)
        if($(this).siblings(".txp-form-radio-reset").length === 0) {
            $(this).after("<input type=\"hidden\" class=\"txp-form-radio-reset\" value=\"\" name=\""+ custom_field_to_reset +"\" />");
        }
        return false;
    });

    // Revert reset state if radio button subsequently clicked (also after async save)
    $(".txp-layout").on("click",".glz-custom-radio .radio", function() {
        var custom_field_to_reanimate = $(this).attr("name");
        $this_reset_button = $(".glz-custom-field-reset[name=" + custom_field_to_reanimate + "]");
        // If "reset" button currently disabled
        if ($this_reset_button.hasClass("disabled")) {
            // Remove input with empty value
            $("input[type=hidden][name=" + custom_field_to_reanimate + "]").remove();
            // Revert disabled status of "reset" button
            $this_reset_button.removeClass("disabled");
        }
    });


    // RE-USABLE FUNCTIONS
    // -------------------

    // Add reset button to radio fields
    function glzResetRadio() {
        // if there are radio fields
        if ($(".glz-custom-radio").length > 0) {
            // loop over each set
            $(".glz-custom-radio").each(function() {
                var custom_field_to_reset = $(this).find("input:first").attr("name");
                $(this).find("label:first").after(" <span class=\"small\"><a href=\"#\" class=\"glz-custom-field-reset\" name=\"" + custom_field_to_reset +"\">Reset</a></span>");
                // if none of the radio buttons are checked on load, set "reset" button to disabled
                if(!$("input:radio[name=" + custom_field_to_reset + "]").is(":checked")) {
                    $(".glz-custom-field-reset[name=" + custom_field_to_reset + "]").addClass("disabled");
                }
            });
        }
    }

    // Hide or disable custom field "value" input/textarea
    function glzCustomFieldValueOff() {
        // Save any textarea contents for future restoring, then remove textarea
        if ($glz_value_field.find("textarea#value").length) {
            GLZ_CF.textarea_value = $glz_value_field.find("textarea#value").html();
            $glz_value_field.find("textarea#value").remove();
        }

        // If input exists and is not disabled, save 'custom scripts path' for future restoring
        if ($glz_value_field.find("input#value").length) {
            if ($glz_value_field.find("input#value").prop('disabled') == false) {
                GLZ_CF.path_value = $glz_value_field.find("input#value").attr('value');
            }
        } else {
            // No input field? Then add a new empty input
            $glz_value_field.find(".txp-form-field-value").prepend('<input type="text" id="value" name="value" />');
        }

        // Blank input field and set to disabled
        $glz_value_field.find("input#value").attr('value', "-----").prop('disabled', true);
        // Remove any 'hint' instructions
        $glz_value_instructions.html('');
    }

    // Show custom field "value" input + messages
    function glzCustomFieldValueOn() {
        // If input exists and is not disabled, save 'custom scripts path' for future restoring,
        // then remove input
        if ( $glz_value_field.find("input#value").length ) {
            if ($glz_value_field.find("input#value").prop('disabled') == false) {
                GLZ_CF.path_value = $glz_value_field.find("input#value").attr('value');
            }
            $glz_value_field.find("input#value").remove();
            $glz_value_instructions.html('');
        }
        // No textarea? Then add one for inserting multiple values
        if ( !$glz_value_field.find("textarea#value").length ) {
            $(".edit-custom-set-value .txp-form-field-value").prepend('<textarea id="value" name="value" rows="5"></textarea>');
        }
        // If textarea contents were previously saved, restore them
        if ( GLZ_CF.textarea_value ) {
            $glz_value_field.find("textarea#value").html(GLZ_CF.textarea_value);
        }
        // Update 'hint' instructions for textarea
        $glz_value_instructions.html(GLZ_CF.messages['textarea']);
    }

    // Custom script path input
    function glzCustomFieldValuePath() {
        // Save any textarea contents for future restoring, then remove textarea
        if ($glz_value_field.find("textarea#value").length) {
            GLZ_CF.textarea_value = $glz_value_field.find("textarea#value").html();
            $glz_value_field.find("textarea#value").remove();
            $glz_value_instructions.html('');
        }
        // If no input field exists, insert one
        if (!$glz_value_field.find("input#value").length) {
            $glz_value_field.find(".txp-form-field-value").prepend('<input type="text" id="value" name="value" size="32" />');
        }
        // Clear any blanked values and restore visibility

        // If input is placeholder "-----" value, reset to empty
        // otherwise let through any other incoming value
        if ($glz_value_field.find("input#value").attr('value') == "-----") {
            $glz_value_field.find("input#value").attr('value', '');
        }
        // Make sure field is visible
        $glz_value_field.find("input#value").prop('disabled', false);

        // Update 'hint' instructions for custom scripts path
        $glz_value_instructions.html(GLZ_CF.messages['customscriptpath']);
        // If the path value was previously saved, restore it
        if ( GLZ_CF.path_value ) {
            $glz_value_field.find("input#value").attr("value", GLZ_CF.path_value);
        }

    }

    // Custom field type dropdown: show/hide 'see settings' messages based on type
    function glzToggleTypeLink() {
        if ( $.inArray($("select#custom_set_type :selected").attr("value"), [].concat(GLZ_CF.special_custom_types, ["multi-select", "custom-script"])) != -1 ) {
            $glz_select_instructions.html("<a href=\"http://"+window.location.host+window.location.pathname+"?event=prefs#prefs_group_glz_custom_f\">" + GLZ_CF.messages['configure'] + "</a>");
        } else {
            $glz_select_instructions.html('');
        }
    }

});
