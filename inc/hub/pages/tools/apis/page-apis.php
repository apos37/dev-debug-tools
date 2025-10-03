<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

// Get the rest routes
$wp_rest_server = rest_get_server();
$all_namespaces = $wp_rest_server->get_namespaces();
$all_routes = array_keys( $wp_rest_server->get_routes() );
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'REST APIs', 'dev-debug-tools' ); ?></h2>
        <p>
            <?php
              $apiRoot = esc_url( rest_url( '/' ) );
              printf(
                // Translators: The REST API root URL as a clickable link.
                esc_html__( 'A list of the site\'s registered REST APIs. Your REST API root is: %s', 'dev-debug-tools' ),
                '<a href="' . esc_url( $apiRoot ) . '" target="_blank">' . esc_html( $apiRoot ) . '</a>'
              );
            ?>
        </p>
    </div>
</div>

<section id="ddtt-tool-section" class="ddtt-apis ddtt-section-content">
    <h3><?php echo esc_html__( 'Total # of APIs:', 'dev-debug-tools' ); ?> <span id="ddtt-total-apis"><?php echo esc_html( count( $all_routes ) ); ?></span></h3>

    <div class="ddtt-action-buttons">
        <button id="ddtt-check-all-apis" class="ddtt-button"><?php echo esc_html__( 'Check All API Statuses', 'dev-debug-tools' ); ?></button>
        <button id="ddtt-stop-checking-all-apis" class="ddtt-button" style="display: none;"><?php echo esc_html__( 'Stop Checking', 'dev-debug-tools' ); ?></button>
    </div>

    <table class="ddtt-table">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Route', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Status Code', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Status', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Actions', 'dev-debug-tools' ); ?></th>
            </tr>
        </thead>
        <?php
        $main_routes = [];
        $all_main_routes = [];

        // Step 1: collect all main routes first
        foreach ( $all_routes as $route ) {
            if ( $route === '/' ) {
                continue;
            }
            if ( strpos( $route, '(' ) === false ) {
                $main_routes[ $route ] = []; // init sub-route array
                $all_main_routes[] = $route;
            }
        }

        // Step 2: attach sub-routes to their correct main route
        foreach ( $all_routes as $route ) {
            if ( strpos( $route, '(' ) !== false ) {
                // find the longest matching main route
                $parent = '';
                foreach ( $all_main_routes as $main ) {
                    if ( str_starts_with( $route, $main ) && strlen( $main ) > strlen( $parent ) ) {
                        $parent = $main;
                    }
                }
                if ( $parent ) {
                    $main_routes[ $parent ][] = $route;
                }
            }
        }

        // Step 3: alphabetize main routes
        ksort( $main_routes );

        // Step 4: alphabetize sub-routes for each main route
        foreach ( $main_routes as $main => &$subs ) {
            sort( $subs );
        }
        unset( $subs ); // break reference
        ?>

        <tbody>
        <?php foreach ( $main_routes as $main => $subs ) : 
            $rest_url = rest_url( $main );

            $code = '<span id="ddtt_api_' . str_replace( [ '/', '.' ], '_', $main ) . '_code"></span>';

            $status = '<span id="ddtt_api_' . str_replace( [ '/', '.' ], '_', $main ) . '_status" class="ddtt-api-status" data-route="' . esc_attr( $main ) . '"></span>';

            $check = '<a id="ddtt_api_' . str_replace( [ '/', '.' ], '_', $main ) . '" class="ddtt-button ddtt-check-api" href="#" data-route="' . esc_attr( $main ) . '">' . __( 'Check', 'dev-debug-tools' ) . '</a>';

            $view = '<a class="ddtt-button ddtt-view-api" href="' . esc_url( $rest_url ) . '" target="_blank">' . __( 'View', 'dev-debug-tools' ) . '</a>';

            // Build sub-routes HTML if any
            $subs_html = '';
            if ( ! empty( $subs ) ) {
                $subs_html .= '<div class="ddtt-sub-routes"><code class="ddtt-table-code"><pre><ul>';
                foreach ( $subs as $sub ) {
                    $subs_html .= '<li>' . esc_html( $sub ) . '</li>';
                }
                $subs_html .= '</ul></pre></code></div>';
            }
            ?>
            <tr>
                <td>
                    <span class="ddtt-highlight-variable"><?php echo esc_html( $main ); ?></span>
                    <?php echo wp_kses_post( $subs_html ); ?>
                </td>
                <td><?php echo wp_kses_post( $code ); ?></td>
                <td><?php echo wp_kses_post( $status ); ?></td>
                <td><?php echo wp_kses_post( $check ); ?> <?php echo wp_kses_post( $view ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>

    </table>
</section>