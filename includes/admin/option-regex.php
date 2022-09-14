<style>
.patterns th:first-child {
    width: 300px;
}
.form-table textarea {
    height: 18rem;
}
.form-table input,
.form-table textarea {
    width: 100%;
    max-width: 100% !important;
}
</style>

<?php include 'header.php'; ?>

<form method="post" action="options.php">
    <?php settings_fields( DDTT_PF.'group_regex' ); ?>
    <?php do_settings_sections( DDTT_PF.'group_regex' ); ?>
    <table class="form-table">

        <?php 
        // Add regex results
        if ( get_option( DDTT_GO_PF.'regex_string' ) && get_option( DDTT_GO_PF.'regex_string' ) != '' &&
            get_option( DDTT_GO_PF.'regex_pattern' ) && get_option( DDTT_GO_PF.'regex_pattern' ) != '' ) {

            $s = wp_kses_post( get_option( DDTT_GO_PF.'regex_string' ) );
            $r = sanitize_text_field( get_option( DDTT_GO_PF.'regex_pattern' ) );

            // Check if there is a global modifier
            $e = explode( '/', $r );
            $pop = array_pop( $e );
            if ( strpos( strtolower( $pop ), 'g' ) !== false ) {
                $test = 'This tester is using "preg_match_all()", so the global modifier "/g" is not necessary. Please remove this from your pattern and try again.';

            // Continue with pattern
            } elseif ( preg_match_all( $r, $s, $m ) ){
                $ms = [];
                foreach( $m[0] as $mi ) {
                    $ms[] = htmlentities( $mi );
                }
                $test = $ms;

            } else {
                $test = 'No matches found. Check your pattern for mistakes.';
            }
        } else {
            $test = 'Enter a string and pattern in the fields below and save the changes to see the results here.';
        }
        
        // Convert print
        if ( is_array( $test ) ) {
            $print = [];
            foreach ( $test as $key => $t ) {
                $print[] = '<span class="indent">['.$key.'] => '.$t.'</span>';
            }
            $print_this = '<code style="padding: 0;">Array
                <br>(
                <br>'.implode( '<br>', $print ).'
                <br>)</code>';
        } else {
            $print_this = $test;
        }
        
        ?>
        <tr>
            <th>Test Results</th>
            <td><?php echo wp_kses_post( $print_this ); ?></td>
        </tr>

        <?php 
        $allowed_html = ddtt_wp_kses_allowed_html();

        echo wp_kses( ddtt_options_tr( 'regex_string', 'String', 'textarea', '<br>// Enter a string you want to search through for all instances', [ 'required' => true ] ), $allowed_html );

        echo wp_kses( ddtt_options_tr( 'regex_pattern', 'Pattern', 'text', '<br>// Enter your regex pattern <strong>including the wrapping forward slashes</strong>', [ 'width' => '100%', 'required' => true, 'pattern' => '\/.*?\/(g|i|m|s|x|A|D|S|U|X|J|u)*' ] ), $allowed_html );
        ?>

    </table>
    <?php submit_button(); ?>
</form>
<br><br>

<?php
// Regex examples
$examples = [
    [
        'string' => 'Let us look for all user merge tags such as {user:first_name} and {user:last_name}.',
        'regex' => '/\{user\:.*?}/'
    ],
    [
        'string' => 'define("FORCE_SSL_ADMIN" , TRUE);',
        'regex' => '/define\s*\(\s*([\'"])FORCE_SSL_ADMIN\1\s*\,\s*(true|1)\s*\)/i'
    ]
];
?>
<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th>String</th>
            <th>Pattern</th>
            <th>Result</th>
        </tr>
        <?php 

        foreach ( $examples as $example ) {
            
            // Perform the regex
            $string = $example[ 'string' ];
            $regex = $example[ 'regex' ];
            if ( preg_match_all( $regex, $string, $matches ) ){
                $all_matches = $matches[0];
            }
            $print = [];
            foreach ( $all_matches as $key => $am ) {
                $print[] = '<span class="indent">['.$key.'] => '.$am.'</span>';
            }

            // Print it
            echo '<tr>
                <td>'.htmlentities( $string ).'</td>
                <td>'.htmlentities( $regex ).'</td>
                <td><code style="padding: 0;">Array
                    <br>(
                    <br>'.wp_kses_post( implode( '<br>', $print ) ).'
                    <br>)</code></td>
            </tr>';
        }

        ?>
    </table>
