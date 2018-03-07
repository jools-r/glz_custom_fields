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
#   PUBLIC TAGS
#
##################


/*
 * Adds a title attribute to txp:custom_fields.
 * Set to title="1" to show title in the current language.
 * If no title is available, it returns nothing.
 */

// Divert txp:custom_field calls through glz_custom_field
if (class_exists('\Textpattern\Tag\Registry')) {
        Txp::get('\Textpattern\Tag\Registry')
            ->register('glz_custom_field', 'custom_field');
}

function glz_custom_field($atts, $thing = null)
{
    // Extract attributes as vars
    extract(lAtts(array(
        'title' => '0',
        'name' => ''
    ), $atts, false));  // false: suppress warnings

    // Unset otherwise non-existent attribute
    unset($atts['title']);

    // if $title is specified, divert to glz_cf_gtxt
    return $title ? glz_cf_gtxt($name) : custom_field($atts, $thing);
}
