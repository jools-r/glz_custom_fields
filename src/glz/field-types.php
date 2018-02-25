<?php

// -------------------------------------------------------------
// Formats the custom set output based on its type
function glz_format_custom_set_by_type($custom, $custom_id, $custom_set_type, $arr_custom_field_values, $custom_value = "", $default_value = "")
{
    if (is_array($arr_custom_field_values)) {
        $arr_custom_field_values = array_map('glz_array_stripslashes', $arr_custom_field_values);
    }

    switch ($custom_set_type) {
        // These are the normal custom fields
        case "text_input":
            return array(
                fInput("text", $custom, $custom_value, "edit", "", "", "22", "", $custom_id),
                'glz_custom_field'
            );

        case "select":
            return array(
                glz_selectInput($custom, $custom_id, $arr_custom_field_values, $custom_value, $default_value),
                'glz_custom_select_field'
            );

        case "multi-select":
            return array(
                glz_selectInput($custom, $custom_id, $arr_custom_field_values, $custom_value, $default_value, 1),
                'glz_custom_multi-select_field'
            );

        case "checkbox":
            return array(
                glz_checkbox($custom, $arr_custom_field_values, $custom_value, $default_value),
                'glz_custom_checkbox_field'
            );

        case "radio":
            return array(
                glz_radio($custom, $custom_id, $arr_custom_field_values, $custom_value, $default_value),
                'glz_custom_radio_field'
            );

        case "textarea":
            return array(
                text_area($custom, 0, 0, $custom_value, $custom_id),
                'glz_text_area_field'
            );

        // Here start the special custom fields, might need to refactor the return, starting to repeat itself
        case "date-picker":
            return array(
                fInput("text", $custom, $custom_value, "edit date-picker", "", "", "22", "", $custom_id),
                'glz_custom_date-picker_field clearfix'
            );

        case "time-picker":
            return array(
                fInput("text", $custom, $custom_value, "edit time-picker", "", "", "22", "", $custom_id),
                'glz_custom_time-picker_field'
            );

        case "custom-script":
            global $custom_scripts_path;
            return array(
                glz_custom_script($custom_scripts_path."/".reset($arr_custom_field_values), $custom, $custom_id, $custom_value),
                'glz_custom_field_script'
            );

        // A type has been passed that is not supported yet
        default:
            return array(
                gTxt('glz_cf_type_not_supported'),
                'glz_custom_field'
            );
    }
}


// -------------------------------------------------------------
// Had to duplicate the default selectInput() because trimming \t and \n didn't work + some other mods & multi-select
function glz_selectInput($name = '', $id = '', $arr_values = '', $custom_value = '', $default_value = '', $multi = '')
{
    if (is_array($arr_values)) {
        global $prefs;
        $out = array();

        // If there is no custom_value coming from the article, let's use our default one
        if (empty($custom_value)) {
            $custom_value = $default_value;
        }

        foreach ($arr_values as $key => $value) {
            $selected = glz_selected_checked('selected', $key, $custom_value, $default_value);
            $out[] = "<option value=\"$key\"{$selected}>$value</option>";
        }

        // We'll need the extra attributes as well as a name that will produce an array
        if ($multi) {
            $multi = ' multiple="multiple" size="'.$prefs['multiselect_size'].'"';
            $name .= "[]";
        }

        return "<select id=\"".glz_idify($id)."\" name=\"$name\" class=\"list\"$multi>".
      ($default_value ? '' : "<option value=\"\"$selected>&nbsp;</option>").
      ($out ? join('', $out) : '').
      "</select>";
    } else {
        return gTxt('glz_cf_field_problems', array('{custom_set_name}' => $name));
    }
}


