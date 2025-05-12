## GLZ CUSTOM FIELDS for Textpattern CMS

### v2.0.6

Compatible with PHP8 and Textpattern 4.9


### Update for Textpattern 4.7 – v2.0.1

**IMPORTANT: Backup your database before experimenting with this version!**

**Note:** This plugin has been extensively refactored and is therefore **not backwards-compatible** to earlier versions of TXP.

### Unlimited custom fields for Textpattern

This plugin sits under the **Extensions** tab in the back-end and gives your custom fields new life. You can have custom fields that are selects, multi-selects, checkboxes, radios and textareas - besides the default input fields. You can predefine values for custom fields and you can select a single default value (selects, multi-selects, checkboxes and radios only). Rather then constantly typing in the same thing over and over again, just select or check it and you're set.

### Installation

1. Make a backup of your existing database for safety's sake.
2. Copy the `plugins` folder including its contents to your textpattern folder.
3. Copy the contents of `glz_custom_fields_v2.0 beta_zip.txt` and paste it into the `Admin › Plugins` installation box. Install and activate the plugin.
4. Visit the `Admin › Preferences » Custom fields preferences` panel to verify your plugin preferences. Correct if needed.
5. Visit the `Extensions › Custom Fields` panel to begin editing and adding new custom fields.

**Other locations:** You can move the files from the plugins folder if you so wish, e.g. to your `assets/js/` and `assets/css/` folders but adjust the new js/css url pref accordingly in the plugin preferences. The Datepicker and Timepicker folders may also be moved but its contents should remain together.

**Multi-site installations:** copy the `plugins` folder including its contents to the `admin` folder of your multi-site. If necessary, adjust the custom_fields prefs to match your admin-side path and subdomain URL.


### Changelog

v2.0.6 – May 2025

- Prevent `{`default`}` braces from showing in radio/checkbox/select choices
- HTML5-compliant void tag endings for radios and checkboxes

v2.0.5 – July 2024

- PHP 8+ patches / deprecation notices
- CSP nonce support for script and style blocks
- CSS tweaks

v2.0.1 – March 2021

- PHP 7.4 deprecation notice / PHP 8 error patched

v2.0 – March 2018

- Refactored for Textpattern v4.7
- Plugin preferences now handled in Admin › Preferences
- Plugin preferences not overwritten during installation
- Support for custom field title labels and supporting instruction hints (also per UI-language)
- Custom field titles are accessible in page templates and forms by adding the attribute `title="1"` to [txp:custom_field](https://docs.textpattern.io/tags/custom_field).
- Change order of custom fields per drag and drop (deactivatable via a hidden pref)
- Translatable: UI now uses textpacks throughout, custom field labels also translatable by switching the UI language
- Under the hood: makes use of Textpattern’s in-built functions for UI creation, error messages/notices, language strings, field labels
- New prefs for js/css URL location including support for relative URLs: relocate your js/css files where you want them
- Compatibility with multi-site Textpattern installations
- Updated + minified js/css and datepicker/timepicker labels
- Adding/deleting/changing a custom field updates site-wide last modified date (for cache renewal)


### Credits

This plugin was originally written by [Gerhard Lazu](https://github.com/gerhard) and dates back to [2007](https://forum.textpattern.io/viewtopic.php?pid=157983#p157983). It has been repaired, expanded and adopted by numerous forum members over the years (credited in the code), most recently by [Bloke](https://github.com/Bloke/glz_custom_fields). This significantly refactored version adds compatibility with Textpattern v4.7 and is currently looked after by [jools-r](https://github.com/jools-r/glz_custom_fields). Its concept and core workings are still those of Gerhard's original plugin. The Textpattern core will ultimately support unlimited custom fields, making this plugin obsolete at some point in the future.

This plugin uses **jQuery DatePicker** ([homepage](http://2008.kelvinluck.com/assets/jquery/datePicker/v2/demo/) / [github](https://github.com/vitch/jQuery-datepicker)) by Kelvin Luck, **jQuery Timepicker** ([github](https://github.com/perifer/timePicker)) by Anders Fajerson and **jQuery sortElements** ([github](https://github.com/padolsey-archive/jquery.fn/tree/master/sortElements)) by James Padolsey.


### License

Copyright (c) 2012 Gerhard Lazu

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
