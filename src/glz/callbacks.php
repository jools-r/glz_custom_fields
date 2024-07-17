<?php

##################
#
#	glz_custom_fields for Textpattern
#	version 2.0 – jools-r
#	Original version: Gerhard Lazu
#
##################

##################
#
#   CALLBACKS – Callback functions called from main.php
#
##################


// -------------------------------------------------------------
// Replaces the default custom fields on the 'Write' panel
function glz_custom_fields_replace($event, $step, $data, $rs)
{
    // Get all custom field sets from prefs
    $all_custom_sets = glz_db_get_all_custom_sets();

    // Filter all custom fields & keep only those that are set for that render step
    $arr_custom_fields = glz_check_custom_set($all_custom_sets, $step);

    // DEBUG
    // dmp($arr_custom_fields);

    $out = ' ';

    if (is_array($arr_custom_fields) && !empty($arr_custom_fields)) {
        // Get all custom fields values for this article
        $arr_article_customs = glz_db_get_article_custom_fields(glz_get_article_id(), $arr_custom_fields);

        // DEBUG
        // dmp($arr_article_customs);

        if (is_array($arr_article_customs)) {
            extract($arr_article_customs);
        }

        // Which custom fields are set
        foreach ($arr_custom_fields as $custom => $custom_set) {
            // Get all possible/default value(s) for this custom set from custom_fields table
            $arr_custom_field_values = glz_db_get_custom_field_values($custom, array('custom_set_name' => $custom_set['name']));

            // DEBUG
            // dmp($arr_custom_field_values);

            // Custom_set formatted for id e.g. custom_1_set => custom-1 - don't ask...
            $custom_id = glz_custom_number($custom, "-");
            // custom_set without "_set" e.g. custom_1_set => custom_1
            $custom = glz_custom_number($custom);

            // If current article holds no value for this custom field and we have no default value, make it empty
            // (not using empty() as it also eradicates values of '0')
            $custom_value = ((isset($$custom) && trim($$custom) <> '') ? $$custom : '');
            // DEBUG
            // dmp("custom_value: {$custom_value}");

            // Check if there is a default value
            // if there is, strip the { }
            $default_value = glz_clean_default(glz_default_value($arr_custom_field_values));
            // DEBUG
            // dmp("default_value: {$default_value}");

            // Now that we've found our default, we need to clean our custom_field values
            if (is_array($arr_custom_field_values)) {
                array_walk($arr_custom_field_values, "glz_clean_default_array_values");
            }

            // DEBUG
            // dmp($arr_custom_field_values);

            // The way our custom field value is going to look like
            list($custom_set_value, $custom_class) = glz_format_custom_set_by_type($custom, $custom_id, $custom_set['type'], $arr_custom_field_values, $custom_value, $default_value);

            // DEBUG
            // dmp($custom_set_value);

            // cf_lang string (define this in your language to create a field label)
            $cf_lang = glz_cf_langname($custom_set["name"]);
            // Get the (localised) label if one exists, otherwise the regular name (as before)
            $cf_label = (gTxt($cf_lang) != $cf_lang) ? gTxt($cf_lang) : $custom_set["name"];

            $out .= inputLabel(
                $custom_id,
                $custom_set_value,
                $cf_label,
                array('', 'instructions_'.$custom),
                array('class' => 'txp-form-field custom-field glz-cf '.$custom_class.' '.$custom_id.' cf-'.glz_cf_idname(str_replace('_', '-', $custom_set["name"])))
            );
        }
    }

    // DEBUG
    // dmp($out);

    // If we're writing textarea custom fields, we need to include the excerpt as well
    if ($step == "body") {
        $out = $data.$out;
    }

    return $out;
}


// -------------------------------------------------------------
// Prep custom fields values for db (convert multiple values into a string e.g. multi-selects, checkboxes & radios)
function glz_custom_fields_before_save()
{
    // Iterate over POST vars
    foreach ($_POST as $key => $value) {
        // Extract custom_{} keys with multiple values as arrays
        if (strstr($key, 'custom_') && is_array($value)) {
            // Convert to delimited string …
            $value = implode('|', $value);
            // and feed back into $_POST
            $_POST[$key] = $value;
        }
    }

    // DEBUG
    // dmp($_POST);
}


