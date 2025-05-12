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
#   HELPERS – Helper functions: checks, sanitizers, preps
#
##################


// -------------------------------------------------------------
// The types our custom fields can take
function glz_custom_set_types()
{
    return array(
        'normal' => array(
            'text_input',
            'checkbox',
            'radio',
            'select',
            'multi-select',
            'textarea'
        ),
        'special' => array(
            'date-picker',
            'time-picker',
            'custom-script'
        )
    );
}


// -------------------------------------------------------------
// Outputs only custom fields that have been set, i.e. have a name assigned to them
function glz_check_custom_set($all_custom_sets, $step)
{
    $out = array();
    foreach ($all_custom_sets as $key => $custom_field) {
        if (!empty($custom_field['name'])) {
            if (($step == "body") && ($custom_field['type'] == "textarea")) {
                $out[$key] = $custom_field;
            } elseif (($step == "custom_fields") && ($custom_field['type'] != "textarea")) {
                $out[$key] = $custom_field;
            }
        }
    }
    return $out;
}


// -------------------------------------------------------------
// Converts all values into id safe ones [A-Za-z0-9-]
function glz_cf_idname($text)
{
    return str_replace("_", "-", glz_sanitize_for_cf($text));
}


// -------------------------------------------------------------
// Converts input into a gTxt-safe lang string 'cf_' prefix + [a-z0-9_]
function glz_cf_langname($text)
{
    return 'cf_'.glz_sanitize_for_cf($text);
}


// -------------------------------------------------------------
// Gets translated title or instruction string if one exists
// @name = custom_field_name
// @cf_number = $custom_field_number for instructions
// returns language string or nothing if none exists
function glz_cf_gtxt($name, $cf_number = null)
{
    // get language string
    if (!empty($cf_number)) {
        // still work if 'custom_X' or 'custom_X_set' is passed in as cf_number
        if (strstr($cf_number, 'custom_')) {
            $parts = explode("_", $cf_number);
            $cf_number = $parts[1];
        }
        $cf_name = 'instructions_custom_'.$cf_number;
    } else {
        $cf_name = glz_cf_langname($name);
    }
    $cf_gtxt = gTxt($cf_name);
    // retrieve gTxt value if it exists
    return ($cf_gtxt != $cf_name) ? $cf_gtxt : '';
}


// -------------------------------------------------------------
// Cleans strings for custom field names and cf_language_names
function glz_sanitize_for_cf($text, $lite = false)
{
    $text = trim($text);

    if ($lite) {
        // lite (legacy)
        // U&lc letters, numbers, spaces, dashes and underscores
        return preg_replace('/[^A-Za-z0-9\s\_\-]/', '', $text);
    } else {
        // strict
        // lowercase letters, numbers and single underscores; may not start with a number
        $patterns[0] = "/[\_\s\-]+/"; // space(s), dash(es), underscore(s)
        $replacements[0] = "_";
        $patterns[1] = "/[^a-z0-9\_]/"; // only a-z, 0-9 and underscore
        $replacements[1] = "";
        $patterns[2] = "/^\d+/"; // numbers at start of string
        $replacements[2] = "";

        return trim(preg_replace($patterns, $replacements, strtolower($text)), "_");
    }
}


// -------------------------------------------------------------
// Checks if a custom field contains invalid characters, starts with a number or has double underscores
function glz_is_valid_cf_name($text)
{
    global $msg;

    if (preg_match('/[^a-z0-9\_]/', $text)) {
        $msg = array(gTxt('glz_cf_name_invalid_chars', array('{custom_name_input}' => $text)), E_WARNING);
    } elseif (preg_match('/^\d+/', $text)) {
        $msg = array(gTxt('glz_cf_name_invalid_starts_with_number', array('{custom_name_input}' => $text)), E_WARNING);
    } elseif (preg_match('/\_{2,}/', $text)) {
        $msg = array(gTxt('glz_cf_name_invalid_double_underscores', array('{custom_name_input}' => $text)), E_WARNING);
    }
}

// -------------------------------------------------------------
// Checks if specified start date matches current date format
function glz_is_valid_start_date($date)
{
    global $prefs;
    $formats = array(
          "d/m/Y" => "dd/mm/yyyy",
          "m/d/Y" => "mm/dd/yyyy",
          "Y-m-d" => "yyyy-mm-dd",
          "d m y" => "dd mm yy",
          "d.m.Y" => "dd.mm.yyyy"
    );

    $datepicker_format = array_search($prefs['glz_cf_datepicker_format'], $formats);

    $d = DateTime::createFromFormat($datepicker_format, $date);
    return $d && $d->format($datepicker_format) == $date;
}


// -------------------------------------------------------------
// Accommodate relative urls in prefs
// $addhost = true prepends the hostname
function glz_relative_url($url, $addhost = false)
{
    $parsed_url = parse_url($url);
    if (empty($parsed_url['scheme']) && empty($parsed_url['hostname'])) {
        if ($addhost) {
            $hostname = (empty($txpcfg['admin_url']) ? hu : ahu);
        } else {
            $hostname = "/";
        }
        $url = $hostname.ltrim($url, '/');
    }
    return $url;
}


