<style>
.api-status-col {
	display: none;
}
.scanning:after {
	display: inline-block;
	animation: dotty steps(1,end) 1s infinite;
	content: "";
}
@keyframes dotty {
	0%   { content: ""; }
	25%  { content: "."; }
	50%  { content: ".."; }
	75%  { content: "..."; }
	100% { content: ""; }
}
</style>

<?php include 'header.php'; 

// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'api';
$current_url = ddtt_plugin_options_path( $tab );

// Get the rest routes
$wp_rest_server = rest_get_server();
$all_namespaces = $wp_rest_server->get_namespaces();
$all_routes = array_keys( $wp_rest_server->get_routes() );

// Add a button to validate
echo '<a id="validate-apis" class="button button-primary" href="#">Check All API Statuses</a><br><br>';

// Return the table
echo '<div class="full_width_container">
<table class="admin-large-table">
    <tr>
        <th>API</th>
		<th class="api-status-col" width="100px">Status Code</th>
		<th id="status-col" width="300px">Status</th>
    </tr>';

	// Iter
	foreach ( $all_routes as $route ) {
		if ( $route == '/' ) {
			continue;
		}

		// Get the URL
		$rest_url = rest_url( $route );

		// Link or not
		if ( strpos( $route, '(' ) === false ) {
			$display_route = '<a href="'.$rest_url.'" target="_blank">'.$route.'</a>';
			$code = '<span id="api'.str_replace( [ '/', '.' ], '_', $route ).'_code"></span>';
			$text = '<span id="api'.str_replace( [ '/', '.' ], '_', $route ).'" class="api-status" data-route="'.esc_html( $route ).'"><a class="button button-primary api-check" href="#">Check</a></span>';
		} else {
			$display_route = $route;
			$code = '';
			$text = '';
		}

		// Add the row
		echo '<tr>
			<td><span class="highlight-variable">'.wp_kses( $display_route, [ 'a' => [ 'href' => [] ] ] ).'</span></td>
			<td class="api-status-col">'.wp_kses( $code, [ 'span' => [ 'id' => [] ] ] ).'</td>
			<td>'.wp_kses( $text, [ 'span' => [ 'id' => [], 'class' => [], 'data-route' => [] ], 'a' => [ 'class' => [], 'href' => [] ] ] ).'</td>
		</tr>';
	}

echo '</table>
</div><br><br>';