// -------------------------------------------------------------
// Inject css & js into admin head
function glz_custom_fields_inject_css_js($debug = false)
{
    global $event, $prefs, $use_minified, $debug;

    $msg = array();
    $min = ($use_minified) ? '.min' : '';

    // do we have a date-picker or time-picker custom field
    $date_picker = glz_check_custom_set_exists("date-picker");
    $time_picker = glz_check_custom_set_exists("time-picker");

    // glz_cf stylesheets (load from file when $debug is set to true)
    if ($debug) {
        $css_url = glz_relative_url($prefs['glz_cf_css_asset_url']).'/glz_custom_fields'.$min.'.css';
        $css = glz_inject_css($css_url, 1, array('media' => 'screen'));
        // Show hidden fields
        $css .= glz_inject_css('#prefs-glz_cf_css_asset_url,#prefs-glz_cf_js_asset_url{display:flex}');
    } else {
        $css = glz_custom_fields_head_css();
    }
    // glz_cf javascript
    $js = '';

    if ($event == 'article') {
        // If a date picker field exists
        if ($date_picker) {
            $css_datepicker_url = glz_relative_url($prefs['glz_cf_datepicker_url']).'/datePicker'.$min.'.css';
            $css .= glz_inject_css($css_datepicker_url, 1, array('media' => 'screen'));
            foreach (array('date'.$min.'.js', 'datePicker'.$min.'.js') as $file) {
                $js .= glz_inject_js(glz_relative_url($prefs['glz_cf_datepicker_url'])."/".$file, 1);
            }
            $js_datepicker_msg = '<span class="messageflash error" role="alert" aria-live="assertive"><span class="ui-icon ui-icon-alert"></span> <a href="'.ahu.'index.php?event=prefs&check_url=1#prefs_group_glz_custom_f">'.gTxt('glz_cf_public_error_datepicker').'</a> <a class="close" role="button" title="Close" href="#close"><span class="ui-icon ui-icon-close">Close</span></a></span>';
            $js_datepicker = <<<JS
$(document).ready(function () {
    textpattern.Relay.register('txpAsyncForm.success', glzDatePicker);

    function glzDatePicker() {
        if ($("input.date-picker").length > 0) {
            try {
                Date.firstDayOfWeek = {$prefs['glz_cf_datepicker_first_day']};
                Date.format = '{$prefs["glz_cf_datepicker_format"]}';
                Date.fullYearStart = '19';
                $(".date-picker").datePicker({startDate:'{$prefs["glz_cf_datepicker_start_date"]}'});
                $(".date-picker").dpSetOffset(29, -1);
            } catch(err) {
                $('#messagepane').html('{$js_datepicker_msg}');
            }
        }
    }

    glzDatePicker();
});
JS;
            $js .= glz_inject_js($js_datepicker);
        }

        // If a time picker field exists
        if ($time_picker) {
            $css_timepicker_url = glz_relative_url($prefs['glz_cf_timepicker_url']).'/timePicker'.$min.'.css';
            $css .= glz_inject_css($css_timepicker_url, 1, array('media' => 'screen'));
            $js_timepicker_url = glz_relative_url($prefs['glz_cf_timepicker_url']).'/timePicker'.$min.'.js';
            $js .= glz_inject_js($js_timepicker_url, 1);
            $js_timepicker_msg = '<span class="messageflash error" role="alert" aria-live="assertive"><span class="ui-icon ui-icon-alert"></span> <a href="'.ahu.'index.php?event=prefs&check_url=1#prefs_group_glz_custom_f">'.gTxt('glz_cf_public_error_timepicker').'</a> <a class="close" role="button" title="Close" href="#close"><span class="ui-icon ui-icon-close">Close</span></a></span>';
            $js_timepicker = <<<JS
$(document).ready(function () {
    textpattern.Relay.register('txpAsyncForm.success', glzTimePicker);

    function glzTimePicker() {
        if ($(".time-picker").length > 0) {
            try {
                $("input.time-picker").timePicker({
                    startTime: '{$prefs["glz_cf_timepicker_start_time"]}',
                    endTime: '{$prefs["glz_cf_timepicker_end_time"]}',
                    step: {$prefs["glz_cf_timepicker_step"]},
                    show24Hours: {$prefs["glz_cf_timepicker_show_24"]}
                });
                $(".glz-custom-timepicker .txp-form-field-value").on("click", function (){
                    $(this).children(".time-picker").trigger("click");
                });
            } catch(err) {
                $("#messagepane").html('{$js_timepicker_msg}');
            }
        }
    }

    glzTimePicker();
});
JS;
            $js .= glz_inject_js($js_timepicker);
        }
    }
    if ($event == 'glz_custom_fields') {
        $js_sortable_url = glz_relative_url($prefs['glz_cf_js_asset_url']).'/glz_jqueryui.sortable'.$min.'.js';
        $js .= glz_inject_js($js_sortable_url, 1);
    }

    // glz_cf javascript (load from file when $debug is set to true)
    if ($event != 'prefs') {
        if ($debug) {
            $js_core_url = glz_relative_url($prefs['glz_cf_js_asset_url']).'/glz_custom_fields'.$min.'.js';
            $js .= glz_inject_js($js_core_url, 1);
        } else {
            $js .= glz_custom_fields_head_js();
        }

    }

    echo $js.n.t.
        $css.n.t;
}


