## GLZ CUSTOM FIELDS for Textpattern CMS

### Update for Textpattern 4.7 first pass

**Backup your database before experimenting with this version!**

**Note:** This plugin has been extensively refactored and is therefore
**not backwards-compatible** to earlier versions of TXP.

### Unlimited custom fields for Textpattern

This plugin sits under the **Extensions** tab in the back-end and gives
your custom fields new life. You can have custom fields that are selects,
multi-selects, checkboxes, radios and textareas - besides the default
input fields. You can predefine values for custom fields and you can
select a single default value (selects, multi-selects, checkboxes
and radios only). Rather then constantly typing in the same thing over
and over again, just select or check it and you're set.

The plugin doesn't require any hacking of TXP, it's a straight install
with instant gratification.

### Installation

1. Make a backup of your existing database for safety's sake.
2. Copy the `plugins` folder including its contents to your textpattern folder.
3. Copy the contents of `glz_custom_fields_v4.7 beta_zip.txt` and paste it into the `Admin › Plugins` installation box. Install and activate the plugin.
4. Visit the `Extensions › Custom Fields` tab and then `Setup` to verify your plugin preferences.

**Other locations:** You can move the files from the plugins folder if you so wish, e.g. to your `assets/js/` and `assets/css/` folders but adjust the new js/css url pref accordingly in the plugin preferences. The Datepicker and Timepicker folders may also be moved but its contents should remain together.

**Multi-site installations:** copy the `plugins` folder including its contents to the `admin` folder of your multi-site. Adjust the custom_fields prefs to match your admin-side path and subdomain URL.

### Changes

- Tentative 4.7 compatibility
- Restored broken functionality
- Installing should not overwrite existing settings
- New js/css URL location prefs: locate your files where you want them in your project (also for multisite installations)
- Translatable: now uses Textpattern gTxt function and textpacks
- Uses TXP’s own error messaging system
- HTML aligned to ‘Hive’ admin styling
- Updated + minified js/css
- Datepicker/timepicker libraries updated (deprecated $.browser detection removed to prevent misleading error message on write tab)
- Refactored source files: edit with [rah_blobin](https://github.com/jools-r/rah_blobin) and compile with [rah_mass_compiler](https://github.com/gocom/MassPlugCompiler)

### Known issues

- Datepicker + timepicker lose their settings after asynchronous saving of an article. Workaround: re-open the article / refresh page to reinitiate.


### License

Copyright (c) 2012 Gerhard Lazu

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
