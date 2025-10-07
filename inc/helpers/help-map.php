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
                'desc' => __( 'PHP tried to instantiate a class that is not defined or autoloaded. Check if the class file exists and autoloaders are configured correctly.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.oop5.autoload.php'
            ],

            // Failed opening required file error
            '/Uncaught Error: Failed opening required \'([^\']+)\' \(include_path=.*\)/' => [
                'desc' => __( 'PHP could not find or open the required file. Verify the file path is correct and readable.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/function.require.php'
            ],

            // Non-static method called statically error
            '/Uncaught Error: Non-static method ([^(]+)\(\) cannot be called statically/' => [
                'desc' => __( 'A non-static method was called in a static context. Change the method to static or call it on an instance of the class.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.oop5.static.php'
            ],

            // Memory exhaustion
            '/Allowed memory size of .* bytes exhausted/' => [
                'desc' => __( 'PHP memory limit reached. Increase memory_limit in php.ini or define WP_MEMORY_LIMIT in wp-config.php.', 'dev-debug-tools' ),
                'link' => 'https://wordpress.org/documentation/article/editing-wp-config-php/#increasing-memory-allocated-to-php'
            ],

            // Maximum execution time exceeded
            '/Maximum execution time of .* seconds exceeded/' => [
                'desc' => __( 'PHP script execution time exceeded max_execution_time. Increase this limit in php.ini or optimize the code.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/info.configuration.php#ini.max-execution-time'
            ],

            // Headers already sent
            '/Cannot modify header information - headers already sent by .+ output started at .+/' => [
                'desc' => __( 'Headers already sent error occurs when output is sent before header() or setcookie(). Check for whitespace or output before headers.', 'dev-debug-tools' ),
                'link' => 'https://developer.wordpress.org/reference/functions/wp_redirect/#headers-already-sent-error'
            ],

            // Call to undefined function
            '/Call to undefined function (\w+)/' => [
                'desc' => __( 'A function is called but not defined or loaded. This often happens if WordPress core or plugins are not fully loaded or a required plugin is missing.', 'dev-debug-tools' ),
                'link' => 'https://developer.wordpress.org/reference/functions/'
            ],

            // _load_textdomain_just_in_time called too early
            '/Function _load_textdomain_just_in_time was called/' => [
                'desc' => __( 'The _load_textdomain_just_in_time() function was triggered before WordPress was ready to load translations. This usually happens if load_plugin_textdomain() or load_theme_textdomain() is called too early. It can also occur if translation functions like __() or _e() are executed before plugins_loaded. To fix, ensure translation loading or text calls happen on or after the plugins_loaded (or later) hook so WordPress has initialized properly.', 'dev-debug-tools' ),
                'link' => 'https://developer.wordpress.org/reference/functions/load_plugin_textdomain/'
            ],

            // Fatal error due to class not found
            '/Class \'(\w+)\' not found/' => [
                'desc' => __( 'A PHP class is referenced but not defined or autoloaded. Check plugin/theme activation and autoloaders.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.oop5.autoload.php'
            ],

            // Deprecated function notice
            '/(Deprecated|Deprecated function): (\w+) is deprecated/' => [
                'desc' => __( 'The code is using a function that is deprecated and may be removed in future versions. Update the code to use supported alternatives.', 'dev-debug-tools' ),
                'link' => 'https://developer.wordpress.org/reference/functions/_deprecated_function/'
            ],

            // Undefined variable notice
            '/Undefined variable \$([\w]+)/i' => [
                'desc' => __( 'A variable was referenced before it was defined or assigned. Ensure all variables are initialized before use.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.variables.basics.php'
            ],

            // Undefined array key notice
            '/Undefined array key ["\']?([\w-]+)["\']?/i' => [
                'desc' => __( 'An array element was accessed with a key that does not exist. Check that the key exists using isset() or array_key_exists() before accessing it.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/errorfunc.constants.php'
            ],

            // Trying to access array offset on null
            '/Trying to access array offset on null/i' => [
                'desc' => __( 'Code attempted to access an array index on a variable that is null. Ensure the variable is an array before accessing its elements, using isset(), array_key_exists(), or a null coalescing check.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.operators.array.php'
            ],

            // Undefined index notice
            '/Undefined index: (\w+)/' => [
                'desc' => __( 'Trying to access an array index/key that does not exist. Use isset() or array_key_exists() to check before access.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.types.array.php#language.types.array'
            ],

            // Trying to get property of non-object
            '/Trying to get property \'(\w+)\' of non-object/' => [
                'desc' => __( 'Accessing a property on a variable that is not an object. Check for null or type before accessing properties.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.types.object.php'
            ],

            // SQL syntax error
            '/You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version/' => [
                'desc' => __( 'Malformed SQL query detected. Review your query syntax and use $wpdb->prepare() for safe queries.', 'dev-debug-tools' ),
                'link' => 'https://developer.wordpress.org/reference/classes/wpdb/prepare/'
            ],

            // Fatal error: Allowed memory size exhausted
            '/Fatal error: Allowed memory size of .* bytes exhausted/' => [
                'desc' => __( 'PHP memory exhausted during script execution. Increase memory_limit or optimize code.', 'dev-debug-tools' ),
                'link' => 'https://wordpress.org/documentation/article/editing-wp-config-php/#increasing-memory-allocated-to-php'
            ],

            // Warning: include file not found
            '/Warning: include\(.+\): failed to open stream: No such file or directory/' => [
                'desc' => __( 'A PHP include or require file was not found. Check file paths and ensure files exist.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/function.include.php'
            ],

            // Warning: session_start(): Cannot start session
            '/Warning: session_start\(\): Cannot start session/' => [
                'desc' => __( 'session_start() failed, often due to headers already sent or session misconfiguration.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/function.session-start.php'
            ],

            // Warning: Cannot modify header information
            '/Warning: Cannot modify header information - headers already sent/' => [
                'desc' => __( 'Output started before headers were sent. Remove whitespace before <?php or after ?> tags.', 'dev-debug-tools' ),
                'link' => 'https://developer.wordpress.org/reference/functions/wp_redirect/#headers-already-sent-error'
            ],

            // Warning: Use of undefined constant
            '/Use of undefined constant (\w+)/' => [
                'desc' => __( 'A constant is used without quotes. Wrap strings in quotes or define the constant.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.constants.syntax.php'
            ],

            // Invalid foreach argument type
            '/foreach\(\) argument must be of type ([^,]+), ([^)]+) given/' => [
                'desc' => __( 'A foreach loop received an invalid argument type. The value must be an array or object that can be iterated over. Verify the variable being looped is initialized as an array or iterable object before using foreach.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/control-structures.foreach.php'
            ],

            // Undefined constant error (class or namespace agnostic)
            '/Uncaught Error: Undefined constant (\w+)/' => [
                'desc' => __( 'A constant was referenced but has not been defined. Check that the constant exists and is properly declared before use.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.constants.php'
            ],

            // Warning: Cannot modify header information - headers already sent by output started
            '/headers already sent by .+ output started at/' => [
                'desc' => __( 'Output before header modification. Check for extra whitespace or output before headers.', 'dev-debug-tools' ),
                'link' => 'https://developer.wordpress.org/reference/functions/wp_redirect/#headers-already-sent-error'
            ],

            // Fatal error: Call to a member function on null
            '/Fatal error: Call to a member function (\w+)\(\) on null/' => [
                'desc' => __( 'Attempted to call a method on a null variable. Check that object is properly instantiated before calling methods.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.oop5.objects.php'
            ],

            // Warning: Cannot modify header information - headers already sent
            '/Warning: Cannot modify header information - headers already sent/' => [
                'desc' => __( 'Headers already sent error. Remove whitespace before/after PHP tags or output before header() calls.', 'dev-debug-tools' ),
                'link' => 'https://developer.wordpress.org/reference/functions/wp_redirect/#headers-already-sent-error'
            ],

            // Fatal error: Cannot redeclare function
            '/Fatal error: Cannot redeclare (\w+)\(\)/' => [
                'desc' => __( 'A function with the same name was declared more than once. Check for duplicate function definitions or plugin conflicts.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.functions.php'
            ],

            // Warning: session_start(): Cannot send session cache limiter
            '/Warning: session_start\(\): Cannot send session cache limiter - headers already sent/' => [
                'desc' => __( 'Session start failed due to output sent before headers. Remove any output before session_start().', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/function.session-start.php'
            ],

            // Syntax error: unexpected token
            '/syntax error, unexpected token ";", expecting "function"/' => [
                'desc' => __( 'PHP encountered a syntax error where a function definition was expected but a semicolon was found. Check for missing function keyword or misplaced semicolon.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/functions.user-defined.php'
            ],

            // TypeError general pattern
            '/Uncaught TypeError: ([^(]+)\(\): Argument #\d+ \(\$\w+\) must be of type ([^,]+), ([^)]+) given/' => [
                'desc' => __( 'A function received an argument of an unexpected type. The specified argument requires a different data type than what was passed. Review the argument types and ensure they match the function\'s expectations.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.types.php'
            ],

            // TypeError: return value type mismatch
            '/Uncaught TypeError: ([^(]+)\(\): Return value must be of type ([^,]+), ([^)]+) returned/' => [
                'desc' => __( 'A function or method returned a value of the wrong type. Update the return statement to match the declared return type or adjust the type declaration.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.return'
            ],

            // Deprecated implicit nullable parameter
            '/Implicitly marking parameter \$(\w+) as nullable is deprecated/' => [
                'desc' => __( 'A function or method parameter is implicitly nullable. Explicitly declare the parameter with a nullable type using the ? type hint (e.g., ?string $param).', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/migration74.deprecated.php'
            ],

            // Cannot access offset of type string on string
            '/Uncaught TypeError: Cannot access offset of type string on string/' => [
                'desc' => __( 'Attempted to use array-style indexing on a string. Ensure the variable is an array before accessing its offset.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.types.array.php'
            ],

            // Undefined method
            '/Call to undefined method ([^(]+)\(\)/' => [
                'desc' => __( 'A method was called on a class that does not exist. Check class definition and method name spelling.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.oop5.basic.php'
            ],

            // Cannot access protected/private property
            '/Cannot access (protected|private) property ([^$]+)\$(\w+)/' => [
                'desc' => __( 'Code tried to access a non-public property directly. Use a public getter/setter method or change property visibility.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.oop5.visibility.php'
            ],

            // Call to private method error
            '/Uncaught Error: Call to private method ([^(]+)\(\) from scope/' => [
                'desc' => __( 'Code attempted to call a private method from outside its defining class. Change the method visibility to public/protected if it should be accessible, or call it from within the class itself.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.oop5.visibility.php'
            ],

            // Division by zero
            '/Division by zero/' => [
                'desc' => __( 'Code attempted to divide by zero. Ensure divisor is checked before performing division.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.operators.arithmetic.php'
            ],

            // Maximum function nesting level reached
            '/Maximum function nesting level of \'\d+\' reached/' => [
                'desc' => __( 'Likely caused by infinite recursion or excessive nested calls. Review function call stack for loops.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/function.debug-backtrace.php'
            ],

            // Call to a member function on array
            '/Call to a member function (\w+)\(\) on array/' => [
                'desc' => __( 'Code attempted to call a method on an array variable. Ensure variable is an object before calling methods.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.types.array.php'
            ],

            // Cannot use object as array
            '/Cannot use object of type (\w+) as array/' => [
                'desc' => __( 'Attempted to access an object as if it were an array. Use object property access instead of array syntax.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.types.object.php'
            ],

            // Too few arguments to function
            '/Too few arguments to function (\w+)/' => [
                'desc' => __( 'Function was called with fewer parameters than required. Review function definition and add missing arguments.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/functions.arguments.php'
            ],

            // Too many arguments to function
            '/Too many arguments to function (\w+)/' => [
                'desc' => __( 'Function was called with more parameters than allowed. Review function definition and remove extra arguments.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/functions.arguments.php'
            ],

            // Cannot redeclare class
            '/Cannot redeclare class (\w+)/' => [
                'desc' => __( 'A class was declared more than once, usually due to multiple includes. Use require_once or autoloading.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/language.oop5.basic.php'
            ],

            // WordPress database error: Illegal mix of collations
            '/WordPress database error Illegal mix of collations \(([^)]+)\) and \(([^)]+)\) for operation \'like\'/' => [
                'desc' => __( 'MySQL detected incompatible collations being compared in a LIKE operation. Ensure all relevant database columns use the same charset and collation, or convert them before comparison.', 'dev-debug-tools' ),
                'link' => 'https://dev.mysql.com/doc/refman/8.0/en/charset-collation-conversion.html'
            ],

            // WordPress database error: Table doesn't exist
            '/WordPress database error Table \'[^\']+\' doesn\'t exist/' => [
                'desc' => __( 'A database query failed because the specified table does not exist. This can happen if a plugin did not create its tables properly, or if the database is missing/corrupted. Try reinstalling or repairing the plugin, or manually creating the required tables.', 'dev-debug-tools' ),
                'link' => 'https://developer.wordpress.org/advanced-administration/repair-database/'
            ],

            // fgets() read failure (directory instead of file)
            '/fgets\(\): Read of \d+ bytes failed with errno=\d+ Is a directory/' => [
                'desc' => __( 'The fgets() function attempted to read from a directory instead of a file. Verify that the provided path points to a valid readable file, not a directory.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/function.fgets.php'
            ],

            // Deprecated constant
            '/Constant ([A-Z_]+) is deprecated/' => [
                'desc' => __( 'A constant used in the code has been deprecated. Review the PHP migration guide for alternatives or remove its usage if no longer needed.', 'dev-debug-tools' ),
                'link' => 'https://www.php.net/manual/en/migration84.deprecated.php'
            ],

        ];


        /**
         * Allow filtering of the map
         */
        $map = apply_filters( 'ddtt_help_map_debug_log', $map );

        return $map;
    } // End map()

}