// -------------------------------------------------------------
// Install glz_cf tables and prefs
function glz_custom_fields_install()
{
    global $prefs;
    $msg = '';

    // Set plugin preferences
    glz_cf_prefs_install();

    // Change 'html' key of default custom fields from 'custom_set'
    // to 'text_input' to avoid confusion with glz set_types()
    safe_update('txp_prefs', "html = 'text_input'", "event = 'custom' AND html = 'custom_set'");

/*
    // LEGACY of the old '<txp:glz_custom_fields_search_form />' tag?
    // Create a search section if not already available (for searching by custom fields)
    if (empty(safe_row("name", 'txp_section', "name='search'"))) {

        // Retrieve skin name used for 'default' section
        $current_skin = safe_field('skin', 'txp_section', "name='default'");

        // Add new 'search' section
        safe_insert('txp_section', "
            name         = 'search',
            title        = 'Search',
            skin         = '".$current_skin."',
            page         = 'default',
            css          = 'default',
            description  = '',
            on_frontpage = '0',
            in_rss       = '0',
            searchable   = '0'
        ");

        $msg = gTxt('glz_cf_search_section_created');
    }
*/
    // Create 'custom_fields' table if it does not already exist
    safe_create(
        'custom_fields',
        "`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL default '',
        `value` varchar(255) NOT NULL default '',
        PRIMARY KEY (id),
        KEY (`name`(50))",
        "ENGINE=MyISAM"
    );

    // Add an 'id' column to an existing legacy 'custom_fields' table
    if (!getRows("SHOW COLUMNS FROM ".safe_pfx('custom_fields')." LIKE 'id'")) {
        safe_alter(
            'custom_fields',
            "ADD `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT KEY"
        );
    }

    // Migrate existing custom_field data to new 'custom_fields' table

    // Skip if glz_cf migration has already been performed
    if (isset($prefs['glz_cf_migrated'])) {
        return;
    }

    // Skip if 'custom_fields' table already contains values (don't overwrite anything)
    if (($count = safe_count('custom_fields', "1 = 1")) !== false) {
        // Set flag in 'txp_prefs' that migration has already been performed
        set_pref("glz_cf_migrated", "1", "glz_custom_f", PREF_HIDDEN);
        $msg = gTxt('glz_cf_migration_skip');
        return;
    }

    // Get all custom field sets from prefs
    $all_custom_sets = glz_db_get_all_custom_sets();

    // Iterate over all custom_fields and retrieve all values
    // in custom field columns in textpattern table
    foreach ($all_custom_sets as $custom => $custom_set) {

        // Check only custom fields that have been set (have a name)
        if ($custom_set['name']) {

            // Get all existing custom values for ALL articles
            $all_values = glz_db_get_all_existing_cf_values(
                glz_custom_number($custom),
                array(
                    'custom_set_name' => $custom_set['name'],
                    'status' => 0
                )
            );

            // If we have results, assemble SQL insert statement to add them to custom_fields table
            if (count($all_values) > 0) {
                $insert = '';
                foreach ($all_values as $escaped_value => $value) {
                    // skip empty values or values > 255 characters (=probably textareas?)
                    if (!empty($escaped_value) && strlen($escaped_value) < 255) {
                        $insert .= "('{$custom}','{$escaped_value}'),";
                    }
                }
                // Trim final comma and space
                $insert = rtrim($insert, ', ');
                $query = "
                    INSERT INTO
                        ".safe_pfx('custom_fields')." (`name`,`value`)
                    VALUES
                        {$insert}
                    ";

                if (isset($query) && !empty($query)) {

                    // Add all custom field values to 'custom_fields' table
                    safe_query($query);

                    // Update the type of this custom field to select
                    // (might want to make this user-adjustable at some point)
                    safe_update(
                        'txp_prefs',
                        "val      = '".$custom_set['name']."',
                         html     = 'select',
                         position = '".$custom_set['position']."'",
                        "name = '{$custom}'"
                    );
                    $msg = gTxt('glz_cf_migration_success');
                }
            }
        }
    }

    // Set flag in txp_prefs that migration has been performed
    set_pref("glz_cf_migrated", "1", "glz_custom_f", PREF_HIDDEN);
}


