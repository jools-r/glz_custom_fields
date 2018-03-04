<?php

function glz_cf_prefs_install()
{
    global $prefs;

    $position = 200;

    // array: old_prefname => array('pref.subevent', 'html', 'default-value')
    $plugin_prefs = array(
        'values_ordering'        => array('', 'glz_prefs_orderby', 'custom'),
        'multiselect_size'       => array('', 'glz_text_input_small', '5'),
        'css_asset_url'          => array('', 'glz_url_input', hu.'plugins/glz_custom_fields'),
        'js_asset_url'           => array('', 'glz_url_input', hu.'plugins/glz_custom_fields'),
        'custom_scripts_path'    => array('', 'glz_url_input', $prefs['path_to_site'].'/plugins/glz_custom_fields'),
        'datepicker_url'         => array('glz_cf_datepicker', 'glz_url_input', hu.'plugins/glz_custom_fields/jquery.datePicker'),
        'datepicker_format'      => array('glz_cf_datepicker', 'glz_prefs_datepicker_format', 'dd/mm/yyyy'),
        'datepicker_first_day'   => array('glz_cf_datepicker', 'glz_prefs_datepicker_firstday', 1),
        'datepicker_start_date'  => array('glz_cf_datepicker', 'glz_text_input_small', '01/01/2017'),
        'timepicker_url'         => array('glz_cf_timepicker', 'glz_url_input', hu.'plugins/glz_custom_fields/jquery.timePicker'),
        'timepicker_start_time'  => array('glz_cf_timepicker', 'glz_text_input_small', '00:00'),
        'timepicker_end_time'    => array('glz_cf_timepicker', 'glz_text_input_small', '23:30'),
        'timepicker_step'        => array('glz_cf_timepicker', 'glz_text_input_small', 30),
        'timepicker_show_24'     => array('glz_cf_timepicker', 'glz_prefs_timepicker_format', true)
    );

    foreach ($plugin_prefs as $name => $val) {
        if (get_pref($name, false) === false) {
            // If pref is new, create new pref with 'glz_cf_' prefix
            create_pref('glz_cf_'.$name, $val[2], 'glz_custom_f'.($val[0] ? '.'.$val[0] : ''), PREF_PLUGIN, $val[1], $position, '');
        } else {
            // If pref exists, add 'glz_cf_' prefix to name, reassign position and html type and set to type PREF_PLUGIN
            safe_update(
                'txp_prefs',
                "name = 'glz_cf_".$name."',
                 event = 'glz_custom_f".($val[0] ? ".".$val[0] : "")."',
                 html = '".$val[1]."',
                 type = ".PREF_PLUGIN.",
                 position = ".$position,
                "name = '".$name."'"
            );
        }
        $position++;
    }

    // Set 'migrated' pref to 'glz_cf_migrated' and to hidden (type = 2);
    if (get_pref('migrated')) {
        safe_update(
            'txp_prefs',
            "name = 'glz_cf_migrated',
             type = ".PREF_HIDDEN,
            "name = 'migrated'"
        );
    }
}

/**
 * Uninstaller.
 *
 * IMPORTANT: There has been no uninstall function until to now to prevent
 * accidental data loss if uninstalling the plugin. Is there a case for it?
 *
 * This should be just as an on-demand clean-up script.
 *
 */
 // TODO: make a hidden pref to expose this function

function glz_prefs_uninstall()
{

    // Delete prefs
    safe_delete('txp_prefs', "event = 'glz_custom_f'");

    // Delete 'custom_fields' table
    safe_query(
        'DROP TABLE IF EXISTS '.safe_pfx('custom_fields')
    );

    // TODO: Delete custom_field data from articles
    // TODO: Delete custom_field > 10 from txp_prefs
}

/**
 * Renders a HTML choice of GLZ value ordering.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */
function glz_prefs_orderby($name, $val)
{
    $vals = array(
        'ascending'   => gTxt('glz_cf_prefs_value_asc'),
        'descending'  => gTxt('glz_cf_prefs_value_desc'),
        'custom'      => gTxt('glz_cf_prefs_value_custom')
    );
    return selectInput($name, $vals, $val, '', '', $name);
}

/**
 * Renders a HTML choice of date formats.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */
function glz_prefs_datepicker_format($name, $val)
{
    $vals = array(
        "dd/mm/yyyy"  => "dd/mm/yyyy",
        "mm/dd/yyyy"  => "mm/dd/yyyy",
        "yyyy-mm-dd"  => "yyyy-mm-dd",
        "dd mm yy"    => "dd mm yy",
        "dd.mm.yyyy"  => "dd.mm.yyyy"
    );
    return selectInput($name, $vals, $val, '', '', $name);
}

/**
 * Renders a HTML choice of weekdays.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */
function glz_prefs_datepicker_firstday($name, $val)
{
    $vals = array(
        0             => gTxt('glz_cf_prefs_sunday'),
        1             => gTxt('glz_cf_prefs_monday'),
        2             => gTxt('glz_cf_prefs_tuesday'),
        3             => gTxt('glz_cf_prefs_wednesday'),
        4             => gTxt('glz_cf_prefs_thursday'),
        5             => gTxt('glz_cf_prefs_friday'),
        6             => gTxt('glz_cf_prefs_saturday')
    );
    return selectInput($name, $vals, $val, '', '', $name);
}

/**
 * Renders a HTML choice of time formats.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */
function glz_prefs_timepicker_format($name, $val)
{
    $vals = array(
        'true'        => '24 hours',
        'false'       => '12 hours'
    );
    return selectInput($name, $vals, $val, '', '', $name);
}

/**
 * Renders a medium-width HTML &lt;input&gt; element.
 *
 * @param  string $name HTML name and id of the text box
 * @param  string $val  Initial (or current) content of the text box
 * @return string HTML
 */
function glz_text_input_medium($name, $val)
{
    return text_input($name, $val, INPUT_MEDIUM);
}

/**
 * Renders a small-width HTML &lt;input&gt; element.
 *
 * @param  string $name HTML name and id of the text box
 * @param  string $val  Initial (or current) content of the text box
 * @return string HTML
 */
function glz_text_input_small($name, $val)
{
    return text_input($name, $val, INPUT_SMALL);
}

/**
 * Renders a regular-width HTML &lt;input&gt; element for an URL with path check.
 *
 * @param  string $name HTML name and id of the text box
 * @param  string $val  Initial (or current) content of the text box
 * @return string HTML
 */
function glz_url_input($name, $val)
{
    global $use_minified;
    $min = ($use_minified === true) ? '.min' : '';

    // Array of possible expected url inputs and corresponding files and error-msg-stubs
    // 'pref_name' => array('/targetfilename.ext', 'gTxt_folder (inserted into error msg)')
    // paths do not require a target filename, urls do.
    $glz_cf_url_inputs = array(
        'glz_cf_css_asset_url'       => array('/glz_custom_fields'.$min.'.css', 'glz_cf_css_folder'),
        'glz_cf_js_asset_url'        => array('/glz_custom_fields'.$min.'.js',  'glz_cf_js_folder'),
        'glz_cf_datepicker_url'      => array('/datePicker'.$min.'.js',         'glz_cf_datepicker_folder'),
        'glz_cf_timepicker_url'      => array('/timePicker'.$min.'.js',         'glz_cf_timepicker_folder'),
        'glz_cf_custom_scripts_path' => array('',                               'glz_cf_custom_folder')
    );
    // File url or path to test = prefs_val (=url/path) + targetfilename (first item in array)
    $glz_cf_url_to_test          = $val.$glz_cf_url_inputs[$name][0];
    // gTxt string ref for folder name for error message (second item in array)
    $glz_cf_url_input_error_stub = $glz_cf_url_inputs[$name][1];

    // See if url / path is readable. If not, produce error message
    if ($glz_cf_url_to_test) {
        // permit relative URLs but conduct url test with hostname
        if (strstr($name, 'url')) {
            $glz_cf_url_to_test = glz_relative_url($glz_cf_url_to_test, $addhost = true);
        }
        $url_error = (@fopen($glz_cf_url_to_test, "r")) ? '' : gTxt('glz_cf_folder_error', array('{folder}' => gTxt($glz_cf_url_input_error_stub) ));
    }

    // Output regular-width text_input for url
    $out  = fInput('text', $name, $val, '', '', '', INPUT_REGULAR, '', $name);
    // Output error notice if one exists
    $out .= ($url_error) ? '<br><span class="error"><span class="ui-icon ui-icon-alert"></span> '.$url_error.'</span>' : '';

    return $out;
}