// -------------------------------------------------------------
// Had to duplicate the default checkbox() to keep the looping in here and check against existing value/s
function glz_checkbox($name = '', $arr_values = '', $custom_value = '', $default_value = '')
{
    if (is_array($arr_values)) {
        $out = array();

        // If there is no custom_value coming from the article, let's use our default one
        if (empty($custom_value)) {
            $custom_value = $default_value;
        }

        foreach ($arr_values as $key => $value) {
            $checked = glz_selected_checked('checked', $key, $custom_value);

            $out[] = "<div class=\"txp-form-checkbox glz-cf-".str_replace("_", "-", glz_idify($key))."\"><input type=\"checkbox\" name=\"{$name}[]\" value=\"$key\" class=\"checkbox\" id=\"".glz_idify($key)."\"{$checked} /> <label for=\"".glz_idify($key)."\">$value</label></div>";
        }

        return join('', $out);
    } else {
        return gTxt('glz_cf_field_problems', array('{custom_set_name}' => $name));
    }
}


// -------------------------------------------------------------
// Had to duplicate the default radio() to keep the looping in here and check against existing value/s
function glz_radio($name = '', $id = '', $arr_values = '', $custom_value = '', $default_value = '')
{
    if (is_array($arr_values)) {
        $out = array();

        // If there is no custom_value coming from the article, let's use our default one
        if (empty($custom_value)) {
            $custom_value = $default_value;
        }

        foreach ($arr_values as $key => $value) {
            $checked = glz_selected_checked('checked', $key, $custom_value);

            $out[] = "<div class=\"txp-form-radio glz-cf-".str_replace("_", "-", glz_idify($key))."\"><input type=\"radio\" name=\"$name\" value=\"$key\" class=\"radio\" id=\"{$id}_".glz_idify($key)."\"{$checked} /> <label for=\"{$id}_".glz_idify($key)."\">$value</label></div>";
        }

        return join('', $out);
    } else {
        return gTxt('glz_cf_field_problems', array('{custom_set_name}' => $name));
    }
}


// -------------------------------------------------------------
// Checking if this custom field has selected or checked values
function glz_selected_checked($range_unit, $value, $custom_value = '')
{
    // We're comparing against a key which is a "clean" value
    $custom_value = htmlspecialchars($custom_value);

    // Make an array if $custom_value contains multiple values
    if (strpos($custom_value, '|')) {
        $arr_custom_value = explode('|', $custom_value);
    }

    if (isset($arr_custom_value)) {
        $out = (in_array($value, $arr_custom_value)) ? " $range_unit=\"$range_unit\"" : "";
    } else {
        $out = ($value == $custom_value) ? " $range_unit=\"$range_unit\"" : "";
    }

    return $out;
}


//-------------------------------------------------------------
// Button gets more consistent styling across browsers rather than input type="submit"
// included in this plugin until in makes it into TXP - if that ever happens...
function glz_fButton($type, $name, $contents='Submit', $value, $class='', $id='', $title='', $onClick='', $disabled = false)
{
    $o  = '<button type="'.$type.'" name="'.$name.'"';
    $o .= ' value="'.htmlspecialchars($value).'"';
    $o .= ($class)    ? ' class="'.$class.'"' : '';
    $o .= ($id)       ? ' id="'.$id.'"' : '';
    $o .= ($title)    ? ' title="'.$title.'"' : '';
    $o .= ($onClick)  ? ' onclick="'.$onClick.'"' : '';
    $o .= ($disabled) ? ' disabled="disabled"' : '';
    $o .= '>';
    $o .= $contents;
    $o .= '</button>';
    return $o;
}


//-------------------------------------------------------------
// Evals a PHP script and displays output right under the custom field label
function glz_custom_script($script, $custom, $custom_id, $custom_value)
{
    if (is_file($script)) {
        include_once($script);
        $custom_function = basename($script, ".php");
        if (is_callable($custom_function)) {
            return call_user_func_array($custom_function, array($custom, $custom_id, $custom_value));
        } else {
            return gTxt('glz_cf_not_callable', array('{function}' => $custom_function, '{file}' => $script));
        }
    } else {
        return '<span class="error"><span class="ui-icon ui-icon-alert"></span> '.gTxt('glz_cf_not_found', array('{file}' => $script)).'</span>';
    }
}
