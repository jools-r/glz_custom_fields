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
#   DATABASE FUNCTIONS –
#
##################


// -------------------------------------------------------------
function glz_custom_fields_MySQL($do, $name='', $table='', $extra='')
{
    if (!empty($do)) {
        switch ($do) {
            case 'all':
                return glz_cf_all_custom_sets();
                break;

            case 'values':
                return glz_values_custom_field($name, $extra);
                break;

            case 'all_values':
                return glz_all_existing_custom_values($name, $extra);
                break;

            case 'article_customs':
                return glz_article_custom_fields($name, $extra);
                break;

            case 'next_custom':
                return glz_next_empty_custom();
                break;

            case 'new':
                glz_new_custom_field($name, $table, $extra);
                glz_custom_fields_update_count();
                break;

            case 'update':
                return glz_update_custom_field($name, $table, $extra);
                break;

            case 'reset':
                return glz_reset_custom_field($name, $table, $extra);
                break;

            case 'delete':
                glz_delete_custom_field($name, $table);
                glz_custom_fields_update_count();
                break;

            case 'custom_set_exists':
                return glz_check_custom_set_exists($name);
                break;

            case 'get_plugin_prefs':
                return glz_get_plugin_prefs($name);
                break;
        }
    } else {
        trigger_error(gTxt('glz_cf_no_do'), E_ERROR);
    }
}


function glz_cf_all_custom_sets()
{
    $all_custom_sets = safe_rows(
        "`name` AS custom_set,
         `val` AS name,
         `position`,
         `html` AS type",
        'txp_prefs',
        "`event` = 'custom' ORDER BY `position`"
    );

    foreach ($all_custom_sets as $custom_set) {
        $custom_set['id'] = glz_custom_digit($custom_set['custom_set']);
        $custom_set['title'] = glz_cf_gtxt($custom_set['name']);
        $custom_set['instructions'] = glz_cf_gtxt('', $custom_set['id']);

        $out[$custom_set['custom_set']] = array(
            'id'            => $custom_set['id'],
            'name'          => $custom_set['name'],
            'title'         => $custom_set['title'],
            'instructions'  => $custom_set['instructions'],
            'position'      => $custom_set['position'],
            'type'          => $custom_set['type']
        );

    }
    return $out;
}


function glz_cf_single_custom_set($id)
{
    if (!ctype_digit($id)) {
        return false;
    }
        $custom_set = safe_row(
            "name AS custom_set,
             val AS name,
             position,
             html AS type",
            'txp_prefs',
            "name = 'custom_".doSlash($id)."_set'"
        );

        $custom_set['id'] = glz_custom_digit($custom_set['custom_set']);
        $custom_set['title'] = glz_cf_gtxt($custom_set['name']);
        $custom_set['instructions'] = glz_cf_gtxt('', $custom_set['id']);

        return $custom_set;
}


