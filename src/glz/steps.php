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
#   STEPS – List, Save, Edit, Reset, Delete
#
##################


/**
 * Renders the main custom fields list pane
 *
 * @param  string $msg  Success, error or warning message shown by Textpattern
 * @return string HTML  Table of custom fields
 */

function glz_cf_list($msg='', $debug = false)
{
    global $event, $step;

    pageTop(gTxt('glz_cf_tab_name'), $msg);

    // Retrieve array of all custom fields properties
    $all_custom_sets = glz_db_get_all_custom_sets();

    $out = array();

    $out[] =
        tag_start('div', array('class' => 'txp-layout')).
            tag_start('div', array('class' => 'txp-layout-2col')).
                hed(gTxt('glz_cf_tab_name'), 1, array('class' => 'txp-heading')).
            tag_end('div').
            tag_start('div', array('class' => 'txp-layout-2col')).
                href(gTxt('tab_preferences'), '?event=prefs#prefs_group_glz_custom_f', array('class' => 'glz-cf-setup-switch')).
            tag_end('div').
            tag_start('div', array('class' => 'txp-layout-1col', 'id' => $event.'_container'));

    // 'Add new custom field' button
    $out[] =
        n.tag(
            href(
                gTxt('glz_cf_add_new_cf'),
                array(
            'event' => 'glz_custom_fields',
            'step'  => 'add',
            '_txp_token' => form_token(),
                ),
                array(
                    'class' => 'txp-button',
                    'title' => gTxt('glz_cf_add_new_cf')
                )
            ),
            'div',
            array('class' => 'txp-control-panel')
        );

    // Column headings
    $headers = array(
        'position'  => 'position',
        'id'        => 'id',
        'name'      => 'name',
        'title'     => 'title',
        'type'      => 'type',
        'options'   => 'options'
    );
    $head_row = '';

    foreach ($headers as $header => $column_head) {
        $head_row .= column_head(
            array(
                'options' => array('class' => trim('txp-list-col-'.$header)),
                'value'   => $column_head,
                'sort'    => $header
            )
        );
    }

    // Table start
    $out[] =
        tag_start('div', array('class' => 'txp-listtables')).
        n.tag_start('table', array('class' => 'txp-list--no-options')).
        n.tag_start('thead').
            tr($head_row).
        n.tag_end('thead').
        n.tag_start('tbody');


    // Table body rows
    foreach ($all_custom_sets as $custom => $custom_set) {

        // Edit link (with 'name' and 'id' as link text)
        foreach (array('name', 'id') as $text) {
            $edit_link[$text] = href(
                $custom_set[$text],
                array(
                    'event'      => 'glz_custom_fields',
                    'step'       => 'edit',
                    'ID'         => $custom_set['id'],
                    '_txp_token' => form_token(),
                ),
                array(
                    'class'       => 'edit-link',
                    'title'       => gTxt('glz_cf_action_edit_title', array('{custom_set_name}' => gTxt('glz_cf_title').' #'.glz_custom_digit($custom)))
                )
            );
        }

        // Reset or delete buttons
        if ($custom_set['id'] < 11) {
            $delete_link = href(
                gTxt('reset'),
                array(
                    'event'      => 'glz_custom_fields',
                    'step'       => 'reset',
                    'ID'         => $custom_set['id'],
                    '_txp_token' => form_token(),
                ),
                array(
                    'class'       => 'ui-icon ui-icon-trash',
                    'title'       => gTxt('reset'),
                    'data-verify' => gTxt('glz_cf_confirm_reset', array('{custom}' => 'ID# '.glz_custom_digit($custom).': '.htmlspecialchars($custom_set['name']) )),
                )
            );
        } else {
            $delete_link = href(
                gTxt('delete'),
                array(
                    'event'      => 'glz_custom_fields',
                    'step'       => 'delete',
                    'ID'         => $custom_set['id'],
                    '_txp_token' => form_token(),
                ),
                array(
                    'class'       => 'ui-icon ui-icon-close',
                    'title'       => gTxt('delete'),
                    'data-verify' => gTxt('glz_cf_confirm_delete', array('{custom}' => 'ID# '.glz_custom_digit($custom).': '.htmlspecialchars($custom_set['name']) )),
                )
            );
        }

        $custom_label = (empty($custom_set['title']) ? gTxt('undefined') : $custom_set['title']);

        if (!empty($custom_set["name"])) {
            $out[] =
                tr(
                    hCell(
                        $custom_set['position'],
                        '',
                        array('class' => 'txp-list-col-position')
                    ).
                    td(
                        $edit_link['id'],
                        '',
                        'txp-list-col-id'
                    ).
                    td(
                        $edit_link['name'],
                        '',
                        'txp-list-col-name'
                    ).
                    td(
                        $custom_label.(empty($custom_set['instructions']) ? '' : ' <span class="cf-instructions ui-icon ui-icon-clipboard" title="'.$custom_set['instructions'].'"></span>'),
                        '',
                        'txp-list-col-title'.(empty($custom_set['title']) ? ' disabled' : '')
                    ).
                    td(
                        (($custom_set['name']) ? gTxt('glz_cf_'.$custom_set['type']) : ''),
                        '',
                        'txp-list-col-type'
                    ).
                    td(
                        $delete_link,
                        '',
                        'txp-list-col-options'
                    ),
                    array('id' => 'glz_custom_'.$custom_set['id'].'_set')
                );
        }
    }

    // Table end
    $out[] =
        n.tag_end('tbody').
        n.tag_end('table').
        n.tag_end('div'). // End of .txp-listtables.
        pluggable_ui('customfields_ui', 'table_end', '').
        tag_end('div'). // End of .txp-layout-1col.
        tag_end('div'); // End of .txp-layout.

    // Render panel
    if (is_array($out)) {
        $out = implode(n, $out);
    }
    echo $out;
}

