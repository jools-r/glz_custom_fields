<?php

global $event, $step, $use_minified;

$use_minified = true;
// DEBUG: set to false to load regular (non-minified) js and css files

// Initialise reused variables
init_glz_custom_fields();

if (@txpinterface === 'admin') {

    // glz admin panels / events
    $glz_admin_events = array(
        'article',
        'prefs',
        'glz_custom_fields',
        'plugin_prefs.glz_custom_fields'
    );

    // Check if all tables exist and everything is setup properly
    add_privs('glz_custom_fields_install', '1');
    register_callback('glz_custom_fields_install', 'plugin_lifecycle.glz_custom_fields', 'installed');

    // Restrict css/js + pre-save to relevant admin pages only
    if (in_array($event, $glz_admin_events)) {

        // Add CSS & JS to admin head area
        add_privs('glz_custom_fields_inject_css_js', '1,2,3,4,5,6');
        register_callback('glz_custom_fields_inject_css_js', 'admin_side', 'head_end');

        // Write tab: array -> string conversion on save/create
        if (($step === 'edit') || ($step === 'create')) {
            add_privs('glz_custom_fields_before_save', '1,2,3,4,5,6');
            register_callback('glz_custom_fields_before_save', 'article', '', 1);
        }
    }

    // Custom fields tab under extensions
    add_privs('glz_custom_fields', '1,2');
    register_tab('extensions', 'glz_custom_fields', gTxt('glz_cf_tab_name'));
    register_callback('glz_custom_fields', 'glz_custom_fields');

    // Plugin preferences
    add_privs('plugin_prefs.glz_custom_fields', '1,2');
    register_callback('glz_custom_fields_preferences', 'plugin_prefs.glz_custom_fields');

    // Replace default custom fields with modified glz custom fields
    add_privs('glz_custom_fields_replace', '1,2,3,4,5,6');
    // -> custom fields
    register_callback('glz_custom_fields_replace', 'article_ui', 'custom_fields');
    // -> textareas
    register_callback('glz_custom_fields_replace', 'article_ui', 'body');
}