</div>
<br><br>

<?php 
// Regex Patterns Cheat Sheet
$cheats = [
    [ 'Use \ before special characters' ],
    [ '[]', 'Matches characters in brackets' ],
    [ '[^ ]', 'Matches characters NOT in brackets' ],
    [ '|', 'Either or' ],
    [ '( )', 'Group' ],
    [ '*', '0 or more' ],
    [ '+', '1 or more' ],
    [ '?', '0 or 1 (aka optional)'],
    [ '{3}', 'Exact number 3' ],
    [ '{3,4}', 'Range of numbers (minimum 3, maximum 4)' ],
];
?>
<div class="full_width_container">
    <table class="admin-large-table patterns">
        <tr>
            <th>Pattern</th>
            <th>Description</th>
        </tr>
        <?php 

        foreach ( $cheats as $cheat ) {
            if ( isset( $cheat[1] ) ) {
                echo '<tr>
                    <td>'.esc_html( $cheat[0] ).'</td>
                    <td>'.wp_kses_post( $cheat[1] ).'</td>
                </tr>';
            } else {
                echo '<tr><td colspan="2">'.wp_kses_post( $cheat[0] ).'</td></tr>';
            }
        }

        ?>
    </table>
</div>
<br><br>

<?php 
// Regex pattern examples
$patterns = [
    [ '/(.)/', 'Search for all characters, including spaces and punctuation marks' ],
    [ '/\s/', 'Search for whitespaces' ],
    [ '/\S/', 'Search for non-whitespaces' ],
    [ '/\d/', 'Search for digits' ],
    [ '/\D/', 'Search for non-digits' ],
    [ '/\w/', 'Search for word characters (a-z, A-Z, 0-9, _)' ],
    [ '/\W/', 'Search for non-word characters' ],
    [ '/\b/', 'Search for word boundary (the character wrapping a word such as a space between words \bWord or Word\b)' ],
    [ '/\B/', 'Search for non-word boundary' ],
    [ '/(a|b)/', 'Group and search for a or b' ],
    [ '/[abc]/', 'Search for a, b, and c' ],
    [ '/[^abc]/', 'Anything other than a, b, and c' ],
    [ '/[a-z]/', 'Range: All lower case characters' ],
    [ '/[A-Z]/', 'Range: All upper case characters' ],
    [ '/[a-zA-Z]/', 'Range: All upper and lower case characters' ],
    [ '/[0-9]/', 'Range: All digits 0 through 9' ],
    [ '/M*/', 'Find zero or more M\'s, include blank arrays' ],
    [ '/M.*/', 'Find all characters after the first M' ],
    [ '/M.*m/', 'Find all characters after the first M until the last m' ],
    [ '/M+/', 'Find zero or more M\'s, not including blank arrays' ],
    [ '/\(.*?\)/', 'Find all instances that start with ( and end with ), includes ()' ],
    [ '/M{2}/', 'Search for 2 M\'s in a row' ],
    [ '/M{1,2}/', 'Search for 1 M in a row or 2 M\'s in a row' ],
    [ '/M{1,}/', 'Search for 1 or more M\'s in a row' ],
    [ '/^M/', 'Check if string starts with M' ],
    [ '/e$/', 'Check if string ends with e' ],
    [ '/^M.*e$/', 'Check if string starts with M and ends with e' ],
];
?>
<div class="full_width_container">
    <table class="admin-large-table patterns">
        <tr>
            <th>Pattern</th>
            <th>Description</th>
        </tr>
        <?php 

        foreach ( $patterns as $pattern ) {
            echo '<tr>
                <td>'.esc_html( $pattern[0] ).'</td>
                <td>'.wp_kses_post( $pattern[1] ).'</td>
            </tr>';
        }

        ?>
    </table>
</div>
<br><br>

