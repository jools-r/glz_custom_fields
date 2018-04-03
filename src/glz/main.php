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
#   MAIN – Register plugin privs + callbacks + dispatcher
#
##################

global  $event, $step, $use_minified, $txp_permissions;

// DEBUG: set to false to load regular (non-minified) js and css files
$use_minified = true;

if(@txpinterface == 'admin') {

    // glz admin panels / events
    $glz_admin_events = array(
        'article',
        'prefs',
        'glz_custom_fields'
    );

    // Add prefs privs
    add_privs('prefs.glz_custom_f', '1');
    add_privs('prefs.glz_custom_f.glz_cf_datepicker', '1');
    add_privs('prefs.glz_custom_f.glz_cf_timepicker', '1');

    // Disable regular customs preferences (remove privs)
    $txp_permissions['prefs.custom'] = '';

    // Redirect 'Options' link on plugins panel to prefrences
    register_callback('glz_custom_fields_prefs_redirect', 'plugin_prefs.glz_custom_fields');

    // Install plugin
    register_callback('glz_custom_fields_install', 'plugin_lifecycle.glz_custom_fields', 'installed');
    register_callback('glz_custom_fields_uninstall', 'plugin_lifecycle.glz_custom_fields', 'deleted');

    // Restrict css/js + pre-save to relevant admin pages only
    if (in_array($event, $glz_admin_events)) {

        // Add CSS & JS to admin head area
        add_privs('glz_custom_fields_inject_css_js', '1,2,3,4,5,6');
        register_callback('glz_custom_fields_inject_css_js', 'admin_side', 'head_end');

        // Use jqueryui.sortable to set the custom field position value
        if ($prefs['glz_cf_use_sortable'] == '1') {
            register_callback('glz_cf_positionsort_js', 'customfields_ui', 'table_end');
            register_callback('glz_cf_positionsort_steps', 'glz_custom_fields');
        }

        // Write tab: multiple value array -> string conversion on save/create
        if (($step === 'edit') || ($step === 'create')) {
            add_privs('glz_custom_fields_before_save', '1,2,3,4,5,6');
            register_callback('glz_custom_fields_before_save', 'article', '', 1);
        }
    }

    // Custom fields tab under extensions
    add_privs('glz_custom_fields', '1,2');
    register_tab('extensions', 'glz_custom_fields', gTxt('glz_cf_tab_name'));
    register_callback('glz_cf_dispatcher', 'glz_custom_fields');

    // Write tab: replace regular custom fields with glz custom fields
    add_privs('glz_custom_fields_replace', '1,2,3,4,5,6');
    // -> custom fields
    register_callback('glz_custom_fields_replace', 'article_ui', 'custom_fields');
    // -> textareas
    register_callback('glz_custom_fields_replace', 'article_ui', 'body');

}


/**
 * Jump off to relevant stub for handling actions.
 */
function glz_cf_dispatcher()
{
    global $event, $step;

    // Available steps
    $steps = array(
        'add'    => true,
        'edit'   => true,
        'save'   => true,
        'reset'  => true,
        'delete' => true
    );

    // Use default step if nothing matches
    if(!$step || ((!bouncer($step, $steps)) || !isset($steps[$step]))) {
        $step = 'list';
    }

    // Run the function
    $func = 'glz_cf_' . $step;
    $func();
}