// -------------------------------------------------------------
// Main function: generates the content for Extensions > Custom Fields
function glz_custom_fields()
{
    global $event, $all_custom_sets, $prefs;
    $msg = '';

    // We have $_POST, let's see if there is any CRUD
    if ($_POST) {
        $incoming = stripPost();
        // DEBUG
        // die(dmp($incoming));

        extract($incoming);

        // Create an empty $value if it's not set in the $_POST
        if (!isset($value)) {
            $value = '';
        }

        // Delete a new custom field
        if (gps('delete')) {
            glz_custom_fields_MySQL("delete", $custom_set, "txp_prefs");
            glz_custom_fields_MySQL("delete", $custom_set, "txp_lang");
            glz_custom_fields_MySQL("delete", $custom_set, "custom_fields");
            glz_custom_fields_MySQL("delete", glz_custom_number($custom_set), "textpattern");

            $msg = gTxt('glz_cf_deleted', array('{custom_set_name}' => $custom_set_name));
        }

        // Reset one of the mighty 10 standard custom fields
        if (gps('reset')) {
            glz_custom_fields_MySQL("reset", $custom_set, "txp_prefs");
            glz_custom_fields_MySQL("delete", $custom_set, "custom_fields");
            glz_custom_fields_MySQL(
                "reset",
                glz_custom_number($custom_set),
                "textpattern",
                array(
                    'custom_set_type' => $custom_set_type,
                    'custom_field' => glz_custom_number($custom_set)
                )
            );

            $msg = gTxt('glz_cf_reset', array('{custom_set_name}' => $custom_set_name));
        }

        // Add a new custom field
        if (gps("custom_field_number")) {
            $custom_set_name = gps("custom_set_name");
            $custom_field_number = gps("custom_field_number");

            // A name has been specified
            if (!empty($custom_set_name)) {
                $custom_set_name = glz_clean_string($custom_set_name);
                $custom_set = "custom_".intval($custom_field_number)."_set";

                $name_exists = glz_check_custom_set_name($all_custom_sets, $custom_set_name, $custom_set);

                // If name doesn't exist
                if ($name_exists == false) {
                    glz_custom_fields_MySQL(
                        "new",
                        $custom_set_name,
                        "txp_prefs",
                        array(
                            'custom_field_number' => $custom_field_number,
                            'custom_set_type'     => $custom_set_type,
                            'custom_set_position' => $custom_set_position
                        )
                    );
                    glz_custom_fields_MySQL(
                        "new",
                        $custom_set_name,
                        "txp_lang",
                        array(
                            'custom_field_number' => $custom_field_number,
                            'lang'                => $GLOBALS['prefs']['language']
                        )
                    );
                    glz_custom_fields_MySQL(
                        "new",
                        $custom_set_name,
                        "textpattern",
                        array(
                            'custom_field_number' => $custom_field_number,
                            'custom_set_type'     => $custom_set_type
                        )
                    );
                    // There are custom fields for which we do not need to touch custom_fields table
                    if (!in_array($custom_set_type, array("textarea", "text_input"))) {
                        glz_custom_fields_MySQL(
                            "new",
                            $custom_set_name,
                            "custom_fields",
                            array(
                                'custom_field_number' => $custom_field_number,
                                'value'               => $value
                            )
                        );
                    }
                    $msg = gTxt('glz_cf_created', array('{custom_set_name}' => $custom_set_name));
                } else {
                    // Name exists, abort
                    $msg = array(gTxt('glz_cf_exists', array('{custom_set_name}' => $custom_set_name)), E_ERROR);
                }
            } else {
                // No name given
                $msg = array(gTxt('glz_cf_no_name'), E_ERROR);
            }
        }

        // Edit an existing custom field
        if (gps('save')) {
            if (!empty($custom_set_name)) {
                $custom_set_name = glz_clean_string($custom_set_name);

                $name_exists = glz_check_custom_set_name($all_custom_sets, $custom_set_name, $custom_set);
                // If name doesn't exist we'll need to create a new custom_set
                if ($name_exists == false) {
                    glz_custom_fields_MySQL(
                        "update",
                        $custom_set,
                        "txp_prefs",
                        array(
                            'custom_set_name'     => $custom_set_name,
                            'custom_set_type'     => $custom_set_type,
                            'custom_set_position' => $custom_set_position
                        )
                    );

                    // Custom sets need to be changed based on their type
                    glz_custom_fields_MySQL(
                        "update",
                        $custom_set,
                        "textpattern",
                        array(
                            'custom_set_type' => $custom_set_type,
                            'custom_field' => glz_custom_number($custom_set)
                        )
                    );

                    // For textareas we do not need to touch custom_fields table
                    if ($custom_set_type != "textarea") {
                        glz_custom_fields_MySQL("delete", $custom_set, "custom_fields");
                        glz_custom_fields_MySQL(
                            "new",
                            $custom_set_name,
                            "custom_fields",
                            array(
                                'custom_set'  => $custom_set,
                                'value'       => $value
                            )
                        );
                    }

                    $msg = gTxt('glz_cf_updated', array('{custom_set_name}' => $custom_set_name));
                } else {
                    // Name exists, abort
                    $msg = array(gTxt('glz_cf_exists', array('{custom_set_name}' => $custom_set_name)), E_ERROR);
                }
            } else {
                $msg = array(gTxt('glz_cf_no_name'), E_ERROR);
            }
        }
    }

    // CUSTOM FIELDS List
    // ––––––––––––––––--

    pagetop(gTxt('glz_cf_tab_name'), $msg);

    echo '<div class="txp-layout">
    <div class="txp-layout-2col">
        <h1 class="txp-heading">'.gTxt('glz_cf_tab_name').'</h1>
    </div>
    <div class="txp-layout-2col">
        <a class="glz-cf-setup-switch" href="?event=plugin_prefs.glz_custom_fields">'.gTxt('glz_cf_setup_prefs').'</a>
    </div>
</div>';

    // Need to re-fetch data since things modified
    $all_custom_sets = glz_custom_fields_MySQL("all");

    // The table with all custom fields follows
    echo
    n.'<div class="txp-listtables">'.n.
    '    <table class="txp-list glz_custom_fields">'.n.
    '        <thead>'.n.
    '            <tr>'.n.
    '                <th scope="col">'.gTxt('glz_cf_col_id').'</th>'.n.
    '                <th scope="col">'.gTxt('glz_cf_col_position').'</th>'.n.
    '                <th scope="col">'.gTxt('glz_cf_col_name').'</th>'.n.
    '                <th scope="col">'.gTxt('glz_cf_col_type').'</th>'.n.
    '                <th scope="col">'.gTxt('glz_cf_col_options').'</th>'.n.
    '            </tr>'.n.
    '        </thead>'.n.
    '        <tbody>'.n;

    // Looping through all our custom fields to build the table
    $i = 0;
    foreach ($all_custom_sets as $custom => $custom_set) {
        // First 10 fields cannot be deleted, just reset
        if ($i < 10) {
            // Only show 'reset' for custom fields that are set
            $reset_delete = ($custom_set['name']) ?
                glz_form_buttons("reset", gTxt('glz_cf_action_reset'), $custom, htmlspecialchars($custom_set['name']), $custom_set['type'], '', 'return confirm(\''.gTxt('glz_cf_confirm_reset', array('{custom}' => $custom )).'\');') :
                null;
        } else {
            $reset_delete = glz_form_buttons("delete", gTxt('glz_cf_action_delete'), $custom, htmlspecialchars($custom_set['name']), $custom_set['type'], '', 'return confirm(\''.gTxt('glz_cf_confirm_delete', array('{custom}' => $custom )).'\');');
        }

        $edit = glz_form_buttons("edit", gTxt('glz_cf_action_edit'), $custom, htmlspecialchars($custom_set['name']), $custom_set['type'], $custom_set['position']);

        echo
        '            <tr>'.n.
        '                <th class="custom-set-id" scope="row">'.$custom_set['id'].'</th>'.n.
        '                <td class="custom-set-position">'.$custom_set['position'].'</td>'.n.
        '                <td class="custom-set-name">'.$custom_set['name'].'</td>'.n.
        '                <td class="type">'.(($custom_set['name']) ? gTxt('glz_cf_'.$custom_set['type']) : '').'</td>'.n.
        '                <td class="events">'.$edit.sp.$reset_delete.'</td>'.n.
        '            </tr>'.n;

        $i++;
    }

    echo
    '        </tbody>'.n.
    '    </table>'.n;
    '</div>'.n;

    // EDIT / ADD Panel
    // ––––––––––––––––

    // Variables for edit or add form
    $legend = gps('edit') ?
        gTxt('glz_cf_action_edit_title', array('{custom_set_name}' => gTxt('glz_cf_title').' #'.glz_custom_digit(gps('custom_set')))) :
        gTxt('glz_cf_action_new_title');

    $custom_field = gps('edit') ?
        '<input name="custom_set" value="'.gps('custom_set').'" type="hidden" />' :
        '<input name="custom_field_number" value="'.glz_custom_next($all_custom_sets).'" type="hidden" />';

    $custom_set = gps('edit') ?
        gps('custom_set') :
        null;

    $custom_name = gps('edit') ?
        gps('custom_set_name') :
        null;

    $custom_set_position = gps('edit') ?
        gps('custom_set_position') :
        null;

    $arr_custom_set_types = glz_custom_set_types();

    $custom_set_types = null;
    foreach ($arr_custom_set_types as $custom_type_group => $custom_types) {
        $custom_set_types .= '<optgroup label="'.ucfirst($custom_type_group).'">'.n;
        foreach ($custom_types as $custom_type) {
            $selected = (gps('edit') && gps('custom_set_type') == $custom_type) ?
                ' selected="selected"' :
                null;
            $custom_set_types .= '<option value="'.$custom_type.'"'.$selected.'>'.gTxt('glz_cf_'.$custom_type).'</option>'.n;
        }
        $custom_set_types .= '</optgroup>'.n;
    }

    // Fetch values for this custom field
    if (gps('edit')) {
        if ($custom_set_type == "text_input") {
            $arr_values = glz_custom_fields_MySQL('all_values', glz_custom_number($custom_set), '', array('custom_set_name' => $custom_set_name, 'status' => 4));
        } else {
            $arr_values = glz_custom_fields_MySQL("values", $custom_set, '', array('custom_set_name' => $custom_set_name));
        }

        $values = ($arr_values) ? implode("\r\n", $arr_values) : '';
    } else {
        $values = '';
    }

    $action = gps('edit') ?
        '<input name="save" value="'.gTxt('save').'" type="submit" class="publish" />' :
        '<input name="add_new" value="'.gTxt('glz_cf_add_new_cf').'" type="submit" class="publish" />';
    // This needs to be different for a script
    $value = (isset($custom_set_type) && $custom_set_type == "custom-script") ?
        '<input type="text" name="value" id="value" value="'.$values.'" /><br>
         <span class="information">'.gTxt('glz_cf_js_script_msg').'</span>' :
        '<textarea name="value" id="value">'.$values.'</textarea><br>
         <span class="information">'.gTxt('glz_cf_edit_on_separate_line').'<br>
         '.gTxt('glz_cf_edit_one_default_allowed').'</span>';

    // Build the form
    echo
'<form method="post" class="txp-edit" action="index.php" id="add_edit_custom_field">'.n.
'<input name="event" value="glz_custom_fields" type="hidden" />'.n.
    $custom_field.n.
'    <h2>'.$legend.'</h2>'.n.
'    <div class="txp-form-field glz-cf-name">
        <div class="txp-form-field-label"><label for="custom_set_name">'.gTxt('glz_cf_edit_name').'</label></div>
        <div class="txp-form-field-value"><input type="text" name="custom_set_name" value="'.htmlspecialchars($custom_name).'" id="custom_set_name" />
        <br><span class="information">'.gTxt('glz_cf_edit_name_hint').'</span></div>
    </div>'.n.
'    <div class="txp-form-field glz-cf-type">
        <div class="txp-form-field-label"><label for="custom_set_type">'.gTxt('glz_cf_edit_type').'</label></div>
        <div class="txp-form-field-value"><select name="custom_set_type" id="custom_set_type">
'.      $custom_set_types.'
        </select></div>
    </div>'.n.
'    <div class="txp-form-field glz-cf-position">
        <div class="txp-form-field-label"><label for="custom_set_position">'.gTxt('glz_cf_edit_position').'</label></div>
        <div class="txp-form-field-value"><input type="text" name="custom_set_position" value="'.htmlspecialchars($custom_set_position).'" id="custom_set_position" />
        <br><span class="information">'.gTxt('glz_cf_edit_position_hint').'</span></div>
    </div>'.n.
'    <div class="txp-form-field glz-cf-value">
        <div class="txp-form-field-label"><label for="value">'.gTxt('glz_cf_edit_value').'</label></div>
        <div class="txp-form-field-value">'.$value.'</div>
    </div>'.n.
'    <p class="txp-edit-actions">'.n.
'        '.$action.n.
'    </p>'.n.
'</form>'.n;
}