<?php 
// Regex Pattern Modifiers
$mods = [
    [ 'Everything should be wrapped with "/ and /" accept available <a href="https://www.php.net/manual/en/reference.pcre.pattern.modifiers.php" target="_blank">pattern modifiers</a> which should go after the last "/" (ie. /pattern/im)' ],
    [ '/i', 'If this modifier is set, letters in the pattern match both upper and lower case letters.' ],
    [ '/m', 'By default, PCRE treats the subject string as consisting of a single "line" of characters (even if it actually contains several newlines). The "start of line" metacharacter (^) matches only at the start of the string, while the "end of line" metacharacter ($) matches only at the end of the string, or before a terminating newline (unless D modifier is set). This is the same as Perl. When this modifier is set, the "start of line" and "end of line" constructs match immediately following or immediately before any newline in the subject string, respectively, as well as at the very start and end. This is equivalent to Perl\'s /m modifier. If there are no "\n" characters in a subject string, or no occurrences of ^ or $ in a pattern, setting this modifier has no effect.' ],
    [ '/s', 'If this modifier is set, a dot metacharacter in the pattern matches all characters, including newlines. Without it, newlines are excluded. This modifier is equivalent to Perl\'s /s modifier. A negative class such as [^a] always matches a newline character, independent of the setting of this modifier.' ],
    [ '/x', 'If this modifier is set, whitespace data characters in the pattern are totally ignored except when escaped or inside a character class, and characters between an unescaped # outside a character class and the next newline character, inclusive, are also ignored. This is equivalent to Perl\'s /x modifier, and makes it possible to include commentary inside complicated patterns. Note, however, that this applies only to data characters. Whitespace characters may never appear within special character sequences in a pattern, for example within the sequence (?( which introduces a conditional subpattern.' ],
    [ '/A', 'If this modifier is set, the pattern is forced to be "anchored", that is, it is constrained to match only at the start of the string which is being searched (the "subject string"). This effect can also be achieved by appropriate constructs in the pattern itself, which is the only way to do it in Perl.' ],
    [ '/D', 'If this modifier is set, a dollar metacharacter in the pattern matches only at the end of the subject string. Without this modifier, a dollar also matches immediately before the final character if it is a newline (but not before any other newlines). This modifier is ignored if m modifier is set. There is no equivalent to this modifier in Perl.' ],
    [ '/S', 'When a pattern is going to be used several times, it is worth spending more time analyzing it in order to speed up the time taken for matching. If this modifier is set, then this extra analysis is performed. At present, studying a pattern is useful only for non-anchored patterns that do not have a single fixed starting character.' ],
    [ '/U', 'This modifier inverts the "greediness" of the quantifiers so that they are not greedy by default, but become greedy if followed by ?. It is not compatible with Perl. It can also be set by a (?U) modifier setting within the pattern or by a question mark behind a quantifier (e.g. .*?).' ],
    [ '/X', 'This modifier turns on additional functionality of PCRE that is incompatible with Perl. Any backslash in a pattern that is followed by a letter that has no special meaning causes an error, thus reserving these combinations for future expansion. By default, as in Perl, a backslash followed by a letter with no special meaning is treated as a literal. There are at present no other features controlled by this modifier.' ],
    [ '/J', 'The (?J) internal option setting changes the local PCRE_DUPNAMES option. Allow duplicate names for subpatterns. As of PHP 7.2.0 J is supported as modifier as well.' ],
    [ '/u', 'This modifier turns on additional functionality of PCRE that is incompatible with Perl. Pattern and subject strings are treated as UTF-8. An invalid subject will cause the preg_* function to match nothing; an invalid pattern will trigger an error of level E_WARNING. Five and six octet UTF-8 sequences are regarded as invalid.' ]
];
?>
<div class="full_width_container">
    <table class="admin-large-table patterns">
        <tr>
            <th>Modifier</th>
            <th>Description</th>
        </tr>
        <?php 

        foreach ( $mods as $mod ) {
            if ( isset( $mod[1] ) ) {
                echo '<tr>
                    <td>'.esc_html( $mod[0] ).'</td>
                    <td>'.wp_kses_post( $mod[1] ).'</td>
                </tr>';
            } else {
                echo '<tr><td colspan="2">'.wp_kses_post( $mod[0] ).'</td></tr>';
            }
        }

        ?>
    </table>
</div>
<br><br>