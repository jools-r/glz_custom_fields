<?php

// -------------------------------------------------------------
// TODO: Is this really necessary or can we cut straight to functions?
function glz_custom_fields_MySQL($do, $name='', $table='', $extra='')
{
    if (!empty($do)) {
        switch ($do) {
            case 'all':
                return glz_all_custom_sets();
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

            case 'set_plugin_prefs':
                return glz_set_plugin_prefs($name, $extra);
                break;
        }
    } else {
        trigger_error(gTxt('glz_cf_no_do'), E_ERROR);
    }
}


function glz_all_custom_sets()
{
    $all_custom_sets = safe_rows(
        "`name` AS custom_set, `val` AS name, `position`, `html` AS type",
        'txp_prefs',
        "`event`='custom' ORDER BY `position`"
    );

    foreach ($all_custom_sets as $custom_set) {
        $custom_set['id'] = glz_custom_digit($custom_set['custom_set']);
        $out[$custom_set['custom_set']] = array(
            'id'        => $custom_set['id'],
            'name'      => $custom_set['name'],
            'position'  => $custom_set['position'],
            'type'      => $custom_set['type']
        );
    }

    return $out;
}


function glz_values_custom_field($name, $extra)
{
    global $prefs;

    if (is_array($extra)) {
        extract($extra);

        if (!empty($name)) {
            switch ($prefs['values_ordering']) {
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
                $query = "
                    INSERT INTO
                        ".safe_pfx('txp_lang')." (`lang`,`name`,`event`,`data`,`lastmod`)
                    VALUES
                        ('{$lang}','{$custom_set}','prefs','{$name}',now())
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

    if (($table == "txp_prefs")) {
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
    } elseif (($table == "textpattern")) {
        // Update custom_field column type in 'textpattern' table
        $column_type = ($custom_set_type == "textarea") ? "TEXT" : "VARCHAR(255)";
        $dflt = ($custom_set_type == "textarea") ? '' : "DEFAULT ''";
        safe_query("
            ALTER TABLE
                ".safe_pfx('textpattern')."
            MODIFY
                `{$custom_field}` {$column_type} NOT NULL {$dflt}
        ");
    }
}


function glz_reset_custom_field($name, $table, $extra)
{
    if (is_array($extra)) {
        extract($extra);
    }

    if ($table == "txp_prefs") {
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
    } elseif ($table == "textpattern") {
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
    }
}


function glz_delete_custom_field($name, $table)
{
    // The first ten custom fields are in-built and should not be deleted
    if (glz_custom_digit($name) > 10) {
        if (in_array($table, array("txp_prefs", "txp_lang", "custom_fields"))) {
            $query = "
                DELETE FROM
                    ".safe_pfx($table)."
                WHERE
                    `name`='{$name}'
            ";
        } elseif ($table == "textpattern") {
            $query = "
                ALTER TABLE
                    ".safe_pfx('textpattern')."
                DROP
                    `{$name}`
            ";
        }
        safe_query($query);
    } else {
        // In first ten custom_fields?
        // Reset custom_field in 'txp_prefs'
        if ($table == "txp_prefs") {
            glz_custom_fields_MySQL("reset", $name, $table);
        } elseif (($table == "custom_fields")) {
            // Delete from 'custom_fields' table
            safe_query("
                DELETE FROM
                    ".safe_pfx($table)."
                WHERE
                    `name`='{$name}'
            ");
        }
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

// -------------------------------------------------------------
// sets all plugin preferences
function glz_set_plugin_prefs($options, $no_reset = false) {
    // DEBUG
    // die(dmp($options, $no_reset));
    $position = 200;
    foreach ($options as $name => $val) {
        // if $no_reset is true, skip already set prefs
        if ($no_reset == true) {
            if (get_pref($name)) {
                continue;
            }
// Gets all plugin preferences
function glz_get_plugin_prefs($arr_preferences)
{
    $r = safe_rows_start('name, val', 'txp_prefs', "event = 'glz_custom_f'");
    if ($r) {
        while ($a = nextRow($r)) {
            $out[$a['name']] = stripslashes($a['val']);
        }
        set_pref($name, addslashes(addslashes(trim($val))), 'glz_custom_f', PREF_PLUGIN, 'text_input', $position);
        $position++;
    }
    return $out;
}

?>