// -------------------------------------------------------------
// glz_custom_fields preferences
function glz_custom_fields_preferences() {

    global $event;
    $msg = '';

    if ( $_POST && gps('save') ) {
        glz_custom_fields_MySQL("set_plugin_prefs", $_POST['glz_custom_fields_prefs']);
        $msg = gTxt('glz_cf_preferences_updated');
    }
    pagetop(gTxt('glz_cf_prefpane_title'), $msg);
    // need to re-fetch from db because this has changed since $prefs has been populated
    $current_preferences = glz_custom_fields_MySQL('get_plugin_prefs');

    // custom_fields
    $arr_values_ordering = array(
        'ascending'   => gTxt('glz_cf_prefs_value_asc'),
        'descending'  => gTxt('glz_cf_prefs_value_desc'),
        'custom'      => gTxt('glz_cf_prefs_value_custom')
    );
    $values_ordering = '<select name="glz_custom_fields_prefs[values_ordering]" id="glz_custom_fields_prefs_values_ordering" class="select-medium">';
    foreach ( $arr_values_ordering as $value => $title ) {
        $selected = ($current_preferences['values_ordering'] == $value) ? ' selected="selected"' : '';
        $values_ordering .= "<option value=\"$value\"$selected>$title</option>";
    }
    $values_ordering .= "</select>";

    $multiselect_size = '<input type="text" class="input-medium" name="glz_custom_fields_prefs[multiselect_size]" id="glz_custom_fields_prefs_multiselect_size" value="'.$current_preferences['multiselect_size'].'" />';

    $custom_scripts_path_error = ( @fopen($current_preferences['custom_scripts_path'], "r") ) ?
    '' :
    '<br><span class="error">'.gTxt('glz_cf_prefs_custom_scripts_path_error').'</span>';

    // jquery.datePicker
    $datepicker_url_error = ( @fopen($current_preferences['datepicker_url']."/datePicker.js", "r") ) ?
    '' :
    '<br><span class="error">'.gTxt('glz_cf_prefs_datepicker_url_error').'</span>';
    $arr_date_format = array("dd/mm/yyyy", "mm/dd/yyyy", "yyyy-mm-dd", "dd mm yy");
    $date_format = '<select name="glz_custom_fields_prefs[datepicker_format]" id="glz_custom_fields_prefs_datepicker_format" class="select-medium">';
    foreach ( $arr_date_format as $format ) {
        $selected = ($current_preferences['datepicker_format'] == $format) ? ' selected="selected"' : '';
        $date_format .= "<option value=\"$format\"$selected>$format</option>";
    }
    $date_format .= "</select>";

    $arr_days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
    $first_day = '<select name="glz_custom_fields_prefs[datepicker_first_day]" id="glz_custom_fields_prefs_datepicker_first_day" class="select-medium">';
    foreach ( $arr_days as $key => $day ) {
        $selected = ($current_preferences['datepicker_first_day'] == $key) ? ' selected="selected"' : '';
        $first_day .= "<option value=\"$key\"$selected>".gTxt('glz_cf_prefs_'.strtolower($day))."</option>";
    }
    $first_day .= "</select>";

    $start_date = '<input type="text" class="input-medium" name="glz_custom_fields_prefs[datepicker_start_date]" id="glz_custom_fields_prefs_datepicker_start_date" value="'.$current_preferences['datepicker_start_date'].'" />';

    // jquery.timePicker
    $timepicker_url_error = ( @fopen($current_preferences['timepicker_url']."/timePicker.js", "r") ) ?
    '' :
    '<br><span class="error">'.gTxt('glz_cf_prefs_timepicker_url_error').'</span>';
    $arr_time_format = array('true' => "24 hours", 'false' => "12 hours");
    $show_24 = '<select name="glz_custom_fields_prefs[timepicker_show_24]" id="glz_custom_fields_prefs_timepicker_show_24" class="select-medium">';
    foreach ( $arr_time_format as $value => $title ) {
        $selected = ($current_preferences['timepicker_show_24'] == $value) ? ' selected="selected"' : '';
        $show_24 .= "<option value=\"$value\"$selected>".gTxt('glz_cf_prefs_'.str_replace(" ", "_", $title))."</option>";
    }
    $show_24 .= "</select>";

    echo '<div class="txp-layout">
    <div class="txp-layout-2col">
        <h1 class="txp-heading">'.gTxt('glz_cf_prefpane_title').'</h1>
    </div>
    <div class="txp-layout-2col">
        <a class="glz-cf-setup-switch" href="?event=glz_custom_fields">'.gTxt('glz_cf_back_to_cf_window').'</a>
    </div>
</div>';

    // initialize replacement var
    if(!isset($gTxt)) {
        $gTxt = 'gTxt';
    }
    $out = <<<EOF
<form class="txp-edit" action="index.php" method="post">

<h2 class="pref-heading">Custom Fields</h2>

<div class="txp-form-field glz-cf-prefs-values-ordering">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_values_ordering">{$gTxt('glz_cf_prefs_value_ordering')}</label></div>
    <div class="txp-form-field-value">{$values_ordering}</div>
</div>

<div class="txp-form-field glz-cf-prefs-multiselect-size">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_multiselect_size">{$gTxt('glz_cf_prefs_multiselect_size')}</label></div>
    <div class="txp-form-field-value">{$multiselect_size}</div>
</div>

<div class="txp-form-field glz-cf-prefs-css-url">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_custom_scripts_path">{$gTxt('glz_cf_prefs_css_url')}</label></div>
    <div class="txp-form-field-value"><input type="text" class="input-large" name="glz_custom_fields_prefs[custom_scripts_path]" id="glz_custom_fields_prefs_custom_scripts_path" value="{$current_preferences['glz_cf_css_url']}" /></div>
</div>

<div class="txp-form-field glz-cf-prefs-js-url">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_custom_scripts_path">{$gTxt('glz_cf_prefs_js_url')}</label></div>
    <div class="txp-form-field-value"><input type="text" class="input-large" name="glz_custom_fields_prefs[custom_scripts_path]" id="glz_custom_fields_prefs_custom_scripts_path" value="{$current_preferences['glz_cf_js_url']}" /></div>
</div>

<div class="txp-form-field glz-cf-prefs-custom-scripts-path">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_custom_scripts_path">{$gTxt('glz_cf_prefs_custom_scripts_path')}</label></div>
    <div class="txp-form-field-value"><input type="text" class="input-large" name="glz_custom_fields_prefs[custom_scripts_path]" id="glz_custom_fields_prefs_custom_scripts_path" value="{$current_preferences['custom_scripts_path']}" /><br><span class="information">{$gTxt('glz_cf_edit_path_from_root')}</span>{$custom_scripts_path_error}</div>
</div>

<h2 class="pref-heading">{$gTxt('glz_cf_date-picker')} <a class="information" href="http://www.kelvinluck.com/assets/jquery/datePicker/v2/demo/index.html" title="A flexible unobtrusive calendar component for jQuery">jQuery datePicker</a></h2>

<div class="txp-form-field glz-cf-prefs-datepicker-url">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_datepicker_url">{$gTxt('glz_cf_prefs_datepicker_url')}</label></div>
    <div class="txp-form-field-value"><input type="text" class="input-large" name="glz_custom_fields_prefs[datepicker_url]" id="glz_custom_fields_prefs_datepicker_url" value="{$current_preferences['datepicker_url']}" />{$datepicker_url_error}</div>
</div>

<div class="txp-form-field glz-cf-prefs-datepicker-format">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_datepicker_format">{$gTxt('glz_cf_prefs_datepicker_format')}</label></div>
    <div class="txp-form-field-value">{$date_format}</div>
</div>

<div class="txp-form-field glz-cf-prefs-datepicker-first-day">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_datepicker_first_day">{$gTxt('glz_cf_prefs_datepicker_first_day')}</label></div>
    <div class="txp-form-field-value">{$first_day}</div>
</div>

<div class="txp-form-field glz-cf-prefs-datepicker-start-date">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_datepicker_start_date">{$gTxt('glz_cf_prefs_datepicker_start_date')}</label></div>
    <div class="txp-form-field-value">{$start_date}<br><span class="information">{$gTxt('glz_cf_prefs_datepicker_start_date_info')}</span></div>
</div>

<h2 class="pref-heading">{$gTxt('glz_cf_time-picker')} <a class="information" href="http://labs.perifer.se/timedatepicker/" title="jQuery time picker">jQuery timePicker</a></h2>

<div class="txp-form-field glz-cf-prefs-timepicker-url">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_timepicker_url">{$gTxt('glz_cf_prefs_timepicker_url')}</label></div>
    <div class="txp-form-field-value"><input type="text" class="input-large" name="glz_custom_fields_prefs[timepicker_url]" id="glz_custom_fields_prefs_timepicker_url" value="{$current_preferences['timepicker_url']}" />{$timepicker_url_error}</div>
</div>

<div class="txp-form-field glz-cf-prefs-timepicker-start-time">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_timepicker_start_time">{$gTxt('glz_cf_prefs_timepicker_start_time')}</label></div>
    <div class="txp-form-field-value"><input type="text" name="glz_custom_fields_prefs[timepicker_start_time]" id="glz_custom_fields_prefs_timepicker_start_time" value="{$current_preferences['timepicker_start_time']}" /></div>
</div>

<div class="txp-form-field glz-cf-prefs-timepicker-end-time">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_timepicker_end_time">{$gTxt('glz_cf_prefs_timepicker_end_time')}</label></div>
    <div class="txp-form-field-value"><input type="text" name="glz_custom_fields_prefs[timepicker_end_time]" id="glz_custom_fields_prefs_timepicker_end_time" value="{$current_preferences['timepicker_end_time']}" /></div>
</div>

<div class="txp-form-field glz-cf-prefs-timepicker-step">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_timepicker_step">{$gTxt('glz_cf_prefs_timepicker_step')}</label></div>
    <div class="txp-form-field-value"><input type="text" name="glz_custom_fields_prefs[timepicker_step]" id="glz_custom_fields_prefs_timepicker_step" value="{$current_preferences['timepicker_step']}" /></div>
</div>

<div class="txp-form-field glz-cf-prefs-timepicker-show-24">
    <div class="txp-form-field-label"><label for="glz_custom_fields_prefs_timepicker_show_24">{$gTxt('glz_cf_prefs_timepicker_format')}</label></div>
    <div class="txp-form-field-value">{$show_24}</div>
</div>

<p class="txp-edit-actions">
    <input class="publish" type="submit" name="save" value="{$gTxt('save')}" />
    <input type="hidden" name="event" value="plugin_prefs.glz_custom_fields" />
</p>
</form>
EOF;

    echo $out;
}

?>
