=== Developer Debug Tools ===
Contributors: apos37
Donate link: https://paypal.com/donate/?business=3XHJUEHGTMK3N
Tags: debug, developer, testing, wp-config, htaccess, user meta, post meta
Requires at least: 5.9.0
Tested up to: 6.1.1
Requires PHP: 7.4
Stable tag: 1.3.12
License: GPL v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Lots of debugging and testing tools for developers.

== Description ==
Developers tools for debugging and testing things as you develop and troubleshoot your WordPress site.

* View debug.log and error logs directly from admin in your timezone, conveniently combining repeats for readability, and with quick links to search Google for suggested solutions.
* Quickly clear your debug and error logs with a click of a button.
* View and download backups of wp-config.php file and .htaccess file from the admin area.
* Add/remove snippets on the wp-config.php file without editing the file directly.
* Add/remove snippets on the .htaccess file without editing the file directly.
* Toggle wp_mail() failure logging.
* View and edit user meta, including custom meta, for a given user directly from the admin panel.
* Add/remove roles for a specific user.
* View and edit post meta, including custom meta for a given post directly from the admin panel.
* Clear all taxonomy terms from a given post.
* View all php.ini values.
* View detailed information about your PHP's configuration.
* View cookies.
* View scheduled cron jobs.
* View all site options and registered settings.
* Clear all or expired transients easily.
* View available WP global variables with ease.
* View additional details about active plugins, and see warnings about outdated plugins at a glance.
* Regex playground with cheat sheet.
* Enable/disable WP heartbeat from settings.
* Extend cURL timeout errors easily.
* Shortcode Finder displays all available shortcodes and lets you search posts and pages where they used.
* Additional user and post information on admin bar.
* View online users to avoid working on the site at the same time as other admins and users.
* Centering tool added to the admin bar that helps you line up elements on a page.
* See all shortcodes used on any page from the front-end in the admin bar.
* If Gravity Forms is installed, see form ids in the admin bar.
* Replaces "Howdy" on admin bar with your user ID.
* Adds date/time that the page was loaded to admin bar for comparing two windows.
* Quick links for debugging users, posts, pages, and Gravity Forms forms and entries.
* Option to remove items from admin bar.
* Allow posts and pages to be searched by ID in the admin area.
* Access to a handful of additional functions and hooks that you can use for debugging.
* A great list of links to helpful resources.

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

= Why can't I edit a username for a user? =
Some hosts will not allow you to update a user's username directly from WP. In order to do so, you'll have to update it in your database directly.

= Where is the centering tool? =
Viewable only on the front-end, there is a link on the admin bar that shows +Off. Click on it and it will add a transparent bar with lines on it at the top of the page underneath the admin bar. If you click on the centering bar it will expand all the way down the page. Click on it again and it will minimize back to the top. You can click on the +On link from the admin bar to make it go away.

= Where are the quick debug links? =
You have to enable them on the Developer Debug Tools settings first. Once they are enabled, an "ID" column will be added to the user and/or post admin list pages. Next to the user or post's ID you will see a lightning bolt icon. Clicking on the lightning bolt will redirect you to the User Meta or Post Meta tab on our plugin where you can view and edit all of the meta easily.

= Where can I get further support? =
Join my [WordPress Support Discord server](https://discord.gg/VeMTXRVkm5)

== Screenshots ==
1. Settings page
2. Activated plugins with warnings
3. View, filter, and clear debug.log file
4. View wp-config.php file
5. Some of the snippets you can add/remove from your wp-config.php file
6. View .htaccess file
7. Cron jobs
8. View and update a user's meta
9. Available functions to use for debugging and testing
10. Regex playground

== Changelog ==
= 1.3.12 =
* Changed author name from Apos37 to Aristocles
* Added feedback form in about tab
* Added Admin Help Docs plugin to recommended plugins and on about tab