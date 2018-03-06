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
#   DATABASE FUNCTIONS – Standalone reusable DB functions
#
##################

/**
 * Adds a new custom field.
 *
 * @param  array $in Incoming array of form values (see below)
 * @param  bool $debug Dump query
 * @return bool FALSE on error
 * @see    glz_db_cf_save()
 *
 * Expects an incoming array as follows:
 * $in = array(
 *     'custom_set',
 *     'custom_field_number',
 *     'custom_set_name',
 *     'custom_set_name_old', **
 *     'custom_set_title', **
 *     'custom_set_instructions', **
 *     'custom_set_type',
 *     'custom_set_position',
 *     'value', **
 *     'save', **
 *     'add_new'
 * );
 * where ** are optional
 *
 * Do all content checks / validation / data completion / manipulation
 * before passing the array to this function
 */

function glz_db_cf_new($in, $debug = false)
{
    // Abort if wrong input format
    if (!is_array($in)) {
        if ($debug) { dmp('$in: wrong input format'); }
        return false;
    }

    // Extract incoming values
    extract($in);
    // Get current UI language
    $current_lang = get_pref('language_ui', TEXTPATTERN_DEFAULT_LANG);

    $ok = false;

    // Insert: table 'txp_prefs' – custom_field entry
    // Custom fields IDs 1-10 already exist (in-built)
    if ($custom_field_number > 10) {
        $ok = safe_insert(
            'txp_prefs',
            "name     = '{$custom_set}',
             val      = '{$custom_set_name}',
             type     = '1',
             event    = 'custom',
             html     = '{$custom_set_type}',
             position = '{$custom_set_position}'",
            $debug
        );
    } else {
        // Custom field IDs 1-10 (update entry)
        $ok = safe_update(
            'txp_prefs',
            "val      = '{$custom_set_name}',
             html     = '{$custom_set_type}',
             position = '{$custom_set_position}'",
             "name = '{$custom_set}'",
            $debug
        );
    }

    // Insert: table 'textpattern' – new 'custom_X' column
    // Custom fields IDs 1-10 already exist (in-built)
    if ($custom_field_number > 10) {
        // If cf type = textarea, column type + default must be different
        $column_type = ($custom_set_type == "textarea") ? "TEXT" : "VARCHAR(255)";
        $dflt =        ($custom_set_type == "textarea") ? ''     : "DEFAULT ''";
        $ok = safe_alter(
            'textpattern',
            "ADD custom_{$custom_field_number} {$column_type} NOT NULL {$dflt}",
            $debug
        );
    } else {
        // Custom field IDs 1-10: only update column type and default as necessary
        // Update: table 'textpattern' – column type and default if type changes
        // If cf type = textarea, column type + default must be different
        $column_type = ($custom_set_type == "textarea") ? "TEXT" : "VARCHAR(255)";
        $dflt =        ($custom_set_type == "textarea") ? ''     : "DEFAULT ''";
        $ok = safe_alter(
            'textpattern',
            "MODIFY custom_{$custom_field_number} {$column_type} NOT NULL {$dflt}",
            $debug
        );
    }

    // Insert: table 'txp_lang' – prefspane label
    // Custom fields IDs 1-10 already exist (in-built)
    if ($custom_field_number > 10) {
        $custom_set_preflabel = gTxt('custom_x_set', array('{number}' => $custom_field_number));
        $ok = safe_insert(
            'txp_lang',
            "lang    = '{$current_lang}',
             name    = 'custom_{$custom_field_number}_set',
             event   = 'prefs',
             owner   = '',
             data    = '{$custom_set_preflabel}',
             lastmod = now()",
            $debug
        );
    }

    // Insert: table 'txp_lang' – custom field label title
    if (!empty($custom_set_title)) {
        $custom_set_cf_name = glz_cf_langname($custom_set_name);
        $custom_set_cf_data = doSlash($custom_set_title);
        $ok = safe_insert(
            'txp_lang',
            "lang    = '{$current_lang}',
             name    = '{$custom_set_cf_name}',
             event   = 'glz_cf',
             owner   = 'glz_custom_fields',
             data    = '{$custom_set_cf_data}',
             lastmod = now()",
            $debug
        );
        $set_gTxt[$custom_set_cf_name] = $custom_set_cf_data;
    }

    // Insert: table 'txp_lang' – custom field instructions
    if (!empty($custom_set_instructions)) {
        $custom_set_instr_name = 'instructions_custom_'.$custom_field_number;
        $custom_set_instr_data = doSlash($custom_set_instructions);
        $ok = safe_insert(
            'txp_lang',
            "lang    = '{$current_lang}',
             name    = '{$custom_set_instr_name}',
             event   = 'glz_cf',
             owner   = 'glz_custom_fields',
             data    = '{$custom_set_instr_data}',
             lastmod = now()",
            $debug
        );
        $set_gTxt[$custom_set_instr_name] = $custom_set_instr_data;
    }

    // Insert: table 'custom_fields' – multiple custom field values
    // 1. break textarea entries into array removing blanks and duplicates
    $cf_values = array_unique(array_filter(explode("\r\n", $value), 'glz_array_empty_values'));
    // 2. fashion insert statement from array
    if (is_array($cf_values) && !empty($cf_values)) {
        $insert = '';
        foreach ($cf_values as $key => $value) {
            // Skip empty values
            if (!empty($value)) {
                // Escape special chars before inserting into database
                $value = doSlash(trim($value));
                // Build insert row
                $insert .= "('{$custom_set}','{$value}'), ";
            }
        }
        // Trim final comma and space from insert statement
        $insert = rtrim($insert, ', ');
        // 3. do insert query
        $ok = safe_query("
            INSERT INTO
                ".safe_pfx('custom_fields')." (`name`,`value`)
            VALUES
                {$insert}
            ",
            $debug
        );
    }

    // As the table UI doesn't include the new strings until a page refresh,
    // set the language strings in $set_gTxt (if they exist) in the current context
    // (true = amend/append to existing textpack)
    if (isset($set_gTxt)) {
        if ($debug) { dmp('$set_gTxt: '.$set_gTxt); }
        Txp::get('\Textpattern\L10n\Lang')->setPack($set_gTxt, true);
    }

    return $ok;
}