/**
 * Add a new custom field.
 * Finds the next vacant custom field and passes it to the edit form
 *
 * @param  string $msg  Pass-thru of success, error or warning message shown by Textpattern
 */
function glz_cf_add($msg='', $debug = false)
{
    // Get next free custom field id
    $next_free_cf_id = glz_next_empty_custom();
    // Pass into edit pane
    glz_cf_edit($msg, $next_free_cf_id);
}


/**
 * Edit a custom field / Add a new custom field.
 * Retrieves values for ID url variable (or id from glz_cf_add)
 *
 * @param  string  $msg  Success, error or warning message shown by Textpattern
 * @param  integer  $id  passed from glz_cf_add
 */
function glz_cf_edit($msg='', $id='', $debug = false)
{
    global $event, $step, $prefs;
    // get ID from URL of $id not supplied (e.g. by "add" step)
    if (empty($id)) {
        $id = gps('ID');
    }
    // Check ID is properly formed, else back to list
    if (!intval($id)) {
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    }
    // If editing (not adding), check ID actually exists, else back to list
    if (($step === 'edit') && (!get_pref('custom_'.$id.'_set'))) {
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    };

    if ($step === 'edit') {
        // 'Edit' Step: retrieve array of all custom field properties
        $custom_field = glz_db_get_custom_set($id);
        $panel_title = gTxt('glz_cf_action_edit_title', array('{custom_set_name}' => gTxt('glz_cf_title').' #'.$custom_field['id']));
    } else {
        // 'Add' step: set available starting properties, null others
        $custom_field = array();
        $custom_field['id'] = $id;
        $custom_field['custom_set'] = 'custom_'.$id.'_set';
        foreach (array('name', 'position', 'type', 'title', 'instructions') as $key) {
            $custom_field[$key] = null;
        }
        $panel_title = gTxt('glz_cf_action_new_title');
    }

    // Pass existing name in case custom field is renamed
    $existing_name = ($step === 'edit') ?
        hInput('custom_set_name_old', $custom_field['name']) :
        '';
    // Pass in existing position as hidden input (change position value in the list)
    $existing_position = (($step === 'edit') && ($prefs['glz_cf_use_sortable'] == '1')) ?
        hInput('custom_set_position', $custom_field['position']) :
        '';

    // Custom field types drop-down
    $arr_custom_set_types = glz_custom_set_types();
    $custom_set_types = null;
    foreach ($arr_custom_set_types as $custom_type_group => $custom_types) {
        $custom_set_types .= '<optgroup label="'.gTxt('glz_cf_types_'.$custom_type_group).'">'.n;
        foreach ($custom_types as $custom_type) {
            $selected = ($custom_field['type'] == $custom_type) ?
                ' selected="selected"' :
                null;
            $custom_set_types .= '<option value="'.$custom_type.'" dir="auto"'.$selected.'>'.gTxt('glz_cf_'.$custom_type).'</option>'.n;
        }
        $custom_set_types .= '</optgroup>'.n;
    }

    // Fetch (multiple) type values for this custom field
    if ($step === 'edit') {
        if ($custom_field['type'] == "text_input") {
            $arr_values = glz_db_get_all_existing_cf_values(glz_custom_number($custom_field['custom_set']), array('custom_set_name' => $custom_field['name'], 'status' => 4));
        } else {
            $arr_values = glz_db_get_custom_field_values($custom_field['custom_set'], array('custom_set_name' => $custom_field['name']));
        }
        $values = ($arr_values) ? implode("\r\n", $arr_values) : '';
    } else {
        $values = '';
    }
    // This needs to be different for a script
    if (isset($custom_field['type']) && $custom_field['type'] == "custom-script") {
        $value = fInput('text', 'value', $values, '', '', '', '', '', 'value');
        $value_instructions = 'glz_cf_js_script_msg';
    } else {
        $value = text_area('value', 0, 0, $values, 'value');
        $value_instructions = 'glz_cf_multiple_values_instructions';
    }

    $action = ($step === 'edit') ?
        fInput('submit', 'save', gTxt('save'), 'publish') :
        fInput('submit', 'add_new', gTxt('glz_cf_add_new_cf'), 'publish');

    // Build the form
    pageTop($panel_title, $msg);
    // dmp($custom_field);

    $out = array();

    $out[] = hed($panel_title, 2);
    $out[] =
        inputLabel(
                'custom_set_name',
                fInput('text', 'custom_set_name', htmlspecialchars($custom_field['name']), '', '', '', 28, '', 'custom_set_name'),
                'glz_cf_edit_name',
                array(
                    0 => '',
                    1 => 'glz_cf_edit_name_hint' // Inline help string
                )
            ).
        inputLabel(
                'custom_set_title',
                fInput('text', 'custom_set_title', htmlspecialchars($custom_field['title']), '', '', '', INPUT_REGULAR, '', 'custom_set_title'),
                'glz_cf_edit_title',
                array(
                    0 => '',
                    1 => 'glz_cf_edit_title_hint' // Inline help string
                )
            ).
        inputLabel(
                'custom_set_instructions',
                fInput('text', 'custom_set_instructions', htmlspecialchars($custom_field['instructions']), '', '', '', INPUT_REGULAR, '', 'custom_set_instructions'),
                'glz_cf_edit_instructions',
                array(
                    0 => '',
                    1 => 'glz_cf_edit_instructions_hint' // Inline help string
                )
            ).
        inputLabel(
                'custom_set_type',
                '<select name="custom_set_type" id="custom_set_type">'.$custom_set_types.'</select>',
                'glz_cf_edit_type',
                array(
                    0 => '',
                    1 => 'glz_cf_js_configure_msg'  // Inline help string
                )
            ).
        ($prefs['glz_cf_use_sortable'] == '0' ?
            inputLabel(
                    'custom_set_position',
                    fInput('text', 'custom_set_position', htmlspecialchars($custom_field['position']), '', '', '', INPUT_MEDIUM, '', 'custom_set_position'),
                    'glz_cf_edit_position',
                    array(
                        0 => '',
                        1 => 'glz_cf_edit_position_hint'  // Inline help string
                    )
                )
        : '').
        inputLabel(
                'custom_set_value',
                $value,
                'glz_cf_edit_value',
                array(
                    0 => '',
                    1 => $value_instructions  // Inline help string
                )
            ).
        n.tag(gTxt('glz_cf_js_script_msg'), 'span', array('class' => 'glz-custom-script-msg hidden')).
        n.tag(gTxt('glz_cf_js_textarea_msg'), 'span', array('class' => 'glz-custom-textarea-msg hidden')).
        eInput('glz_custom_fields').
        sInput('save').
        hInput('custom_set', $custom_field['custom_set']).
        hInput('custom_field_number', $custom_field['id']).
        $existing_name.
        $existing_position.
        graf(
            sLink('glz_custom_fields', '', gTxt('cancel'), 'txp-button').
            $action,
            array('class' => 'txp-edit-actions')
        );

    echo form(join('', $out), '', '', 'post', 'txp-edit', '', 'add_edit_custom_field');
}


