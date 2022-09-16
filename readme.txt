=== Developer Debug Tools ===
Contributors: apos37
Donate link: https://paypal.me/apos37
Tags: debug, developer, testing, wp-config, htaccess, user meta, post meta
Requires at least: 5.9.0
Tested up to: 6.0.2
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

WordPress debugging and testing tools for developers

== Description ==
A simple plugin that gives developers the tools they need to debug and test things as they develop their WordPress site from the comfort of the admin area.

* View debug.log and error.log directly from admin in your timezone, conveniently combining repeats for readability, and with quick links to search Google for suggested solutions.
* Quickly clear your logs with a click of a button.
* View and download wp-config.php file and .htaccess file.
* BETA TEST: add/remove snippets on the wp-config.php file, such as toggling Debug Log.
* BETA TEST: add/remove snippets on the .htaccess file.
* Toggle wp_mail() failure logging.
* View and edit user meta, including custom meta, for a given user.
* Add/remove roles for a specific user.
* View and edit post meta, including custom meta for a given post.
* Clear all taxonomy terms from a given post.
* View all php.ini values.
* View all site options/registered settings.
* View available WP global variables with ease.
* Clear all or expired transients easily.
* View additional details about active plugins, and see warnings about outdated plugins at a glance.
* Access to a handful of additional functions and hooks that you can use for debugging.
* Regex playground with cheat sheet.
* Toggle WP heartbeat.
* Extend cURL timeout errors.
* All this in dark mode, with the ability to change syntax colors.
* Adds user and post info to admin bar.

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

= My site broke when updating my wp-config.php or .htaccess. How do I revert back to my original?
The originals are stored in your root folder and renamed with the date and time from which they were replaced. For example, the wp-config.php file will have been renamed to wp-config-2022-08-22-15-25-46.php and replaced with a new file. Simply log into your FTP or File Manager (from your host), rename the current file to something else such as wp-config-BROKEN.php (just in case you need it), and then rename the version you want to revert back to as wp-config.php. If everything looks good, then you can either delete this file or send a copy of it to me so I can figure out what went wrong. You can do so in the Discord server mentioned below.

= Where can I get further support? =
Join my [WordPress Support Discord server](https://discord.gg/VeMTXRVkm5)

== Screenshots ==
1. Settings page
2. Activated plugins with warnings
3. View and clear debug.log file
4. View wp-config.php file
5. Some of the snippets you can add/remove from your wp-config.php file
6. View .htaccess file
7. List of all php.ini values
8. View and update a user's meta
9. Available functions to use for debugging and testing
10. Regex playground

== Changelog ==
= 1.2.0 =
* Prepare for release to WP.org repository