/**
 * Uninstaller.
 *
 * IMPORTANT: There has been no uninstall function until to now to prevent
 * accidental loss of user input if uninstalling the plugin.
 *
 * This is intended just as an on-demand clean-up script and is hidden
 * behind a 'safety catch'. In the 'txp_prefs' table, set the column 'type'
 * of 'glz_cf_permit_full_deinstall' to '1' to reveal the switch in the
 * preferences panel. The installer sets this to hidden from the beginning.
 *
 */

function glz_custom_fields_uninstall()
{
    global $prefs;

    // To prevent inadvertent data loss, full deinstallation is only permitted
    // if the 'safety catch' has been disabled: set 'glz_cf_permit_full_deinstall' = 1
    if ($prefs['glz_cf_permit_full_deinstall'] == '1') {

        // Delete 'custom_fields' table
        safe_query(
            'DROP TABLE IF EXISTS '.safe_pfx('custom_fields')
        );

        // Get all custom fields > 10
        $additional_cfs = safe_rows('name', 'txp_prefs', "name LIKE 'custom\___\_set' AND name <> 'custom_10_set'");

        $drop_query ='';
        foreach ($additional_cfs as $val) {
            // Delete prefs labels for custom fields > 10
            safe_delete('txp_lang', "name = '".$val['name']."'");
            // Build DROP query for 'textpattern' table
            $drop_query .= 'DROP '.str_replace("_set", "", $val['name']).', ';
        }
        // Trim final comma and space from drop statement
        $drop_query = rtrim($drop_query, ', ');
        // Drop used 'custom_X' > 10 columns from 'textpattern' table
        safe_alter('textpattern', $drop_query);

        // Delete all saved language strings
        safe_delete('txp_lang', "event = 'glz_cf' OR name LIKE 'instructions\_glz\_cf%'");

        // Delete custom field entries > 10 from 'txp_prefs' (custom_ __ _set = must have two chars in the middle)
        safe_delete('txp_prefs', "name LIKE 'custom\___\_set' AND name <> 'custom_10_set' AND event = 'custom'");

        // Delete plugin prefs
        safe_delete('txp_prefs', "event LIKE 'glz\_custom\_f%'");

        // Reset all remaining custom fields (1-10) back to original type 'custom_set'
        safe_update('txp_prefs', "html = 'custom_set'", "event = 'custom'");

        // The following also clears the built-in custom fields 1-10
        // For the "full whammy" uncomment these too.
    /*
        // Zero custom field user input in the 'textpattern' table
        safe_update('textpattern', "custom_1 = NULL, custom_2 = NULL, custom_3 = NULL, custom_4 = NULL, custom_5 = NULL, custom_6 = NULL, custom_7 = NULL, custom_8 = NULL, custom_9 = NULL, custom_10 = NULL", "1 = 1");
        // Erase names from 'txp_prefs' tables
        safe_update('txp_prefs', "val = NULL", "name LIKE 'custom\_%%\_set'");
    */
        $message = "‘glz_custom_fields’ has been deinstalled. ALL CUSTOM FIELD USER DATA has also been removed.";

    } else {

        // Regular deinstall

        // Should we restore the 'html' type for custom fields 1-10 to 'text_input'?
        // Yes: it prevents errors occurring (or is there an automatic fallback)
        // No:  switching them back loses their settings. The data is kept but the
        //      custom_field type is then lost in the case of a reinstallation.

        $message = "‘glz_custom_fields’ has been deinstalled. Your custom field data has NOT been deleted and will reappear if you reinstall ‘glz_custom_fields’.";
    }

}