/**
 * Saves a new or existing custom field
 * Retrieves incoming $POST variables
 *
 * @param  string  $msg  Success, error or warning message shown by Textpattern
 */
function glz_cf_save($msg='', $debug = false)
{
    global $event, $step, $prefs, $msg;

    $in = array_map('assert_string', psa(array(
        'custom_set',
        'custom_field_number',
        'custom_set_name',
        'custom_set_name_old',
        'custom_set_title',
        'custom_set_instructions',
        'custom_set_type',
        'custom_set_position',
        'value',
        'save',
        'add_new'
    )));

    extract($in);

    // No name given -> error + return to list
    if (empty($custom_set_name)) {
        if ($debug) {
            dmp('No name specified');
        } // DEBUG info
        $msg = array(gTxt('glz_cf_no_name'), E_ERROR);
        glz_cf_list($msg);
        return;
    }

    // Same name given as another existing custom field -> error + return to list
    if (glz_check_custom_set_name($custom_set_name, $custom_set)) {
        if ($debug) {
            dmp('Same name as other custom field specified');
        } // DEBUG info
        // If the sanitized cf name matches an existing custom field, provide an extra hint in the error message
        $name_sanitized = glz_sanitize_for_cf($custom_set_name);
        $name_exists_msg = ($custom_set_name <> $name_sanitized) ? $custom_set_name.' ('.$name_sanitized.')' : $custom_set_name;
        $msg = array(gTxt('glz_cf_exists', array('{custom_set_name}' => $name_exists_msg)), E_ERROR);
        glz_cf_list($msg);
        return;
    }

    // No values specified for checkbox type -> error + return to list
    if ($custom_set_type == 'checkbox' && empty($value)) {
        $msg = array(gTxt('glz_cf_no_values'), E_ERROR);
        glz_cf_list($msg);
        return;
    }

    // At lest two values must specified for radiobutton/multiselect type -> error + return to list
    $cf_values = array_unique(array_filter(explode("\r\n", $value), 'glz_array_empty_values'));
    if ( ($custom_set_type == 'radio' || $custom_set_type == 'multi-select') && (count($cf_values) < 2) ) {
        $msg = array(gTxt('glz_cf_not_enough_values', array('{cf_type}' => gTxt('glz_cf_'.$custom_set_type))), E_ERROR);
        glz_cf_list($msg);
        return;
    }

    if ($debug) {
        dmp('CF name as input: '.$custom_set_name);
    } // DEBUG info

    $create_new_cf = (!empty($add_new)) ? true : false;

    if ($create_new_cf) {
        // Adding a new custom field
        if ($debug) {
            dmp('Creating a new custom field');
        } // DEBUG info

        // Note the custom field name input by the user
        $custom_set_name_input = $custom_set_name;
        // Sanitize custom field name : use strict mode for new custom fields
        $custom_set_name = glz_sanitize_for_cf($custom_set_name);
        // Compare: if different -> Raise information notice
        if ($custom_set_name_input <> $custom_set_name) {
            $msg = array(gTxt('glz_cf_name_renamed_notice', array('{custom_name_input}' => $custom_set_name_input, '{custom_name_output}' => $custom_set_name )), E_WARNING);
        }
    } else {
        // Editing an existing custom field
        if ($debug) {
            dmp('Updating an existing custom field');
        } // DEBUG info

        // Check if custom field name is valid -> Raise warning notice if not
        glz_is_valid_cf_name($custom_set_name);
        // Sanitize custom field name : use $lite mode for backwards compatibility
        $custom_set_name = glz_sanitize_for_cf($custom_set_name, $lite = true);
    }

    // Use sanitized custom set name
    $in['custom_set_name'] = $custom_set_name;
    if ($debug) {
        dmp('CF name cleaned: '.$custom_set_name);
    } // DEBUG info

    // If there is no value for 'position' specified, use the custom field numbers
    // if using jqueryui.sortable use 999 (the end of the list)
    if (empty($custom_set_position)) {
        $in['custom_set_position'] = ($prefs['glz_cf_use_sortable'] == '1') ? '999' : $custom_field_number;
    }

    if ($debug) {
        dmp('$in: '.$in);
    } // DEBUG info

    // OK, good to go

    if ($create_new_cf) {

        // ACTION! Save new custom field to DB
        $result = glz_db_cf_new($in, $debug);

        if ($result) {
            // update lastmod + corresponding event
            update_lastmod(
                'custom_field_created',
                compact(
                    'custom_set',
                    'custom_field_number',
                    'custom_set_name',
                    'custom_set_title',
                    'custom_set_instructions',
                    'custom_set_type',
                    'custom_set_position'
                    )
                );

            // Success or warning message (if generated earlier by glz_is_valid_cf_name)
            if (empty($msg)) {
                $msg = gTxt('glz_cf_created', array('{custom_set_name}' => $custom_set_name));
            }
        }
    } else {

        // ACTION! Update custom field in DB
        $result = glz_db_cf_save($in, $debug);

        if ($result) {
            // update lastmod + corresponding event
            update_lastmod(
                'custom_field_updated',
                compact(
                    'custom_set',
                    'custom_field_number',
                    'custom_set_name',
                    'custom_set_name_old',
                    'custom_set_title',
                    'custom_set_instructions',
                    'custom_set_type',
                    'custom_set_position'
                    )
                );
            // Success or warning message (if generated earlier by glz_is_valid_cf_name)
            if (empty($msg)) {
                $msg = gTxt('glz_cf_updated', array('{custom_set_name}' => $custom_set_name));
            }
        }
    }

    // Render custom field list
    glz_cf_list($msg);
}


