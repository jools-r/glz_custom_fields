## GLZ CUSTOM FIELDS for Textpattern CMS

### Update for Textpattern 4.7 first pass

**IMPORTANT: Backup your database before experimenting with this version!**

**Note:** This plugin has been extensively refactored and is therefore **not backwards-compatible** to earlier versions of TXP.

### Unlimited custom fields for Textpattern

This plugin sits under the **Extensions** tab in the back-end and gives your custom fields new life. You can have custom fields that are selects, multi-selects, checkboxes, radios and textareas - besides the default input fields. You can predefine values for custom fields and you can select a single default value (selects, multi-selects, checkboxes and radios only). Rather then constantly typing in the same thing over and over again, just select or check it and you're set.

The plugin doesn't require any hacking of TXP, it's a straight install with instant gratification.

### Installation

1. Make a backup of your existing database for safety's sake.
2. Copy the `plugins` folder including its contents to your textpattern folder.
3. Copy the contents of `glz_custom_fields_v2.0 beta_zip.txt` and paste it into the `Admin › Plugins` installation box. Install and activate the plugin.
4. Visit the `Extensions › Custom Fields` tab and then `Preferences` to verify your plugin preferences.

**Other locations:** You can move the files from the plugins folder if you so wish, e.g. to your `assets/js/` and `assets/css/` folders but adjust the new js/css url pref accordingly in the plugin preferences. The Datepicker and Timepicker folders may also be moved but its contents should remain together.

**Multi-site installations:** copy the `plugins` folder including its contents to the `admin` folder of your multi-site. Adjust the custom_fields prefs to match your admin-side path and subdomain URL.

### Changes

- 4.7 compatibility + repaired broken functionality
- Custom field label titles and field label instructions text
- Translatable: now uses in-built gTxt function and textpacks
- Plugin preferences now handled in Admin › Preferences
- Separate list and edit panels in line with other Textpattern panes (better for longer lists)
- UI now built with Textpattern's in-built functions (aligns with admin theme styling)
- Error messaging now handled by Textpattern's own error messaging system
- Installing should not overwrite existing settings (but will migrate to 'namespaced' prefs names)
- New js/css URL location prefs: locate your files where you want them in your project (also for multisite installations). Relative URLs also possible
- Better prefs consistency checking
- Updated + minified js/css and only loaded where needed
- Datepicker/timepicker libraries updated (deprecated $.browser detection removed to prevent misleading error message on write tab)
- Refactored source files: edit with [rah_blobin](https://github.com/jools-r/rah_blobin) and compile with [rah_mass_compiler](https://github.com/gocom/MassPlugCompiler)


### License

Copyright (c) 2012 Gerhard Lazu

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
