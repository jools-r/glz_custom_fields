<?php

global $event, $step, $use_minified, $txp_permissions;

// DEBUG: set to false to load regular (non-minified) js and css files
$use_minified = true;

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
    add_privs('prefs.glz_custom_f', '1');
    add_privs('prefs.glz_custom_f.glz_cf_datepicker', '1');
    add_privs('prefs.glz_custom_f.glz_cf_timepicker', '1');
    // Disable regular customs preferences (remove privs)
    $txp_permissions['prefs.custom'] = '';

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

    // CUSTOM FIELDS Pane
    // ––––––––––––––––--

    pagetop(gTxt('glz_cf_tab_name'), $msg);

    $contentBlock = tag_start('div', array('class' => 'txp-layout')).
            tag_start('div', array('class' => 'txp-layout-2col')).
                hed(gTxt('glz_cf_tab_name'), 1, array('class' => 'txp-heading')).
            tag_end('div').
            tag_start('div', array('class' => 'txp-layout-2col')).
                href(gTxt('tab_preferences'), '?event=prefs#prefs_group_glz_custom_f', array('class' => 'glz-cf-setup-switch')).
            tag_end('div').
        tag_end('div'); // end .txp-layout

    // Need to re-fetch data since things modified
    $all_custom_sets = glz_custom_fields_MySQL("all");

    // CUSTOM FIELDS Table

    // Column headings
    $headers = array(
        'id'        => 'id',
        'position'  => 'position',
        'name'      => 'name',
        'type'      => 'type',
        'options'   => 'options'
    );

    foreach ($headers as $header => $column_head) {
        $head_row .= column_head(array(
                'options' => array('class' => trim('txp-list-col-'.$header)),
                'value'   => $column_head,
                'sort'    => $header
            )
        );
    }

    // Table head
    $contentBlock .= tag_start('div', array('class' => 'txp-listtables')).
                n.tag_start('table', array('class' => 'txp-list glz_custom_fields')).
                n.tag_start('thead').
                tr($head_row).
                n.tag_end('thead');

    // Table body
    $contentBlock .= n.tag_start('tbody');

    // Custom field table rows
    $i = 0;
    foreach ($all_custom_sets as $custom => $custom_set) {
        // First 10 fields cannot be deleted, just reset
        if ($i < 10) {
            // Only show 'reset' for custom fields that are set
            $reset_delete = ($custom_set['name']) ?
                    glz_form_buttons("reset",
                        gTxt('reset'),
                        $custom,
                        htmlspecialchars($custom_set['name']),
                        $custom_set['type'],
                        '',
                        'return confirm(\''.gTxt('glz_cf_confirm_reset', array('{custom}' => 'ID# '.glz_custom_digit($custom).': '.htmlspecialchars($custom_set['name']) )).'\')'
                    )
                :
                    null;
        } else {
            $reset_delete =
                    glz_form_buttons("delete",
                        gTxt('delete'),
                        $custom,
                        htmlspecialchars($custom_set['name']),
                        $custom_set['type'],
                        '',
                        'return confirm(\''.gTxt('glz_cf_confirm_delete', array('{custom}' => 'ID# '.glz_custom_digit($custom).': '.htmlspecialchars($custom_set['name']) )).'\')'
                    );
        }

        $edit =
                    glz_form_buttons("edit",
                        gTxt('edit'),
                        $custom,
                        htmlspecialchars($custom_set['name']),
                        $custom_set['type'],
                        $custom_set['position']
                    );

        $contentBlock .= tr(
            hCell(
                $custom_set['id'], '', array('class' => 'txp-list-col-id')
            ).
            td(
                $custom_set['position'], '', 'txp-list-col-position'
            ).
            td(
                $custom_set['name'], '', 'txp-list-col-name'
            ).
            td(
                (($custom_set['name']) ? gTxt('glz_cf_'.$custom_set['type']) : ''), '', 'txp-list-col-type'
            ).
            td(
                $edit.sp.$reset_delete, '', 'txp-list-col-options'
            )
        );
        $i++;
    }

    $contentBlock .= n.tag_end('tbody').
        n.tag_end('table').
        n.tag_end('div'); // End of .txp-listtables.

    echo $contentBlock;

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
