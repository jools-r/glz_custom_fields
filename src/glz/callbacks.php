<?php

// -------------------------------------------------------------
// Replaces the default custom fields under write tab
function glz_custom_fields_replace($event, $step, $data, $rs)
{
    global $all_custom_sets, $date_picker;
    // Get all custom fields & keep only the ones which are set, filter by step
    $arr_custom_fields = glz_check_custom_set($all_custom_sets, $step);

    // DEBUG
    // dmp($arr_custom_fields);

    $out = ' ';

    if (is_array($arr_custom_fields) && !empty($arr_custom_fields)) {
        // Get all custom fields values for this article
        $arr_article_customs = glz_custom_fields_MySQL("article_customs", glz_get_article_id(), '', $arr_custom_fields);

        // DEBUG
        // dmp($arr_article_customs);

        if (is_array($arr_article_customs)) {
            extract($arr_article_customs);
        }

        // Which custom fields are set
        foreach ($arr_custom_fields as $custom => $custom_set) {
            // Get all possible/default value(s) for this custom set from custom_fields table
            $arr_custom_field_values = glz_custom_fields_MySQL("values", $custom, '', array('custom_set_name' => $custom_set['name']));

            // DEBUG
            // dmp($arr_custom_field_values);

            // Custom_set formatted for id e.g. custom_1_set => custom-1 - don't ask...
            $custom_id = glz_custom_number($custom, "-");
            // custom_set without "_set" e.g. custom_1_set => custom_1
            $custom = glz_custom_number($custom);

            // If current article holds no value for this custom field and we have no default value, make it empty
            $custom_value = (!empty($$custom) ? $$custom : '');
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
            $cf_lang = glz_cf_lang($custom_set["name"]);
            // Get the (localised) label if one exists, otherwise the regular name (as before)
            $cf_label = (gTxt($cf_lang) != $cf_lang) ? gTxt($cf_lang) : $custom_set["name"];

            $out .= inputLabel(
                $custom_id,
                $custom_set_value,
                $cf_label,
                array('', 'instructions_'.$custom),
                array('class' => 'txp-form-field custom-field glz-cf '.$custom_class.' '.$custom_id.' cf-'.glz_idify(str_replace('_', '-', $custom_set["name"])))
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
            $value = implode($value, '|');
            // and feed back into $_POST
            $_POST[$key] = $value;
        }
    }

    // DEBUG
    // dmp($_POST);
}


// -------------------------------------------------------------
// Inject css & js into admin head
function glz_custom_fields_inject_css_js()
{
    global $event, $date_picker, $time_picker, $prefs, $use_minified;

    $msg = array();
    $min = ($use_minified) ? '.min' : '';

    // glz_cf stylesheets
    $css = '<link rel="stylesheet" type="text/css" media="all" href="'.glz_relative_url($prefs['glz_cf_css_asset_url']).'/glz_custom_fields'.$min.'.css">'.n;
    // glz_cf javascript
    $js = '';

    if ($event == 'article') {
        // If a date picker field exists
        if ($date_picker) {
            $css .= '<link rel="stylesheet" type="text/css" media="all" href="'.glz_relative_url($prefs['glz_cf_datepicker_url']).'/datePicker'.$min.'.css" />'.n;
            foreach (array('date'.$min.'.js', 'datePicker'.$min.'.js') as $file) {
                $js .= '<script src="'.glz_relative_url($prefs['glz_cf_datepicker_url'])."/".$file.'"></script>'.n;
            }
            $js_datepicker_msg = '<span class="messageflash error" role="alert" aria-live="assertive"><span class="ui-icon ui-icon-alert"></span> <a href="'.ahu.'index.php?event=prefs#prefs_group_glz_custom_f">'.gTxt('glz_cf_public_error_datepicker').'</a> <a class="close" role="button" title="Close" href="#close"><span class="ui-icon ui-icon-close">Close</span></a></span>';
            $js .= <<<JS
<script>
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
</script>
JS;
        }

        // If a time picker field exists
        if ($time_picker) {
            $css .= '<link rel="stylesheet" type="text/css" media="all" href="'.glz_relative_url($prefs['glz_cf_timepicker_url']).'/timePicker'.$min.'.css" />'.n;
            $js  .= '<script src="'.glz_relative_url($prefs['glz_cf_timepicker_url']).'/timePicker'.$min.'.js"></script>'.n;
            $js_timepicker_msg = '<span class="messageflash error" role="alert" aria-live="assertive"><span class="ui-icon ui-icon-alert"></span> <a href="'.ahu.'index.php?event=prefs#prefs_group_glz_custom_f">'.gTxt('glz_cf_public_error_timepicker').'</a> <a class="close" role="button" title="Close" href="#close"><span class="ui-icon ui-icon-close">Close</span></a></span>';
            $js  .= <<<JS
<script>
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
</script>
JS;
        }
    }

    // Displays the notices we have gathered throughout the entire plugin
    if (count($msg) > 0) {
        // Let's turn our notices into a string
        $msg = join("<br>", array_unique($msg));

        $js .= '<script>
<!--//--><![CDATA[//><!--
$(document).ready(function() {
    // add our notices
    $("#messagepane").html(\''.$msg.'\');
});
//--><!]]>
</script>';
    if ($event != 'prefs') {
        $js .= '<script src="'.glz_relative_url($prefs['glz_cf_js_asset_url']).'/glz_custom_fields'.$min.'.js"></script>';
    }

    echo $js.n.t.
        $css.n.t;
}


// -------------------------------------------------------------
// Set up pre-requisite values for glz_custom_fields
function init_glz_custom_fields()
{
    // We will be reusing these globals across the whole plugin
    global $all_custom_sets, $prefs, $date_picker, $time_picker;

    // glz_notice collects all plugin notices
    // $msg = array();

    // Get all custom field sets from prefs
    $all_custom_sets = glz_custom_fields_MySQL("all");

    // do we have a date-picker or time-picker custom field
    $date_picker = glz_custom_fields_MySQL("custom_set_exists", "date-picker");
    $time_picker = glz_custom_fields_MySQL("custom_set_exists", "time-picker");
}


// -------------------------------------------------------------
// Install glz_cf tables and prefs
function glz_custom_fields_install()
{
    global $all_custom_sets, $prefs;
    $msg = '';

    // Set plugin preferences
    glz_cf_prefs_install();

    // Change 'html' key of default custom fields from 'custom_set'
    // to 'text_input' to avoid confusion with glz set_types()
    safe_update('txp_prefs', "html = 'text_input'", "event = 'custom' AND html = 'custom_set'");

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

    // Iterate over all custom_fields and retrieve all values
    // in custom field columns in textpattern table
    foreach ($all_custom_sets as $custom => $custom_set) {

        // Check only custom fields that have been set (have a name)
        if ($custom_set['name']) {

            // Get all existing custom values for ALL articles
            $all_values = glz_custom_fields_MySQL(
                'all_values',
                glz_custom_number($custom),
                '',
                array('custom_set_name' => $custom_set['name'],
                'status' => 0)
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
                    glz_custom_fields_MySQL(
                        "update",
                        $custom,
                        safe_pfx('txp_prefs'),
                        array(
                            'custom_set_name'     => $custom_set['name'],
                            'custom_set_type'     => "select",
                            'custom_set_position' => $custom_set['position']
                        )
                    );
                    $msg = gTxt('glz_cf_migration_success');
                }
            }
        }
    }

    // Set flag in txp_prefs that migration has been performed
    set_pref("glz_cf_migrated", "1", "glz_custom_f", PREF_HIDDEN);
}


// -------------------------------------------------------------
// Re-route 'Options' link on Plugins panel to Admin › Preferences
function glz_custom_fields_prefs_redirect()
{
    require_privs('plugin_prefs.glz_custom_fields');
    header("Location: index.php?event=prefs#prefs_group_glz_custom_f");
}
