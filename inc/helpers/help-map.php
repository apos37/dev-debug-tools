<?php
/**
 * Help Map for Logs
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Help {

    /**
     * Returns a map of debug log patterns to their descriptions and links.
     *
     * @return array
     */
    public static function debug_log_map() : array {
        $map = [
            // Class not found error
            '/Uncaught Error: Class "([^"]+)" not found/' => [
                'desc' => 'PHP tried to instantiate a class that is not defined or autoloaded. Check if the class file exists and autoloaders are configured correctly.',
                'link' => 'https://www.php.net/manual/en/language.oop5.autoload.php'
            ],

            // Failed opening required file error
            '/Uncaught Error: Failed opening required \'([^\']+)\' \(include_path=.*\)/' => [
                'desc' => 'PHP could not find or open the required file. Verify the file path is correct and readable.',
                'link' => 'https://www.php.net/manual/en/function.require.php'
            ],

            // Non-static method called statically error
            '/Uncaught Error: Non-static method ([^(]+)\(\) cannot be called statically/' => [
                'desc' => 'A non-static method was called in a static context. Change the method to static or call it on an instance of the class.',
                'link' => 'https://www.php.net/manual/en/language.oop5.static.php'
            ],

            // Memory exhaustion
            '/Allowed memory size of .* bytes exhausted/' => [
                'desc' => 'PHP memory limit reached. Increase memory_limit in php.ini or define WP_MEMORY_LIMIT in wp-config.php.',
                'link'        => 'https://wordpress.org/documentation/article/editing-wp-config-php/#increasing-memory-allocated-to-php'
            ],

            // Maximum execution time exceeded
            '/Maximum execution time of .* seconds exceeded/' => [
                'desc' => 'PHP script execution time exceeded max_execution_time. Increase this limit in php.ini or optimize the code.',
                'link'        => 'https://www.php.net/manual/en/info.configuration.php#ini.max-execution-time'
            ],

            // Headers already sent
            '/Cannot modify header information - headers already sent by .+ output started at .+/' => [
                'desc' => 'Headers already sent error occurs when output is sent before header() or setcookie(). Check for whitespace or output before headers.',
                'link'        => 'https://developer.wordpress.org/reference/functions/wp_redirect/#headers-already-sent-error'
            ],

            // Call to undefined function
            '/Call to undefined function (\w+)/' => [
                'desc' => 'A function is called but not defined or loaded. This often happens if WordPress core or plugins are not fully loaded or a required plugin is missing.',
                'link'        => 'https://developer.wordpress.org/reference/functions/'
            ],

            // _load_textdomain_just_in_time called too early
            '/Function _load_textdomain_just_in_time was called/' => [
                'desc' => 'The _load_textdomain_just_in_time() function was triggered before WordPress was ready to load translations. This usually happens if load_plugin_textdomain() or load_theme_textdomain() is called too early. It can also occur if translation functions like __() or _e() are executed before plugins_loaded. To fix, ensure translation loading or text calls happen on or after the plugins_loaded (or later) hook so WordPress has initialized properly.',
                'link' => 'https://developer.wordpress.org/reference/functions/load_plugin_textdomain/'
            ],

            // Fatal error due to class not found
            '/Class \'(\w+)\' not found/' => [
                'desc' => 'A PHP class is referenced but not defined or autoloaded. Check plugin/theme activation and autoloaders.',
                'link'        => 'https://www.php.net/manual/en/language.oop5.autoload.php'
            ],

            // Deprecated function notice
            '/(Deprecated|Deprecated function): (\w+) is deprecated/' => [
                'desc' => 'The code is using a function that is deprecated and may be removed in future versions. Update the code to use supported alternatives.',
                'link'        => 'https://developer.wordpress.org/reference/functions/_deprecated_function/'
            ],

            // Undefined variable notice
            '/Undefined variable: (\w+)/' => [
                'desc' => 'A variable is used before it is defined. Initialize variables before use.',
                'link'        => 'https://www.php.net/manual/en/language.variables.basics.php'
            ],

            // Undefined index notice
            '/Undefined index: (\w+)/' => [
                'desc' => 'Trying to access an array index/key that does not exist. Use isset() or array_key_exists() to check before access.',
                'link'        => 'https://www.php.net/manual/en/language.types.array.php#language.types.array'
            ],

            // Trying to get property of non-object
            '/Trying to get property \'(\w+)\' of non-object/' => [
                'desc' => 'Accessing a property on a variable that is not an object. Check for null or type before accessing properties.',
                'link'        => 'https://www.php.net/manual/en/language.types.object.php'
            ],

            // SQL syntax error
            '/You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version/' => [
                'desc' => 'Malformed SQL query detected. Review your query syntax and use $wpdb->prepare() for safe queries.',
                'link'        => 'https://developer.wordpress.org/reference/classes/wpdb/prepare/'
            ],

            // Fatal error: Allowed memory size exhausted
            '/Fatal error: Allowed memory size of .* bytes exhausted/' => [
                'desc' => 'PHP memory exhausted during script execution. Increase memory_limit or optimize code.',
                'link'        => 'https://wordpress.org/documentation/article/editing-wp-config-php/#increasing-memory-allocated-to-php'
            ],

            // Warning: include file not found
            '/Warning: include\(.+\): failed to open stream: No such file or directory/' => [
                'desc' => 'A PHP include or require file was not found. Check file paths and ensure files exist.',
                'link'        => 'https://www.php.net/manual/en/function.include.php'
            ],

            // Warning: session_start(): Cannot start session
            '/Warning: session_start\(\): Cannot start session/' => [
                'desc' => 'session_start() failed, often due to headers already sent or session misconfiguration.',
                'link'        => 'https://www.php.net/manual/en/function.session-start.php'
            ],

            // Warning: Cannot modify header information
            '/Warning: Cannot modify header information - headers already sent/' => [
                'desc' => 'Output started before headers were sent. Remove whitespace before <?php or after ?> tags.',
                'link'        => 'https://developer.wordpress.org/reference/functions/wp_redirect/#headers-already-sent-error'
            ],

            // Warning: Use of undefined constant
            '/Use of undefined constant (\w+)/' => [
                'desc' => 'A constant is used without quotes. Wrap strings in quotes or define the constant.',
                'link'        => 'https://www.php.net/manual/en/language.constants.syntax.php'
            ],

            // Undefined constant error (class or namespace agnostic)
            '/Uncaught Error: Undefined constant (\w+)/' => [
                'desc' => 'A constant was referenced but has not been defined. Check that the constant exists and is properly declared before use.',
                'link' => 'https://www.php.net/manual/en/language.constants.php'
            ],

            // Warning: Cannot modify header information - headers already sent by output started
            '/headers already sent by .+ output started at/' => [
                'desc' => 'Output before header modification. Check for extra whitespace or output before headers.',
                'link'        => 'https://developer.wordpress.org/reference/functions/wp_redirect/#headers-already-sent-error'
            ],

            // Fatal error: Call to a member function on null
            '/Fatal error: Call to a member function (\w+)\(\) on null/' => [
                'desc' => 'Attempted to call a method on a null variable. Check that object is properly instantiated before calling methods.',
                'link'        => 'https://www.php.net/manual/en/language.oop5.objects.php'
            ],

            // Warning: Cannot modify header information - headers already sent
            '/Warning: Cannot modify header information - headers already sent/' => [
                'desc' => 'Headers already sent error. Remove whitespace before/after PHP tags or output before header() calls.',
                'link'        => 'https://developer.wordpress.org/reference/functions/wp_redirect/#headers-already-sent-error'
            ],

            // Fatal error: Cannot redeclare function
            '/Fatal error: Cannot redeclare (\w+)\(\)/' => [
                'desc' => 'A function with the same name was declared more than once. Check for duplicate function definitions or plugin conflicts.',
                'link'        => 'https://www.php.net/manual/en/language.functions.php'
            ],

            // Warning: session_start(): Cannot send session cache limiter
            '/Warning: session_start\(\): Cannot send session cache limiter - headers already sent/' => [
                'desc' => 'Session start failed due to output sent before headers. Remove any output before session_start().',
                'link'        => 'https://www.php.net/manual/en/function.session-start.php'
            ],

            // Syntax error: unexpected token
            '/syntax error, unexpected token ";", expecting "function"/' => [
                'desc' => 'PHP encountered a syntax error where a function definition was expected but a semicolon was found. Check for missing function keyword or misplaced semicolon.',
                'link' => 'https://www.php.net/manual/en/functions.user-defined.php'
            ],

            // TypeError general pattern
            '/Uncaught TypeError: ([^(]+)\(\): Argument #\d+ \(\$\w+\) must be of type ([^,]+), ([^)]+) given/' => [
                'desc' => 'A function received an argument of an unexpected type. The specified argument requires a different data type than what was passed. Review the argument types and ensure they match the function\'s expectations.',
                'link' => 'https://www.php.net/manual/en/language.types.php'
            ],

            // TypeError: return value type mismatch
            '/Uncaught TypeError: ([^(]+)\(\): Return value must be of type ([^,]+), ([^)]+) returned/' => [
                'desc' => 'A function or method returned a value of the wrong type. Update the return statement to match the declared return type or adjust the type declaration.',
                'link' => 'https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.return'
            ],

            // Undefined method
            '/Call to undefined method ([^(]+)\(\)/' => [
                'desc' => 'A method was called on a class that does not exist. Check class definition and method name spelling.',
                'link' => 'https://www.php.net/manual/en/language.oop5.basic.php'
            ],

            // Cannot access protected/private property
            '/Cannot access (protected|private) property ([^$]+)\$(\w+)/' => [
                'desc' => 'Code tried to access a non-public property directly. Use a public getter/setter method or change property visibility.',
                'link' => 'https://www.php.net/manual/en/language.oop5.visibility.php'
            ],

            // Call to private method error
            '/Uncaught Error: Call to private method ([^(]+)\(\) from scope/' => [
                'desc' => 'Code attempted to call a private method from outside its defining class. Change the method visibility to public/protected if it should be accessible, or call it from within the class itself.',
                'link' => 'https://www.php.net/manual/en/language.oop5.visibility.php'
            ],


            // Division by zero
            '/Division by zero/' => [
                'desc' => 'Code attempted to divide by zero. Ensure divisor is checked before performing division.',
                'link' => 'https://www.php.net/manual/en/language.operators.arithmetic.php'
            ],

            // Maximum function nesting level reached
            '/Maximum function nesting level of \'\d+\' reached/' => [
                'desc' => 'Likely caused by infinite recursion or excessive nested calls. Review function call stack for loops.',
                'link' => 'https://www.php.net/manual/en/function.debug-backtrace.php'
            ],

            // Call to a member function on array
            '/Call to a member function (\w+)\(\) on array/' => [
                'desc' => 'Code attempted to call a method on an array variable. Ensure variable is an object before calling methods.',
                'link' => 'https://www.php.net/manual/en/language.types.array.php'
            ],

            // Cannot use object as array
            '/Cannot use object of type (\w+) as array/' => [
                'desc' => 'Attempted to access an object as if it were an array. Use object property access instead of array syntax.',
                'link' => 'https://www.php.net/manual/en/language.types.object.php'
            ],

            // Too few arguments to function
            '/Too few arguments to function (\w+)/' => [
                'desc' => 'Function was called with fewer parameters than required. Review function definition and add missing arguments.',
                'link' => 'https://www.php.net/manual/en/functions.arguments.php'
            ],

            // Too many arguments to function
            '/Too many arguments to function (\w+)/' => [
                'desc' => 'Function was called with more parameters than allowed. Review function definition and remove extra arguments.',
                'link' => 'https://www.php.net/manual/en/functions.arguments.php'
            ],

            // Cannot redeclare class
            '/Cannot redeclare class (\w+)/' => [
                'desc' => 'A class was declared more than once, usually due to multiple includes. Use require_once or autoloading.',
                'link' => 'https://www.php.net/manual/en/language.oop5.basic.php'
            ],

            // WordPress database error: Illegal mix of collations
            '/WordPress database error Illegal mix of collations \(([^)]+)\) and \(([^)]+)\) for operation \'like\'/' => [
                'desc' => 'MySQL detected incompatible collations being compared in a LIKE operation. Ensure all relevant database columns use the same charset and collation, or convert them before comparison.',
                'link' => 'https://dev.mysql.com/doc/refman/8.0/en/charset-collation-conversion.html'
            ],

            // WordPress database error: Table doesn't exist
            '/WordPress database error Table \'[^\']+\' doesn\'t exist/' => [
                'desc' => 'A database query failed because the specified table does not exist. This can happen if a plugin did not create its tables properly, or if the database is missing/corrupted. Try reinstalling or repairing the plugin, or manually creating the required tables.',
                'link' => 'https://developer.wordpress.org/advanced-administration/repair-database/'
            ],

        ];


        /**
         * Allow filtering of the map
         */
        $map = apply_filters( 'ddtt_help_map_debug_log', $map );

        return $map;
    } // End map()

}