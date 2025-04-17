=== Developer Debug Tools ===
Contributors: apos37, venutius
Tags: debug, developer, testing, wp-config, htaccess
Requires at least: 5.9
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Lots of debugging and testing tools for developers.

== Description ==
The "Developer Debug Tools" WordPress plugin is a powerhouse for developers and site administrators! It's a comprehensive toolkit that helps you identify, troubleshoot, and resolve issues in your WordPress site, making debugging a breeze.

This plugin offers a suite of features to aid in debugging, including, but not limited to:

* Viewing and clearing `debug.log` and other error logs
* Debug log Easy Reader combines duplicate lines and breaks down each error in an easy to read format
* Viewing and updating `wp-config.php` and `.htaccess` files
* Viewing and editing user meta and post meta
* Viewing detailed info on post types and taxonomies
* Quick links for debugging users, posts, pages and comments
* Clearing all taxonomy terms from a given post
* Clearing all or expired transients easily
* Finding which posts and pages shortcodes are used on
* Seeing whom is online with their roles
* Activity logging such as when user meta, post meta, site settings, plugins and themes are updated or when bots crawl the site
* Discord notifications of fatal errors, user page loads, and user logins
* Viewing your database tables and entries at a glance without worry
* Enhancements to the admin bar such as condensing/removing items and seeing user/post info at a glance
* Viewing helpful information such php.ini values, php configs, scheduled cron jobs, site options, global variables, server metrics and more
* Access to a handful of additional functions and hooks that you can use for debugging

With "Developer Debug Tools", you can:

* Identify and fix errors, bugs, and conflicts
* Troubleshoot complex issues with ease
* Update user and post meta straight from the admin area
* Streamline your development and testing workflow

This plugin is a must-have for any WordPress developer or site administrator who wants to ensure a stable, efficient, and high-performing website. It's like having a trusty sidekick that helps you tackle even the most challenging debugging tasks!

---------------------

== Installation ==
1. Install the plugin from your website's plugin directory, or upload the plugin to your plugins folder. 
2. Activate it.
3. Go to Developer Debug Tools in your admin menu.
4. Enter your account email address as a "Developer Email Address" to view the rest of the tools.

== Frequently Asked Questions ==
= Should I backup my wp-config.php and .htaccess files before using the tools to add/remove snippets?
Yes! It is always best to back these files up when making updates to them.

= Can I use this plugin on a live website? =
Yes, but you should always make a backup of your site before using functionality that makes changes to your core files or database.

= My site broke when updating my wp-config.php or .htaccess. How do I revert back to my original? =
The originals are stored in your root folder and renamed with the date and time from which they were replaced. For example, the `wp-config.php` file will have been renamed to `wp-config-2022-08-22-15-25-46.php` and replaced with a new file. Simply log into your FTP or File Manager (from your host), rename the current file to something else such as `wp-config-BROKEN.php` (just in case you need it), and then rename the version you want to revert back to as `wp-config.php`. If everything looks good, then you can either delete this file or send a copy of it to me so I can figure out what went wrong. You can do so in the Discord server mentioned below.

= Why can't I edit a username for a user? =
Some hosts will not allow you to update a user's username directly from WP. In order to do so, you'll have to update it in your database directly.

= Where is the centering tool? =
Viewable only on the front-end, there is a link on the admin bar that shows `+Off`. Click on it and it will add a transparent bar with lines on it at the top of the page underneath the admin bar. If you click on the centering bar it will expand all the way down the page. Click on it again and it will minimize back to the top. You can click on the `+On` link from the admin bar to make it go away.

= Where are the quick debug links? =
You have to enable them on the Developer Debug Tools settings first. Once they are enabled, an "ID" column will be added to the user and/or post admin list pages. Next to the user or post's ID you will see a lightning bolt icon. Clicking on the lightning bolt will redirect you to the User Meta or Post Meta tab on our plugin where you can view and edit all of the meta easily.

= I hid the plugin, now I can't find it! =
You can get there directly by going to `https://yourdomain.com/wp-admin/admin.php?page=dev-debug-tools`. Be sure to bookmark it next time like the instructions say to do!