/**
 * Saves / updates an existing custom field in the DB.
 *
 * @param  array $in  Incoming array of form values (see below)
 * @param  bool  $debug  Dump query
 * @return bool  FALSE on error
 * @see    glz_db_cf_new()
 *
 * Expects an incoming array as follows:
 * $in = array(
 *     'custom_set',
 *     'custom_field_number',
 *     'custom_set_name',
 *     'custom_set_name_old',
 *     'custom_set_title', **
 *     'custom_set_instructions', **
 *     'custom_set_type',
 *     'custom_set_position',
 *     'value', **
 *     'save',
 *     'add_new' **
 * );
 * where ** are optional
 *
 * Do all content checks / validation / data completion / manipulation
 * before passing the array to this function
 */

function glz_db_cf_save($in, $debug = false)
{
    // Abort if wrong input format
    if (!is_array($in)) {
        if ($debug) { dmp('$in: wrong input format'); }
        return false;
    }

    // Extract incoming values
    extract($in);
    // Get current UI language
    $current_lang = get_pref('language_ui', TEXTPATTERN_DEFAULT_LANG);

    // Has the custom field been renamed
    $is_cf_renamed = ($custom_set_name <> $custom_set_name_old) ? true : false;

    $ok = false;

    // Update: table 'txp_prefs' – custom_field entry
    $ok = safe_update(
        'txp_prefs',
        "val      = '{$custom_set_name}',
         html     = '{$custom_set_type}',
         position = '{$custom_set_position}'",
        "name     = '{$custom_set}'", // WHERE
        $debug
    );

    // Update: table 'textpattern' – column type and default if type changes
    // If cf type = textarea, column type + default must be different
    $column_type = ($custom_set_type == "textarea") ? "TEXT" : "VARCHAR(255)";
    $dflt =        ($custom_set_type == "textarea") ? ''     : "DEFAULT ''";
    $ok = safe_alter(
        'textpattern',
        "MODIFY custom_{$custom_field_number} {$column_type} NOT NULL {$dflt}",
        $debug
    );

    // Update: table 'custom_fields' – values entries (textarea requires none)
    // For textareas we do not need to touch custom_fields table
    if ($custom_set_type != "textarea") {
        safe_delete(
            'custom_fields',
            "name ='{$custom_set}'",
            $debug
        );
        // Insert: table 'custom_fields' – multiple custom field values
        // 1. break textarea entries into array removing blanks and duplicates
        $cf_values = array_unique(array_filter(explode("\r\n", $value), 'glz_array_empty_values'));
        // 2. fashion insert statement from array
        if (is_array($cf_values) && !empty($cf_values)) {
            $insert = '';
            foreach ($cf_values as $key => $value) {
                // Skip empty values
                if (!empty($value)) {
                    // Escape special chars before inserting into database
                    $value = doSlash(trim($value));
                    // Build insert row
                    $insert .= "('{$custom_set}','{$value}'), ";
                }
            }
            // Trim final comma and space from insert statement
            $insert = rtrim($insert, ', ');
            // 3. do insert query
            $ok = safe_query("
                INSERT INTO
                    ".safe_pfx('custom_fields')." (`name`,`value`)
                VALUES
                    {$insert}
                ",
                $debug
            );
        }
    } // endif ($custom_set_type != "textarea")

    // Update: table 'txp_lang' – custom field label title
    $has_cf_title = (!empty($custom_set_title)) ? true : false;
    $custom_set_cf_langname = glz_cf_langname($custom_set_name);

    if ($is_cf_renamed) {
        $custom_set_cf_langname_old = glz_cf_langname($custom_set_name_old);
        // OK, cf is renamed. Do cfnames still match perchance?
        // such as when renaming spaces/dashes to underscores or uppercase to lowercase
        if ($custom_set_cf_langname <> $custom_set_cf_langname_old) {
            // Update name of all custom fields (if cf_langname has actually changed)
            $custom_set_cf_name_old = $custom_set_cf_langname_old;
            $custom_set_cf_data_old = null;
            $ok = safe_update(
                'txp_lang',
                "name = '{$custom_set_cf_langname}'",
                "name = '{$custom_set_cf_langname_old}'",
                $debug
            );
            $set_gTxt[$custom_set_cf_name_old] = $custom_set_cf_data_old;
        }
    }

    if ($has_cf_title) {
        // A) Custom field title is specified: update or insert
        $custom_set_cf_name = $custom_set_cf_langname;
        $custom_set_cf_data = doSlash($custom_set_title);
        $ok = safe_upsert(
            'txp_lang',
            "event   = 'glz_cf',
             owner   = 'glz_custom_fields',
             data    = '{$custom_set_cf_data}',
             lastmod = now()",
             array('name' => $custom_set_cf_name, 'lang' => $current_lang),
            $debug
        );
        $set_gTxt[$custom_set_cf_name] = $custom_set_cf_data;
    } else {
        // B) Custom field title not specified (or blanked)
        $custom_set_cf_name = $custom_set_cf_langname;
        $custom_set_cf_data = null;
        // Only delete it if it actually exists
        $gtxt_data = glz_cf_gtxt($custom_set_name);
        if (!empty($gtxt_data)) {
            $ok = safe_delete(
                'txp_lang',
                "name = '{$custom_set_cf_name}' AND lang = '{$current_lang}'",
                $debug
            );
        }
        $set_gTxt[$custom_set_cf_name] = $custom_set_cf_data;
    }

    // Update: table 'txp_lang' – custom field instructions

    // A) If instructions string exists, update existing or insert new entry, or…
    if (!empty($custom_set_instructions)) {
        $custom_set_instr_name = 'instructions_custom_'.$custom_field_number;
        $custom_set_instr_data = doSlash($custom_set_instructions);
        $ok = safe_upsert(
            'txp_lang',
            "event   = 'glz_cf',
             owner   = 'glz_custom_fields',
             data    = '{$custom_set_instr_data}',
             lastmod = now()",
             array('name' => $custom_set_instr_name, 'lang' => $current_lang),
            $debug
        );
        $set_gTxt[$custom_set_instr_name] = $custom_set_instr_data;

    // B) If instructions string is empty but previously existed, remove old entry
    } elseif (glz_cf_gtxt('', $custom_field_number) != '') {
        $custom_set_instr_name = 'instructions_custom_'.$custom_field_number;
        $custom_set_instr_data = null;
        // Only delete it if it actually exists
        $gtxt_data = glz_cf_gtxt('',$custom_field_number);
        if (!empty($gtxt_data)) {
            $ok = safe_delete(
                'txp_lang',
                "name = '{$custom_set_instr_name}' AND lang = '{$current_lang}'",
                $debug
            );
        }
        $set_gTxt[$custom_set_instr_name] = $custom_set_instr_data;
    }

    // As the table UI doesn't include the new strings until a page refresh,
    // set the language strings in $set_gTxt (if they exist) in the current context
    // (true = amend/append to existing textpack)
    if (isset($set_gTxt)) {
        if ($debug) { dmp('$set_gTxt: '.$set_gTxt); }
        Txp::get('\Textpattern\L10n\Lang')->setPack($set_gTxt, true);
    }

    return $ok;
}


