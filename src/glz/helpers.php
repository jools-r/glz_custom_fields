<?php

// -------------------------------------------------------------
// the types our custom fields can take
function glz_custom_set_types() {

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
// outputs only custom fields that have been set, i.e. have a name assigned to them
function glz_check_custom_set($all_custom_sets, $step) {

    $out = array();
    foreach ($all_custom_sets as $key => $custom_field) {
        if (!empty($custom_field['name'])) {
            if ( ($step == "body") && ($custom_field['type'] == "textarea") ) {
                $out[$key] = $custom_field;
            } else if ( ($step == "custom_fields") && ($custom_field['type'] != "textarea") ) {
                $out[$key] = $custom_field;
            }
        }
    }
    return $out;

}


// -------------------------------------------------------------
// goes through all custom sets, returns the first one which is not being used
function glz_next_empty_custom() {

    global $all_custom_sets;

    foreach ( $all_custom_sets as $custom => $custom_set ) {
        if ( empty($custom_set['name']) ) {
            return $custom;
        }
    }

}


// -------------------------------------------------------------
// converts all values into id safe ones [A-Za-z0-9-]
function glz_idify($value) {

    $patterns[0] = "/\s/";
    $replacements[0] = "-";
    $patterns[1] = "/[^a-zA-Z0-9\-]/";
    $replacements[1] = "";

    return preg_replace($patterns, $replacements, strtolower($value));

}


// -------------------------------------------------------------
// will leave only [A-Za-z0-9_- ] in the string
function glz_clean_string($string) {

    if ($string) {
        return preg_replace('/[^A-Za-z0-9\s\_\-]/', '', $string);
    }

}


// -------------------------------------------------------------
// removes empty values from arrays - used for new custom fields
function glz_array_empty_values($value) {

    if ( !empty($value) ) {
        return $value;
    }

}


// -------------------------------------------------------------
// strips slashes in arrays, used in conjuction with e.g. array_map
function glz_array_stripslashes(&$value) {

    return stripslashes($value);

}


// -------------------------------------------------------------
// removes { } from values which are marked as default
function glz_clean_default($value) {

    $pattern = "/^.*\{(.*)\}.*/";
    return preg_replace($pattern, "$1", $value);

}


// -------------------------------------------------------------
// calls glz_clean_default() in an array context
function glz_clean_default_array_values(&$value) {

    $value = glz_clean_default($value);

}


// -------------------------------------------------------------
// return our default value from all custom_field values
function glz_default_value($all_values) {

    if ( is_array($all_values) ) {
        preg_match("/(\{.*\})/", join(" ", $all_values), $default);
        return ( (!empty($default) && $default[0]) ? $default[0] : '');
    }

}


// -------------------------------------------------------------
// custom_set without "_set" e.g. custom_1_set => custom_1
// or custom set formatted for IDs e.g. custom-1
function glz_custom_number($custom_set, $delimiter="_") {

    // trim "_set" from the end of the string
    $custom_field = substr($custom_set, 0, -4);

    // if a delimeter is specified custom_X to custom{delimeter}X
    if ($delimiter != "_") {
        $custom_field = str_replace("_", $delimiter, $custom_field);
    }
    return $custom_field;

}


// -------------------------------------------------------------
// custom_set digit e.g. custom_1_set => 1
function glz_custom_digit($custom_set) {

    $out = explode("_", $custom_set);
    // $out[0] will always be 'custom'
    return $out[1]; // so take $out[1]

}


// -------------------------------------------------------------
// returns the custom_X_set from a custom set name e.g. "Rating" gives us custom_1_set
function glz_get_custom_set($value) {

    global $all_custom_sets;

    // loop over custom fields and see if requested name exists
    foreach ( $all_custom_sets as $custom => $custom_set ) {
        if ( $custom_set['name'] == $value ) {
            return $custom;
        }
    }
    // no result -> return error message
    trigger_error(gTxt('glz_cf_doesnt_exist', array('{custom_set_name}' => $value)), E_USER_WARNING);

}


// -------------------------------------------------------------
// get the article ID, even if it's newly saved
function glz_get_article_id() {

    return ( !empty($GLOBALS['ID']) ? $GLOBALS['ID'] : gps('ID') );

}


// -------------------------------------------------------------
// helps with range formatting - just DRY
function glz_format_ranges($arr_values, $custom_set_name) {

    $out = array();
    foreach ( $arr_values as $key => $value ) {
        $out[$key] = ( strstr($custom_set_name, 'range') ) ?
            glz_custom_fields_range($value, $custom_set_name) :
            $value;
    }

    return $out;

}


// -------------------------------------------------------------
// a callback for the glz_format_ranges() function
function glz_custom_fields_range($custom_value, $custom_set_name) {

    // last part of string is the range unit (e.g. $, &pound;, m<sup>3</sup> etc.)
    $range_unit = array_pop(explode(' ', $custom_set_name));

    // should range unit should go after range
    if ( strstr($range_unit, '(after)') ) {
        // trim '(after)' from the range unit
        $range_unit = substr($range_unit, 0, -7);
        $after = 1;
    }

    // is it a range or single value
    $arr_value = explode('-', $custom_value);
    // a range
    if ( is_array($arr_value) ) {
        $out = '';
        foreach ( $arr_value as $value ) {
            // add range unit before or after
            $out[] = ( !isset($after) ) ?
              $range_unit.number_format($value) : number_format($value).$range_unit;
        }
        return implode('-', $out);
    }
    // a single value
    else {
        // add range unit before or after
        return ( !isset($after) ) ?
          $range_unit.number_format($value) : number_format($value).$range_unit;
    }

}


// -------------------------------------------------------------
// returns the next available number for custom set
function glz_custom_next($arr_custom_sets) {

    $arr_extra_custom_sets = array();
    foreach ( array_keys($arr_custom_sets) as $extra_custom_set) {
        $arr_extra_custom_sets[] = glz_custom_digit($extra_custom_set);
    }
    // order the array
    sort($arr_extra_custom_sets);

    for ( $i=0; $i < count($arr_extra_custom_sets); $i++ ) {
        if ($arr_extra_custom_sets[$i] > $i+1) {
            return $i+1;
        }
    }

    return count($arr_extra_custom_sets)+1;

}


// -------------------------------------------------------------
// is the custom field name already taken?
function glz_check_custom_set_name($arr_custom_fields, $custom_set_name, $custom_set='') {

    foreach ( $arr_custom_fields as $custom => $arr_custom_set ) {
        if ( ($custom_set_name === $arr_custom_set['name']) && (!empty($custom_set) && $custom_set != $custom) ) {
            return TRUE;
        }

    }

    return FALSE;
}


// -------------------------------------------------------------
// edit/delete buttons in custom_fields table require a form each
function glz_form_buttons($action, $value, $custom_set, $custom_set_name, $custom_set_type, $custom_set_position, $onsubmit='') {

    $onsubmit = ($onsubmit) ? 'onsubmit="'.$onsubmit.'"' : '';

    // ui-icon (see admin hive styling)
    if ( $action == "delete") { $ui_icon = "close";  }
    if ( $action == "reset")  { $ui_icon = "trash"; }
    if ( $action == "edit")   { $ui_icon = "pencil"; }

    return
    '<form class="action-button" method="post" action="index.php" '.$onsubmit.'>
        <input name="custom_set" value="'.$custom_set.'" type="hidden" />
        <input name="custom_set_name" value="'.$custom_set_name.'" type="hidden" />
        <input name="custom_set_type" value="'.$custom_set_type.'" type="hidden" />
        <input name="custom_set_position" value="'.$custom_set_position.'" type="hidden" />
        <input name="event" value="glz_custom_fields" type="hidden" />
        <button name="'.$action.'" type="submit" value="'.$value.'"
                class="jquery-ui-button-icon-left ui-button ui-corner-all ui-widget">
            <span class="ui-button-icon ui-icon ui-icon-'.$ui_icon.'"></span>
            <span class="ui-button-icon-space"> </span>
            '.gTxt("glz_cf_action_".$action).'
        </button>
    </form>';
//      <input name="'.$action.'" value="'.$value.'" type="submit" />
}


// TODO: Appears to be unused?!
// -------------------------------------------------------------
// returns all sections/categories that are searchable
function glz_all_searchable_sections_categories($type) {

    $type = (in_array($type, array('category', 'section')) ? $type : 'section');
    $condition = "";

    if ( $type == "section" ) {
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

?>