/**
 * Reset step in UI – for custom field IDs 1-10.
 * Retrieves value for ID from url
 *
 * @param  string  $msg  Pass-thru of success / error / warning message
 * @param  bool  $debug  Switch on debug messaging and query dumps
 */
function glz_cf_reset($msg='', $debug = false)
{
    global $event, $step;

    // Get ID from URL
    $id = gps('ID');

    // Check ID is properly formed, else back to list
    if (!intval($id)) {
        if ($debug) {
            dmp($id.' is not an integer');
        } // DEBUG info
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    }
    // Check ID actually exists before resetting, else back to list
    if (!get_pref('custom_'.$id.'_set')) {
        if ($debug) {
            dmp('custom_'.$id.'_set does not exist');
        } // DEBUG info
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    };

    // ACTION! Reset in DB
    $result = glz_db_cf_reset($id, $debug);

    if ($result) {
        update_lastmod('custom_field_reset', $id);
        $msg = gTxt('glz_cf_reset', array('{custom_set_id}' => 'ID# '.$id));
    } else {
        $msg = array(gTxt('glz_cf_reset_error', array('{custom_set_id}' => 'ID# '.$id)), E_ERROR);
    }

    // Render custom field list + message
    glz_cf_list($msg);
}


/**
 * Delete step in UI – for custom fields ID > 10.
 * Retrieves value for ID from url
 *
 * @param  string  $msg  Success / error / warning message
 * @param  bool  $debug  Switch on debug messaging and query dumps
 */
function glz_cf_delete($msg='', $reset= false, $debug = false)
{
    global $event, $step;

    // Get ID from URL
    $id = gps('ID');

    // Check ID is properly formed, else back to list
    if (!intval($id)) {
        if ($debug) {
            dmp($id.' is not an integer');
        } // DEBUG info
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    }
    // Check ID actually exists before deleting, else back to list
    if (!get_pref('custom_'.$id.'_set')) {
        if ($debug) {
            dmp('custom_'.$id.'_set does not exist');
        } // DEBUG info
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    };

    // ACTION! Delete from DB (reset for IDs 1-10)
    $result = glz_db_cf_delete($id, $reset, $debug);

    if ($result) {
        update_lastmod('custom_field_deleted', $id);
        $msg = gTxt('glz_cf_deleted', array('{custom_set_id}' => 'ID# '.$id));
    } else {
        $msg = array(gTxt('glz_cf_deleted_error', array('{custom_set_id}' => 'ID# '.$id)), E_ERROR);
    }

    // Render custom field list + message
    glz_cf_list($msg);
}