function glz_values_custom_field($name, $extra)
{
    global $prefs;

    if (is_array($extra)) {
        extract($extra);

        if (!empty($name)) {
            switch ($prefs['glz_cf_values_ordering']) {
                case "ascending":
                    $orderby = "value ASC";
                    break;
                case "descending":
                    $orderby = "value DESC";
                    break;
                default:
                    $orderby = "id ASC";
            }

            $arr_values = getThings("
                SELECT
                    `value`
                FROM
                    ".safe_pfx('custom_fields')."
                WHERE
                    `name` = '{$name}'
                ORDER BY
                    {$orderby}
            ");

            if (count($arr_values) > 0) {
                // Decode all special characters e.g. ", & etc. and use them for keys
                foreach ($arr_values as $key => $value) {
                    $arr_values_formatted[glz_clean_default(htmlspecialchars($value))] = stripslashes($value);
                }

                // if this is a range, format ranges accordingly
                return glz_format_ranges($arr_values_formatted, $custom_set_name);
            }
        }
    } else {
        trigger_error(gTxt('glz_cf_not_specified', array('{what}' => "extra attributes")), E_ERROR);
    }
}


function glz_all_existing_custom_values($name, $extra)
{
    if (is_array($extra)) {
        extract(lAtts(array(
            'custom_set_name'   => "",
            'status'            => 4
        ), $extra));

        // On occasions (e.g. initial migration) we may need to check the custom field values for all articles
        $status_condition = ($status == 0) ? "<> ''" : "= '$status'";

        if (!empty($name)) {
            $arr_values = getThings("
                SELECT DISTINCT
                    `$name`
                FROM
                    ".safe_pfx('textpattern')."
                WHERE
                    `Status` $status_condition
                AND
                    `$name` <> ''
                ORDER BY
                    `$name`
            ");

            // Trim all values
            foreach ($arr_values as $key => $value) {
                $arr_values[$key] = trim($value);
            }

            // DEBUG
            // dmp($arr_values);

            // Temporary string of array values for checking for instances of | and -.
            $values_check = join('::', $arr_values);

            // DEBUG
            // dmp($values_check);

            // Are any values multiple ones (=‘|’)?
            if (strstr($values_check, '|')) {
                // Initialize $out
                $out = array();
                // Put all values in an array
                foreach ($arr_values as $value) {
                    $arr_values = explode('|', $value);
                    $out = array_merge($out, $arr_values);
                }
                // Keep only the unique ones
                $out = array_unique($out);
                // Keys and values need to be the same
                $out = array_combine($out, $out);
            }
            // Are any values ranges (=‘-’)?
            elseif (strstr($values_check, '-') && strstr($custom_set_name, 'range')) {
                // Keys won't have the unit ($, £, m³, etc.), values will
                $out = glz_format_ranges($arr_values, $custom_set_name);
            } else {
                // Keys and values need to be the same
                $out = array_combine($arr_values, $arr_values);
            }

            // Calling stripslashes on all array values
            array_map('glz_array_stripslashes', $out);

            return $out;
        }
    } else {
        trigger_error(gTxt('glz_cf_not_specified', array('{what}' => "extra attributes")), E_ERROR);
    }
}


function glz_article_custom_fields($name, $extra)
{
    if (is_array($extra)) {
        // See what custom fields we need to query for
        foreach ($extra as $custom => $custom_set) {
            $select[] = glz_custom_number($custom);
        }

        // Prepare the select elements
        $select = implode(',', $select);

        $arr_article_customs = safe_row(
            $select,
            'textpattern',
            "`ID`='".$name."'"
        );
        return $arr_article_customs;
    } else {
        trigger_error(gTxt('glz_cf_not_specified', array('{what}' => "extra attributes")), E_ERROR);
    }
}


// -------------------------------------------------------------
// Goes through all custom sets, returns the first one which is not being used
// Returns next free id#.
function glz_next_empty_custom()
{
    $result = safe_field(
        "name",
        'txp_prefs',
        "event = 'custom' AND val = '' ORDER BY name LIMIT 1"
    );
    return glz_custom_digit($result);
}


function glz_new_custom_field($name, $table, $extra)
{
    if (is_array($extra)) {
        extract($extra);

        $custom_set = (isset($custom_field_number)) ?
            "custom_{$custom_field_number}_set" : $custom_set;

        switch ($table) {
            case 'txp_prefs':
                // If this is a new field without a position, use the $custom_field_number
                if (empty($custom_set_position)) {
                    $custom_set_position = $custom_field_number;
                }
                $query = "
                    INSERT INTO
                        ".safe_pfx('txp_prefs')." (`name`, `val`, `type`, `event`, `html`, `position`)
                    VALUES
                        ('{$custom_set}', '{$name}', '1', 'custom', '{$custom_set_type}', {$custom_set_position})
                ";
                break;

            case 'txp_lang':
                // if no 'gtxt_name' specified, default lang name = 'custom_X_set'
                $lang_name = (isset($gtxt_name) ? $gtxt_name : $custom_set);
                // if no 'gtxt_event' specified, default event = 'prefs'
                $lang_event = (isset($gtxt_event) ? $gtxt_event : 'prefs');
                $owner ="glz_custom_fields";
                $query = "
                    INSERT INTO
                        ".safe_pfx('txp_lang')." (`lang`,`name`,`event`,`owner`,`data`,`lastmod`)
                    VALUES
                        ('{$lang}','{$lang_name}','{$lang_event}','{$owner}','{$name}',now())
                ";
                break;

            case 'textpattern':
                $column_type = ($custom_set_type == "textarea") ? "TEXT" : "VARCHAR(255)";
                $dflt =        ($custom_set_type == "textarea") ? ''     : "DEFAULT ''";
                $query = "
                    ALTER TABLE
                        ".safe_pfx('textpattern')."
                    ADD
                        `custom_{$custom_field_number}` {$column_type} NOT NULL {$dflt}
                    ";
                break;

            case 'custom_fields':
                $arr_values = array_unique(array_filter(explode("\r\n", $value), 'glz_array_empty_values'));

                if (is_array($arr_values) && !empty($arr_values)) {
                    $insert = '';
                    foreach ($arr_values as $key => $value) {
                        // Skip empty values
                        if (!empty($value)) {
                            // Escape special chars before inserting into database
                            $value = addslashes(addslashes(trim($value)));
                            // Build insert string
                            $insert .= "('{$custom_set}','{$value}'), ";
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
                }
                break;
        }
        //dmp($query);

        // Execute DB query if it exists
        if (isset($query) && !empty($query)) {
            safe_query($query);
        }
    } else {
        trigger_error(gTxt('glz_cf_not_specified', array('{what}' => "extra attributes")), E_ERROR);
    }
}


function glz_update_custom_field($name, $table, $extra)
{
    if (is_array($extra)) {
        extract($extra);
    }
    //dmp('name: '.$name,'table: '.$table,$extra);
    switch ($table) {
        case 'txp_prefs':
            // Update custom_field data in 'txp_prefs' table
            safe_query("
                UPDATE
                    ".safe_pfx('txp_prefs')."
                SET
                    `val` = '{$custom_set_name}',
                    `html` = '{$custom_set_type}',
                    `position` = '{$custom_set_position}'
                WHERE
                    `name` = '{$name}'
            ");
            break;
        case 'txp_lang':
            // Update custom_field title (switch reference from old to new)
            $current_lang = get_pref('language_ui', TEXTPATTERN_DEFAULT_LANG);
            // If the custom_field name has changed, update cf_langname to match new name
            $new_cf_name = ($name <> $old_cf_name) ? glz_cf_langname($name) : "";
            // if cf title is not empty, update or insert it
            if (!empty($cf_title)) {
                // name is unchanged: safe_update entry
                if ( empty($new_cf_name) && !empty(glz_cf_gtxt($old_cf_name)) ) {
                    safe_update(
                        'txp_lang',
                        "event  = 'prefs',
                        owner   = 'glz_custom_fields',
                        data    = '".doSlash($cf_title)."',
                        lastmod = now()",
                        "name = '".glz_cf_langname($old_cf_name)."' AND lang = '{$current_lang}'"
                    );
                } else {
                    // name has been changed: insert entry with new name
                    safe_insert(
                        'txp_lang',
                        "lang   = '{$current_lang}',
                        name    = '".$new_cf_name."',
                        event   = 'prefs',
                        owner   = 'glz_custom_fields',
                        data    = '".doSlash($cf_title)."',
                        lastmod = now()"
                    );
                    // delete entry with old name
                    if (!empty($old_cf_name) && !empty($new_cf_name)) {
                        safe_delete(
                            'txp_lang',
                            "name = '".glz_cf_langname($old_cf_name)."' AND lang = '{$current_lang}'"
                        );
                    }
                }
            // if cf title field is empty but a current translation exists: delete it (e.g. cancel field)
            } elseif (glz_cf_gtxt($old_cf_name) != '') {
                safe_delete(
                    'txp_lang',
                    "name = '".glz_cf_langname($old_cf_name)."' AND lang = '{$current_lang}'"
                );
            }
            // Update 'instructions' string
            $cf_instructions_langname = 'instructions_custom_'.$custom_field_number;
            // if cf instructions is not empty, update or insert it
            if (!empty($cf_instructions)) {
                safe_upsert(
                    'txp_lang',
                     "event   = 'glz_cf',
                     owner   = 'glz_custom_fields',
                     data    = '".doSlash($cf_instructions)."',
                     lastmod = now()",
                     array('name' => $cf_instructions_langname, 'lang' => $current_lang)
                );
            // if cf instructions field is empty but a current translation exists: delete it (e.g. cancel field)
            } elseif (glz_cf_gtxt('', $custom_field_number) != '') {
                safe_delete(
                    'txp_lang',
                    "name = '{$cf_instructions_langname}' AND lang = '{$current_lang}'"
                );
            }
            break;
        case 'textpattern':
            // Update custom_field column type in 'textpattern' table
            $column_type = ($custom_set_type == "textarea") ? "TEXT" : "VARCHAR(255)";
            $dflt = ($custom_set_type == "textarea") ? '' : "DEFAULT ''";
            safe_query("
                ALTER TABLE
                    ".safe_pfx('textpattern')."
                MODIFY
                    `{$custom_field}` {$column_type} NOT NULL {$dflt}
            ");
            break;
    }
}


function glz_reset_custom_field($name, $table, $extra)
{
    if (is_array($extra)) {
        extract($extra);
    }

    switch ($table) {
        case 'txp_prefs':
            // Reset custom field in 'txp_prefs' table to standard values
            safe_query("
                UPDATE
                    ".safe_pfx('txp_prefs')."
                SET
                    `val` = '',
                    `html` = 'text_input'
                WHERE
                    `name`='{$name}'
            ");
            break;

        case 'textpattern':
            // Reset custom field in 'textpattern' table to empty
            safe_query("
                UPDATE
                    ".safe_pfx('textpattern')."
                SET
                    `{$name}` = ''
            ");
            // Reset custom_field column type in 'textpattern' table back to standard value
            safe_query("
                ALTER TABLE
                    ".safe_pfx('textpattern')."
                MODIFY
                    `{$custom_field}` VARCHAR(255) NOT NULL DEFAULT ''
            ");
            break;
    }
}


function glz_delete_custom_field($name, $table)
{
    // Only custom fields > 10 are actually deleted
    if (glz_custom_digit($name) > 10) {
        switch ($table) {
            case 'txp_prefs':
            case 'custom_fields':
                safe_delete(
                    $table,
                    "name = '{$name}'"
                );
                break;

            case 'txp_lang':
                $custom_set_name = safe_field(
                    'val',
                    'txp_prefs',
                    "name = '".$name."'"
                );
                safe_delete(
                    $table,
                    "name = '".glz_cf_langname($custom_set_name)."'"
                );
                safe_delete(
                    $table,
                    "name = 'instructions_custom_".glz_custom_digit($name)."'"
                );
                safe_delete(
                    $table,
                    "name = '{$name}'"
                );
                break;

            case 'textpattern':
                safe_query("
                    ALTER TABLE
                        ".safe_pfx('textpattern')."
                    DROP
                        `{$name}`
                ");
                break;
        } // end switch
    } else {
        // In-built custom fields <= 10
        switch ($table) {
            case 'txp_prefs':
                // Reset custom_field in 'txp_prefs'
                glz_custom_fields_MySQL("reset", $name, $table);
                break;

            case 'custom_fields':
                // Delete from 'custom_fields' table
                safe_query("
                    DELETE FROM
                        ".safe_pfx($table)."
                    WHERE
                        `name`='{$name}'
                ");
                break;

                case 'txp_lang':
                    $custom_set_name = safe_field(
                        'val',
                        'txp_prefs',
                        "name = '".$name."'"
                    );
                    safe_delete(
                        $table,
                        "name = '".glz_cf_langname($custom_set_name)."'"
                    );
                    safe_delete(
                        $table,
                        "name = 'instructions_custom_".glz_custom_digit($name)."'"
                    );
                break;
        } // end switch
    }
}


// -------------------------------------------------------------
// Check if one of the special custom fields exists
function glz_check_custom_set_exists($name)
{
    if (!empty($name)) {
        return safe_field("name", 'txp_prefs', "html = '".$name."' AND name LIKE 'custom_%'");
    }
}


// -------------------------------------------------------------
// Updates max_custom_fields
function glz_custom_fields_update_count()
{
    set_pref('max_custom_fields', safe_count('txp_prefs', "event='custom'"));
}


// -------------------------------------------------------------
// Gets all plugin preferences
function glz_get_plugin_prefs($arr_preferences)
{
    $r = safe_rows_start('name, val', 'txp_prefs', "event = 'glz_custom_f'");
    if ($r) {
        while ($a = nextRow($r)) {
            $out[$a['name']] = stripslashes($a['val']);
        }
    }
    return $out;
}