// -------------------------------------------------------------
// Removes empty values from arrays - used for new custom fields
function glz_array_empty_values($value)
{
    if (!empty($value)) {
        return $value;
    }
}


// -------------------------------------------------------------
// Removes { } from values which are marked as default
function glz_clean_default($value)
{
    if(!empty($value)) {
        $pattern = "/^.*\{(.*)\}.*/";
        return preg_replace($pattern, "$1", $value);
    }
}


// -------------------------------------------------------------
// Calls glz_clean_default() in an array context
function glz_clean_default_array_values($value)
{
    $value = glz_clean_default($value);
}


// -------------------------------------------------------------
// Return our default value from all custom_field values
function glz_default_value($all_values)
{
    if (is_array($all_values)) {
        preg_match("/(\{.*\})/", join(" ", $all_values), $default);
        return ((!empty($default) && $default[0]) ? $default[0] : '');
    }
}


// -------------------------------------------------------------
// Custom_set without "_set" e.g. custom_1_set => custom_1
// or custom set formatted for IDs e.g. custom-1
function glz_custom_number($custom_set, $delimiter="_")
{
    // Trim "_set" from the end of the string
    $custom_field = substr($custom_set, 0, -4);

    // If a delimeter is specified custom_X to custom{delimeter}X
    if ($delimiter != "_") {
        $custom_field = str_replace("_", $delimiter, $custom_field);
    }
    return $custom_field;
}


// -------------------------------------------------------------
// Custom_set digit e.g. custom_1_set => 1
function glz_custom_digit($custom_set)
{
    $out = explode("_", $custom_set);
    // $out[0] will always be 'custom'
    return $out[1]; // so take $out[1]
}


// -------------------------------------------------------------
// Returns the custom_X_set from a custom set name e.g. "rating" gives us custom_1_set
function glz_get_custom_set($value)
{
    $result = safe_field(
        "name",
        'txp_prefs',
        "event = 'custom' AND val = '".doSlash(val)."'"
    );
    if (!$result) {
        // No result -> return error message
        trigger_error(gTxt('glz_cf_doesnt_exist', array('{custom_set_name}' => $value)), E_USER_WARNING);
        return false;
    }
    return true;
}


// -------------------------------------------------------------
// Get the article ID, even if it's newly saved
function glz_get_article_id()
{
    return (!empty($GLOBALS['ID']) ? $GLOBALS['ID'] : gps('ID'));
}


// -------------------------------------------------------------
// Is the custom field name already taken?
function glz_check_custom_set_name($custom_set_name, $custom_set)
{
    // Check that the name input by the user as well as its sanitized version don't already exist
    return safe_field(
        "name",
        'txp_prefs',
        "event = 'custom' AND val IN ('".doSlash($custom_set_name)."', '".glz_sanitize_for_cf($custom_set_name)."') AND name <> '".doSlash($custom_set)."'"
    );
}


// -------------------------------------------------------------
// Edit/delete buttons in custom_fields table require a form each
function glz_form_buttons($action, $value, $custom_set, $custom_set_name, $custom_set_type, $custom_set_position, $onsubmit='')
{
    $onsubmit = ($onsubmit) ? 'onsubmit="'.$onsubmit.'"' : '';

    // ui-icon (see admin hive styling)
    if ($action == "delete") {
        $ui_icon = "close";
    }
    if ($action == "reset") {
        $ui_icon = "trash";
    }
    if ($action == "add") {
        $ui_icon = "circlesmall-plus";
    }

    $tagend = get_pref('doctype') === 'html5' ? ">" : " />";

    return
    '<form class="action-button" method="post" action="index.php" '.$onsubmit.'>
        <input name="custom_set" value="'.$custom_set.'" type="hidden"'.$tagend.'
        <input name="custom_set_name" value="'.$custom_set_name.'" type="hidden"'.$tagend.'
        <input name="custom_set_type" value="'.$custom_set_type.'" type="hidden"'.$tagend.'>
        <input name="custom_set_position" value="'.$custom_set_position.'" type="hidden"'.$tagend.'
        <input name="event" value="glz_custom_fields" type="hidden"'.$tagend.'
        <button name="'.$action.'" type="submit" value="'.$value.'"
                class="jquery-ui-button-icon-left ui-button ui-corner-all ui-widget">
            <span class="ui-button-icon ui-icon ui-icon-'.$ui_icon.'"></span>
            <span class="ui-button-icon-space"> </span>
            '.gTxt("glz_cf_action_".$action).'
        </button>
    </form>';
}


// TODO: Appears to be unused?!
// -------------------------------------------------------------
// Returns all sections/categories that are searchable
function glz_all_searchable_sections_categories($type)
{
    $type = (in_array($type, array('category', 'section')) ? $type : 'section');
    $condition = "";

    if ($type == "section") {
        $condition .= "searchable='1'";
    } else {
        $condition .= "name <> 'root' AND type='article'";
    }

    $result = safe_rows('*', "txp_{$type}", $condition);

    $out = array();
    foreach ($result as $value) {
        $out[$value['name']] = $value['title'];
    }

    return $out;
}