= Where can I get further support? =
Join my [Discord support server](https://discord.gg/3HnzNEJVnR)

== Screenshots ==
1. Settings page
2. Activated plugins with warnings
3. View, filter, and clear debug.log file
4. View wp-config.php file
5. Some of the snippets you can add/remove from your wp-config.php file
6. View .htaccess file
7. Cron jobs
8. View and update a user's meta
9. Database table records
10. Check API statuses

== Changelog ==
= 2.0.2 =
* Update: Updated author name and website again per WordPress trademark policy
* Update: Changed centering tool option to height and width options for more control

= 2.0.1 =
* Update: Changed author name from Apos37 to WordPress Enhanced, new Author URI
* Tweak: Optimization of main file
* Fix: Remove error count if just cleared
* Fix: Mispelling on WPCONFIG tab
* Fix: Count on tab not using updated debug log path

= 2.0.0.3 =
* Fix: CPU showing overload when using shell

= 2.0.0.2 =
* Fix: Version check error if there is a firewall
* Fix: Server tab issues when there is no access to `/proc/` files
* Fix: If memory is N/A, notice is showing critical; should be hidden

= 2.0.0.1 =
* Fix: Notices at the top of the page smashed into the header

= 2.0.0 =
* Update: Added a new Activity Log tab with different options in settings
* Update: Added a new tab for Server metrics
* Update: Added a new Taxonomies tab
* Update: Added a new Post Types tab
* Update: Added a new Media Library tab
* Update: Added CPU Load and Memory Usage to header
* Tweak: Renamed Logs tab to Error Logs
* Tweak: Changed checkboxes to squares instead of circles because they looked like radio buttons
* Update: Added setting for changing menu type
* Fix: filemtime(): stat failed for Dreamhost Panel Login plugin
* Tweak: Updated Globals tab to include all globals available
* Tweak: Added role names to User Meta tab
* Update: Added API Rest URL to Post Meta tab
* Update: Added featured image to Post Meta tab
* Tweak: Added _edit_lock and _edit_last conversion to Post Meta tab
* Tweak: Added author name and email to Post Meta tab
* Update: Added post comments to Post Meta tab
* Update: Added a "what's new" button to the header

= 1.8.5 =
* Tweak: Updated screenshots for repo
* Update: Added ABSPATH to header
* Fix: A few mispellings
* Tweak: Added support for HTML in Easy Reader error box

= 1.8.4 =
* Fix: On plugins tab, some plugins not in the WP repo causing error with last updated date, preventing page from loading
* Fix: Clearing log and transients removes full path from url instead of just the correct query string

= 1.8.3.2 =
* Fix: Download Debug Log button not downloading file when using custom path

= 1.8.3.1 =
* Tweak: Make DB Tables tab header row sticky when viewing a single table (props venutius for suggestion)
* Update: Added a setting for custom debug log path (for viewing and clearing only)
* Update: Added a Domain tab for checking DNS records
* Fix: Discord fatal error messages not showing quotes properly

= 1.8.3 =
* Update: Added a password feature, new security settings on bottom of Settings tab (props venutius for suggestion)
* Tweak: Bolded the folder size on the Plugins tab if the size is over 2MB
* Update: Refactored the Plugins tab to cache data for 1 day, added caching function to Functions tab for external use (props venutius for suggestion)
* Update: Added table entry viewer to DB Tables tab (props venutius for suggestion)

= 1.8.2 =
* Fix: Error reporting level conversion tool not highlighting correctly, using ajax too much
* Update: Added Error Suppressing tab
* Fix: unserialize(): Error at offset

= 1.8.1 =
* Fix: JS not loading on Post Meta tab for most recent post
* Tweak: Hide purge transient buttons if there are no transients
* Fix: Nonce verification failed on ddtt_get()

= 1.8.0 =
* Fix: Undefined variable $is_mu_plugin (props venutius)
* Fix: GF finder not finding forms
* Update: Added ability to add/update arrays and objects on User Meta and Post Meta tabs
* Tweak: Added horizontal lines to centering tool
* Fix: Centering tool not opening on first click
* Update: Added option to disable admin bar "My Account" enhancements (props venutius)
* Fix: Undefined array key "SERVER_ADDR" (props venutius)
* Fix: ddtt_get_latest_plugin_version() calling api without https:// (props venutius)
* Update: Added Transients tab and moved the Delete Transients buttons to this tab 
* Fix: Warnings from Plugin Checker

= 1.7.8 =
* Fix: Formatting on Available Functions tab

= 1.7.7 =
* Fix: Formatting on Cron Jobs tab
* Fix: Shortcode Finder tab fatal error if a method does not exist
* Fix: Test example array still logging on Shortcode Finder tab
* Fix: Site Options values not displaying
* Fix: Error log path not using option if changed in total error count
* Fix: Error logs only showing 1 error if any are found, now counts all line items
* Fix: PHP 8.3 deprecation notices
* Update: Added server ip address to top of HTACCESS tab for easy reference
* Fix: LearnDash LMS link in admin bar dropdown broken

= 1.7.6 =
* Fix: Converted directory separator to forward slash when using ABSPATH (props amurashkin17)
* Fix: Redacting not working for some people
* Tweak: Reimagine EOL option behavior (props amurashkin17)
* Fix: Detected HTACCESS snippets being ignored if DDT section does not exist

= 1.7.5 =
* Update: Added all site options to Site Options tab, not just registered ones
* Update: Added clear buttons to each Cookie in the Cookies tab, and added a button to add a test cookie
* Update: Added table column names to DB Tables tab
* Update: Added checks for end-of-line delimiters, option to change on WP-CONFIG and HTACCESS tabs (props amurashkin17)
* Fix: WP-CONFIG tab not working for some platforms (props amurashkin17)
* Fix: Some WP-CONFIG files showing incorrect comment color (props amurashkin17)
* Tweak: Added WP_ENVIRONMENT_TYPE snippet to WP-CONFIG tab
* Fix: Detected WP-CONFIG snippets being moved to DDT section if no snippets have been added yet (props amurashkin17)
* Tweak: Added WP_CACHE_KEY_SALT to list of defines to be redacted
* Update: Allowed viewing of last portion of debug log only if above limit (props amurashkin17)
* Update: Added option for updating max debug log size (props amurashkin17 for suggestion)

= 1.7.4 =
* Update: Added a way to list available hooks in other plugins on hooks tab
* Update: Added option to hide plugin
* Tweak: Reorganized main file
* Fix: `include_once` path to `plugin.php` showing incorrectly (props amurashkin17)
* Fix: Installation path on error_log showing incorrectly if using different abspath (props amurashkin17)
* Update: Overhalled snippet sections (props amurashkin17 for suggestions)
* Fix: Gravity Forms quick links not working, incorrect path

= 1.7.3 =
* Fix: Wp-config snippets showing as checked even when they are commented out (props amurashkin17)
* Update: Added an APIs tab to check availability of local REST APIs
* Update: Added a DB Tables tab for quick reference of the database table structure
* Tweak: Comment Meta tab removed and debug info moved to hidden debug page, same as GF forms, entries, etc.
* Fix: JavaScript on Error Reporting tab not loading if Discord fatal error notifications are enabled
* Fix: Priority roles not showing as priority in admin bar
* Fix: Wp-config auth keys and salts not redacting if containing spaces

= 1.7.2 =
* Fix: Error when no description is found on snippets when using hooks
* Tweak: Updated hook examples for `ddtt_wpconfig_snippets` and `ddtt_htaccess_snippets` to include a description
* Tweak: Removed donate option; nobody ever donates
* Update: Discord user notifications now include user avatar as embed thumbnail if one exists
* Fix: Some plugins causing a bottom border on active tab and moving 2nd row of tabs over on hover

= 1.7.1 =
* Fix: Debug log Easy Reader not combining repeated line items

= 1.7.0 =
* Fix: Undefined variable $cancel on htaccess tab
* Fix: Debug log Easy Reader not displaying arrays properly
* Update: Added setting option to disable extra plugin info from plugins page as it's causing some drag for those with a lot of plugins
* Update: Made recommended/featured plugins only load with qs, added button to plugins tab
* Update: Added descriptions to each snippet
* Update: Added a new tab for defined constants
* Update: Added 10 additional wpconfig snippets
* Update: Made adjustments to snippets in wpconfig and htaccess tabs
* Update: Added new snippet to add WP_HOME and WP_SITEURL to wpconfig

= 1.6.8.1 =
* Update: Added new plugin to About tab

= 1.6.8 =
* Update: Added settings for modifying error log paths (props rawsta)
* Fix: Testing playground instructions not showing up by default
* Update: Added quick links and debug colums to comments 
* Tweak: Added thousands separator to total users count 
* Tweak: Removed deactivation survey code and files permanently; only one legitimate response - not worth it
* Tweak: Updated some functions and techniques as recommended by WP Plugin team
* Fix: Warning in live preview about Hello Dolly path

= 1.6.7 =
* Fix: All plugins showing as inactive on sites not on a network
* Tweak: Reduced tags to max 5

= 1.6.6 =
* Update: Prepared for live preview
* Update: Added other plugins to About tab
* Fix: Deprecation warning for ctype_digit(): Argument of type int will be interpreted as string in the future
* Fix: Sorting of plugins on Plugins tab was case sensitive, putting lowercase names like bbPress on bottom
* Fix: Plugins tab not showing all sites on network
* Update: Temporarily disable deactivation feedback form
* Update: Added new tab for viewing and clearing Auto-Drafts

= 1.6.5.1 =
* Fix: Front-end admin menu links visible to people without permissions
* Fix: Front-end admin menu links not able to click if list is too long, made scrollable
* Fix: Separators added to front-end admin menu link if another class is added

= 1.6.5 =
* Update: Added option to add admin menu links to admin bar on front end
* Tweak: Changed Remove Admin Bar Items section to Admin Bar
* Fix: Admin bar post id showing inaccurate info for non-posts/pages

= 1.6.4 =
* Fix: Page load Discord notifications getting inaccurate page when loading non-post/pages
* Fix: Easy reader combining arrays and displaying them inside another array
* Update: Added new function ddtt_backtrace() that logs wp_debug_backtrace_summary() to debug.log
* Update: Added snippets in wp-config and htaccess tabs to increase max input vars
* Fix: Inaccurate error log reporting fatal error to Discord
* Update: Added field to post meta tab for hiding post meta keys with a prefix
* Update: Added field to user meta tab for hiding user meta keys with a prefix

= 1.6.3 =
* Update: Add hook for filtering quick link post types in case some post types are not registered
* Fix: Quick links not showing up on posts, pages, and custom post types

= 1.6.2 =
* Tweak: Prevent adding non-txt files to additional logs field
* Tweak: Clean up some code
* Fix: Removed unneccesary instantiation of Discord class
* Tweak: Removed all unneccesary static declarations and usage
* Fix: is_plugin_active() not found when Gravity Forms is deactivated
* Tweak: Added Child Theme Configurator to recommended plugins
* Tweak: Added NS Cloner - Site Copier to recommended plugins

= 1.6.1 =
* Fix: array_intersect() error on class-online-users.php

= 1.6.0 =
* Update: Added option in settings for sending fatal errors to a Discord channel

= 1.5.9 =
* Fix: Custom logs trying to load when saving settings with no custom log defined
* Tweak: Added a video tutorial for migrating WP in Resources

= 1.5.8.1 =
* Fix: Developer email field pattern not recognizing dashes or periods in domain

= 1.5.8 =
* Tweak: Added sections in Logs tab for each log that is being checked
* Update: Added field in settings for adding custom logs and viewing them on the Logs tab
* Tweak: Added String locator to recommended plugins
* Update: Added quick error_reporting code converter to bottom of Error Reporting tab

= 1.5.7.1 =
* Tweak: Added a notice to Error Reporting tab if error reporting is being overwritten by another plugin or custom code

= 1.5.7 =
* Fix: User error tracking in debug.log causing issues when not executed by a user directly
* Fix: File size and last modified dates not working on must-use plugins
* Update: Added Error Reporting tab

= 1.5.6 =
* Tweak: Added title, ID, and post type to Discord page load notifications if on front-end or editing back-end
* Tweak: Added Redirection to recommended plugins
* Tweak: Added version logging to deactivation feedback to make it easier to chase down errors
* Tweak: Reformat hook examples on Hooks tab, removed `is_plugin_active()`
* Fix: Dark CSS was affecting h2 tags in notices on DDT pages

= 1.5.5 =
* Tweak: Updated the TESTING_PLAYGROUND.php file to allow deletion of all content, added example code
* Tweak: Changed name of Hooks tab to Available Hooks
* Tweak: Changed name of Functions tab to Available functions
* Tweak: Changed name of FX tab to Functions.php
* Tweak: Updated some CSS styles and highlighted syntax on Functions tab
* Tweak: Removed planned features from About tab
* Fix: Links inside notices were too light with the background
* Fix: If someone is not a dev and saves the settings, it was clearing all settings
* Tweak: Updated Discord server link on Resources tab
* Update: Added unserialized array values underneath serialized values in User Meta and Post Meta tabs, making it easier to read

= 1.5.4 =
* Fix: Attempting to send Discord notifications when there are no priority roles selected causing fatal error
* Update Added option in settings to disable error counts to improve page load time when there are lots of errors
* Fix: Debug log not pulling up if location is changed

= 1.5.3 =
* Fix: Some sites do not have a blog name, so default to domain in Discord notifications

= 1.5.2 =
* Tweak: ddtt_print_r() / dpr() now accepts array for user id
* Fix: Improved performance on show online users feature
* Update: Added option for Discord Notifications of online priority users

= 1.5.1 =
* Tweak: Added an option to stop showing feedback form on deactivate; will automatically disable for certain choices

= 1.5.0 =
* Tweak: Changed order of deactivate feedback form options
* Update: Automatically prioritize online users with same email domain as website
* Update: Added setting to choose priority roles for "show online users"
* Update: Removed "show online users" active users dashboard widget as it's redundant
* Update: Added total users count to "show online users" admin bar dropdown
* Fix: Automatic conversion of false to array being deprecated

= 1.4.9 =
* Fix: New install dev email address field populating user id instead of email
* Tweak: Wordwrapped plugin file path on plugin pages if super long
* Fix: Error on Post Meta tab if the website does not have any posts
* Fix: Timezone error if someone tries to save their timezone as blank, revert back to default

= 1.4.8 =
* Fix: Easy Reader debug log viewer causing issues if writing an array to the logs
* Tweak: Move author URL and support server to defines
* Update: Added deactivation survey
* Fix: Timezone conversion on false date or timestamp returning error
* Tweak: Changed default developer email to the user that activated the plugin instead of the admin email
* Fix: A few minor text corrections
* Tweak: Update planned features list on About tab
* Tweak: Stylized warning symbols
* Fix: PHP Warning for undefined variable $err

= 1.4.7 =
* Fix: Issue removing some snippets on wp-config
* Tweak: Added semicolon to end of existing snippets
* Fix: Removed modified date and compatibility check for Hello Dolly plugin
* Tweak: Removed temp files if updating wp-config/htaccess are cancelled
* Tweak: Added warnings for outdated plugin/WP/PHP versions in header
* Fix: DateTime::__construct(): Passing null to parameter #1 ($datetime) of type string is deprecated
* Update: Added more options for showing online users in admin bar

= 1.4.6 =
* Update: Added option to also log user id, user display name and url with query string when an error is triggered

= 1.4.5 =
* Tweak: Update Discord support link
* Update: Added search field for Site Options to include options not registered

= 1.4.4 =
* Tweak: Added full changelog to readme.txt
* Tweak: Updated changelog to use commonly used prefixes (Fix, Tweak, and Update)
* Tweak: Changed `date()` to `gmdate()` in activation hook (props [@sybrew](https://github.com/sybrew))
* Tweak: Changed multiple calls to `site_url()` to variable in root file (props [@sybrew](https://github.com/sybrew))
* Tweak: Moved `TESTING_PLAYGROUND.php` file to `includes` folder (props [@sybrew](https://github.com/sybrew))
* Fix: Patched security issues with downloads (props [@sybrew](https://github.com/sybrew))
* Fix: Unserialize notice on usermeta tab

= 1.4.3 =
* Fix: Error with GFAPI not being found on feed page
* Tweak: Sorted plugins alphabetically by name

= 1.4.2 =
* Update: Added quick debug links to Gravity Form entry page
* Tweak: Moved quick debug link results for Gravity Forms to it's own page instead of sharing the Testing tab
* Update: Added quick debug link for Gravity Forms feeds
* Update: Added more recommended plugins
* Tweak: Redacted sensitive information from view to hide when getting support and showing demos
* Fix: Shortcode finder attribute field not filtering out value properly

= 1.4.1 =
* Update: Added functions.php viewer

= 1.4.0 =
* Update: Added a simple functions.php viewer
* Update: Added backups sections to wp-config/htaccess tabs with ability to clear old backups
* Tweak: Disabled preview button on wp-config/htaccess tabs if nothing is checked or unchecked
* Fix: Deprecated function in online users class
* Fix: Admin side menu showing tabs for non-devs
* Tweak: Made cURL seconds field show/hide with JS instead of needing to save the changes first
* Update: Added settings link, website link, and Discord support link to plugins list page
* Update: Added message for user that activated the plugin with instructions on how to begin
* Tweak: Only make paths to plugin/theme editor pages if editors are not disabled
* Update: Added notice to top of Cron Jobs page if `WP Cron` is disabled
* Update: Added two more snippets to wp-config: `DISALLOW_FILE_EDIT`, `DISABLE_WP_CRON`

= 1.3.12 =
* Update: Added feedback form in About tab
* Update: Added [Admin Help Docs](https://wordpress.org/plugins/admin-help-docs/) plugin to recommended plugins and on About tab

= 1.3.11 =
* Fix: Minor bugs related to multisite
* Update: Added a couple more recommended plugins

= 1.3.10 =
* Update: Added buttons to cookies tab for clearing cookies and browser local storage
* Fix: Debug quick link on post edit screens not showing up

= 1.3.9 =
* Update: Added link to primary site on debug log if not on primary site
* Fix: Subsite links on network settings page all pointing to primary site
* Fix: Debug log easy viewer highlighting issue

= 1.3.8 =
* Tweak: Changed classic debug log viewer to show raw last 100 lines with user's timezone
* Tweak: Shortened submenu slugs to not include full url
* Tweak: Removed `clear_debug_log` query string so we can refresh log without clearing it again

= 1.3.7 =
* Fix: Browser tab customization on subsites that are not primary
* Fix: Active tab highlighting on side menu
* Tweak: Updated plugin slug in url to not include path to options page
* Fix: Bug on network plugins tab

= 1.3.6 =
* Update: Added inactive plugins to plugins tab
* Update: Added "Sites" column to plugins tab on multisite network to see which sites plugins are installed on
* Update: Added multisite suffixes to title and browser tabs to clearly identify which site you are on
* Update: Added support for multisite
* Tweak: Reverted to display name in Online Users if no first and last name is provided
* Tweak: Ensured `is_plugin_active()` is defined for admin on multisite

= 1.3.5 =
* Update: Added setting for condensing admin bar items
* Tweak: Updated `ddtt_highlight_debug_log` and `ddtt_debug_log_help_col` hooks with more options
* Update: Added view recent links to debug log in Easy Reader
* Update: Added links to color key on debug log in Easy Reader to filter results
* Update: Added a search field to debug log in Easy Reader
* Fix: Removed plugin from menu if not admin

= 1.3.4 =
* Fix: Error on debug log easy reader
* Fix: Error in online user column

= 1.3.3 =
* Update: Added max filesize for debug log to prevent site crashing with filter to change amount
* Tweak: Changed plugins last modified date to developer's timezone
* Update: Added filter for changing debug log help links
* Update: Added filter for changing debug log highlight colors
* Update: Added Easy Reader view and viewer options to debug log
* Tweak: Changed logs tab slug to "logs" instead of "debug"
* Update: Added full regex array and preg_match_all with pattern to Regex tab for easy copying
* Update: Added tab titles to browser tabs, and push user/post ids on user/post meta tabs
* Tweak: Changed PHP testing playground local path to theme root folder, old path still works as backup
* Fix: PHP Warning for undefined variable

= 1.3.2 =
* Update: Added setting for swapping out discord link if already a member
* Update: Added confirmation for updating wp-config.php and .htaccess files
* Tweak: Combined repeated shortcodes found on admin bar, replaced with count
* Update: Added sources to available shortcodes on shortcode finder
* Tweak: Changed shortcode finder input field to select field

= 1.3.1 =
* Update: Added filter for omitting shortcodes from shortcode finder, good for minimizing page builders
* Update: Added capability for posts and pages to be searched by ID in the admin area
* Tweak: Changed order of admin bar items
* Update: Added online users feature
* Update: Added quick debug links to Gravity Forms action links
* Tweak: Changed admin bar user info to existing wp-account so Debug Bar will work
* Tweak: Minimized space at top of each page
* Fix: Log path notice

= 1.3.0 =
* Update: Added shortcode finder
* Tweak: Allowed disabling of admin bar items
* Fix: Beta htaccess editing was still in test mode
* Update: Added Resource dropdown to admin bar
* Update: Added additional resources
* Update: Added jQuery versions to header
* Update: Added Cron Jobs tab
* Update: Added Cookies tab
* Update: Added PHP Info tab
* Tweak: Added WP.org links to About tab
* Fix: Test # incrementing when not used
* Fix: Not finding Gravity Form forms on admin bar if added via Cornerstone element
* Fix: Not allowing updating user or post meta keys that are not all lowercase

= 1.2.0 =
* Update: Made preparations for release to WP.org repository

= 1.0.1 =
* Created plugin on May 13, 2022