// -------------------------------------------------------------
// Re-route 'Options' link on Plugins panel to Admin › Preferences
function glz_custom_fields_prefs_redirect()
{
    header("Location: index.php?event=prefs#prefs_group_glz_custom_f");
}


// -------------------------------------------------------------
// Custom field sortable position router function
function glz_cf_positionsort_steps($event='', $step='', $msg='')
{
    switch ($step) {
    case 'get_js':
        glz_cf_positionsort(gps('js'));
        break;
    case 'put':
        glz_cf_positionsort(gps('type'));
        break;
    }
}


// -------------------------------------------------------------
// Custom field sortable position inject js
function glz_cf_positionsort_js()
{
    $js_positionsort = glz_inject_js('"index.php?event=glz_custom_fields&step=get_js', 1);
    echo <<<HTML
        $js_positionsort
HTML;
}


// -------------------------------------------------------------
// Custom field sortable position steps function
function glz_cf_positionsort($js)
{
    header('Content-Type: text/javascript');

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        header('Content-Type: application/json');
        $success = true;
        foreach ($_POST as $customfield => $sort) {
            if (!safe_update('txp_prefs', 'position=\''.doSlash($sort).'\'', 'name=\''.doSlash($customfield).'\'')) {
                $success = false;
            }
        }
        echo json_encode(array('success' => $success));

    } else {

        $position = array();
        foreach (safe_rows('name, position', 'txp_prefs', "event = 'custom'") as $row) {
            $customfield = $row['name'];
            $sort = $row['position'];
            if (!strlen($sort)) {
                $sort = 0;
            }
            $position['glz_' . $customfield] = ctype_digit($sort) ? (int)$sort : $sort;
        }

        // Language strings
        $ui_sort = gTxt('glz_cf_col_sort');
        $msg_success = gTxt('glz_cf_sort_success');
        $msg_error = gTxt('glz_cf_sort_error');

        echo 'var position = ', json_encode($position), ';'."\n";
        echo <<<EOB
$(function() {
    $('#glz_custom_fields_container thead tr').prepend('<th class="txp-list-col-sort">$ui_sort</th>').find('th').each(function() {
        var th = $(this);
        th.html(th.text());
    });
    $('#glz_custom_fields_container table').addClass('sortable').find('tbody tr').prepend('<td></td>').appendTo('#glz_custom_fields_container tbody').sortElements(function(a, b) {
        var a_sort = position[$(a).attr('id')];
        var b_sort = position[$(b).attr('id')];
        if (a_sort == b_sort) {
            return 0;
        }
        return a_sort > b_sort ? 1 : -1;
    }).parent().sortable({
        items: 'tr',
        helper: function(e, ui) {
            $('.ui-sortable').parent().addClass('fixed-width');
            ui.children().each(function() {
                $(this).width($(this).width());
            });
            return ui;
        },
        axis: 'y',
        handle: 'td:first-child',
        start: function(event, ui) {
        },
        stop: function() {
            $('.ui-sortable').parent().removeClass('fixed-width');
            var position = {};
            $(this).find('tr').each(function() {
                var tr = $(this);
                position[tr.attr('id').replace('glz_', '')] = tr.index();
            });
            var set_message = function(message, type) {
                $('#messagepane').html('<span id="message" class="messageflash ' + type + '" role="alert" aria-live="assertive">' + message + ' <a class="close" role="button" title="Close" href="#close"><span class="ui-icon ui-icon-close">Close</span></a>');
            }
            $.ajax(
                'index.php?event=glz_custom_fields&step=put', {
                    type: 'POST',
                    data: position,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            set_message('$msg_success', 'success')
                        } else {
                            this.error();
                        }
                    },
                    error: function() {
                        set_message('$msg_error', 'error');
                    }
                }
            );
        }
    }).find('tr').find('td:first-child').html('&#9776;');
});
EOB;

    }
    exit();
}