/**
 * Resets a custom field.
 *
 * Passes through to glz_db_cf_delete()
 * with reset flag set to true
 *
 * @param  string $id Custom field ID#
 * @param  bool $debug Dump query
 * @return bool FALSE on error
 * @see    glz_db_cf_delete()
 *
 * Do all checks on $id (if integer / if exists)
 * before passing $id to this function
 */

function glz_db_cf_reset($id, $debug = false)
{
    return glz_db_cf_delete($id, $reset = true, $debug);
}


/**
 * Deletes or resets a custom field.
 *
 * ID#s 1-10 are always reset, not deleted
 *
 * @param  string $id Custom field ID#
 * @param  bool $reset Reset rather than delete
 * @param  bool $debug Dump query
 * @return bool FALSE on error
 * @see    glz_db_cf_reset()
 *
 * Do all checks on $id (if integer / if exists)
 * before passing $id to this function
 */

function glz_db_cf_delete($id, $reset = false, $debug = false)
{
    // Custom fields 1-10 are in-built -> only reset
    if ($id <= 10) {
        $reset = true;
    }

    // Retrieve this custom_field values
    $custom_set = glz_db_get_custom_set($id);

    $ok = false;

    // --- COMMON DELETE AND RESET STEPS

    // Delete: table 'txp_lang' – custom field label title
    if (!empty($custom_set['title'])) {
        $ok = safe_delete(
            'txp_lang',
            "name = '".glz_cf_langname($custom_set['name'])."'",
            $debug
        );
    }

    // Delete: table 'txp_lang' – custom field instructions
    if (!empty($custom_set['instructions'])) {
        $ok = safe_delete(
            'txp_lang',
            "name = 'instructions_custom_".$custom_set['id']."'",
            $debug
        );
    }

    // Delete: table 'custom_fields' – custom field multiple values settings
    $cf_row = safe_row('*', 'custom_fields', "name = '".$custom_set['custom_set']."'", $debug);
    if ($cf_row) {
        $ok = safe_delete(
            'custom_fields',
            "name = '".$custom_set['custom_set']."'",
            $debug
        );
    }

    if ($reset) {  // --- RESET ONLY STEPS

        // Reset: table 'txp_prefs' – reset to standard custom field settings
        $ok = safe_update(
            'txp_prefs',
            "val = '',
             html = 'text_input'",
            "name = '".$custom_set['custom_set']."'",
            $debug
        );

        // Reset: table 'textpattern' – empty custom field article data
        $ok = safe_update(
            'textpattern',
            "custom_".$custom_set['id']." = ''",
            $debug
        );

        // Reset: table 'textpattern' – reset custom_X column type to VARCHAR
        $ok = safe_alter(
            'textpattern',
            "MODIFY custom_".$custom_set['id']." VARCHAR(255) NOT NULL DEFAULT ''",
            $debug
        );

    } else {  // --- DELETE ONLY STEPS

        // Delete: table 'txp_lang' – prefspane label
        $ok = safe_delete(
            'txp_lang',
            "name = '".$custom_set['custom_set']."'",
            $debug
        );

        // Delete: table 'txp_prefs' – custom field entry
        $ok = safe_delete(
            'txp_prefs',
            "name = '".$custom_set['custom_set']."'",
            $debug
        );

        // Delete: table 'textpattern' – custom field article data
        $ok = safe_alter(
            'textpattern',
            "DROP `custom_".$custom_set['id']."`",
            $debug
        );
    }

    return $ok;
}


function glz_db_get_all_custom_sets()
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


function glz_db_get_custom_set($id)
{
    if (!intval($id)) {
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


function glz_db_get_custom_field_values($name, $extra)
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


function glz_db_get_all_existing_cf_values($name, $extra)
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


function glz_db_get_article_custom_fields($name, $extra)
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
// Updates 'max_custom_fields' pref
function glz_db_update_custom_fields_count()
{
    set_pref('max_custom_fields', safe_count('txp_prefs', "event='custom'"));
}


// -------------------------------------------------------------
// Goes through all custom sets, returns the first one which is not being used
// Returns next free id#.
function glz_next_empty_custom()
{
    $result = safe_field(
        "name",
        'txp_prefs',
        "event = 'custom' AND val = '' ORDER BY LENGTH(name), name LIMIT 1"
    );
    if ($result) {
        $result = glz_custom_digit($result);
    } else {
        $result = get_pref('max_custom_fields') + 1;
    }
    return $result;
}


// -------------------------------------------------------------
// Check if one of the special custom fields exists (e.g. date-picker / time-picker)
function glz_check_custom_set_exists($name)
{
    if (!empty($name)) {
        return safe_field("name", 'txp_prefs', "html = '".$name."' AND name LIKE 'custom_%'");
    }
}
