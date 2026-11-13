<?php
/**
 * Security Checks
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class SecurityChecks {

    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_security_checks_nonce';


    /**
     * Option key for the last result of each check
     *
     * @var string
     */
    const RESULTS_KEY = 'ddtt_security_checks_results';


    /**
     * Seconds to wait for each request to this site
     *
     * @var int
     */
    const TIMEOUT = 5;


    /**
     * Max items listed in a result
     *
     * @var int
     */
    const MAX_ITEMS = 25;


    /**
     * Max seconds a file scan may run
     *
     * @var int
     */
    const SCAN_SECONDS = 10;


    /**
     * The example token in inc/backdoor.php, which is public in the plugin source
     *
     * @var string
     */
    const EXAMPLE_BACKDOOR_TOKEN = 'AfLbVk8wYXoT12zVdV6HQbSh0OBbAAyU';


    /**
     * Responses already fetched during this request
     *
     * @var array
     */
    private static array $responses = [];


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?SecurityChecks $instance = null;


    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance() : self {
        return self::$instance ??= new self();
    } // End instance()


    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_security_run_check', [ $this, 'ajax_run_check' ] );
        add_action( 'wp_ajax_nopriv_ddtt_security_run_check', '__return_false' );
        add_action( 'wp_ajax_ddtt_security_clear_results', [ $this, 'ajax_clear_results' ] );
        add_action( 'wp_ajax_nopriv_ddtt_security_clear_results', '__return_false' );
    } // End __construct()


    /**
     * Categories
     *
     * @return array
     */
    public static function categories() : array {
        return [
            'exposure'      => [
                'label' => __( 'Public Exposure', 'dev-debug-tools' ),
                'desc'  => __( 'Requests this site the way a visitor would, without logging in, to find files and information that should not be public.', 'dev-debug-tools' ),
            ],
            'configuration' => [
                'label' => __( 'Configuration', 'dev-debug-tools' ),
                'desc'  => __( 'Settings in wp-config.php, PHP and WordPress that weaken the site.', 'dev-debug-tools' ),
            ],
            'access'        => [
                'label' => __( 'Users & Access', 'dev-debug-tools' ),
                'desc'  => __( 'Accounts, logins and who can get into the admin area.', 'dev-debug-tools' ),
            ],
            'software'      => [
                'label' => __( 'Software & Files', 'dev-debug-tools' ),
                'desc'  => __( 'Out-of-date software and files that do not belong on the server.', 'dev-debug-tools' ),
            ],
            'code'          => [
                'label' => __( 'Code Review', 'dev-debug-tools' ),
                'desc'  => __( 'Endpoints any visitor can call. A developer should review each one.', 'dev-debug-tools' ),
            ],
        ];
    } // End categories()


    /**
     * Severities, most severe first
     *
     * @return array
     */
    public static function severities() : array {
        return [
            'critical' => __( 'Critical', 'dev-debug-tools' ),
            'high'     => __( 'High', 'dev-debug-tools' ),
            'medium'   => __( 'Medium', 'dev-debug-tools' ),
            'low'      => __( 'Low', 'dev-debug-tools' ),
            'info'     => __( 'Info', 'dev-debug-tools' ),
        ];
    } // End severities()


    /**
     * Result statuses
     *
     * @return array
     */
    public static function statuses() : array {
        return [
            'fail'    => __( 'Failed', 'dev-debug-tools' ),
            'warning' => __( 'Warning', 'dev-debug-tools' ),
            'unknown' => __( 'Unverified', 'dev-debug-tools' ),
            'info'    => __( 'Review', 'dev-debug-tools' ),
            'pass'    => __( 'Passed', 'dev-debug-tools' ),
            'none'    => __( 'Not Run', 'dev-debug-tools' ),
        ];
    } // End statuses()


    /**
     * All registered checks, sorted by category and severity
     *
     * @return array
     */
    public static function checks() : array {
        $checks = [
            'exposed_debug_log' => [
                'label'    => __( 'Debug Log Publicly Accessible', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'high',
                'risk'     => __( 'Anyone can read error messages that reveal file paths, plugin names, database table names, and sometimes user data or API keys.', 'dev-debug-tools' ),
                'fix'      => __( 'Set <code>WP_DEBUG_LOG</code> to a path outside the web root, such as <code>/home/account/logs/debug.log</code>, or block <code>.log</code> files at the server. Then delete the exposed file.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_exposed_debug_log' ],
            ],
            'exposed_config_backups' => [
                'label'    => __( 'Config Backup Files Exposed', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'critical',
                'risk'     => __( 'Copies of wp-config.php with other extensions are served as plain text. They reveal the database password and security keys, which let an attacker log in to the database or forge login cookies.', 'dev-debug-tools' ),
                'fix'      => __( 'Delete the listed files from the server. Then change the database password and replace the security keys and salts.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_exposed_config_backups' ],
            ],
            'exposed_vcs_env' => [
                'label'    => __( 'Version Control or Environment Files Exposed', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'critical',
                'risk'     => __( 'Exposed .git folders let an attacker download the full source code and its history. Exposed .env files hold passwords and API keys.', 'dev-debug-tools' ),
                'fix'      => __( 'Remove these from the web root, or block dot-files at the server. Apache: <code>RedirectMatch 404 /\.(git|svn|env)</code>. Nginx: <code>location ~ /\.(git|svn|env) { deny all; }</code>. Change any secrets the files contained.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_exposed_vcs_env' ],
            ],
            'exposed_backups' => [
                'label'    => __( 'Database or Site Backups Exposed', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'critical',
                'risk'     => __( 'A downloadable backup contains every user\'s email address and password hash, all form entries, and the site configuration.', 'dev-debug-tools' ),
                'fix'      => __( 'Move backups outside the web root or to off-site storage, and delete the listed files.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_exposed_backups' ],
            ],
            'exposed_info_files' => [
                'label'    => __( 'Diagnostic Files Exposed', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'medium',
                'risk'     => __( 'phpinfo pages, PHP error logs, server status pages and database tools left on the server reveal paths, settings, versions and visitor requests an attacker uses to plan a targeted attack. A database tool also offers a login form to guess against.', 'dev-debug-tools' ),
                'fix'      => __( 'Delete the listed files. Server status pages are turned off in the server config; ask your host if you cannot change it.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_exposed_info_files' ],
            ],
            'directory_listing' => [
                'label'    => __( 'Directory Listing Enabled', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'medium',
                'risk'     => __( 'Anyone can browse these folders and see every file in them, including uploads that were never linked publicly, such as form attachments and private documents.', 'dev-debug-tools' ),
                'fix'      => __( 'Apache: add <code>Options -Indexes</code> to .htaccess. Nginx: set <code>autoindex off;</code>.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_directory_listing' ],
            ],
            'xmlrpc' => [
                'label'    => __( 'XML-RPC Enabled', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'medium',
                'risk'     => __( 'XML-RPC accepts hundreds of password guesses in a single request, which gets around most login limits. Pingbacks can also be used to send traffic at other sites.', 'dev-debug-tools' ),
                'fix'      => __( 'If the site does not use Jetpack or the WordPress mobile apps, block <code>xmlrpc.php</code> at the server or with a security plugin.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_xmlrpc' ],
            ],
            'user_enumeration' => [
                'label'    => __( 'Usernames Exposed', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'low',
                'risk'     => __( 'Attackers collect usernames to target with password guessing. When an author slug matches a login username, half of that login is already known.', 'dev-debug-tools' ),
                'fix'      => __( 'Change the login username of any affected account, or remove the users endpoint for logged-out visitors with the <code>rest_endpoints</code> filter and stop author redirects with <code>redirect_canonical</code>.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_user_enumeration' ],
            ],
            'security_headers' => [
                'label'    => __( 'Security Headers Missing', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'low',
                'risk'     => __( 'Without these headers, other sites can frame your pages to trick visitors into clicking things, browsers may run uploaded files as scripts, and visitors can be downgraded to HTTP.', 'dev-debug-tools' ),
                'fix'      => __( 'Add the missing headers in your server config, CDN, or with the <code>send_headers</code> hook.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_security_headers' ],
            ],
            'https' => [
                'label'    => __( 'HTTPS Not Enforced', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'high',
                'risk'     => __( 'Logins, cookies and form entries sent over HTTP can be read and changed by anyone on the same network, such as public Wi-Fi.', 'dev-debug-tools' ),
                'fix'      => __( 'Install an SSL certificate, set both site addresses in Settings > General to https://, and redirect all http:// requests to https:// at the server.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_https' ],
            ],
            'version_disclosure' => [
                'label'    => __( 'Software Versions Disclosed', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'low',
                'risk'     => __( 'Exact version numbers let attackers match the site to known vulnerabilities without probing it.', 'dev-debug-tools' ),
                'fix'      => __( 'Remove the generator tag with <code>remove_action( \'wp_head\', \'wp_generator\' );</code>, set <code>expose_php = Off</code> in php.ini, hide server versions (Apache: <code>ServerTokens Prod</code>; Nginx: <code>server_tokens off;</code>), and delete readme.html.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_version_disclosure' ],
            ],
            'debug_display' => [
                'label'    => __( 'Errors Displayed on Screen', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'high',
                'risk'     => __( 'PHP errors printed on pages show visitors file paths, function names and sometimes query details.', 'dev-debug-tools' ),
                'fix'      => __( 'Add <code>define( \'WP_DEBUG_DISPLAY\', false );</code> to wp-config.php and set <code>display_errors = Off</code> in php.ini. Use the debug log instead.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_debug_display' ],
            ],
            'file_editing' => [
                'label'    => __( 'Plugin and Theme Editor Enabled', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'medium',
                'risk'     => __( 'Anyone who gets an administrator login can add PHP to any plugin or theme from the dashboard and take over the server.', 'dev-debug-tools' ),
                'fix'      => __( 'Add <code>define( \'DISALLOW_FILE_EDIT\', true );</code> to wp-config.php. The WP-CONFIG tool has it as a snippet.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_file_editing' ],
            ],
            'salts' => [
                'label'    => __( 'Security Keys and Salts Weak', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'high',
                'risk'     => __( 'Weak or exposed keys make login cookies easier to forge, which gives access without a password.', 'dev-debug-tools' ),
                'fix'      => __( 'Generate new keys at <a href="https://api.wordpress.org/secret-key/1.1/salt/" target="_blank" rel="noopener">api.wordpress.org/secret-key/1.1/salt/</a> and replace them in wp-config.php. Everyone will be logged out.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_salts' ],
            ],
            'db_credentials' => [
                'label'    => __( 'Database Login Weak', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'high',
                'risk'     => __( 'If the database can be reached another way, such as from another hacked site on the same server, a root account or blank password gives full control of every database.', 'dev-debug-tools' ),
                'fix'      => __( 'Create a database user with a long random password and access to this database only, then update <code>DB_USER</code> and <code>DB_PASSWORD</code> in wp-config.php.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_db_credentials' ],
            ],
            'file_permissions' => [
                'label'    => __( 'World-Writable Files', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'high',
                'risk'     => __( 'Any account or process on the server can change these files, so another hacked site on shared hosting can rewrite this one.', 'dev-debug-tools' ),
                'fix'      => __( 'Set files to 644 (wp-config.php to 640 or 600) and folders to 755.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_file_permissions' ],
            ],
            'php_version' => [
                'label'    => __( 'PHP Version Unsupported', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'high',
                'risk'     => __( 'Security holes found in PHP after its end-of-life date are never fixed.', 'dev-debug-tools' ),
                'fix'      => __( 'Upgrade PHP from your hosting control panel. Test on a staging copy first.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_php_version' ],
            ],
            'registration' => [
                'label'    => __( 'Open Registration Role', 'dev-debug-tools' ),
                'category' => 'access',
                'severity' => 'critical',
                'risk'     => __( 'Anyone can create an account with the default role. A role above Subscriber lets strangers publish content, upload files, or take over the site.', 'dev-debug-tools' ),
                'fix'      => __( 'In Settings > General, set New User Default Role to Subscriber, or turn off "Anyone can register".', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_registration' ],
            ],
            'admin_accounts' => [
                'label'    => __( 'Administrator Accounts', 'dev-debug-tools' ),
                'category' => 'access',
                'severity' => 'medium',
                'risk'     => __( 'Administrator accounts nobody uses are easy to take over without anyone noticing, especially when they belong to former staff or contractors. Easy-to-guess usernames are the first ones attackers try, and an administrator you do not recognize can be a sign of a break-in.', 'dev-debug-tools' ),
                'fix'      => __( 'Delete inactive administrators or lower them to a smaller role, and move their content to another account first. Remove administrators you do not recognize and change all passwords. Replace easy-to-guess usernames with a new administrator account.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_admin_accounts' ],
            ],
            'app_passwords' => [
                'label'    => __( 'Administrator Application Passwords', 'dev-debug-tools' ),
                'category' => 'access',
                'severity' => 'low',
                'risk'     => __( 'Application passwords log in through the REST API and XML-RPC without two-factor. One that is no longer used is still a working login.', 'dev-debug-tools' ),
                'fix'      => __( 'Revoke unused ones from each user\'s profile, or turn the feature off with <code>add_filter( \'wp_is_application_passwords_available\', \'__return_false\' );</code>.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_app_passwords' ],
            ],
            'two_factor' => [
                'label'    => __( 'Two-Factor Authentication', 'dev-debug-tools' ),
                'category' => 'access',
                'severity' => 'medium',
                'risk'     => __( 'Without two-factor, a stolen or guessed password is all it takes to log in.', 'dev-debug-tools' ),
                'fix'      => sprintf(
                    /* translators: 1: Two Factor plugin URL, 2: WP 2FA plugin URL */
                    __( 'Install a two-factor plugin and have every administrator set it up. We recommend <a href="%1$s" target="_blank" rel="noopener noreferrer">Two Factor</a>, a lightweight plugin from WordPress core contributors. Each user turns it on from their profile, so confirm every administrator has done so. If you need to require it by role, use <a href="%2$s" target="_blank" rel="noopener noreferrer">WP 2FA</a> instead.', 'dev-debug-tools' ),
                    'https://wordpress.org/plugins/two-factor/',
                    'https://wordpress.org/plugins/wp-2fa/'
                ),
                'callback' => [ self::class, 'check_two_factor' ],
            ],
            'login_protection' => [
                'label'    => __( 'Login Attempt Limiting', 'dev-debug-tools' ),
                'category' => 'access',
                'severity' => 'medium',
                'risk'     => __( 'WordPress does not limit failed logins, so bots can guess passwords without stopping.', 'dev-debug-tools' ),
                'fix'      => __( 'Install a plugin that limits login attempts, or confirm your host or firewall already does.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_login_protection' ],
            ],
            'ddt_backdoor' => [
                'label'    => __( 'Emergency Login Enabled', 'dev-debug-tools' ),
                'category' => 'access',
                'severity' => 'critical',
                'risk'     => __( 'The emergency login in Developer Debug Tools logs anyone with the URL and token in as an administrator, with no password or two-factor.', 'dev-debug-tools' ),
                'fix'      => __( 'Set <code>$enabled</code> back to <code>FALSE</code> in <code>inc/backdoor.php</code> and upload the file again.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_ddt_backdoor' ],
            ],
            'ddt_access' => [
                'label'    => __( 'Developer Debug Tools Access', 'dev-debug-tools' ),
                'category' => 'access',
                'severity' => 'low',
                'risk'     => __( 'These tools include file editors and a database viewer. Every administrator who can open them can change wp-config.php and .htaccess.', 'dev-debug-tools' ),
                'fix'      => __( 'Turn on Developer Access Only or Password Protection in Settings > Security.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_ddt_access' ],
            ],
            'core_updates' => [
                'label'    => __( 'WordPress Core Updates', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'high',
                'risk'     => __( 'Security fixes in newer releases are public, so attackers know exactly what to try on older ones.', 'dev-debug-tools' ),
                'fix'      => __( 'Update WordPress from Dashboard > Updates, and leave automatic security releases on.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_core_updates' ],
            ],
            'plugin_updates' => [
                'label'    => __( 'Plugin Updates', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'high',
                'risk'     => __( 'Out-of-date plugins are the most common way WordPress sites are broken into.', 'dev-debug-tools' ),
                'fix'      => __( 'Update the listed plugins. Replace any plugin that is no longer maintained.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_plugin_updates' ],
            ],
            'theme_updates' => [
                'label'    => __( 'Theme Updates', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'medium',
                'risk'     => __( 'Themes run PHP like plugins, and out-of-date ones have the same kinds of holes.', 'dev-debug-tools' ),
                'fix'      => __( 'Update the listed themes. If the active theme is customized directly, move the changes into a child theme first.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_theme_updates' ],
            ],
            'inactive_software' => [
                'label'    => __( 'Inactive Plugins and Themes', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'low',
                'risk'     => __( 'Turning a plugin off does not remove its files. A vulnerable file can still be requested directly.', 'dev-debug-tools' ),
                'fix'      => __( 'Delete plugins and themes you do not use. Keep one default theme as a fallback.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_inactive_software' ],
            ],
            'core_integrity' => [
                'label'    => __( 'Core Files Modified', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'critical',
                'risk'     => __( 'Changed or extra files in wp-admin and wp-includes are a common sign of malware, which often hides there to survive plugin updates.', 'dev-debug-tools' ),
                'fix'      => __( 'Reinstall WordPress from Dashboard > Updates, then delete any unexpected files. Compares files against checksums from api.wordpress.org.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_core_integrity' ],
            ],
            'php_in_uploads' => [
                'label'    => __( 'PHP Files in Uploads', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'high',
                'risk'     => __( 'The uploads folder should only hold media and documents. PHP files there are a common sign of a backdoor left after a break-in.', 'dev-debug-tools' ),
                'fix'      => __( 'Open each file to confirm where it came from, and delete anything unexpected. Then block PHP in uploads. Apache: add <code>&lt;Files *.php&gt; Require all denied &lt;/Files&gt;</code> to <code>wp-content/uploads/.htaccess</code>. Nginx: <code>location ~* /uploads/.*\.php$ { deny all; }</code>.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_php_in_uploads' ],
            ],
            'rest_write_public' => [
                'label'    => __( 'Public REST Routes That Accept Changes', 'dev-debug-tools' ),
                'category' => 'code',
                'severity' => 'medium',
                'risk'     => __( 'These routes accept POST, PUT, PATCH or DELETE requests from anyone without logging in. Depending on the code, that can mean creating content, changing data, or sending email.', 'dev-debug-tools' ),
                'fix'      => __( 'Give each route a <code>permission_callback</code> that checks a capability, unless it is meant to be public, such as a contact form. Public routes still need input validation and rate limits.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_rest_write_public' ],
            ],
            'ajax_nopriv' => [
                'label'    => __( 'Logged-Out AJAX Actions', 'dev-debug-tools' ),
                'category' => 'code',
                'severity' => 'low',
                'risk'     => __( 'These actions run for visitors who are not logged in. Any that skip nonce checks or input validation can be called by anyone, as often as they like.', 'dev-debug-tools' ),
                'fix'      => __( 'Confirm each action needs to be public. Each one should verify a nonce, validate input, and limit how often it can send email or write data.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_ajax_nopriv' ],
            ],
            'exposed_dev_files' => [
                'label'    => __( 'Development Files Exposed', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'medium',
                'risk'     => __( 'Dependency lists reveal every library and version the site uses, so an attacker can match them to known vulnerabilities. Editor deployment files such as sftp.json often contain server logins in plain text.', 'dev-debug-tools' ),
                'fix'      => __( 'Delete the listed files from the server, or keep them outside the web root. If an editor deployment file was exposed, change the server password it contains.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_exposed_dev_files' ],
            ],
            'exposed_installers' => [
                'label'    => __( 'Migration Installer Files Exposed', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'high',
                'risk'     => __( 'Installer and database replacement scripts left behind after a migration let anyone overwrite the site or rewrite the database, often without logging in.', 'dev-debug-tools' ),
                'fix'      => __( 'Delete the listed files and any related archive files. Migration tools usually offer a cleanup button after the move is finished.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_exposed_installers' ],
            ],
            'tls_certificate' => [
                'label'    => __( 'TLS Certificate Problems', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'high',
                'risk'     => __( 'Browsers show a full-page warning when the certificate is expired, untrusted or issued for another name. Visitors who click through can have their traffic read or changed.', 'dev-debug-tools' ),
                'fix'      => __( 'Renew the certificate or turn on automatic renewal with your host. Make sure the certificate covers every name the site uses, including www.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_tls_certificate' ],
            ],
            'cors' => [
                'label'    => __( 'Cross-Origin Requests Allowed (CORS)', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'medium',
                'risk'     => __( 'When the site trusts requests from any other website and allows cookies, a page on another site can act as a logged-in visitor and read the responses.', 'dev-debug-tools' ),
                'fix'      => __( 'Remove the server or plugin rule that copies the request\'s Origin into <code>Access-Control-Allow-Origin</code>. List only the domains that need access.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_cors' ],
            ],
            'http_trace' => [
                'label'    => __( 'TRACE Method Enabled', 'dev-debug-tools' ),
                'category' => 'exposure',
                'severity' => 'low',
                'risk'     => __( 'The server echoes requests back, including headers. Combined with another flaw, this can expose cookies or authentication headers.', 'dev-debug-tools' ),
                'fix'      => __( 'Apache: add <code>TraceEnable off</code> to the server config. Nginx does not support TRACE. Otherwise ask your host.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_http_trace' ],
            ],
            'remote_file_inclusion' => [
                'label'    => __( 'Remote File Inclusion Enabled', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'high',
                'risk'     => __( 'PHP can include and run code from other websites. One unsafe include in any plugin then lets an attacker run their own code on the server.', 'dev-debug-tools' ),
                'fix'      => __( 'Set <code>allow_url_include = Off</code> in php.ini, or ask your host.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_remote_file_inclusion' ],
            ],
            'db_version' => [
                'label'    => __( 'Database Version Unsupported', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'medium',
                'risk'     => __( 'Database versions past end of life no longer receive security fixes.', 'dev-debug-tools' ),
                'fix'      => __( 'Ask your host to upgrade MySQL or MariaDB to a supported version, or move to a plan that has one.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_db_version' ],
            ],
            'upload_types' => [
                'label'    => __( 'Risky Upload Types Allowed', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'medium',
                'risk'     => __( 'Files such as SVG, HTML and JavaScript can carry scripts that run when someone opens them, which lets any user who can upload attack administrators.', 'dev-debug-tools' ),
                'fix'      => __( 'Remove these types from the <code>upload_mimes</code> filter or the plugin that adds them. If you need SVG, use a plugin that sanitizes each file on upload.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_upload_types' ],
            ],
            'force_ssl_admin' => [
                'label'    => __( 'Admin Area Not Forced to HTTPS', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'low',
                'risk'     => __( 'Without it, a login or admin page opened over http:// can send the login cookie unencrypted.', 'dev-debug-tools' ),
                'fix'      => __( 'Add <code>define( \'FORCE_SSL_ADMIN\', true );</code> to wp-config.php.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_force_ssl_admin' ],
            ],
            'admin_email' => [
                'label'    => __( 'Admin Email Address', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'low',
                'risk'     => __( 'WordPress sends security notices, update failures and recovery-mode links to this address. If it is a placeholder or cannot receive email, nobody sees them.', 'dev-debug-tools' ),
                'fix'      => __( 'Set a working address in Settings > General.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_admin_email' ],
            ],
            'email_auth' => [
                'label'    => __( 'Email Authentication (SPF and DMARC)', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'low',
                'risk'     => __( 'Without SPF and DMARC records, anyone can send email that appears to come from this domain, such as fake password reset messages.', 'dev-debug-tools' ),
                'fix'      => __( 'Add SPF and DMARC TXT records with your DNS provider. Your email provider lists the exact values to use.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_email_auth' ],
            ],
            'auto_updates' => [
                'label'    => __( 'Plugin and Theme Auto-Updates', 'dev-debug-tools' ),
                'category' => 'configuration',
                'severity' => 'info',
                'risk'     => __( 'Security fixes for plugins and themes are only applied when someone runs the update.', 'dev-debug-tools' ),
                'fix'      => __( 'Turn on auto-updates from the Plugins and Themes screens, or make sure someone updates these regularly.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_auto_updates' ],
            ],
            'role_capabilities' => [
                'label'    => __( 'Powerful Capabilities Outside Administrators', 'dev-debug-tools' ),
                'category' => 'access',
                'severity' => 'high',
                'risk'     => __( 'Roles or users that can install plugins, edit files, change settings or manage users can take over the site. Attackers sometimes add one of these capabilities to an ordinary account instead of creating an administrator, because it is less noticeable.', 'dev-debug-tools' ),
                'fix'      => __( 'Remove the listed capabilities with a role editor plugin, or remove the plugin that added them. Keep the number of administrators small.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_role_capabilities' ],
            ],
            'plugin_integrity' => [
                'label'    => __( 'Plugin Files Modified', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'high',
                'risk'     => __( 'Changed or extra PHP files in a plugin from WordPress.org are a common sign of malware, or of edits that will be lost at the next update.', 'dev-debug-tools' ),
                'fix'      => __( 'Reinstall the plugin from WordPress.org, then run this check again. If you edited the plugin on purpose, move the changes to a separate plugin. Compares files against checksums from downloads.wordpress.org.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_plugin_integrity' ],
            ],
            'php_unexpected' => [
                'label'    => __( 'PHP Files in Unexpected Places', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'high',
                'risk'     => __( 'The main WordPress folder, the top of wp-content, the languages folder and the upgrade folder hold very few PHP files. Extra ones are a common hiding place for backdoors.', 'dev-debug-tools' ),
                'fix'      => sprintf(
                    /* translators: 1: WP Config tool URL, 2: HTACCESS tool URL */
                    __( 'Open each file to confirm where it came from, and delete anything unexpected. To clear old Developer Debug Tools backups, open the <a href="%1$s">WP Config</a> or <a href="%2$s">HTACCESS</a> tool and click the button next to Backups (Clear all extra backups besides the most recent).', 'dev-debug-tools' ),
                    esc_url( Bootstrap::tool_url( 'wpconfig' ) ),
                    esc_url( Bootstrap::tool_url( 'htaccess' ) )
                ),
                'callback' => [ self::class, 'check_php_unexpected' ],
            ],
            'abandoned_software' => [
                'label'    => __( 'Closed or Abandoned Plugins and Themes', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'medium',
                'risk'     => __( 'Closed plugins are often removed from WordPress.org for a security problem that will never be fixed. Software with no update in two years will likely not get a fix when the next problem is found.', 'dev-debug-tools' ),
                'fix'      => __( 'Replace closed plugins right away, and plan to replace abandoned ones with maintained alternatives. Compares against plugin and theme information from api.wordpress.org.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_abandoned_software' ],
            ],
            'dev_plugins' => [
                'label'    => __( 'Developer Plugins on Production', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'medium',
                'risk'     => __( 'Database managers, file managers, reset tools and debug plugins give extra power to anyone who gets into an admin account, and several have had serious vulnerabilities.', 'dev-debug-tools' ),
                'fix'      => __( 'Deactivate and delete these on production sites. Use them on staging or local copies instead.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_dev_plugins' ],
            ],
            'prerelease_core' => [
                'label'    => __( 'Pre-Release WordPress on Production', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'low',
                'risk'     => __( 'Alpha, beta and release candidate versions are still being tested and can include bugs, including security bugs, that are fixed before the final release.', 'dev-debug-tools' ),
                'fix'      => __( 'Update to the final release when it comes out, and test pre-release versions on staging instead.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_prerelease_core' ],
            ],
            'unlisted_software' => [
                'label'    => __( 'Plugins Not From WordPress.org', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'info',
                'risk'     => __( 'These plugins are not updated through WordPress.org. Premium plugins update through their own system, but plugins copied from other sites, or "nulled" premium plugins, often never update and sometimes contain malware.', 'dev-debug-tools' ),
                'fix'      => __( 'Confirm each plugin came from the developer or a source you trust, and that it receives updates.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_unlisted_software' ],
            ],
            'mu_dropins' => [
                'label'    => __( 'Must-Use Plugins and Drop-ins', 'dev-debug-tools' ),
                'category' => 'software',
                'severity' => 'info',
                'risk'     => __( 'These load on every request and do not appear in the normal plugin list, which makes them a favorite hiding place after a break-in.', 'dev-debug-tools' ),
                'fix'      => __( 'Confirm you recognize each file. Hosting companies and caching plugins add some of these.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_mu_dropins' ],
            ],
            'ssl_verify' => [
                'label'    => __( 'Outgoing SSL Verification Disabled', 'dev-debug-tools' ),
                'category' => 'code',
                'severity' => 'medium',
                'risk'     => __( 'WordPress does not check certificates when it connects to other servers, so updates, license checks and API calls can be intercepted or replaced on the way.', 'dev-debug-tools' ),
                'fix'      => __( 'Remove the code that returns false from the <code>https_ssl_verify</code> filter or sets <code>sslverify</code> to false in <code>http_request_args</code>.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_ssl_verify' ],
            ],
            'rest_read_public' => [
                'label'    => __( 'Public REST Routes From Plugins and Themes', 'dev-debug-tools' ),
                'category' => 'code',
                'severity' => 'info',
                'risk'     => __( 'Anyone can read these routes without logging in. Most are meant to be public, but some return private data such as form entries, emails or settings.', 'dev-debug-tools' ),
                'fix'      => __( 'Open each route as a logged-out visitor and confirm it returns nothing private. Give routes that should not be public a <code>permission_callback</code> that checks a capability.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_rest_read_public' ],
            ],
            'admin_post_nopriv' => [
                'label'    => __( 'Logged-Out Admin-Post Actions', 'dev-debug-tools' ),
                'category' => 'code',
                'severity' => 'info',
                'risk'     => __( 'These form handlers run for visitors who are not logged in. Any that skip nonce checks or input validation can be called by anyone, as often as they like.', 'dev-debug-tools' ),
                'fix'      => __( 'Confirm each action needs to be public. Each one should verify a nonce, validate input, and limit how often it can send email or write data.', 'dev-debug-tools' ),
                'callback' => [ self::class, 'check_admin_post_nopriv' ],
            ],
        ];


        /**
         * Filter the security checks.
         *
         * Each check needs a label, category, severity and callback. The callback returns
         * an array with a status (pass, fail, warning, unknown or info), a message and optional items.
         */
        $checks = apply_filters( 'ddtt_security_checks', $checks );

        $categories = array_keys( self::categories() );
        $severities = array_keys( self::severities() );

        $checks = array_filter( $checks, function( $check ) use ( $categories, $severities ) {
            return is_array( $check ) && ! empty( $check[ 'label' ] ) && isset( $check[ 'callback' ] ) && is_callable( $check[ 'callback' ] ) && in_array( $check[ 'category' ] ?? '', $categories, true ) && in_array( $check[ 'severity' ] ?? '', $severities, true );
        } );

        uasort( $checks, function( $a, $b ) use ( $categories, $severities ) {
            return [ array_search( $a[ 'category' ], $categories, true ), array_search( $a[ 'severity' ], $severities, true ) ] <=> [ array_search( $b[ 'category' ], $categories, true ), array_search( $b[ 'severity' ], $severities, true ) ];
        } );

        return $checks;
    } // End checks()


    /**
     * Checks turned on in Settings > Security Checks
     *
     * @return array
     */
    public static function enabled_checks() : array {
        $enabled = [];
        $saved = [];

        foreach ( array_keys( self::categories() ) as $category ) {
            $saved[ $category ] = get_option( 'ddtt_security_checks_' . $category, '__notset' );
        }

        foreach ( self::checks() as $key => $check ) {
            $category_saved = $saved[ $check[ 'category' ] ];
            if ( $category_saved === '__notset' || ( is_array( $category_saved ) && in_array( $key, $category_saved, true ) ) ) {
                $enabled[ $key ] = $check;
            }
        }

        return $enabled;
    } // End enabled_checks()


    /**
     * Run a check
     *
     * @param string $key
     * @param bool   $scheduled Whether this is a scheduled run, which does not load the admin area.
     * @return array
     */
    public static function run( string $key, bool $scheduled = false ) : array {
        $checks = self::checks();
        if ( ! isset( $checks[ $key ] ) ) {
            return self::result( 'unknown', __( 'This check does not exist.', 'dev-debug-tools' ) );
        }

        try {
            $result = call_user_func( $checks[ $key ][ 'callback' ] );
        } catch ( \Throwable $e ) {
            /* translators: %s: error message */
            $result = self::result( 'unknown', sprintf( __( 'The check could not finish: %s', 'dev-debug-tools' ), $e->getMessage() ) );
        }

        $result = is_array( $result ) ? $result : [];
        $status = isset( $result[ 'status' ] ) && $result[ 'status' ] !== 'none' && isset( self::statuses()[ $result[ 'status' ] ] ) ? $result[ 'status' ] : 'unknown';
        $severity = isset( $result[ 'severity' ] ) && isset( self::severities()[ $result[ 'severity' ] ] ) ? $result[ 'severity' ] : $checks[ $key ][ 'severity' ];

        $message = isset( $result[ 'message' ] ) ? (string) $result[ 'message' ] : '';
        if ( $scheduled && $checks[ $key ][ 'category' ] === 'code' ) {
            $message .= ' ' . __( 'This result is from a scheduled run, which does not load the admin area. Some plugins only add their actions and routes there, so run this check manually for the full list.', 'dev-debug-tools' );
        }

        return [
            'status'    => $status,
            'severity'  => $severity,
            'message'   => trim( $message ),
            'items'     => isset( $result[ 'items' ] ) && is_array( $result[ 'items' ] ) ? self::cap_items( array_map( 'strval', $result[ 'items' ] ) ) : [],
            'time'      => time(),
            'scheduled' => $scheduled,
        ];
    } // End run()


    /**
     * Get the last saved results
     *
     * @return array
     */
    public static function get_results() : array {
        $results = get_option( self::RESULTS_KEY, [] );
        return is_array( $results ) ? $results : [];
    } // End get_results()


    /**
     * Save the result of a check
     *
     * @param string $key
     * @param array  $result
     * @return void
     */
    public static function save_result( string $key, array $result ) : void {
        $results = self::get_results();
        $results[ $key ] = $result;
        update_option( self::RESULTS_KEY, $results, false );
    } // End save_result()


    /**
     * How the security score is calculated
     *
     * Weights count each check by severity, credit is earned per status, caps limit the score
     * when a check of that severity fails, and bands set the score's color.
     *
     * @return array
     */
    public static function score_settings() : array {
        return [
            'weights' => [ 'critical' => 10, 'high' => 6, 'medium' => 3, 'low' => 1 ],
            'credit'  => [ 'pass' => 1, 'warning' => 0.5, 'fail' => 0 ],
            'caps'    => [ 'critical' => 59, 'high' => 79 ],
            'bands'   => [ 'good' => 90, 'fair' => 70 ],
        ];
    } // End score_settings()


    /**
     * The security score from the last saved results of the enabled checks
     *
     * @return array [ value (int|null), band, counted, total, counts by status, last_run ]
     */
    public static function score() : array {
        $settings = self::score_settings();
        $severities = self::severities();
        $results = self::get_results();
        $checks = self::enabled_checks();
        $counts = array_fill_keys( array_keys( self::statuses() ), 0 );
        $earned = 0;
        $possible = 0;
        $counted = 0;
        $cap = 100;
        $last_run = 0;

        foreach ( $checks as $key => $check ) {
            $result = $results[ $key ] ?? null;
            $status = $result && isset( $counts[ $result[ 'status' ] ] ) ? $result[ 'status' ] : 'none';
            $counts[ $status ]++;

            if ( ! $result ) {
                continue;
            }

            $last_run = max( $last_run, (int) ( $result[ 'time' ] ?? 0 ) );
            $severity = isset( $severities[ $result[ 'severity' ] ?? '' ] ) ? $result[ 'severity' ] : $check[ 'severity' ];
            $weight = $settings[ 'weights' ][ $severity ] ?? 0;

            if ( ! $weight || ! isset( $settings[ 'credit' ][ $status ] ) ) {
                continue;
            }

            $earned += $weight * $settings[ 'credit' ][ $status ];
            $possible += $weight;
            $counted++;

            if ( $status === 'fail' && isset( $settings[ 'caps' ][ $severity ] ) ) {
                $cap = min( $cap, $settings[ 'caps' ][ $severity ] );
            }
        }

        $value = $possible ? min( $cap, (int) floor( $earned / $possible * 100 ) ) : null;
        $band = 'none';
        if ( $value !== null ) {
            $band = $value >= $settings[ 'bands' ][ 'good' ] ? 'good' : ( $value >= $settings[ 'bands' ][ 'fair' ] ? 'fair' : 'poor' );
        }

        return [
            'value'    => $value,
            'band'     => $band,
            'counted'  => $counted,
            'total'    => count( $checks ),
            'counts'   => $counts,
            'last_run' => $last_run,
        ];
    } // End score()


    /**
     * Build a result
     *
     * @param string $status
     * @param string $message
     * @param array  $items
     * @param string $severity Overrides the check's severity when set.
     * @return array
     */
    private static function result( string $status, string $message, array $items = [], string $severity = '' ) : array {
        return [
            'status'   => $status,
            'message'  => $message,
            'items'    => $items,
            'severity' => $severity,
        ];
    } // End result()


    /**
     * Limit a list of items
     *
     * @param array $items
     * @return array
     */
    private static function cap_items( array $items ) : array {
        $items = array_values( array_unique( $items ) );
        $total = count( $items );
        if ( $total <= self::MAX_ITEMS ) {
            return $items;
        }

        $items = array_slice( $items, 0, self::MAX_ITEMS );
        /* translators: %s: number of items not shown */
        $items[] = sprintf( __( 'and %s more', 'dev-debug-tools' ), number_format_i18n( $total - self::MAX_ITEMS ) );

        return $items;
    } // End cap_items()


    /**
     * Request a URL on this site without cookies
     *
     * @param string $url
     * @param array  $args
     * @return array
     */
    private static function request( string $url, array $args = [] ) : array {
        $args = array_merge( [
            'method'              => 'GET',
            'timeout'             => self::TIMEOUT,
            'redirection'         => 0,
            'sslverify'           => false,
            'limit_response_size' => 8192,
            'user-agent'          => 'Developer Debug Tools Security Checks; ' . home_url(),
            'headers'             => [ 'Cache-Control' => 'no-cache' ],
        ], $args );

        $cache_key = md5( $url . '|' . wp_json_encode( $args ) );
        if ( isset( self::$responses[ $cache_key ] ) ) {
            return self::$responses[ $cache_key ];
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            $result = [ 'code' => 0, 'headers' => [], 'body' => '', 'error' => $response->get_error_message() ];
        } else {
            $headers = wp_remote_retrieve_headers( $response );
            $headers = is_object( $headers ) && method_exists( $headers, 'getAll' ) ? $headers->getAll() : (array) $headers;

            $result = [
                'code'    => (int) wp_remote_retrieve_response_code( $response ),
                'headers' => array_change_key_case( $headers ),
                'body'    => (string) wp_remote_retrieve_body( $response ),
                'error'   => '',
            ];
        }

        self::$responses[ $cache_key ] = $result;

        return $result;
    } // End request()


    /**
     * Get the home page as a logged-out visitor sees it
     *
     * @return array
     */
    private static function home_response() : array {
        return self::request( home_url( '/' ), [ 'redirection' => 3, 'limit_response_size' => 524288 ] );
    } // End home_response()


    /**
     * Get a response header as a string
     *
     * @param array  $headers
     * @param string $name
     * @return string
     */
    private static function header_value( array $headers, string $name ) : string {
        $value = $headers[ strtolower( $name ) ] ?? '';
        return trim( is_array( $value ) ? implode( ', ', $value ) : (string) $value );
    } // End header_value()


    /**
     * Confirm this server can request its own pages
     *
     * @return string Empty when reachable, otherwise the reason it is not.
     */
    private static function unreachable_reason() : string {
        $response = self::home_response();

        if ( $response[ 'error' ] ) {
            /* translators: %s: error message */
            return sprintf( __( 'This server could not request its own home page (%s). Some hosts block these loopback requests, so outside checks could not run.', 'dev-debug-tools' ), $response[ 'error' ] );
        }

        if ( $response[ 'code' ] === 401 ) {
            return __( 'The site asks for a username and password (HTTP authentication), so outside checks could not run.', 'dev-debug-tools' );
        }

        if ( $response[ 'code' ] >= 400 ) {
            /* translators: %d: HTTP status code */
            return sprintf( __( 'The home page returned HTTP %d to this server. A firewall or security plugin may be blocking the server\'s own IP address, so outside checks could not run.', 'dev-debug-tools' ), $response[ 'code' ] );
        }

        return '';
    } // End unreachable_reason()


    /**
     * Request files and report the ones whose content matches
     *
     * Matching content, not the status code, decides a hit, so sites that answer
     * every missing file with HTTP 200 do not cause false alarms.
     *
     * @param array  $targets      [ url => regex ]
     * @param string $fail_message
     * @param string $pass_message
     * @return array
     */
    private static function probe_result( array $targets, string $fail_message, string $pass_message ) : array {
        $unreachable = self::unreachable_reason();
        if ( $unreachable ) {
            return self::result( 'unknown', $unreachable );
        }

        $found = [];
        $failed = 0;

        foreach ( $targets as $url => $signature ) {
            $response = self::request( $url );
            if ( $response[ 'error' ] ) {
                $failed++;
                continue;
            }

            if ( $response[ 'code' ] === 200 && preg_match( $signature, $response[ 'body' ] ) ) {
                $found[] = (string) wp_parse_url( $url, PHP_URL_PATH );
            }
        }

        if ( ! empty( $found ) ) {
            return self::result( 'fail', $fail_message, $found );
        }

        if ( $failed ) {
            /* translators: 1: failed requests, 2: total requests */
            return self::result( 'unknown', sprintf( __( '%1$d of %2$d requests failed, so some files could not be checked.', 'dev-debug-tools' ), $failed, count( $targets ) ) );
        }

        return self::result( 'pass', $pass_message );
    } // End probe_result()


    /**
     * Convert a server path inside the WordPress folder to its URL
     *
     * @param string $path
     * @return string Empty when the path is outside the WordPress folder.
     */
    private static function path_to_url( string $path ) : string {
        $root = wp_normalize_path( ABSPATH );
        $path = wp_normalize_path( $path );
        $path = str_starts_with( $path, '/' ) || preg_match( '#^[a-z]:/#i', $path ) ? $path : $root . ltrim( $path, '/' );

        if ( ! str_starts_with( $path, $root ) ) {
            return '';
        }

        return site_url( '/' . substr( $path, strlen( $root ) ) );
    } // End path_to_url()


    /**
     * Show a server path relative to the WordPress folder
     *
     * @param string $path
     * @return string
     */
    private static function relative_path( string $path ) : string {
        $root = wp_normalize_path( ABSPATH );
        $path = wp_normalize_path( $path );

        if ( str_starts_with( $path, $root ) ) {
            return substr( $path, strlen( $root ) );
        }

        return str_replace( trailingslashit( wp_normalize_path( dirname( ABSPATH ) ) ), '../', $path );
    } // End relative_path()


    /**
     * Find files whose names match a pattern
     *
     * @param string $dir
     * @param string $pattern
     * @param float  $deadline microtime() to stop at, or 0 for no limit.
     * @return array [ 'files' => string[], 'stopped' => bool ]
     */
    private static function find_files( string $dir, string $pattern, float $deadline = 0 ) : array {
        $files = [];
        $stopped = false;

        if ( ! is_dir( $dir ) ) {
            return [ 'files' => $files, 'stopped' => $stopped ];
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ),
                \RecursiveIteratorIterator::LEAVES_ONLY,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            foreach ( $iterator as $file ) {
                if ( $deadline && microtime( true ) > $deadline ) {
                    $stopped = true;
                    break;
                }

                if ( $file->isFile() && preg_match( $pattern, $file->getFilename() ) ) {
                    $files[] = wp_normalize_path( $file->getPathname() );
                }
            }
        } catch ( \UnexpectedValueException $e ) {
            $stopped = true;
        }

        return [ 'files' => $files, 'stopped' => $stopped ];
    } // End find_files()


    /**
     * Folder names of active plugins, including network-active ones
     *
     * @return array
     */
    private static function active_plugin_folders() : array {
        $plugins = (array) get_option( 'active_plugins', [] );
        if ( is_multisite() ) {
            $plugins = array_merge( $plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) ) );
        }

        return array_values( array_unique( array_map( function( $plugin ) {
            return strtolower( dirname( $plugin ) === '.' ? basename( $plugin, '.php' ) : dirname( $plugin ) );
        }, $plugins ) ) );
    } // End active_plugin_folders()


    /**
     * Administrators of this site, plus super admins on multisite
     *
     * @return array
     */
    private static function admins() : array {
        $admins = [];
        foreach ( get_users( [ 'role' => 'administrator' ] ) as $user ) {
            $admins[ $user->ID ] = $user;
        }

        if ( is_multisite() ) {
            foreach ( get_super_admins() as $login ) {
                $user = get_user_by( 'login', $login );
                if ( $user ) {
                    $admins[ $user->ID ] = $user;
                }
            }
        }

        return $admins;
    } // End admins()


    /**
     * Describe where a hooked callback is defined
     *
     * @param mixed $callback
     * @return string "core", or a description such as "plugin: name"
     */
    private static function callback_source( $callback ) : string {
        try {
            if ( is_string( $callback ) && str_contains( $callback, '::' ) ) {
                $callback = explode( '::', $callback, 2 );
            }

            if ( is_array( $callback ) ) {
                $reflection = new \ReflectionMethod( is_object( $callback[ 0 ] ) ? get_class( $callback[ 0 ] ) : $callback[ 0 ], $callback[ 1 ] );
            } elseif ( is_object( $callback ) && ! $callback instanceof \Closure ) {
                $reflection = new \ReflectionMethod( $callback, '__invoke' );
            } else {
                $reflection = new \ReflectionFunction( $callback );
            }
        } catch ( \ReflectionException $e ) {
            return __( 'unknown source', 'dev-debug-tools' );
        }

        $file = wp_normalize_path( (string) $reflection->getFileName() );
        if ( $file === '' ) {
            return __( 'unknown source', 'dev-debug-tools' );
        }

        $places = [
            /* translators: %s: plugin folder */
            wp_normalize_path( WP_PLUGIN_DIR ) . '/'   => __( 'plugin: %s', 'dev-debug-tools' ),
            /* translators: %s: must-use plugin file or folder */
            wp_normalize_path( WPMU_PLUGIN_DIR ) . '/' => __( 'must-use plugin: %s', 'dev-debug-tools' ),
            /* translators: %s: theme folder */
            wp_normalize_path( get_theme_root() ) . '/' => __( 'theme: %s', 'dev-debug-tools' ),
        ];

        foreach ( $places as $dir => $label ) {
            if ( str_starts_with( $file, $dir ) ) {
                return sprintf( $label, strtok( substr( $file, strlen( $dir ) ), '/' ) );
            }
        }

        $root = wp_normalize_path( ABSPATH );
        if ( str_starts_with( $file, $root . 'wp-admin/' ) || str_starts_with( $file, $root . WPINC . '/' ) ) {
            return 'core';
        }

        return self::relative_path( $file );
    } // End callback_source()


    /**
     * WordPress's last plugin or theme update check, when it reached WordPress.org
     *
     * WordPress saves the time of a check before it sends the request, so a failed check
     * leaves a timestamp with empty lists. Every installed item from WordPress.org would be
     * in one of the lists, so empty lists with items installed mean the check failed.
     *
     * @param string $type plugins|themes
     * @return object|null
     */
    private static function update_data( string $type ) : ?object {
        $transient = get_site_transient( 'update_' . $type );
        if ( ! is_object( $transient ) || empty( $transient->last_checked ) ) {
            return null;
        }

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $installed = $type === 'plugins' ? get_plugins() : wp_get_themes();
        if ( ! empty( $installed ) && empty( $transient->response ) && empty( $transient->no_update ) ) {
            return null;
        }

        return $transient;
    } // End update_data()


    /**
     * Message for when WordPress has no usable update data
     *
     * @return string
     */
    private static function update_data_missing() : string {
        return __( 'WordPress has not checked for updates yet, or its last check could not reach WordPress.org. Open Dashboard > Updates, then run this check again.', 'dev-debug-tools' );
    } // End update_data_missing()


    /**
     * Pending updates for plugins or themes
     *
     * @param string $type plugins|themes
     * @return array|null Items, or null when WordPress has not checked yet.
     */
    private static function pending_updates( string $type ) : ?array {
        $transient = self::update_data( $type );
        if ( ! $transient ) {
            return null;
        }

        $installed = $type === 'plugins' ? get_plugins() : [];
        $items = [];

        foreach ( (array) ( $transient->response ?? [] ) as $slug => $update ) {
            $update = (array) $update;

            if ( $type === 'plugins' ) {
                $name = $installed[ $slug ][ 'Name' ] ?? $slug;
                $version = $installed[ $slug ][ 'Version' ] ?? '?';
            } else {
                $theme = wp_get_theme( $slug );
                $name = $theme->exists() ? $theme->get( 'Name' ) : $slug;
                $version = $theme->exists() ? $theme->get( 'Version' ) : '?';
            }

            /* translators: 1: name, 2: installed version, 3: available version */
            $items[] = sprintf( __( '%1$s %2$s (update to %3$s)', 'dev-debug-tools' ), $name, $version, $update[ 'new_version' ] ?? '?' );
        }

        return $items;
    } // End pending_updates()


    /**
     * Check: debug log publicly accessible
     *
     * @return array
     */
    public static function check_exposed_debug_log() : array {
        $signature = '/\A\s*\[[^\]]+\]\s*PHP /';
        $targets = [ content_url( 'debug.log' ) => $signature ];

        $paths = [ defined( 'WP_DEBUG_LOG' ) ? WP_DEBUG_LOG : '', get_option( 'ddtt_debug_log_path', '' ) ];
        foreach ( $paths as $path ) {
            if ( ! is_string( $path ) || $path === '' || in_array( strtolower( $path ), [ 'true', '1', 'false', '0' ], true ) ) {
                continue;
            }

            $url = self::path_to_url( $path );
            if ( $url ) {
                $targets[ $url ] = $signature;
            }
        }

        return self::probe_result( $targets, __( 'Anyone can download the debug log.', 'dev-debug-tools' ), __( 'The debug log cannot be downloaded.', 'dev-debug-tools' ) );
    } // End check_exposed_debug_log()


    /**
     * Check: wp-config.php backups
     *
     * @return array
     */
    public static function check_exposed_config_backups() : array {
        $names = [ 'wp-config.php.bak', 'wp-config.php.old', 'wp-config.php.orig', 'wp-config.php.save', 'wp-config.php.swp', '.wp-config.php.swp', 'wp-config.php~', 'wp-config.php.txt', 'wp-config.bak', 'wp-config.old', 'wp-config.txt' ];
        $targets = [];
        foreach ( $names as $name ) {
            $targets[ site_url( '/' . $name ) ] = '/<\?php.*?\b(DB_NAME|DB_PASSWORD|AUTH_KEY)\b/s';
        }

        return self::probe_result( $targets, __( 'Copies of wp-config.php can be downloaded:', 'dev-debug-tools' ), __( 'No copies of wp-config.php can be downloaded.', 'dev-debug-tools' ) );
    } // End check_exposed_config_backups()


    /**
     * Check: .git, .svn and .env files
     *
     * @return array
     */
    public static function check_exposed_vcs_env() : array {
        $env = '/\A(?:\s*(?:#[^\n]*)?\n)*\s*[A-Za-z_][A-Za-z0-9_]*\s*=/';
        $git_head = '/\Aref: refs\//';
        $git_config = '/\A\s*\[core\]/';

        $targets = [
            site_url( '/.env' )            => $env,
            site_url( '/.env.local' )      => $env,
            site_url( '/.env.production' ) => $env,
            site_url( '/.env.backup' )     => $env,
            site_url( '/.git/HEAD' )       => $git_head,
            site_url( '/.git/config' )     => $git_config,
            site_url( '/.svn/entries' )    => '/\A\d+\s*\n/',
            content_url( '.git/HEAD' )     => $git_head,
            content_url( '.git/config' )   => $git_config,
        ];

        return self::probe_result( $targets, __( 'Version control or environment files can be downloaded:', 'dev-debug-tools' ), __( 'No .git, .svn or .env files can be downloaded.', 'dev-debug-tools' ) );
    } // End check_exposed_vcs_env()


    /**
     * Check: database and site backups
     *
     * @return array
     */
    public static function check_exposed_backups() : array {
        $host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        $names = [ 'backup.sql', 'backup.sql.gz', 'backup.zip', 'backup.tar.gz', 'db.sql', 'database.sql', 'dump.sql', 'mysql.sql', 'site.zip', 'wordpress.zip', 'wp.zip', 'public_html.zip', 'www.zip', $host . '.sql', $host . '.zip', $host . '.tar.gz' ];
        $signatures = [
            'sql' => '/\A(?:\s*(?:--|\/\*|#)[^\n]*\n)*\s*(?:CREATE|INSERT|DROP|SET|LOCK|USE)\b/i',
            'zip' => '/\APK\x03\x04/',
            'gz'  => '/\A\x1f\x8b/',
        ];

        $targets = [];
        foreach ( $names as $name ) {
            $targets[ site_url( '/' . $name ) ] = $signatures[ pathinfo( $name, PATHINFO_EXTENSION ) ];
        }

        return self::probe_result( $targets, __( 'Backups can be downloaded:', 'dev-debug-tools' ), __( 'No backups were found at common file names.', 'dev-debug-tools' ) );
    } // End check_exposed_backups()


    /**
     * Check: phpinfo, error logs and database tools
     *
     * @return array
     */
    public static function check_exposed_info_files() : array {
        $phpinfo = '/<title>[^<]*phpinfo\(\)/i';
        $log = '/\A\s*\[[^\]]+\]\s*PHP /';
        $ds_store = '/\A\x00\x00\x00\x01Bud1/';
        $adminer = '/<title>[^<]*Adminer/i';

        $targets = [
            site_url( '/phpinfo.php' )        => $phpinfo,
            site_url( '/info.php' )           => $phpinfo,
            site_url( '/php_info.php' )       => $phpinfo,
            site_url( '/test.php' )           => $phpinfo,
            site_url( '/error_log' )          => $log,
            site_url( '/wp-admin/error_log' ) => $log,
            content_url( 'error_log' )        => $log,
            site_url( '/.DS_Store' )          => $ds_store,
            content_url( '.DS_Store' )        => $ds_store,
            site_url( '/adminer.php' )        => $adminer,
        ];

        $origin = self::site_origin();
        $targets[ $origin . '/server-status' ] = '/<title>Apache Status<\/title>|Apache Server Status for/i';
        $targets[ $origin . '/server-info' ] = '/<title>Server Information<\/title>|Apache Server Information/i';
        $targets[ $origin . '/nginx_status' ] = '/Active connections:\s*\d+/';

        return self::probe_result( $targets, __( 'Diagnostic files can be opened by anyone:', 'dev-debug-tools' ), __( 'No phpinfo pages, error logs, database tools or server status pages were found at common file names.', 'dev-debug-tools' ) );
    } // End check_exposed_info_files()


    /**
     * Check: directory listing
     *
     * @return array
     */
    public static function check_directory_listing() : array {
        $listing = '/<title>\s*Index of \//i';
        $uploads = wp_upload_dir( null, false );

        $targets = [
            trailingslashit( $uploads[ 'baseurl' ] ) => $listing,
            content_url( '/' )                       => $listing,
            content_url( 'plugins/' )                => $listing,
            content_url( 'themes/' )                 => $listing,
            includes_url( '/' )                      => $listing,
            includes_url( 'js/' )                    => $listing,
        ];

        $year = gmdate( 'Y' );
        if ( is_dir( trailingslashit( $uploads[ 'basedir' ] ) . $year ) ) {
            $targets[ trailingslashit( $uploads[ 'baseurl' ] ) . $year . '/' ] = $listing;
        }

        return self::probe_result( $targets, __( 'These folders list their contents:', 'dev-debug-tools' ), __( 'Folders do not list their contents.', 'dev-debug-tools' ) );
    } // End check_directory_listing()


    /**
     * Check: XML-RPC
     *
     * @return array
     */
    public static function check_xmlrpc() : array {
        $unreachable = self::unreachable_reason();
        if ( $unreachable ) {
            return self::result( 'unknown', $unreachable );
        }

        $response = self::request( site_url( 'xmlrpc.php' ) );
        if ( $response[ 'error' ] ) {
            /* translators: %s: error message */
            return self::result( 'unknown', sprintf( __( 'xmlrpc.php could not be requested: %s', 'dev-debug-tools' ), $response[ 'error' ] ) );
        }

        if ( stripos( $response[ 'body' ], 'XML-RPC server accepts POST requests only' ) === false ) {
            return self::result( 'pass', __( 'xmlrpc.php is blocked.', 'dev-debug-tools' ) );
        }

        if ( in_array( 'jetpack', self::active_plugin_folders(), true ) ) {
            return self::result( 'warning', __( 'xmlrpc.php responds. Jetpack needs it, so allow only Jetpack\'s servers instead of blocking it entirely.', 'dev-debug-tools' ), [], 'low' );
        }

        if ( ! apply_filters( 'xmlrpc_enabled', true ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter applied to read its effective value.
            return self::result( 'warning', __( 'xmlrpc.php responds, but logins through it are turned off. Pingbacks can still be misused.', 'dev-debug-tools' ), [], 'low' );
        }

        return self::result( 'fail', __( 'xmlrpc.php responds and accepts logins.', 'dev-debug-tools' ) );
    } // End check_xmlrpc()


    /**
     * Check: usernames exposed by the REST API and author redirects
     *
     * @return array
     */
    public static function check_user_enumeration() : array {
        $unreachable = self::unreachable_reason();
        if ( $unreachable ) {
            return self::result( 'unknown', $unreachable );
        }

        $slugs = [];

        $rest = self::request( rest_url( 'wp/v2/users' ), [ 'limit_response_size' => 65536 ] );
        $users = $rest[ 'code' ] === 200 ? json_decode( $rest[ 'body' ], true ) : null;
        if ( is_array( $users ) ) {
            foreach ( $users as $user ) {
                if ( is_array( $user ) && ! empty( $user[ 'slug' ] ) ) {
                    $slugs[] = sanitize_title( $user[ 'slug' ] );
                }
            }
        }

        $author = self::request( add_query_arg( 'author', 1, home_url( '/' ) ) );
        $location = self::header_value( $author[ 'headers' ], 'location' );
        if ( $author[ 'code' ] >= 300 && $author[ 'code' ] < 400 && preg_match( '#/author/([^/?]+)#', $location, $matches ) ) {
            $slugs[] = sanitize_title( $matches[ 1 ] );
        }

        $slugs = array_unique( $slugs );
        if ( empty( $slugs ) ) {
            return self::result( 'pass', __( 'The REST API and author redirects do not reveal any usernames.', 'dev-debug-tools' ) );
        }

        $items = [];
        $login_exposed = false;

        foreach ( $slugs as $slug ) {
            $user = get_user_by( 'slug', $slug );
            if ( $user && strtolower( $user->user_login ) === $slug ) {
                $login_exposed = true;
                /* translators: %s: author slug */
                $items[] = sprintf( __( '%s (also the login username)', 'dev-debug-tools' ), $slug );
            } else {
                $items[] = $slug;
            }
        }

        if ( $login_exposed ) {
            return self::result( 'fail', __( 'Author slugs are public, and at least one is also a login username.', 'dev-debug-tools' ), $items, 'medium' );
        }

        return self::result( 'warning', __( 'Author slugs are public. None of them is a login username.', 'dev-debug-tools' ), $items );
    } // End check_user_enumeration()


    /**
     * Check: security headers
     *
     * @return array
     */
    public static function check_security_headers() : array {
        $unreachable = self::unreachable_reason();
        if ( $unreachable ) {
            return self::result( 'unknown', $unreachable );
        }

        $headers = self::home_response()[ 'headers' ];
        $csp = self::header_value( $headers, 'content-security-policy' );
        $is_https = wp_parse_url( home_url(), PHP_URL_SCHEME ) === 'https';

        $required = [
            'Strict-Transport-Security'              => ! $is_https || self::header_value( $headers, 'strict-transport-security' ) !== '',
            'X-Content-Type-Options: nosniff'        => stripos( self::header_value( $headers, 'x-content-type-options' ), 'nosniff' ) !== false,
            'X-Frame-Options or CSP frame-ancestors' => self::header_value( $headers, 'x-frame-options' ) !== '' || stripos( $csp, 'frame-ancestors' ) !== false,
        ];

        $recommended = [
            'Referrer-Policy'         => self::header_value( $headers, 'referrer-policy' ) !== '',
            'Permissions-Policy'      => self::header_value( $headers, 'permissions-policy' ) !== '',
            'Content-Security-Policy' => $csp !== '',
        ];

        $missing_required = array_keys( $required, false, true );
        $missing_recommended = array_keys( $recommended, false, true );

        $items = $missing_required;
        foreach ( $missing_recommended as $header ) {
            /* translators: %s: header name */
            $items[] = sprintf( __( '%s (recommended)', 'dev-debug-tools' ), $header );
        }

        $cdn_note = __( 'Headers added by a CDN or proxy, such as Cloudflare, may not reach requests made from this server. Confirm in your browser\'s network tab.', 'dev-debug-tools' );

        if ( ! empty( $missing_required ) ) {
            return self::result( 'fail', __( 'The home page is missing headers that protect visitors.', 'dev-debug-tools' ) . ' ' . $cdn_note, $items );
        }

        if ( ! empty( $missing_recommended ) ) {
            return self::result( 'warning', __( 'The main protection headers are set. Some recommended ones are missing.', 'dev-debug-tools' ) . ' ' . $cdn_note, $items );
        }

        return self::result( 'pass', __( 'All checked security headers are set.', 'dev-debug-tools' ) );
    } // End check_security_headers()


    /**
     * Check: HTTPS
     *
     * @return array
     */
    public static function check_https() : array {
        if ( wp_parse_url( home_url(), PHP_URL_SCHEME ) !== 'https' ) {
            return self::result( 'fail', __( 'The site address uses http://.', 'dev-debug-tools' ) );
        }

        $unreachable = self::unreachable_reason();
        if ( $unreachable ) {
            return self::result( 'unknown', $unreachable );
        }

        $http = self::request( set_url_scheme( home_url( '/' ), 'http' ) );
        $location = self::header_value( $http[ 'headers' ], 'location' );
        $redirects = $http[ 'code' ] >= 300 && $http[ 'code' ] < 400 && str_starts_with( strtolower( $location ), 'https://' );

        if ( ! $http[ 'error' ] && ! $redirects ) {
            /* translators: %d: HTTP status code */
            return self::result( 'fail', sprintf( __( 'Visitors can load the site over http:// without being redirected (HTTP %d).', 'dev-debug-tools' ), $http[ 'code' ] ) );
        }

        preg_match_all( '/<(?:script|img|iframe|link|source|video|audio|embed)\b[^>]*?\s(?:src|href)\s*=\s*["\'](http:\/\/[^"\']+)/i', self::home_response()[ 'body' ], $matches );
        if ( ! empty( $matches[ 1 ] ) ) {
            return self::result( 'warning', __( 'The home page loads some files over http://, which browsers block or flag as not secure.', 'dev-debug-tools' ), $matches[ 1 ], 'medium' );
        }

        if ( $http[ 'error' ] ) {
            return self::result( 'pass', __( 'The site uses https://. The http:// redirect could not be tested from this server.', 'dev-debug-tools' ) );
        }

        return self::result( 'pass', __( 'The site uses https:// and redirects http:// requests to it.', 'dev-debug-tools' ) );
    } // End check_https()


    /**
     * Check: software versions disclosed
     *
     * @return array
     */
    public static function check_version_disclosure() : array {
        $unreachable = self::unreachable_reason();
        if ( $unreachable ) {
            return self::result( 'unknown', $unreachable );
        }

        $home = self::home_response();
        $items = [];

        foreach ( [ 'X-Powered-By', 'Server' ] as $header ) {
            $value = self::header_value( $home[ 'headers' ], $header );
            if ( preg_match( '/\d/', $value ) ) {
                $items[] = $header . ': ' . $value;
            }
        }

        if ( preg_match( '/<meta[^>]+name=["\']generator["\'][^>]+content=["\'](WordPress\s[\d.]+)/i', $home[ 'body' ], $matches ) ) {
            /* translators: %s: generator content */
            $items[] = sprintf( __( 'Generator tag: %s', 'dev-debug-tools' ), $matches[ 1 ] );
        }

        $readme = self::request( site_url( '/readme.html' ) );
        if ( $readme[ 'code' ] === 200 && preg_match( '/<title>[^<]*WordPress[^<]*ReadMe/i', $readme[ 'body' ] ) ) {
            $items[] = '/readme.html';
        }

        if ( empty( $items ) ) {
            return self::result( 'pass', __( 'No version numbers were found in headers, the home page, or readme.html.', 'dev-debug-tools' ) );
        }

        return self::result( 'warning', __( 'Version numbers are visible to visitors:', 'dev-debug-tools' ), $items );
    } // End check_version_disclosure()


    /**
     * Check: errors displayed on screen
     *
     * @return array
     */
    public static function check_debug_display() : array {
        $debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
        $display = $debug ? ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY ) : filter_var( get_cfg_var( 'display_errors' ), FILTER_VALIDATE_BOOLEAN );
        $environment = wp_get_environment_type();

        if ( $display ) {
            $message = $debug ? __( 'WP_DEBUG is on and WP_DEBUG_DISPLAY is not false, so errors print on pages.', 'dev-debug-tools' ) : __( 'display_errors is on in php.ini, so PHP errors print on pages.', 'dev-debug-tools' );

            if ( $environment !== 'production' ) {
                /* translators: %s: environment type */
                return self::result( 'warning', $message . ' ' . sprintf( __( 'The environment type is "%s", so this is acceptable for now. Turn it off before the site goes live.', 'dev-debug-tools' ), $environment ), [], 'medium' );
            }

            return self::result( 'fail', $message . ' ' . __( 'The environment type is "production" (the default when WP_ENVIRONMENT_TYPE is not set).', 'dev-debug-tools' ) );
        }

        if ( $debug && $environment === 'production' ) {
            return self::result( 'warning', __( 'WP_DEBUG is on in production. Errors are hidden from visitors, but debug mode adds overhead and logs more.', 'dev-debug-tools' ), [], 'low' );
        }

        return self::result( 'pass', __( 'Errors are not displayed to visitors.', 'dev-debug-tools' ) );
    } // End check_debug_display()


    /**
     * Check: plugin and theme editor
     *
     * @return array
     */
    public static function check_file_editing() : array {
        if ( ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) || ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) ) {
            return self::result( 'pass', __( 'The plugin and theme editor is turned off.', 'dev-debug-tools' ) );
        }

        return self::result( 'fail', __( 'Administrators can edit plugin and theme PHP from the dashboard.', 'dev-debug-tools' ) );
    } // End check_file_editing()


    /**
     * Check: security keys and salts (values are never shown)
     *
     * @return array
     */
    public static function check_salts() : array {
        $names = [ 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ];
        $values = [];
        foreach ( $names as $name ) {
            if ( defined( $name ) ) {
                $values[ $name ] = (string) constant( $name );
            }
        }

        $counts = array_count_values( $values );
        $fallback = [];
        $weak = [];

        foreach ( $names as $name ) {
            if ( ! isset( $values[ $name ] ) || $values[ $name ] === 'put your unique phrase here' || $counts[ $values[ $name ] ] > 1 ) {
                $fallback[] = $name;
            } elseif ( strlen( $values[ $name ] ) < 32 ) {
                $weak[] = $name;
            }
        }

        if ( ! empty( $weak ) ) {
            return self::result( 'fail', __( 'These keys are shorter than 32 characters:', 'dev-debug-tools' ), $weak );
        }

        if ( ! empty( $fallback ) ) {
            return self::result( 'warning', __( 'These keys are missing, duplicated or still the default, so WordPress uses copies stored in the database. Anyone who can read the database can forge login cookies.', 'dev-debug-tools' ), $fallback, 'medium' );
        }

        return self::result( 'pass', __( 'All 8 keys and salts are set, unique and at least 32 characters.', 'dev-debug-tools' ) );
    } // End check_salts()


    /**
     * Check: database login (values are never shown)
     *
     * @return array
     */
    public static function check_db_credentials() : array {
        $items = [];

        if ( defined( 'DB_USER' ) && strtolower( (string) DB_USER ) === 'root' ) {
            $items[] = __( 'The database user is root.', 'dev-debug-tools' );
        }

        $password = defined( 'DB_PASSWORD' ) ? (string) DB_PASSWORD : '';
        if ( $password === '' ) {
            $items[] = __( 'The database password is blank.', 'dev-debug-tools' );
        }

        if ( ! empty( $items ) ) {
            return self::result( 'fail', __( 'The database login is weak:', 'dev-debug-tools' ), $items );
        }

        if ( strlen( $password ) < 12 ) {
            return self::result( 'warning', __( 'The database password is shorter than 12 characters.', 'dev-debug-tools' ), [], 'medium' );
        }

        return self::result( 'pass', __( 'The database user is not root and the password is at least 12 characters.', 'dev-debug-tools' ) );
    } // End check_db_credentials()


    /**
     * Check: world-writable files and folders
     *
     * @return array
     */
    public static function check_file_permissions() : array {
        if ( DIRECTORY_SEPARATOR === '\\' ) {
            return self::result( 'unknown', __( 'File permissions cannot be checked on Windows servers.', 'dev-debug-tools' ) );
        }

        $config = file_exists( ABSPATH . 'wp-config.php' ) ? ABSPATH . 'wp-config.php' : dirname( ABSPATH ) . '/wp-config.php';
        $uploads = wp_upload_dir( null, false );
        $paths = [ $config, ABSPATH . '.htaccess', ABSPATH . 'index.php', ABSPATH, ABSPATH . 'wp-admin', ABSPATH . WPINC, WP_CONTENT_DIR, WP_PLUGIN_DIR, get_theme_root(), $uploads[ 'basedir' ] ];

        $items = [];
        foreach ( array_unique( $paths ) as $path ) {
            if ( ! file_exists( $path ) ) {
                continue;
            }

            $perms = fileperms( $path ) & 0777;
            if ( $perms & 0002 ) {
                $relative = self::relative_path( $path );
                $items[] = sprintf( '%1$s (%2$s)', $relative !== '' ? $relative : '/', sprintf( '%04o', $perms ) );
            }
        }

        if ( ! empty( $items ) ) {
            return self::result( 'fail', __( 'Any account on the server can change these:', 'dev-debug-tools' ), $items );
        }

        return self::result( 'pass', __( 'wp-config.php, .htaccess and the main WordPress folders are not world-writable.', 'dev-debug-tools' ) );
    } // End check_file_permissions()


    /**
     * Check: PHP version support
     *
     * @return array
     */
    public static function check_php_version() : array {
        $branch = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;


        /**
         * Filter the PHP end-of-life dates (security support end, from php.net).
         */
        $eol = apply_filters( 'ddtt_security_php_eol', [
            '8.0' => '2023-11-26',
            '8.1' => '2025-12-31',
            '8.2' => '2026-12-31',
            '8.3' => '2027-12-31',
            '8.4' => '2028-12-31',
            '8.5' => '2029-12-31',
        ] );

        return self::support_result( 'PHP', PHP_VERSION, $branch, (array) $eol );
    } // End check_php_version()


    /**
     * Check: open registration role
     *
     * @return array
     */
    public static function check_registration() : array {
        $open = is_multisite() ? in_array( get_site_option( 'registration' ), [ 'user', 'all' ], true ) : (bool) get_option( 'users_can_register' );
        if ( ! $open ) {
            return self::result( 'pass', __( 'Visitors cannot register accounts.', 'dev-debug-tools' ) );
        }

        $role_key = (string) get_option( 'default_role', 'subscriber' );
        $role = get_role( $role_key );
        $role_names = wp_roles()->get_names();
        $role_name = isset( $role_names[ $role_key ] ) ? translate_user_role( $role_names[ $role_key ] ) : $role_key;

        if ( $role && ( $role->has_cap( 'manage_options' ) || $role->has_cap( 'edit_users' ) || $role->has_cap( 'activate_plugins' ) ) ) {
            /* translators: %s: role name */
            return self::result( 'fail', sprintf( __( 'Anyone can register as %s, which can take over the site.', 'dev-debug-tools' ), $role_name ) );
        }

        if ( $role && ( $role->has_cap( 'edit_posts' ) || $role->has_cap( 'upload_files' ) ) ) {
            /* translators: %s: role name */
            return self::result( 'fail', sprintf( __( 'Anyone can register as %s, which can create content or upload files.', 'dev-debug-tools' ), $role_name ), [], 'high' );
        }

        /* translators: %s: role name */
        return self::result( 'info', sprintf( __( 'Anyone can register as %s. Confirm the site needs open registration.', 'dev-debug-tools' ), $role_name ), [], 'info' );
    } // End check_registration()


    /**
     * Check: administrator accounts
     *
     * @return array
     */
    public static function check_admin_accounts() : array {
        $guessable = [ 'admin', 'administrator', 'root', 'test', 'demo', 'user', 'webmaster', 'wpadmin', 'sysadmin', 'support' ];
        $days = absint( apply_filters( 'ddtt_security_admin_inactive_days', 180 ) );
        $cutoff = time() - ( $days * DAY_IN_SECONDS );
        $admins = self::admins();
        $fails = [];
        $warnings = [];

        foreach ( $admins as $admin ) {
            $login = $admin->user_login;
            $registered = strtotime( $admin->user_registered . ' UTC' );

            if ( in_array( strtolower( $login ), $guessable, true ) ) {
                /* translators: %s: username */
                $fails[] = sprintf( __( '%s: easy-to-guess username', 'dev-debug-tools' ), $login );
            }

            if ( $registered && $registered > time() - WEEK_IN_SECONDS ) {
                /* translators: 1: username, 2: time ago */
                $warnings[] = sprintf( __( '%1$s: created %2$s ago. Confirm you know this account.', 'dev-debug-tools' ), $login, human_time_diff( $registered ) );
            }

            if ( ! $days ) {
                continue;
            }

            $last_active = self::last_active( $admin->ID );

            if ( $last_active && $last_active < $cutoff ) {
                /* translators: 1: username, 2: time ago */
                $warnings[] = sprintf( __( '%1$s: last active %2$s ago', 'dev-debug-tools' ), $login, human_time_diff( $last_active ) );
            } elseif ( ! $last_active && $registered && $registered < $cutoff ) {
                /* translators: 1: username, 2: time ago */
                $warnings[] = sprintf( __( '%1$s: no recorded activity, created %2$s ago', 'dev-debug-tools' ), $login, human_time_diff( $registered ) );
            }
        }

        if ( ! empty( $fails ) ) {
            return self::result( 'fail', __( 'Some administrator usernames are easy to guess.', 'dev-debug-tools' ), array_merge( $fails, $warnings ) );
        }

        if ( ! empty( $warnings ) ) {
            /* translators: %d: number of days */
            return self::result( 'info', sprintf( __( 'Review these administrator accounts. Accounts are flagged when they are new or have not been active for %d days.', 'dev-debug-tools' ), $days ), $warnings );
        }

        /* translators: 1: number of administrators, 2: number of days */
        return self::result( 'pass', sprintf( _n( '%1$d administrator account. It has been active in the last %2$d days and its username is not easy to guess.', '%1$d administrator accounts. All have been active in the last %2$d days and none use easy-to-guess usernames.', count( $admins ), 'dev-debug-tools' ), count( $admins ), $days ) );
    } // End check_admin_accounts()


    /**
     * Get a user's last activity from DDT's online tracking or their latest login session
     *
     * @param int $user_id
     * @return int Unix timestamp, or 0 when unknown.
     */
    private static function last_active( int $user_id ) : int {
        $times = [ absint( get_user_meta( $user_id, 'ddtt_last_online', true ) ) ];

        $sessions = get_user_meta( $user_id, 'session_tokens', true );
        if ( is_array( $sessions ) && ! empty( $sessions ) ) {
            $times = array_merge( $times, array_map( 'absint', array_column( $sessions, 'login' ) ) );
        }

        return max( $times );
    } // End last_active()


    /**
     * Check: administrator application passwords
     *
     * @return array
     */
    public static function check_app_passwords() : array {
        if ( ! function_exists( 'wp_is_application_passwords_available' ) || ! wp_is_application_passwords_available() ) {
            return self::result( 'pass', __( 'Application passwords are turned off.', 'dev-debug-tools' ) );
        }

        $items = [];
        foreach ( self::admins() as $admin ) {
            $passwords = \WP_Application_Passwords::get_user_application_passwords( $admin->ID );
            if ( empty( $passwords ) ) {
                continue;
            }

            $last_used = max( array_map( 'intval', wp_list_pluck( $passwords, 'last_used' ) ) );
            /* translators: 1: username, 2: number of passwords, 3: date last used */
            $items[] = sprintf( __( '%1$s: %2$d, last used %3$s', 'dev-debug-tools' ), $admin->user_login, count( $passwords ), $last_used ? wp_date( get_option( 'date_format' ), $last_used ) : __( 'never', 'dev-debug-tools' ) );
        }

        if ( ! empty( $items ) ) {
            return self::result( 'info', __( 'These administrators have application passwords, which log in without two-factor. Revoke any that are not in use.', 'dev-debug-tools' ), $items );
        }

        return self::result( 'pass', __( 'No administrator has an application password.', 'dev-debug-tools' ) );
    } // End check_app_passwords()


    /**
     * Check: two-factor plugin
     *
     * @return array
     */
    public static function check_two_factor() : array {
        $active = self::active_plugin_folders();
        $dedicated = array_intersect( [ 'two-factor', 'wp-2fa', 'two-factor-authentication', 'two-factor-login', 'google-authenticator', 'miniorange-2-factor-authentication', 'duo-wordpress', 'wordfence-login-security' ], $active );
        $suites = array_intersect( [ 'wordfence', 'better-wp-security', 'ithemes-security-pro', 'all-in-one-wp-security-and-firewall', 'wp-simple-firewall', 'defender-security', 'wp-defender', 'loginizer' ], $active );

        if ( ! empty( $dedicated ) ) {
            /* translators: %s: plugin folders */
            return self::result( 'pass', sprintf( __( 'Two-factor plugin active: %s. Confirm every administrator has set it up.', 'dev-debug-tools' ), implode( ', ', $dedicated ) ) );
        }

        if ( ! empty( $suites ) ) {
            /* translators: %s: plugin folders */
            return self::result( 'warning', sprintf( __( 'A security plugin with a two-factor feature is active: %s. Confirm two-factor is turned on and every administrator has set it up.', 'dev-debug-tools' ), implode( ', ', $suites ) ), [], 'low' );
        }

        return self::result( 'fail', __( 'No two-factor plugin was found. This check looks for known plugin folders, so a custom or host-provided setup will not be detected.', 'dev-debug-tools' ) );
    } // End check_two_factor()


    /**
     * Check: login attempt limiting
     *
     * @return array
     */
    public static function check_login_protection() : array {
        $active = self::active_plugin_folders();
        $found = array_intersect( [ 'limit-login-attempts-reloaded', 'limit-login-attempts', 'wordfence', 'better-wp-security', 'ithemes-security-pro', 'all-in-one-wp-security-and-firewall', 'loginizer', 'wp-cerber', 'wp-simple-firewall', 'defender-security', 'wp-defender', 'login-lockdown', 'jetpack-protect' ], $active );

        if ( ! empty( $found ) ) {
            /* translators: %s: plugin folders */
            return self::result( 'pass', sprintf( __( 'Login protection plugin active: %s.', 'dev-debug-tools' ), implode( ', ', $found ) ) );
        }

        if ( in_array( 'jetpack', $active, true ) ) {
            return self::result( 'warning', __( 'Jetpack is active. Confirm its Brute Force Protection is turned on.', 'dev-debug-tools' ), [], 'low' );
        }

        return self::result( 'fail', __( 'No login protection plugin was found. Your host or firewall may already limit login attempts; this check cannot see that.', 'dev-debug-tools' ) );
    } // End check_login_protection()


    /**
     * Check: Developer Debug Tools emergency login (tokens are never shown)
     *
     * @return array
     */
    public static function check_ddt_backdoor() : array {
        $class = __NAMESPACE__ . '\BackDoor';
        if ( ! class_exists( $class ) ) {
            return self::result( 'pass', __( 'The emergency login file is not loaded.', 'dev-debug-tools' ) );
        }

        $defaults = ( new \ReflectionClass( $class ) )->getDefaultProperties();
        if ( empty( $defaults[ 'enabled' ] ) ) {
            return self::result( 'pass', __( 'The emergency login is turned off.', 'dev-debug-tools' ) );
        }

        $tokens = isset( $defaults[ 'tokens' ] ) && is_array( $defaults[ 'tokens' ] ) ? $defaults[ 'tokens' ] : [];
        $public = array_keys( $tokens, self::EXAMPLE_BACKDOOR_TOKEN, true );

        if ( ! empty( $public ) ) {
            /* translators: %s: user IDs */
            return self::result( 'fail', sprintf( __( 'The emergency login is on and still uses the example token from the plugin\'s public source code. Anyone can log in as user ID %s.', 'dev-debug-tools' ), implode( ', ', $public ) ) );
        }

        return self::result( 'fail', __( 'The emergency login is on.', 'dev-debug-tools' ) );
    } // End check_ddt_backdoor()


    /**
     * Check: who can open Developer Debug Tools
     *
     * @return array
     */
    public static function check_ddt_access() : array {
        $password = get_option( 'ddtt_enable_pass', false ) && get_option( 'ddtt_pass', '' );
        $dev_only = (bool) get_option( 'ddtt_dev_access_only', false );
        $non_devs = array_diff( array_keys( self::admins() ), array_map( 'intval', Helpers::get_devs() ) );

        if ( $password || $dev_only || empty( $non_devs ) ) {
            return self::result( 'pass', __( 'Only developers can open Developer Debug Tools, or it is password protected.', 'dev-debug-tools' ) );
        }

        /* translators: %d: number of administrators */
        return self::result( 'warning', sprintf( _n( '%d administrator who is not a developer can open Developer Debug Tools, and password protection is off.', '%d administrators who are not developers can open Developer Debug Tools, and password protection is off.', count( $non_devs ), 'dev-debug-tools' ), count( $non_devs ) ) );
    } // End check_ddt_access()


    /**
     * Check: WordPress core updates
     *
     * @return array
     */
    public static function check_core_updates() : array {
        $transient = get_site_transient( 'update_core' );
        if ( ! is_object( $transient ) || empty( $transient->updates ) ) {
            return self::result( 'unknown', __( 'WordPress has not checked for updates yet. Open Dashboard > Updates, then run this check again.', 'dev-debug-tools' ) );
        }

        $version = get_bloginfo( 'version' );
        $offer = '';
        foreach ( $transient->updates as $update ) {
            if ( isset( $update->response ) && $update->response === 'upgrade' ) {
                $offer = (string) $update->current;
                break;
            }
        }

        $auto_off = ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED ) || ( defined( 'WP_AUTO_UPDATE_CORE' ) && WP_AUTO_UPDATE_CORE === false ) || apply_filters( 'automatic_updater_disabled', false ) || ! apply_filters( 'allow_minor_auto_core_updates', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter applied to read its effective value.
        $items = $auto_off ? [ __( 'Automatic security releases are turned off.', 'dev-debug-tools' ) ] : [];

        if ( $offer ) {
            /* translators: 1: installed version, 2: available version */
            return self::result( 'fail', sprintf( __( 'WordPress %1$s is installed. %2$s is available.', 'dev-debug-tools' ), $version, $offer ), $items );
        }

        if ( $auto_off ) {
            /* translators: %s: installed version */
            return self::result( 'info', sprintf( __( 'WordPress %s is up to date, but automatic security releases are turned off. Make sure someone installs security releases promptly.', 'dev-debug-tools' ), $version ) );
        }

        /* translators: 1: installed version, 2: time since last check */
        return self::result( 'pass', sprintf( __( 'WordPress %1$s is up to date (checked %2$s ago).', 'dev-debug-tools' ), $version, human_time_diff( (int) $transient->last_checked ) ) );
    } // End check_core_updates()


    /**
     * Check: plugin updates
     *
     * @return array
     */
    public static function check_plugin_updates() : array {
        $items = self::pending_updates( 'plugins' );
        if ( $items === null ) {
            return self::result( 'unknown', self::update_data_missing() );
        }

        if ( ! empty( $items ) ) {
            /* translators: %d: number of plugins */
            return self::result( 'fail', sprintf( _n( '%d plugin has an update.', '%d plugins have updates.', count( $items ), 'dev-debug-tools' ), count( $items ) ), $items );
        }

        return self::result( 'pass', __( 'All plugins are up to date.', 'dev-debug-tools' ) );
    } // End check_plugin_updates()


    /**
     * Check: theme updates
     *
     * @return array
     */
    public static function check_theme_updates() : array {
        $items = self::pending_updates( 'themes' );
        if ( $items === null ) {
            return self::result( 'unknown', self::update_data_missing() );
        }

        if ( ! empty( $items ) ) {
            /* translators: %d: number of themes */
            return self::result( 'fail', sprintf( _n( '%d theme has an update.', '%d themes have updates.', count( $items ), 'dev-debug-tools' ), count( $items ) ), $items );
        }

        return self::result( 'pass', __( 'All themes are up to date.', 'dev-debug-tools' ) );
    } // End check_theme_updates()


    /**
     * Check: inactive plugins and themes
     *
     * @return array
     */
    public static function check_inactive_software() : array {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $items = [];

        foreach ( get_plugins() as $file => $plugin ) {
            if ( ! is_plugin_active( $file ) ) {
                /* translators: %s: plugin name */
                $items[] = sprintf( __( 'Plugin: %s', 'dev-debug-tools' ), $plugin[ 'Name' ] );
            }
        }

        if ( ! is_multisite() ) {
            $keep = array_filter( [ get_stylesheet(), get_template(), defined( 'WP_DEFAULT_THEME' ) ? WP_DEFAULT_THEME : '' ] );
            foreach ( wp_get_themes() as $slug => $theme ) {
                if ( ! in_array( $slug, $keep, true ) ) {
                    /* translators: %s: theme name */
                    $items[] = sprintf( __( 'Theme: %s', 'dev-debug-tools' ), $theme->get( 'Name' ) );
                }
            }
        }

        if ( empty( $items ) ) {
            return self::result( 'pass', __( 'No inactive plugins or themes are installed.', 'dev-debug-tools' ) );
        }

        $message = is_multisite() ? __( 'These plugins are not active on this site. Some may be active on other sites in the network.', 'dev-debug-tools' ) : __( 'These are installed but not active:', 'dev-debug-tools' );

        return self::result( 'fail', $message, $items );
    } // End check_inactive_software()


    /**
     * Check: WordPress core file integrity
     *
     * @return array
     */
    public static function check_core_integrity() : array {
        global $wp_version;

        if ( ! function_exists( 'get_core_checksums' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        $checksums = get_core_checksums( $wp_version, get_locale() );
        if ( ! is_array( $checksums ) || empty( $checksums ) ) {
            /* translators: %s: WordPress version */
            return self::result( 'unknown', sprintf( __( 'Checksums for WordPress %s could not be downloaded from api.wordpress.org. Development and beta versions do not have them.', 'dev-debug-tools' ), $wp_version ) );
        }

        $modified = [];
        $missing = [];

        foreach ( $checksums as $file => $hash ) {
            if ( str_starts_with( $file, 'wp-content/' ) ) {
                continue;
            }

            $path = ABSPATH . $file;
            if ( ! file_exists( $path ) ) {
                /* translators: %s: file path */
                $missing[] = sprintf( __( 'Missing: %s', 'dev-debug-tools' ), $file );
            } elseif ( md5_file( $path ) !== $hash ) {
                /* translators: %s: file path */
                $modified[] = sprintf( __( 'Modified: %s', 'dev-debug-tools' ), $file );
            }
        }

        $unexpected = [];
        foreach ( [ 'wp-admin', WPINC ] as $dir ) {
            foreach ( self::find_files( ABSPATH . $dir, '/\.php$/i' )[ 'files' ] as $path ) {
                $relative = self::relative_path( $path );
                if ( ! isset( $checksums[ $relative ] ) ) {
                    /* translators: %s: file path */
                    $unexpected[] = sprintf( __( 'Unexpected: %s', 'dev-debug-tools' ), $relative );
                }
            }
        }

        if ( ! empty( $modified ) || ! empty( $unexpected ) ) {
            return self::result( 'fail', __( 'Core files do not match the official release:', 'dev-debug-tools' ), array_merge( $modified, $unexpected, $missing ) );
        }

        if ( ! empty( $missing ) ) {
            return self::result( 'warning', __( 'Some core files are missing. This can break features, but is not a sign of malware on its own.', 'dev-debug-tools' ), $missing, 'medium' );
        }

        /* translators: 1: number of files, 2: WordPress version */
        return self::result( 'pass', sprintf( __( 'All %1$s core files match WordPress %2$s.', 'dev-debug-tools' ), number_format_i18n( count( $checksums ) ), $wp_version ) );
    } // End check_core_integrity()


    /**
     * Check: PHP files in uploads
     *
     * @return array
     */
    public static function check_php_in_uploads() : array {
        $uploads = wp_upload_dir( null, false );
        if ( ! is_dir( $uploads[ 'basedir' ] ) ) {
            return self::result( 'pass', __( 'The uploads folder does not exist yet.', 'dev-debug-tools' ) );
        }

        $scan = self::find_files( $uploads[ 'basedir' ], '/\.(php\d?|phtml|phar|phps|pht)$/i', microtime( true ) + self::SCAN_SECONDS );

        $found = [];
        foreach ( $scan[ 'files' ] as $path ) {
            if ( basename( $path ) === 'index.php' && filesize( $path ) <= 200 ) {
                continue;
            }

            $found[] = self::relative_path( $path );
        }

        if ( ! empty( $found ) ) {
            return self::result( 'fail', __( 'PHP files were found in the uploads folder:', 'dev-debug-tools' ), $found );
        }

        if ( $scan[ 'stopped' ] ) {
            /* translators: %d: seconds */
            return self::result( 'unknown', sprintf( __( 'The uploads folder could not be fully scanned in %d seconds. No PHP files were found in the part that was scanned.', 'dev-debug-tools' ), self::SCAN_SECONDS ) );
        }

        return self::result( 'pass', __( 'No PHP files are in the uploads folder.', 'dev-debug-tools' ) );
    } // End check_php_in_uploads()


    /**
     * Check: REST routes that accept changes without a permission check
     *
     * @return array
     */
    public static function check_rest_write_public() : array {
        $skip = self::public_rest_skip();

        $items = [];
        foreach ( rest_get_server()->get_routes() as $route => $endpoints ) {
            if ( in_array( $route, $skip, true ) ) {
                continue;
            }

            foreach ( $endpoints as $endpoint ) {
                $methods = array_keys( array_filter( (array) ( $endpoint[ 'methods' ] ?? [] ) ) );
                $write = array_diff( $methods, [ 'GET', 'HEAD', 'OPTIONS' ] );
                if ( empty( $write ) ) {
                    continue;
                }

                $permission = $endpoint[ 'permission_callback' ] ?? null;
                if ( $permission === null || $permission === '__return_true' ) {
                    $items[] = implode( ', ', $write ) . ' ' . $route;
                }
            }
        }

        if ( ! empty( $items ) ) {
            /* translators: %d: number of routes */
            return self::result( 'fail', sprintf( _n( '%d route accepts changes from anyone. Review its code to confirm that is intended.', '%d routes accept changes from anyone. Review their code to confirm that is intended.', count( $items ), 'dev-debug-tools' ), count( $items ) ), $items );
        }

        return self::result( 'pass', __( 'Every route that accepts changes has a permission check. The checks themselves were not reviewed.', 'dev-debug-tools' ) );
    } // End check_rest_write_public()


    /**
     * Check: AJAX actions open to logged-out visitors
     *
     * @return array
     */
    public static function check_ajax_nopriv() : array {
        $items = self::public_hook_callbacks( 'wp_ajax_nopriv_' );

        if ( empty( $items ) ) {
            return self::result( 'pass', __( 'No plugin or theme adds AJAX actions for logged-out visitors.', 'dev-debug-tools' ) );
        }

        return self::result( 'info', __( 'These AJAX actions run for logged-out visitors. Actions a plugin only adds when nobody is logged in are not listed.', 'dev-debug-tools' ), $items );
    } // End check_ajax_nopriv()


    /**
     * The scheme, host and port of the site, without a path
     *
     * @return string
     */
    private static function site_origin() : string {
        $parts = wp_parse_url( home_url() );
        return ( $parts[ 'scheme' ] ?? 'http' ) . '://' . ( $parts[ 'host' ] ?? '' ) . ( isset( $parts[ 'port' ] ) ? ':' . $parts[ 'port' ] : '' );
    } // End site_origin()


    /**
     * Build a result from a list of support end dates
     *
     * @param string $name    Software name, such as "PHP".
     * @param string $version Full installed version.
     * @param string $branch  Major and minor version, such as "8.3".
     * @param array  $eol     [ branch => Y-m-d ]
     * @return array
     */
    private static function support_result( string $name, string $version, string $branch, array $eol ) : array {
        $label = $name . ' ' . $version;
        $branches = array_map( 'strval', array_keys( $eol ) );
        usort( $branches, 'version_compare' );

        if ( ! isset( $eol[ $branch ] ) ) {
            if ( ! empty( $branches ) && version_compare( $branch, $branches[ 0 ], '<' ) ) {
                /* translators: %s: software name and version */
                return self::result( 'fail', sprintf( __( '%s no longer receives security fixes.', 'dev-debug-tools' ), $label ) );
            }

            if ( ! empty( $branches ) && version_compare( $branch, end( $branches ), '>' ) ) {
                /* translators: %s: software name and version */
                return self::result( 'pass', sprintf( __( '%s is newer than the support dates this check knows about.', 'dev-debug-tools' ), $label ) );
            }

            /* translators: %s: software name and version */
            return self::result( 'unknown', sprintf( __( '%s is not in the list of support dates this check knows about.', 'dev-debug-tools' ), $label ) );
        }

        $end = strtotime( $eol[ $branch ] . ' 23:59:59 UTC' );
        $end_date = wp_date( get_option( 'date_format' ), $end );

        if ( time() > $end ) {
            /* translators: 1: software name and version, 2: date */
            return self::result( 'fail', sprintf( __( '%1$s stopped receiving security fixes on %2$s.', 'dev-debug-tools' ), $label, $end_date ) );
        }

        if ( $end - time() < 90 * DAY_IN_SECONDS ) {
            /* translators: 1: software name and version, 2: date */
            return self::result( 'warning', sprintf( __( '%1$s stops receiving security fixes on %2$s. Plan the upgrade now.', 'dev-debug-tools' ), $label, $end_date ), [], 'medium' );
        }

        /* translators: 1: software name and version, 2: date */
        return self::result( 'pass', sprintf( __( '%1$s receives security fixes until %2$s.', 'dev-debug-tools' ), $label, $end_date ) );
    } // End support_result()


    /**
     * REST routes that are public by design and should not be reported
     *
     * @return array
     */
    private static function public_rest_skip() : array {
        /**
         * Filter REST routes that are public by design and should not be reported.
         */
        return (array) apply_filters( 'ddtt_security_public_rest_routes', [ '/batch/v1', '/llar/v1/mfa/send-code' ] );
    } // End public_rest_skip()


    /**
     * Non-core callbacks on hooks that run for logged-out visitors
     *
     * @param string $prefix Hook prefix, such as "wp_ajax_nopriv_".
     * @return array "action (source)" items, sorted.
     */
    private static function public_hook_callbacks( string $prefix ) : array {
        global $wp_filter;

        $ignore = [ '__return_false', '__return_null', '__return_zero', '__return_empty_string', '__return_empty_array' ];
        $base = rtrim( $prefix, '_' );
        $items = [];

        foreach ( $wp_filter as $hook => $filter ) {
            if ( ( $hook !== $base && ! str_starts_with( $hook, $prefix ) ) || ! $filter instanceof \WP_Hook ) {
                continue;
            }

            $action = $hook === $base ? __( '(any action)', 'dev-debug-tools' ) : substr( $hook, strlen( $prefix ) );

            foreach ( $filter->callbacks as $callbacks ) {
                foreach ( $callbacks as $callback ) {
                    if ( is_string( $callback[ 'function' ] ) && in_array( $callback[ 'function' ], $ignore, true ) ) {
                        continue;
                    }

                    $source = self::callback_source( $callback[ 'function' ] );
                    if ( $source !== 'core' ) {
                        $items[] = sprintf( '%1$s (%2$s)', $action, $source );
                    }
                }
            }
        }

        sort( $items );

        return $items;
    } // End public_hook_callbacks()


    /**
     * Where the callbacks on a hook are defined, leaving out core
     *
     * @param string $hook
     * @return array
     */
    private static function hook_sources( string $hook ) : array {
        global $wp_filter;

        if ( ! isset( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof \WP_Hook ) {
            return [];
        }

        $sources = [];
        foreach ( $wp_filter[ $hook ]->callbacks as $callbacks ) {
            foreach ( $callbacks as $callback ) {
                $function = $callback[ 'function' ];
                $source = self::callback_source( $function );
                if ( $source !== 'core' ) {
                    $sources[] = $hook . ': ' . $source;
                } elseif ( is_string( $function ) && str_starts_with( $function, '__return_' ) ) {
                    /* translators: 1: hook name, 2: function name */
                    $sources[] = sprintf( __( '%1$s: %2$s (added by a plugin, theme or custom code)', 'dev-debug-tools' ), $hook, $function );
                }
            }
        }

        return $sources;
    } // End hook_sources()


    /**
     * Plugin or theme information from api.wordpress.org, cached for a day
     *
     * @param string $type plugin|theme
     * @param string $slug
     * @return array|null Selected fields, or null when the request failed.
     */
    private static function wporg_info( string $type, string $slug ) : ?array {
        $cache = get_transient( 'ddtt_security_wporg' );
        $cache = is_array( $cache ) ? $cache : [];
        $key = $type . ':' . $slug;

        if ( isset( $cache[ $key ][ 'time' ] ) && $cache[ $key ][ 'time' ] > time() - DAY_IN_SECONDS ) {
            return $cache[ $key ][ 'data' ];
        }

        $url = add_query_arg( [
            'action'  => $type . '_information',
            'request' => [
                'slug'   => $slug,
                'fields' => [ 'sections' => false, 'versions' => false, 'screenshots' => false, 'reviews' => false, 'banners' => false, 'icons' => false, 'tags' => false, 'contributors' => false ],
            ],
        ], 'https://api.wordpress.org/' . $type . 's/info/1.2/' );

        $response = wp_safe_remote_get( $url, [ 'timeout' => self::TIMEOUT ] );
        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) ) {
            return null;
        }

        $data = array_intersect_key( $body, array_flip( [ 'name', 'error', 'closed', 'closed_date', 'reason_text', 'last_updated' ] ) );

        $cache[ $key ] = [ 'time' => time(), 'data' => $data ];
        set_transient( 'ddtt_security_wporg', $cache, DAY_IN_SECONDS );

        return $data;
    } // End wporg_info()


    /**
     * Official file checksums for a plugin version from downloads.wordpress.org
     *
     * @param string $slug
     * @param string $version
     * @return array|null [ relative path => [ sha256 hashes ] ], an empty array when the
     *                    version has no checksums, or null when the request failed.
     */
    private static function plugin_checksums( string $slug, string $version ) : ?array {
        $url = 'https://downloads.wordpress.org/plugin-checksums/' . rawurlencode( $slug ) . '/' . rawurlencode( $version ) . '.json';
        $response = wp_safe_remote_get( $url, [ 'timeout' => self::TIMEOUT ] );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( $code === 404 ) {
            return [];
        }

        $body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
        if ( $code !== 200 || ! is_array( $body ) || ! isset( $body[ 'files' ] ) || ! is_array( $body[ 'files' ] ) ) {
            return null;
        }

        $checksums = [];
        foreach ( $body[ 'files' ] as $file => $hashes ) {
            if ( is_array( $hashes ) && isset( $hashes[ 'sha256' ] ) ) {
                $checksums[ (string) $file ] = array_map( 'strtolower', array_map( 'strval', (array) $hashes[ 'sha256' ] ) );
            }
        }

        return $checksums;
    } // End plugin_checksums()


    /**
     * A fingerprint of a folder's file names, sizes and modified times
     *
     * @param string $dir
     * @return string
     */
    private static function folder_signature( string $dir ) : string {
        $parts = [];

        try {
            $iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::LEAVES_ONLY, \RecursiveIteratorIterator::CATCH_GET_CHILD );
            foreach ( $iterator as $file ) {
                if ( $file->isFile() ) {
                    $parts[] = $file->getPathname() . '|' . $file->getSize() . '|' . $file->getMTime();
                }
            }
        } catch ( \UnexpectedValueException $e ) {
            return '';
        }

        sort( $parts );

        return md5( implode( "\n", $parts ) );
    } // End folder_signature()


    /**
     * Connect to a host over TLS and describe its certificate
     *
     * @param string $host
     * @param int    $port
     * @return array|null [ expires, names, name_match, trusted, issuer ], or null when no connection could be made.
     */
    private static function tls_certificate( string $host, int $port ) : ?array {
        $connect = function( bool $verify ) use ( $host, $port ) {
            $context = stream_context_create( [
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer'       => $verify,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => ! $verify,
                    'SNI_enabled'       => true,
                    'peer_name'         => $host,
                    'cafile'            => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
                ],
            ] );

            $stream = @stream_socket_client( 'ssl://' . $host . ':' . $port, $errno, $errstr, self::TIMEOUT, STREAM_CLIENT_CONNECT, $context ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            if ( ! $stream ) {
                return null;
            }

            $params = stream_context_get_params( $stream );
            fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

            return $params[ 'options' ][ 'ssl' ][ 'peer_certificate' ] ?? null;
        };

        $certificate = $connect( false );
        if ( ! $certificate ) {
            return null;
        }

        $parsed = openssl_x509_parse( $certificate );
        if ( ! is_array( $parsed ) ) {
            return null;
        }

        $names = [];
        if ( ! empty( $parsed[ 'extensions' ][ 'subjectAltName' ] ) ) {
            foreach ( explode( ',', $parsed[ 'extensions' ][ 'subjectAltName' ] ) as $entry ) {
                $entry = trim( $entry );
                if ( stripos( $entry, 'DNS:' ) === 0 ) {
                    $names[] = strtolower( substr( $entry, 4 ) );
                }
            }
        }

        if ( empty( $names ) && ! empty( $parsed[ 'subject' ][ 'CN' ] ) ) {
            $names[] = strtolower( (string) $parsed[ 'subject' ][ 'CN' ] );
        }

        $host = strtolower( $host );
        $name_match = false;
        foreach ( $names as $name ) {
            if ( $name === $host || ( str_starts_with( $name, '*.' ) && substr_count( $host, '.' ) === substr_count( $name, '.' ) && str_ends_with( $host, substr( $name, 1 ) ) ) ) {
                $name_match = true;
                break;
            }
        }

        return [
            'expires'    => (int) ( $parsed[ 'validTo_time_t' ] ?? 0 ),
            'names'      => $names,
            'name_match' => $name_match,
            'trusted'    => (bool) $connect( true ),
            'issuer'     => (string) ( $parsed[ 'issuer' ][ 'O' ] ?? $parsed[ 'issuer' ][ 'CN' ] ?? '' ),
        ];
    } // End tls_certificate()


    /**
     * TXT records for a domain
     *
     * @param string $domain
     * @return array|null Record values, or null when the lookup failed.
     */
    private static function txt_records( string $domain ) : ?array {
        $records = @dns_get_record( $domain, DNS_TXT ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        if ( $records === false ) {
            return null;
        }

        $values = [];
        foreach ( (array) $records as $record ) {
            $values[] = isset( $record[ 'entries' ] ) ? implode( '', (array) $record[ 'entries' ] ) : (string) ( $record[ 'txt' ] ?? '' );
        }

        return $values;
    } // End txt_records()


    /**
     * Whether DNS lookups from this server return records, tested with a domain that must resolve
     *
     * Some servers return no records instead of an error when DNS is blocked, so an empty
     * answer is only trusted when this returns true.
     *
     * @param string $host
     * @return bool
     */
    private static function dns_works( string $host ) : bool {
        if ( $host === '' || filter_var( $host, FILTER_VALIDATE_IP ) || ! str_contains( $host, '.' ) ) {
            return false;
        }

        return ! empty( @dns_get_record( $host, DNS_A ) ) || ! empty( @dns_get_record( $host, DNS_AAAA ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
    } // End dns_works()


    /**
     * Check: development files exposed
     *
     * @return array
     */
    public static function check_exposed_dev_files() : array {
        $json_keys = '/"(require|require-dev|autoload|dependencies|devDependencies|scripts)"\s*:/';
        $credentials = '/"(host|user|username|password|pass)"\s*:/i';

        $files = [
            'composer.json'       => $json_keys,
            'composer.lock'       => '/"content-hash"\s*:/',
            'package.json'        => $json_keys,
            'package-lock.json'   => '/"lockfileVersion"\s*:/',
            'yarn.lock'           => '/^# yarn lockfile|^__metadata:/m',
            'phpunit.xml'         => '/<phpunit\b/i',
            'phpunit.xml.dist'    => '/<phpunit\b/i',
            'Dockerfile'          => '/^\s*FROM\s+\S+/mi',
            'docker-compose.yml'  => '/^\s*services\s*:/m',
            '.idea/workspace.xml' => '/<project\s+version=/i',
            '.vscode/sftp.json'   => $credentials,
            'sftp-config.json'    => $credentials,
            '.ftpconfig'          => $credentials,
        ];

        $targets = [];
        foreach ( $files as $file => $signature ) {
            $targets[ site_url( '/' . $file ) ] = $signature;
        }

        return self::probe_result( $targets, __( 'Development files can be downloaded by anyone:', 'dev-debug-tools' ), __( 'No dependency lists, build files or editor deployment files were found at common file names.', 'dev-debug-tools' ) );
    } // End check_exposed_dev_files()


    /**
     * Check: migration installer files left in the web root
     *
     * @return array
     */
    public static function check_exposed_installers() : array {
        $duplicator = '/Duplicator|DUPX|dup-installer/i';
        $search_replace = '/Search[ -]Replace[ -]DB|interconnect\/it/i';
        $root = trailingslashit( wp_normalize_path( ABSPATH ) );

        $files = [
            'installer.php'                      => $duplicator,
            'installer-backup.php'               => $duplicator,
            'dup-installer/main.installer.php'   => $duplicator,
            'importbuddy.php'                    => '/ImportBuddy|BackupBuddy/i',
            'searchreplacedb2.php'               => $search_replace,
            'srdb.php'                           => $search_replace,
            'Search-Replace-DB-master/index.php' => $search_replace,
            'Search-Replace-DB/index.php'        => $search_replace,
        ];

        $found = [];
        foreach ( $files as $file => $signature ) {
            if ( is_file( $root . $file ) && preg_match( $signature, (string) file_get_contents( $root . $file, false, null, 0, 65536 ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                $found[] = $file;
            }
        }

        foreach ( [ '*_archive.zip', '*_archive.daf' ] as $pattern ) {
            foreach ( (array) glob( $root . $pattern ) as $path ) {
                if ( $path ) {
                    $found[] = self::relative_path( $path );
                }
            }
        }

        if ( ! empty( $found ) ) {
            return self::result( 'fail', __( 'Migration files were left in the main WordPress folder:', 'dev-debug-tools' ), $found );
        }

        return self::result( 'pass', __( 'No migration installers, archives or database replacement scripts were found in the main WordPress folder.', 'dev-debug-tools' ) );
    } // End check_exposed_installers()


    /**
     * Check: TLS certificate
     *
     * @return array
     */
    public static function check_tls_certificate() : array {
        if ( wp_parse_url( home_url(), PHP_URL_SCHEME ) !== 'https' ) {
            return self::result( 'unknown', __( 'The site address does not use https://, so there is no certificate to check. See the HTTPS check.', 'dev-debug-tools' ) );
        }

        if ( ! function_exists( 'openssl_x509_parse' ) || ! function_exists( 'stream_socket_client' ) ) {
            return self::result( 'unknown', __( 'The PHP OpenSSL extension is not available, so the certificate could not be read.', 'dev-debug-tools' ) );
        }

        $host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        $port = (int) ( wp_parse_url( home_url(), PHP_URL_PORT ) ?: 443 );
        $certificate = self::tls_certificate( $host, $port );

        if ( ! $certificate ) {
            /* translators: 1: host name, 2: port */
            return self::result( 'unknown', sprintf( __( 'This server could not open a secure connection to %1$s on port %2$d. Some hosts block these loopback connections.', 'dev-debug-tools' ), $host, $port ) );
        }

        $expires = $certificate[ 'expires' ];
        $expires_date = wp_date( get_option( 'date_format' ), $expires );
        $fails = [];
        $warnings = [];

        if ( $expires && $expires < time() ) {
            /* translators: %s: date */
            $fails[] = sprintf( __( 'Expired on %s', 'dev-debug-tools' ), $expires_date );
        } elseif ( $expires && $expires - time() < 14 * DAY_IN_SECONDS ) {
            /* translators: 1: time until expiry, 2: date */
            $warnings[] = sprintf( __( 'Expires in %1$s (%2$s)', 'dev-debug-tools' ), human_time_diff( $expires ), $expires_date );
        }

        if ( ! $certificate[ 'name_match' ] ) {
            /* translators: 1: host name, 2: names on the certificate */
            $fails[] = sprintf( __( 'Issued for %2$s, not %1$s', 'dev-debug-tools' ), $host, implode( ', ', $certificate[ 'names' ] ) ?: __( 'no names', 'dev-debug-tools' ) );
        }

        if ( ! $certificate[ 'trusted' ] ) {
            /* translators: %s: certificate issuer */
            $fails[] = sprintf( __( 'Not trusted by browsers (issued by %s)', 'dev-debug-tools' ), $certificate[ 'issuer' ] ?: __( 'unknown', 'dev-debug-tools' ) );
        }

        if ( ! empty( $fails ) ) {
            return self::result( 'fail', __( 'Browsers will show a security warning for this site:', 'dev-debug-tools' ), array_merge( $fails, $warnings ) );
        }

        if ( ! empty( $warnings ) ) {
            return self::result( 'warning', __( 'The certificate expires soon. Confirm it renews automatically.', 'dev-debug-tools' ), $warnings );
        }

        /* translators: 1: certificate issuer, 2: date */
        return self::result( 'pass', sprintf( __( 'The certificate from %1$s is valid until %2$s.', 'dev-debug-tools' ), $certificate[ 'issuer' ] ?: __( 'an unknown issuer', 'dev-debug-tools' ), $expires_date ) );
    } // End check_tls_certificate()


    /**
     * Check: cross-origin requests
     *
     * @return array
     */
    public static function check_cors() : array {
        $unreachable = self::unreachable_reason();
        if ( $unreachable ) {
            return self::result( 'unknown', $unreachable );
        }

        $origin = 'https://ddtt-cors-check.invalid';
        $fails = [];
        $warnings = [];

        foreach ( [ home_url( '/' ), admin_url( 'admin-ajax.php' ) ] as $url ) {
            $response = self::request( $url, [ 'headers' => [ 'Origin' => $origin, 'Cache-Control' => 'no-cache' ] ] );
            if ( $response[ 'error' ] || self::header_value( $response[ 'headers' ], 'access-control-allow-origin' ) !== $origin ) {
                continue;
            }

            $path = (string) wp_parse_url( $url, PHP_URL_PATH );
            if ( strtolower( self::header_value( $response[ 'headers' ], 'access-control-allow-credentials' ) ) === 'true' ) {
                $fails[] = $path;
            } else {
                $warnings[] = $path;
            }
        }

        if ( ! empty( $fails ) ) {
            return self::result( 'fail', __( 'These pages trust requests from any website and allow cookies:', 'dev-debug-tools' ), array_merge( $fails, $warnings ) );
        }

        if ( ! empty( $warnings ) ) {
            return self::result( 'warning', __( 'These pages let any website read them. Cookies are not allowed, so the risk is low.', 'dev-debug-tools' ), $warnings, 'low' );
        }

        return self::result( 'pass', __( 'The home page and admin-ajax.php do not trust requests from other websites.', 'dev-debug-tools' ) );
    } // End check_cors()


    /**
     * Check: TRACE method
     *
     * @return array
     */
    public static function check_http_trace() : array {
        $unreachable = self::unreachable_reason();
        if ( $unreachable ) {
            return self::result( 'unknown', $unreachable );
        }

        $token = wp_generate_password( 20, false );
        $response = self::request( home_url( '/' ), [ 'method' => 'TRACE', 'headers' => [ 'X-DDTT-Trace' => $token ] ] );

        if ( $response[ 'error' ] ) {
            return self::result( 'pass', __( 'The server refused the TRACE request.', 'dev-debug-tools' ) );
        }

        if ( $response[ 'code' ] === 200 && str_contains( $response[ 'body' ], $token ) ) {
            return self::result( 'fail', __( 'The server echoes TRACE requests back, including their headers.', 'dev-debug-tools' ) );
        }

        /* translators: %d: HTTP status code */
        return self::result( 'pass', sprintf( __( 'The server does not echo TRACE requests (HTTP %d).', 'dev-debug-tools' ), $response[ 'code' ] ) );
    } // End check_http_trace()


    /**
     * Check: remote file inclusion
     *
     * @return array
     */
    public static function check_remote_file_inclusion() : array {
        if ( filter_var( ini_get( 'allow_url_include' ), FILTER_VALIDATE_BOOLEAN ) ) {
            return self::result( 'fail', __( 'allow_url_include is on, so PHP can include code from other websites.', 'dev-debug-tools' ) );
        }

        return self::result( 'pass', __( 'allow_url_include is off.', 'dev-debug-tools' ) );
    } // End check_remote_file_inclusion()


    /**
     * Check: database version
     *
     * @return array
     */
    public static function check_db_version() : array {
        global $wpdb;

        if ( ( defined( 'DB_ENGINE' ) && DB_ENGINE === 'sqlite' ) || ! method_exists( $wpdb, 'db_server_info' ) ) {
            return self::result( 'unknown', __( 'This site does not use MySQL or MariaDB, so the database version was not checked.', 'dev-debug-tools' ) );
        }

        $info = (string) $wpdb->db_server_info();
        $is_mariadb = stripos( $info, 'mariadb' ) !== false;
        $info = preg_replace( '/^5\.5\.5-/', '', $info );

        if ( ! preg_match( '/^(\d+)\.(\d+)(?:\.\d+)?/', $info, $matches ) ) {
            /* translators: %s: database server version string */
            return self::result( 'unknown', sprintf( __( 'The database version "%s" could not be read.', 'dev-debug-tools' ), $info ) );
        }

        $branch = $matches[ 1 ] . '.' . $matches[ 2 ];

        if ( $is_mariadb ) {
            /**
             * Filter the MariaDB end-of-life dates (community support end, from endoflife.date).
             */
            $eol = apply_filters( 'ddtt_security_mariadb_eol', [
                '10.4'  => '2024-06-18',
                '10.5'  => '2025-06-24',
                '10.6'  => '2026-07-06',
                '10.7'  => '2023-02-09',
                '10.8'  => '2023-05-20',
                '10.9'  => '2023-08-22',
                '10.10' => '2023-11-17',
                '10.11' => '2028-02-16',
                '11.0'  => '2024-06-06',
                '11.1'  => '2024-08-21',
                '11.2'  => '2024-11-21',
                '11.3'  => '2024-05-29',
                '11.4'  => '2029-05-29',
                '11.5'  => '2024-11-21',
                '11.6'  => '2025-02-13',
                '11.7'  => '2025-05-12',
                '11.8'  => '2028-06-04',
                '12.0'  => '2025-11-18',
                '12.1'  => '2026-02-13',
                '12.2'  => '2026-05-28',
                '12.3'  => '2029-06-12',
                '13.0'  => '2026-12-31',
            ] );

            return self::support_result( 'MariaDB', $matches[ 0 ], $branch, (array) $eol );
        }


        /**
         * Filter the MySQL end-of-life dates (from endoflife.date).
         */
        $eol = apply_filters( 'ddtt_security_mysql_eol', [
            '5.7'  => '2023-10-31',
            '8.0'  => '2026-04-30',
            '8.1'  => '2023-10-25',
            '8.2'  => '2024-01-16',
            '8.3'  => '2024-04-30',
            '8.4'  => '2032-04-30',
            '9.0'  => '2024-10-15',
            '9.1'  => '2025-01-21',
            '9.2'  => '2025-04-15',
            '9.3'  => '2025-07-22',
            '9.4'  => '2025-10-21',
            '9.5'  => '2026-01-20',
            '9.6'  => '2026-04-21',
            '9.7'  => '2034-04-30',
            '26.7' => '2026-10-20',
        ] );

        return self::support_result( 'MySQL', $matches[ 0 ], $branch, (array) $eol );
    } // End check_db_version()


    /**
     * Check: risky upload types
     *
     * @return array
     */
    public static function check_upload_types() : array {
        $dangerous = [ 'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps', 'pht', 'exe', 'com', 'bat', 'cmd', 'sh', 'cgi', 'pl', 'py', 'asp', 'aspx', 'jsp', 'htm', 'html', 'xhtml', 'shtml', 'js', 'mjs', 'swf' ];
        $scriptable = [ 'svg', 'svgz' ];
        $fails = [];
        $warnings = [];

        foreach ( get_allowed_mime_types( new \WP_User( 0 ) ) as $extensions => $mime ) {
            foreach ( explode( '|', strtolower( $extensions ) ) as $extension ) {
                $item = '.' . $extension . ' (' . $mime . ')';
                if ( in_array( $extension, $dangerous, true ) ) {
                    $fails[] = $item;
                } elseif ( in_array( $extension, $scriptable, true ) ) {
                    $warnings[] = $item;
                }
            }
        }

        if ( ! empty( $fails ) ) {
            return self::result( 'fail', __( 'Users without the unfiltered_html capability can upload files that run code or scripts:', 'dev-debug-tools' ), array_merge( $fails, $warnings ) );
        }

        if ( ! empty( $warnings ) ) {
            return self::result( 'warning', __( 'SVG uploads are allowed. This is safe only if a plugin sanitizes each file on upload.', 'dev-debug-tools' ), $warnings );
        }

        return self::result( 'pass', __( 'Only the standard media and document types can be uploaded.', 'dev-debug-tools' ) );
    } // End check_upload_types()


    /**
     * Check: admin area forced to HTTPS
     *
     * @return array
     */
    public static function check_force_ssl_admin() : array {
        if ( wp_parse_url( home_url(), PHP_URL_SCHEME ) !== 'https' ) {
            return self::result( 'unknown', __( 'The site address does not use https://, so this was not checked. See the HTTPS check.', 'dev-debug-tools' ) );
        }

        if ( force_ssl_admin() ) {
            return self::result( 'pass', __( 'FORCE_SSL_ADMIN is on.', 'dev-debug-tools' ) );
        }

        return self::result( 'fail', __( 'FORCE_SSL_ADMIN is not set, so the login and admin pages can be opened over http://.', 'dev-debug-tools' ) );
    } // End check_force_ssl_admin()


    /**
     * Check: admin email address
     *
     * @return array
     */
    public static function check_admin_email() : array {
        $emails = [ __( 'Administration email', 'dev-debug-tools' ) => (string) get_option( 'admin_email' ) ];
        if ( is_multisite() ) {
            $emails[ __( 'Network admin email', 'dev-debug-tools' ) ] = (string) get_site_option( 'admin_email' );
        }

        $placeholders = [ 'example.com', 'example.org', 'example.net', 'localhost', 'localhost.localdomain', 'test.com', 'domain.com', 'email.com', 'yourdomain.com', 'mailinator.com' ];
        $dns_works = null;
        $fails = [];
        $warnings = [];

        foreach ( $emails as $label => $email ) {
            if ( ! is_email( $email ) ) {
                /* translators: 1: setting name, 2: email address */
                $fails[] = sprintf( __( '%1$s: "%2$s" is not a valid address', 'dev-debug-tools' ), $label, $email );
                continue;
            }

            $domain = strtolower( substr( (string) strrchr( $email, '@' ), 1 ) );
            if ( in_array( $domain, $placeholders, true ) || preg_match( '/\.(test|example|invalid|localhost|local)$/', $domain ) ) {
                /* translators: 1: setting name, 2: email address */
                $fails[] = sprintf( __( '%1$s: %2$s is a placeholder address', 'dev-debug-tools' ), $label, $email );
                continue;
            }

            if ( function_exists( 'dns_get_record' ) ) {
                $records = @dns_get_record( $domain, DNS_MX | DNS_A ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                $dns_works ??= self::dns_works( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
                if ( $records === [] && $dns_works ) {
                    /* translators: 1: setting name, 2: email address */
                    $warnings[] = sprintf( __( '%1$s: the domain of %2$s has no mail server or address record', 'dev-debug-tools' ), $label, $email );
                }
            }
        }

        if ( ! empty( $fails ) ) {
            return self::result( 'fail', __( 'Security notices from WordPress will not reach anyone:', 'dev-debug-tools' ), array_merge( $fails, $warnings ) );
        }

        if ( ! empty( $warnings ) ) {
            return self::result( 'warning', __( 'This address may not be able to receive email:', 'dev-debug-tools' ), $warnings );
        }

        return self::result( 'pass', __( 'The admin email address is valid and is not a placeholder. Confirm someone reads it.', 'dev-debug-tools' ) );
    } // End check_admin_email()


    /**
     * Check: SPF and DMARC records
     *
     * @return array
     */
    public static function check_email_auth() : array {
        if ( ! function_exists( 'dns_get_record' ) ) {
            return self::result( 'unknown', __( 'DNS lookups are not available on this server, so the records could not be checked.', 'dev-debug-tools' ) );
        }

        $host = strtolower( (string) preg_replace( '/^www\./i', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
        if ( filter_var( $host, FILTER_VALIDATE_IP ) || ! str_contains( $host, '.' ) ) {
            return self::result( 'unknown', __( 'The site address is not a domain name, so there are no email records to check.', 'dev-debug-tools' ) );
        }

        $spf = self::txt_records( $host );
        if ( $spf === null || ( empty( $spf ) && ! self::dns_works( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) ) {
            /* translators: %s: domain */
            return self::result( 'unknown', sprintf( __( 'The DNS lookup for %s failed, so the records could not be checked.', 'dev-debug-tools' ), $host ) );
        }

        $has_spf = ! empty( preg_grep( '/^v=spf1\b/i', $spf ) );

        $dmarc = '';
        $labels = explode( '.', $host );
        while ( count( $labels ) >= 2 && $dmarc === '' ) {
            $records = self::txt_records( '_dmarc.' . implode( '.', $labels ) );
            $match = $records ? preg_grep( '/^v=DMARC1\b/i', $records ) : [];
            $dmarc = $match ? (string) reset( $match ) : '';
            array_shift( $labels );
        }

        $items = [];
        if ( ! $has_spf ) {
            /* translators: %s: domain */
            $items[] = sprintf( __( 'No SPF record on %s', 'dev-debug-tools' ), $host );
        }

        if ( $dmarc === '' ) {
            /* translators: %s: domain */
            $items[] = sprintf( __( 'No DMARC record for %s', 'dev-debug-tools' ), $host );
        } elseif ( preg_match( '/;\s*p\s*=\s*none\b/i', $dmarc ) ) {
            $items[] = __( 'The DMARC policy is p=none, which reports spoofed email but does not block it', 'dev-debug-tools' );
        }

        if ( ! $has_spf && $dmarc === '' ) {
            return self::result( 'fail', __( 'Anyone can send email that appears to come from this domain:', 'dev-debug-tools' ), $items );
        }

        if ( ! empty( $items ) ) {
            return self::result( 'warning', __( 'Email authentication is incomplete:', 'dev-debug-tools' ), $items );
        }

        /* translators: %s: domain */
        return self::result( 'pass', sprintf( __( '%s has SPF and DMARC records.', 'dev-debug-tools' ), $host ) );
    } // End check_email_auth()


    /**
     * Check: plugin and theme auto-updates
     *
     * @return array
     */
    public static function check_auto_updates() : array {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if ( ! function_exists( 'wp_is_auto_update_enabled_for_type' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        $items = [];

        if ( wp_is_auto_update_enabled_for_type( 'plugin' ) ) {
            $enabled = (array) get_site_option( 'auto_update_plugins', [] );
            foreach ( get_plugins() as $file => $data ) {
                if ( ! is_plugin_active( $file ) ) {
                    continue;
                }

                $forced = wp_is_auto_update_forced_for_item( 'plugin', null, (object) array_merge( $data, [ 'plugin' => $file, 'slug' => dirname( $file ) ] ) );
                if ( ! ( $forced === null ? in_array( $file, $enabled, true ) : (bool) $forced ) ) {
                    /* translators: %s: plugin name */
                    $items[] = sprintf( __( 'Plugin: %s', 'dev-debug-tools' ), $data[ 'Name' ] );
                }
            }
        } else {
            $items[] = __( 'Plugin auto-updates are turned off for the whole site', 'dev-debug-tools' );
        }

        if ( wp_is_auto_update_enabled_for_type( 'theme' ) ) {
            $enabled = (array) get_site_option( 'auto_update_themes', [] );
            foreach ( array_unique( [ get_stylesheet(), get_template() ] ) as $slug ) {
                $theme = wp_get_theme( $slug );
                $forced = wp_is_auto_update_forced_for_item( 'theme', null, (object) [ 'theme' => $slug ] );
                if ( ! ( $forced === null ? in_array( $slug, $enabled, true ) : (bool) $forced ) ) {
                    /* translators: %s: theme name */
                    $items[] = sprintf( __( 'Theme: %s', 'dev-debug-tools' ), $theme->exists() ? $theme->get( 'Name' ) : $slug );
                }
            }
        } else {
            $items[] = __( 'Theme auto-updates are turned off for the whole site', 'dev-debug-tools' );
        }

        if ( empty( $items ) ) {
            return self::result( 'pass', __( 'Auto-updates are on for every active plugin and theme.', 'dev-debug-tools' ) );
        }

        return self::result( 'info', __( 'Auto-updates are off for these. That is fine if someone updates them regularly.', 'dev-debug-tools' ), $items );
    } // End check_auto_updates()


    /**
     * Check: powerful capabilities outside administrators
     *
     * @return array
     */
    public static function check_role_capabilities() : array {
        global $wpdb;

        $critical = [ 'activate_plugins', 'install_plugins', 'update_plugins', 'delete_plugins', 'edit_plugins', 'install_themes', 'update_themes', 'delete_themes', 'edit_themes', 'edit_files', 'update_core', 'manage_options', 'promote_users', 'unfiltered_upload', 'manage_network', 'manage_sites', 'manage_network_users', 'manage_network_plugins', 'manage_network_themes', 'manage_network_options' ];
        $review = [ 'create_users', 'edit_users', 'delete_users', 'list_users', 'unfiltered_html', 'import', 'export', 'switch_themes', 'edit_theme_options' ];
        $default_allowed = [ 'editor' => [ 'unfiltered_html' ] ];

        $roles = wp_roles()->roles;
        $fails = [];
        $warnings = [];

        foreach ( $roles as $slug => $role ) {
            if ( $slug === 'administrator' ) {
                continue;
            }

            $caps = array_keys( array_filter( (array) ( $role[ 'capabilities' ] ?? [] ) ) );
            $critical_caps = array_intersect( $caps, $critical );
            $review_caps = array_diff( array_intersect( $caps, $review ), $default_allowed[ $slug ] ?? [] );
            $name = translate_user_role( $role[ 'name' ] ?? $slug );

            if ( ! empty( $critical_caps ) ) {
                /* translators: 1: role name, 2: capabilities */
                $fails[] = sprintf( __( 'Role %1$s: %2$s', 'dev-debug-tools' ), $name, implode( ', ', $critical_caps ) );
            }

            if ( ! empty( $review_caps ) ) {
                /* translators: 1: role name, 2: capabilities */
                $warnings[] = sprintf( __( 'Role %1$s: %2$s', 'dev-debug-tools' ), $name, implode( ', ', $review_caps ) );
            }
        }

        $names = array_merge( $critical, $review );
        $likes = implode( ' OR ', array_fill( 0, count( $names ), 'meta_value LIKE %s' ) );
        $values = array_map( function( $cap ) use ( $wpdb ) {
            return '%' . $wpdb->esc_like( '"' . $cap . '";b:1' ) . '%';
        }, $names );

        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND ( {$likes} )", array_merge( [ $wpdb->get_blog_prefix() . 'capabilities' ], $values ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

        foreach ( (array) $rows as $row ) {
            $caps = maybe_unserialize( $row->meta_value );
            if ( ! is_array( $caps ) || ! empty( $caps[ 'administrator' ] ) ) {
                continue;
            }

            $direct = array_keys( array_filter( array_diff_key( $caps, $roles ) ) );
            $critical_caps = array_intersect( $direct, $critical );
            $review_caps = array_intersect( $direct, $review );
            $user = get_userdata( (int) $row->user_id );
            $login = $user ? $user->user_login : '#' . $row->user_id;

            if ( ! empty( $critical_caps ) ) {
                /* translators: 1: username, 2: capabilities */
                $fails[] = sprintf( __( 'User %1$s: %2$s', 'dev-debug-tools' ), $login, implode( ', ', $critical_caps ) );
            }

            if ( ! empty( $review_caps ) ) {
                /* translators: 1: username, 2: capabilities */
                $warnings[] = sprintf( __( 'User %1$s: %2$s', 'dev-debug-tools' ), $login, implode( ', ', $review_caps ) );
            }
        }


        /**
         * Filter how many administrator accounts are allowed before this check warns.
         */
        $max_admins = absint( apply_filters( 'ddtt_security_max_admins', 5 ) );
        $admin_count = count( self::admins() );

        if ( $max_admins && $admin_count > $max_admins ) {
            /* translators: 1: number of administrators, 2: limit */
            $warnings[] = sprintf( __( '%1$d administrator accounts (more than %2$d)', 'dev-debug-tools' ), $admin_count, $max_admins );
        }

        if ( ! empty( $fails ) ) {
            return self::result( 'fail', __( 'Accounts other than administrators can take over the site:', 'dev-debug-tools' ), array_merge( $fails, $warnings ) );
        }

        if ( ! empty( $warnings ) ) {
            return self::result( 'warning', __( 'Review these roles and accounts:', 'dev-debug-tools' ), $warnings, 'medium' );
        }

        return self::result( 'pass', __( 'Only administrators can install plugins, edit files, change settings or manage users.', 'dev-debug-tools' ) );
    } // End check_role_capabilities()


    /**
     * Check: plugin files compared with WordPress.org
     *
     * @return array
     */
    public static function check_plugin_integrity() : array {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $updates = self::update_data( 'plugins' );
        if ( ! $updates ) {
            return self::result( 'unknown', self::update_data_missing() );
        }

        $listed = array_merge( (array) ( $updates->response ?? [] ), (array) ( $updates->no_update ?? [] ) );
        $cache = get_transient( 'ddtt_security_plugin_integrity' );
        $cache = is_array( $cache ) ? $cache : [];
        $deadline = microtime( true ) + self::SCAN_SECONDS;
        $php = '/\.(php\d?|phtml|phar|phps|pht)$/i';
        $findings = [];
        $failed = [];
        $total = 0;
        $checked = 0;
        $stopped = false;

        foreach ( get_plugins() as $file => $data ) {
            $slug = dirname( $file );
            $id = (string) ( ( (array) ( $listed[ $file ] ?? [] ) )[ 'id' ] ?? '' );
            if ( $slug === '.' || ! str_starts_with( $id, 'w.org/plugins/' ) ) {
                continue;
            }

            $total++;
            if ( microtime( true ) > $deadline ) {
                $stopped = true;
                continue;
            }

            $dir = trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) ) . $slug . '/';
            $cache_key = $slug . '|' . $data[ 'Version' ];
            $signature = self::folder_signature( $dir );

            if ( isset( $cache[ $cache_key ] ) && $cache[ $cache_key ][ 'signature' ] === $signature ) {
                if ( empty( $cache[ $cache_key ][ 'skipped' ] ) ) {
                    $findings = array_merge( $findings, $cache[ $cache_key ][ 'findings' ] );
                    $checked++;
                }
                continue;
            }

            $checksums = self::plugin_checksums( $slug, $data[ 'Version' ] );
            if ( $checksums === null ) {
                $failed[] = $data[ 'Name' ];
                continue;
            }

            if ( empty( $checksums ) ) {
                $cache[ $cache_key ] = [ 'signature' => $signature, 'findings' => [], 'skipped' => true ];
                continue;
            }

            $plugin_findings = [];
            foreach ( self::find_files( $dir, '/./', $deadline )[ 'files' ] as $path ) {
                $relative = substr( $path, strlen( $dir ) );
                if ( isset( $checksums[ $relative ] ) ) {
                    if ( ! in_array( strtolower( (string) hash_file( 'sha256', $path ) ), $checksums[ $relative ], true ) ) {
                        /* translators: %s: file path */
                        $plugin_findings[] = sprintf( __( 'Modified: %s', 'dev-debug-tools' ), $slug . '/' . $relative );
                    }
                } elseif ( preg_match( $php, $relative ) ) {
                    /* translators: %s: file path */
                    $plugin_findings[] = sprintf( __( 'Unexpected: %s', 'dev-debug-tools' ), $slug . '/' . $relative );
                }
            }

            if ( microtime( true ) > $deadline ) {
                $stopped = true;
                continue;
            }

            $cache[ $cache_key ] = [ 'signature' => $signature, 'findings' => $plugin_findings ];
            $findings = array_merge( $findings, $plugin_findings );
            $checked++;
        }

        set_transient( 'ddtt_security_plugin_integrity', $cache, DAY_IN_SECONDS );

        if ( ! empty( $findings ) ) {
            return self::result( 'fail', __( 'These plugin files do not match the official release on WordPress.org:', 'dev-debug-tools' ), $findings );
        }

        if ( $stopped ) {
            /* translators: 1: plugins checked, 2: total plugins, 3: seconds */
            return self::result( 'unknown', sprintf( __( '%1$d of %2$d plugins were checked in %3$d seconds and matched. Run this check again to continue; finished plugins are remembered for a day.', 'dev-debug-tools' ), $checked, $total, self::SCAN_SECONDS ) );
        }

        if ( ! empty( $failed ) ) {
            return self::result( 'unknown', __( 'Checksums could not be downloaded from downloads.wordpress.org for these plugins:', 'dev-debug-tools' ), $failed );
        }

        if ( ! $total ) {
            return self::result( 'pass', __( 'No plugins from WordPress.org are installed.', 'dev-debug-tools' ) );
        }

        /* translators: %d: number of plugins */
        return self::result( 'pass', sprintf( _n( '%d plugin from WordPress.org matches its official release. Plugin versions without published checksums are skipped.', '%d plugins from WordPress.org match their official releases. Plugin versions without published checksums are skipped.', $checked, 'dev-debug-tools' ), $checked ) );
    } // End check_plugin_integrity()


    /**
     * Check: PHP files in unexpected places
     *
     * @return array
     */
    public static function check_php_unexpected() : array {
        if ( ! function_exists( '_get_dropins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $php = '/\.(php\d?|phtml|phar|phps|pht)$/i';
        $root = trailingslashit( wp_normalize_path( ABSPATH ) );
        $content = trailingslashit( wp_normalize_path( WP_CONTENT_DIR ) );
        $deadline = microtime( true ) + self::SCAN_SECONDS;


        /**
         * Filter PHP files allowed in the main WordPress folder and at the top of wp-content.
         */
        $known = (array) apply_filters( 'ddtt_security_known_php_files', [
            'root'    => [ 'index.php', 'wp-activate.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config.php', 'wp-config-sample.php', 'wp-cron.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php', 'wordfence-waf.php' ],
            'content' => array_merge( [ 'index.php', 'wp-cache-config.php' ], array_keys( _get_dropins() ) ),
        ] );

        $backup_patterns = [
            'wp-config.php' => '/^wp-config-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.php$/',
            '.htaccess'     => '/^\.htaccess-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}$/',
        ];
        $backups = array_fill_keys( array_keys( $backup_patterns ), [] );
        $found = [];
        $stopped = false;

        foreach ( [ 'root' => $root, 'content' => $content ] as $place => $dir ) {
            foreach ( array_merge( (array) glob( $dir . '*' ), (array) glob( $dir . '.*' ) ) as $path ) {
                if ( ! $path || ! is_file( $path ) ) {
                    continue;
                }

                $name = basename( $path );
                if ( $place === 'root' ) {
                    foreach ( $backup_patterns as $file => $pattern ) {
                        if ( preg_match( $pattern, $name ) ) {
                            $backups[ $file ][] = $name;
                            continue 2;
                        }
                    }
                }

                if ( preg_match( $php, $name ) && ! in_array( $name, (array) ( $known[ $place ] ?? [] ), true ) ) {
                    $found[] = self::relative_path( $path );
                }
            }
        }

        $extra_backups = [];
        foreach ( $backups as $file => $names ) {
            if ( count( $names ) > 1 ) {
                rsort( $names );
                /* translators: %s: backup file name */
                $names[ 0 ] = sprintf( __( '%s (newest, keep)', 'dev-debug-tools' ), $names[ 0 ] );
                $extra_backups = array_merge( $extra_backups, $names );
            }
        }

        $folders = [ wp_normalize_path( WP_LANG_DIR ), $content . 'upgrade' ];
        foreach ( array_unique( $folders ) as $dir ) {
            $scan = self::find_files( $dir, $php, $deadline );
            $stopped = $stopped || $scan[ 'stopped' ];

            foreach ( $scan[ 'files' ] as $path ) {
                if ( str_ends_with( $path, '.l10n.php' ) || ( basename( $path ) === 'index.php' && filesize( $path ) <= 200 ) ) {
                    continue;
                }

                $found[] = self::relative_path( $path );
            }
        }

        $backup_note = __( 'Developer Debug Tools saves a dated backup of wp-config.php and .htaccess in the main WordPress folder each time you save one from its WP Config or HTACCESS tool. Older backups keep old database passwords and settings, so keep only the newest. See How to fix it.', 'dev-debug-tools' );

        if ( ! empty( $found ) ) {
            $message = __( 'PHP files were found where WordPress does not put them. Files in the upgrade folder can be left over from an update that is running or failed.', 'dev-debug-tools' );
            return self::result( 'fail', empty( $extra_backups ) ? $message : $message . ' ' . $backup_note, array_merge( $found, $extra_backups ) );
        }

        if ( ! empty( $extra_backups ) ) {
            return self::result( 'warning', __( 'No unexpected PHP files were found, but there are old backups to clean up.', 'dev-debug-tools' ) . ' ' . $backup_note, $extra_backups, 'low' );
        }

        if ( $stopped ) {
            /* translators: %d: seconds */
            return self::result( 'unknown', sprintf( __( 'The scan could not finish in %d seconds. No unexpected PHP files were found in the part that was scanned.', 'dev-debug-tools' ), self::SCAN_SECONDS ) );
        }

        return self::result( 'pass', __( 'No unexpected PHP files were found in the main WordPress folder, the top of wp-content, the languages folder or the upgrade folder.', 'dev-debug-tools' ) );
    } // End check_php_unexpected()


    /**
     * Check: closed or abandoned plugins and themes
     *
     * @return array
     */
    public static function check_abandoned_software() : array {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $deadline = microtime( true ) + self::SCAN_SECONDS;
        $cutoff = time() - 2 * YEAR_IN_SECONDS;
        $closed = [];
        $stale = [];
        $failed = 0;
        $stopped = false;
        $software = [];

        foreach ( get_plugins() as $file => $data ) {
            $update_uri = (string) ( $data[ 'UpdateURI' ] ?? '' );
            if ( dirname( $file ) === '.' || ( $update_uri !== '' && ! str_contains( $update_uri, 'wordpress.org' ) ) ) {
                continue;
            }

            $software[] = [ 'type' => 'plugin', 'slug' => dirname( $file ), 'name' => $data[ 'Name' ] ];
        }

        foreach ( array_unique( [ get_stylesheet(), get_template() ] ) as $slug ) {
            $theme = wp_get_theme( $slug );
            $software[] = [ 'type' => 'theme', 'slug' => $slug, 'name' => $theme->exists() ? $theme->get( 'Name' ) : $slug ];
        }

        foreach ( $software as $item ) {
            if ( microtime( true ) > $deadline ) {
                $stopped = true;
                break;
            }

            $info = self::wporg_info( $item[ 'type' ], $item[ 'slug' ] );
            if ( $info === null ) {
                $failed++;
                continue;
            }

            if ( ( $info[ 'error' ] ?? '' ) === 'closed' || ! empty( $info[ 'closed' ] ) ) {
                /* translators: 1: plugin name, 2: date, 3: reason */
                $closed[] = sprintf( __( '%1$s: closed on WordPress.org on %2$s (%3$s)', 'dev-debug-tools' ), $item[ 'name' ], (string) ( $info[ 'closed_date' ] ?? '?' ), ! empty( $info[ 'reason_text' ] ) ? (string) $info[ 'reason_text' ] : __( 'no public reason', 'dev-debug-tools' ) );
                continue;
            }

            if ( isset( $info[ 'error' ] ) ) {
                continue;
            }

            $updated = strtotime( (string) ( $info[ 'last_updated' ] ?? '' ) );
            if ( $updated && $updated < $cutoff ) {
                /* translators: 1: plugin or theme name, 2: time ago */
                $stale[] = sprintf( __( '%1$s: last updated %2$s ago', 'dev-debug-tools' ), $item[ 'name' ], human_time_diff( $updated ) );
            }
        }

        if ( ! empty( $closed ) ) {
            return self::result( 'fail', __( 'These have been closed on WordPress.org and will not get security fixes:', 'dev-debug-tools' ), array_merge( $closed, $stale ) );
        }

        if ( ! empty( $stale ) ) {
            return self::result( 'warning', __( 'These have not been updated in over two years:', 'dev-debug-tools' ), $stale );
        }

        if ( $stopped || $failed ) {
            return self::result( 'unknown', __( 'Some plugins and themes could not be looked up on api.wordpress.org. Run this check again; results are remembered for a day.', 'dev-debug-tools' ) );
        }

        return self::result( 'pass', __( 'No installed plugin or active theme from WordPress.org is closed or abandoned.', 'dev-debug-tools' ) );
    } // End check_abandoned_software()


    /**
     * Check: developer plugins on production
     *
     * @return array
     */
    public static function check_dev_plugins() : array {
        $environment = wp_get_environment_type();
        if ( $environment !== 'production' ) {
            /* translators: %s: environment type */
            return self::result( 'pass', sprintf( __( 'The environment type is "%s", so developer plugins are expected.', 'dev-debug-tools' ), $environment ) );
        }


        /**
         * Filter the plugin folders treated as developer plugins.
         */
        $known = (array) apply_filters( 'ddtt_security_dev_plugins', [ 'query-monitor', 'debug-bar', 'wp-reset', 'fakerpress', 'wp-adminer', 'ari-adminer', 'pexlechris-adminer', 'wp-phpmyadmin-extension', 'wp-file-manager', 'file-manager-advanced', 'wp-console', 'theme-check', 'log-deprecated-notices', 'developer', 'wp-debugging' ] );

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $active = self::active_plugin_folders();
        $items = [];

        foreach ( get_plugins() as $file => $data ) {
            $folder = strtolower( dirname( $file ) );
            if ( in_array( $folder, $active, true ) && ( in_array( $folder, $known, true ) || str_starts_with( $folder, 'debug-bar-' ) ) ) {
                $items[] = $data[ 'Name' ];
            }
        }

        if ( ! empty( $items ) ) {
            return self::result( 'warning', __( 'These developer plugins are active on a production site:', 'dev-debug-tools' ), $items );
        }

        return self::result( 'pass', __( 'No known database managers, file managers, reset tools or debug plugins are active.', 'dev-debug-tools' ) );
    } // End check_dev_plugins()


    /**
     * Check: pre-release WordPress
     *
     * @return array
     */
    public static function check_prerelease_core() : array {
        $version = get_bloginfo( 'version' );
        if ( ! preg_match( '/-(alpha|beta|rc|src)/i', $version ) ) {
            /* translators: %s: WordPress version */
            return self::result( 'pass', sprintf( __( 'WordPress %s is a final release.', 'dev-debug-tools' ), $version ) );
        }

        $environment = wp_get_environment_type();
        if ( $environment !== 'production' ) {
            /* translators: 1: WordPress version, 2: environment type */
            return self::result( 'pass', sprintf( __( 'WordPress %1$s is a pre-release, and the environment type is "%2$s".', 'dev-debug-tools' ), $version, $environment ) );
        }

        /* translators: %s: WordPress version */
        return self::result( 'warning', sprintf( __( 'WordPress %s is a pre-release version on a production site.', 'dev-debug-tools' ), $version ) );
    } // End check_prerelease_core()


    /**
     * Check: plugins not from WordPress.org
     *
     * @return array
     */
    public static function check_unlisted_software() : array {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $updates = self::update_data( 'plugins' );
        if ( ! $updates ) {
            return self::result( 'unknown', self::update_data_missing() );
        }

        $listed = array_merge( (array) ( $updates->response ?? [] ), (array) ( $updates->no_update ?? [] ) );
        $items = [];

        foreach ( get_plugins() as $file => $data ) {
            $id = (string) ( ( (array) ( $listed[ $file ] ?? [] ) )[ 'id' ] ?? '' );
            if ( str_starts_with( $id, 'w.org/plugins/' ) ) {
                continue;
            }

            $update_uri = (string) ( $data[ 'UpdateURI' ] ?? '' );
            $host = $update_uri !== '' ? (string) wp_parse_url( $update_uri, PHP_URL_HOST ) : '';
            /* translators: 1: plugin name, 2: update host */
            $items[] = $host ? sprintf( __( '%1$s (updates from %2$s)', 'dev-debug-tools' ), $data[ 'Name' ], $host ) : $data[ 'Name' ];
        }

        if ( empty( $items ) ) {
            return self::result( 'pass', __( 'Every installed plugin is listed on WordPress.org.', 'dev-debug-tools' ) );
        }

        return self::result( 'info', __( 'These plugins are not updated through WordPress.org. Closed plugins also appear here.', 'dev-debug-tools' ), $items );
    } // End check_unlisted_software()


    /**
     * Check: must-use plugins and drop-ins
     *
     * @return array
     */
    public static function check_mu_dropins() : array {
        if ( ! function_exists( 'get_mu_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $items = [];

        foreach ( get_mu_plugins() as $file => $data ) {
            /* translators: 1: file name, 2: plugin name */
            $items[] = sprintf( __( 'Must-use plugin: %1$s (%2$s)', 'dev-debug-tools' ), $file, $data[ 'Name' ] ?: __( 'no name', 'dev-debug-tools' ) );
        }

        foreach ( get_dropins() as $file => $data ) {
            /* translators: 1: file name, 2: plugin name */
            $items[] = sprintf( __( 'Drop-in: %1$s (%2$s)', 'dev-debug-tools' ), $file, $data[ 'Name' ] ?: __( 'no name', 'dev-debug-tools' ) );
        }

        if ( empty( $items ) ) {
            return self::result( 'pass', __( 'No must-use plugins or drop-ins are installed.', 'dev-debug-tools' ) );
        }

        return self::result( 'info', __( 'These load on every request and do not appear in the plugin list:', 'dev-debug-tools' ), $items );
    } // End check_mu_dropins()


    /**
     * Check: outgoing SSL verification
     *
     * @return array
     */
    public static function check_ssl_verify() : array {
        $url = 'https://api.wordpress.org/';
        $verify = apply_filters( 'https_ssl_verify', true, $url ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter applied to read its effective value.
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter applied to read its effective value.
        $args = apply_filters( 'http_request_args', [ 
            'method'              => 'GET',
            'timeout'             => 5,
            'redirection'         => 5,
            'httpversion'         => '1.0',
            'user-agent'          => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
            'reject_unsafe_urls'  => false,
            'blocking'            => true,
            'headers'             => [],
            'cookies'             => [],
            'body'                => null,
            'compress'            => false,
            'decompress'          => true,
            'sslverify'           => true,
            'sslcertificates'     => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
            'stream'              => false,
            'filename'            => null,
            'limit_response_size' => null,
        ], $url );

        if ( $verify && ( ! is_array( $args ) || ! empty( $args[ 'sslverify' ] ) ) ) {
            return self::result( 'pass', __( 'WordPress checks certificates when it connects to other servers.', 'dev-debug-tools' ) );
        }

        $items = array_merge( $verify ? [] : self::hook_sources( 'https_ssl_verify' ), is_array( $args ) && empty( $args[ 'sslverify' ] ) ? self::hook_sources( 'http_request_args' ) : [] );
        $message = empty( $items ) ? __( 'Certificate checks are turned off for outgoing requests.', 'dev-debug-tools' ) : __( 'Certificate checks are turned off for outgoing requests. Code hooked into these filters may be the cause:', 'dev-debug-tools' );

        return self::result( 'fail', $message, $items );
    } // End check_ssl_verify()


    /**
     * Check: public REST routes from plugins and themes
     *
     * @return array
     */
    public static function check_rest_read_public() : array {
        $skip = self::public_rest_skip();
        $items = [];

        foreach ( rest_get_server()->get_routes() as $route => $endpoints ) {
            if ( in_array( $route, $skip, true ) ) {
                continue;
            }

            foreach ( $endpoints as $endpoint ) {
                $methods = (array) ( $endpoint[ 'methods' ] ?? [] );
                $permission = $endpoint[ 'permission_callback' ] ?? null;
                if ( empty( $methods[ 'GET' ] ) || ( $permission !== null && $permission !== '__return_true' ) ) {
                    continue;
                }

                $source = isset( $endpoint[ 'callback' ] ) && is_callable( $endpoint[ 'callback' ] ) ? self::callback_source( $endpoint[ 'callback' ] ) : __( 'unknown source', 'dev-debug-tools' );
                if ( $source !== 'core' ) {
                    $items[] = 'GET ' . $route . ' (' . $source . ')';
                    break;
                }
            }
        }

        if ( empty( $items ) ) {
            return self::result( 'pass', __( 'No plugin or theme adds REST routes that anyone can read.', 'dev-debug-tools' ) );
        }

        return self::result( 'info', __( 'Anyone can read these routes without logging in:', 'dev-debug-tools' ), $items );
    } // End check_rest_read_public()


    /**
     * Check: logged-out admin-post actions
     *
     * @return array
     */
    public static function check_admin_post_nopriv() : array {
        $items = self::public_hook_callbacks( 'admin_post_nopriv_' );

        if ( empty( $items ) ) {
            return self::result( 'pass', __( 'No plugin or theme adds admin-post actions for logged-out visitors.', 'dev-debug-tools' ) );
        }

        return self::result( 'info', __( 'These admin-post actions run for logged-out visitors. Actions a plugin only adds when nobody is logged in are not listed.', 'dev-debug-tools' ), $items );
    } // End check_admin_post_nopriv()


    /**
     * Render a check's row and its details row
     *
     * @param string     $key
     * @param array      $check
     * @param array|null $result
     * @return void
     */
    public static function render_row( string $key, array $check, ?array $result ) : void {
        $statuses = self::statuses();
        $severities = self::severities();
        $status = $result && isset( $statuses[ $result[ 'status' ] ] ) ? $result[ 'status' ] : 'none';
        $severity = $result && isset( $severities[ $result[ 'severity' ] ] ) ? $result[ 'severity' ] : $check[ 'severity' ];
        $message = $result ? $result[ 'message' ] : __( 'Not run yet.', 'dev-debug-tools' );
        $items = $result ? (array) $result[ 'items' ] : [];
        $details_id = 'ddtt-security-details-' . $key;
        ?>
        <tr class="ddtt-security-row" data-check="<?php echo esc_attr( $key ); ?>" data-status="<?php echo esc_attr( $status ); ?>">
            <td><span class="ddtt-security-status" data-status="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $statuses[ $status ] ); ?></span></td>
            <td>
                <strong><?php echo esc_html( $check[ 'label' ] ); ?></strong>
                <span class="ddtt-security-severity" data-severity="<?php echo esc_attr( $severity ); ?>"><?php echo esc_html( $severities[ $severity ] ); ?></span>
            </td>
            <td>
                <?php echo esc_html( $message ); ?>
                <?php if ( $result && ! empty( $result[ 'time' ] ) ) : ?>
                    <?php /* translators: %s: time since the check ran */ ?>
                    <span class="ddtt-security-time"><?php echo esc_html( sprintf( empty( $result[ 'scheduled' ] ) ? __( 'Checked %s ago', 'dev-debug-tools' ) : __( 'Checked %s ago by a scheduled run', 'dev-debug-tools' ), human_time_diff( (int) $result[ 'time' ] ) ) ); ?></span>
                <?php endif; ?>
            </td>
            <td class="ddtt-security-actions">
                <button type="button" class="ddtt-button ddtt-security-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $details_id ); ?>"><?php esc_html_e( 'Details', 'dev-debug-tools' ); ?></button>
                <button type="button" class="ddtt-button ddtt-security-run" data-check="<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'Run', 'dev-debug-tools' ); ?></button>
            </td>
        </tr>
        <tr id="<?php echo esc_attr( $details_id ); ?>" class="ddtt-security-details" data-details-for="<?php echo esc_attr( $key ); ?>" hidden>
            <td colspan="4">
                <?php if ( ! empty( $items ) ) : ?>
                    <h4><?php esc_html_e( 'Found', 'dev-debug-tools' ); ?></h4>
                    <ul class="ddtt-security-items">
                        <?php foreach ( $items as $item ) : ?>
                            <li><code><?php echo esc_html( $item ); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ( ! empty( $check[ 'risk' ] ) ) : ?>
                    <h4><?php esc_html_e( 'What an attacker could do', 'dev-debug-tools' ); ?></h4>
                    <p><?php echo esc_html( $check[ 'risk' ] ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $check[ 'fix' ] ) ) : ?>
                    <h4><?php esc_html_e( 'How to fix it', 'dev-debug-tools' ); ?></h4>
                    <p><?php echo wp_kses_post( $check[ 'fix' ] ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    } // End render_row()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'security-checks' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-tool-security-checks', 'ddtt_security_checks', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n'  => [
                'running'      => __( 'Running', 'dev-debug-tools' ),
                /* translators: 1: current check number, 2: total checks */
                'runningOf'    => __( 'Running check %1$d of %2$d', 'dev-debug-tools' ),
                'error'        => __( 'Error', 'dev-debug-tools' ),
                'errorMsg'     => __( 'The check could not be run. Your session may have expired; reload the page and try again.', 'dev-debug-tools' ),
                'notRunYet'    => __( 'No checks have run yet. Click Run All Checks to start.', 'dev-debug-tools' ),
                /* translators: %d: number of failed checks */
                'issues'       => __( '%d issue(s) need attention.', 'dev-debug-tools' ),
                /* translators: %d: number of warnings */
                'warningsOnly' => __( 'No failures. %d warning(s) to review.', 'dev-debug-tools' ),
                /* translators: %d: number of unverified checks */
                'unknownOnly'  => __( 'No failures. %d check(s) could not be verified.', 'dev-debug-tools' ),
                'allPassed'    => __( 'These checks found no problems.', 'dev-debug-tools' ),
                'scoreLabel'   => __( 'Security score', 'dev-debug-tools' ),
                /* translators: 1: number of checks counted in the score, 2: total number of checks */
                'scoreBasis'   => __( 'Based on %1$d of %2$d checks.', 'dev-debug-tools' ),
                'scoreSkipped' => __( 'Checks that have not run, could not be verified, or are for review only are not counted.', 'dev-debug-tools' ),
                'clearConfirm' => __( 'Clear all saved results?', 'dev-debug-tools' ),
                'statuses'     => self::statuses(),
            ],
            'score' => self::score_settings(),
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Run a check
     *
     * @return void
     */
    public function ajax_run_check() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) || ! Helpers::is_dev() ) {
            wp_send_json_error( 'unauthorized' );
        }

        $key = isset( $_POST[ 'check' ] ) ? sanitize_key( wp_unslash( $_POST[ 'check' ] ) ) : '';
        $checks = self::enabled_checks();
        if ( ! isset( $checks[ $key ] ) ) {
            wp_send_json_error( 'invalid_check' );
        }

        $result = self::run( $key );
        self::save_result( $key, $result );

        ob_start();
        self::render_row( $key, $checks[ $key ], $result );

        wp_send_json_success( [
            'status' => $result[ 'status' ],
            'html'   => ob_get_clean(),
        ] );
    } // End ajax_run_check()


    /**
     * AJAX: Clear saved results
     *
     * @return void
     */
    public function ajax_clear_results() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) || ! Helpers::is_dev() ) {
            wp_send_json_error( 'unauthorized' );
        }

        delete_option( self::RESULTS_KEY );

        wp_send_json_success();
    } // End ajax_clear_results()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


SecurityChecks::instance();
