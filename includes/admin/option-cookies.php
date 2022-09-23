<?php include 'header.php'; ?>

<p><strong>What are cookies?</strong> Cookies are small pieces of text sent to your browser by a website you visit. They help that website remember information about your visit, which can both make it easier to visit the site again and make the site more useful to you.</p>

<br>
<?php
// Get the cookies and sort them
$cookies = $_COOKIE;
ksort( $cookies );

// Return the table
echo '<div class="full_width_container">
    <table class="admin-large-table">
        <tr>
            <th>Cookie Name</th>
            <th>Value</th>
        </tr>';

        // Cycle through the options
        foreach( $cookies as $key => $cookie ) {
            echo '<tr>
                <td>'.esc_attr( $key ).'</td>
                <td>'.esc_html( $cookie ).'</td>
            </tr>';
        }

echo '</table>
</div>';
?>
<br>
<p>Note: Dots (.) and spaces ( ) in cookie names are being replaced with underscores (_).</p>