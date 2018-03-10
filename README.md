## GLZ CUSTOM FIELDS for Textpattern CMS

### Update for Textpattern 4.7 – v2.0 beta

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

See "changelog":https://github.com/jools-r/glz_custom_fields/blob/master/docs/changelog.textile.

### Credits

See "credits":https://github.com/jools-r/glz_custom_fields/blob/master/docs/credits.textile.


### License

Copyright (c) 2012 Gerhard Lazu

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
