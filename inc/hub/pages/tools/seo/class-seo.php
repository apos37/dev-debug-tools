<?php
/**
 * SEO (robots.txt and sitemap viewer)
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Seo {

    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_seo_nonce';


    /**
     * Option key for viewer customizations
     *
     * @var string
     */
    const CUSTOMIZATIONS_KEY = 'ddtt_seo_viewer_customizations';


    /**
     * Max response body to cache in a transient (bytes)
     *
     * @var int
     */
    const MAX_CACHE_BYTES = 2097152;


    /**
     * Max child sitemaps to status check
     *
     * @var int
     */
    const MAX_CHILD_CHECKS = 50;


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Seo $instance = null;


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
        $this->handle_button_actions();
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddtt_seo_get_view', [ $this, 'ajax_get_view' ] );
        add_action( 'wp_ajax_nopriv_ddtt_seo_get_view', '__return_false' );
    } // End __construct()


    /**
     * Tabs
     *
     * @return array
     */
    public static function sections() : array {
        return [
            'robots'  => __( 'Robots.txt', 'dev-debug-tools' ),
            'sitemap' => __( 'Sitemap', 'dev-debug-tools' ),
            'llms'    => __( 'LLMs.txt', 'dev-debug-tools' ),
        ];
    } // End sections()


    /**
     * Get the current subsection
     *
     * @return string
     */
    public static function get_current_subsection() : string {
        $subsection = isset( $_GET[ 's' ] ) ? sanitize_key( wp_unslash( $_GET[ 's' ] ) ) : 'robots'; // phpcs:ignore
        return isset( self::sections()[ $subsection ] ) ? $subsection : 'robots';
    } // End get_current_subsection()


    /**
     * Get the stored viewer customizations
     *
     * @return array
     */
    public static function get_customizations() : array {
        $stored = get_option( self::CUSTOMIZATIONS_KEY, [] );
        $stored = is_array( $stored ) ? $stored : [];

        return [
            'type'     => isset( $stored[ 'type' ] ) && $stored[ 'type' ] === 'code' ? 'code' : 'output',
            'sitemap'  => isset( $stored[ 'sitemap' ] ) ? esc_url_raw( $stored[ 'sitemap' ] ) : '',
            'llms'     => isset( $stored[ 'llms' ] ) ? esc_url_raw( $stored[ 'llms' ] ) : '',
            'per_page' => isset( $stored[ 'per_page' ] ) ? absint( $stored[ 'per_page' ] ) : 100,
            'search'   => isset( $stored[ 'search' ] ) ? sanitize_text_field( $stored[ 'search' ] ) : '',
        ];
    } // End get_customizations()


    /**
     * Resolve a path setting to a full URL on this site
     *
     * @param string $path
     * @return string
     */
    public static function resolve_url( string $path ) : string {
        $path = trim( $path );
        if ( $path === '' ) {
            return '';
        }

        if ( preg_match( '#^https?://#i', $path ) ) {
            return esc_url_raw( $path );
        }

        return esc_url_raw( home_url( '/' . ltrim( $path, '/' ) ) );
    } // End resolve_url()


    /**
     * Get the robots.txt URL
     *
     * @return string
     */
    public static function get_robots_url() : string {
        $path = sanitize_text_field( get_option( 'ddtt_seo_robots_path', 'robots.txt' ) );
        return self::resolve_url( $path ? $path : 'robots.txt' );
    } // End get_robots_url()


    /**
     * Detect the active sitemap provider
     *
     * @return array [ 'label' => string, 'url' => string ]
     */
    public static function detect_provider() : array {
        $providers = [];

        if ( defined( 'WPSEO_VERSION' ) ) {
            $providers[] = [ 'label' => 'Yoast SEO', 'url' => home_url( '/sitemap_index.xml' ) ];
        }

        if ( class_exists( '\RankMath' ) ) {
            $providers[] = [ 'label' => 'Rank Math', 'url' => home_url( '/sitemap_index.xml' ) ];
        }

        if ( defined( 'AIOSEO_VERSION' ) ) {
            $providers[] = [ 'label' => 'All in One SEO', 'url' => home_url( '/sitemap.xml' ) ];
        }

        if ( defined( 'SEOPRESS_VERSION' ) ) {
            $providers[] = [ 'label' => 'SEOPress', 'url' => home_url( '/sitemaps.xml' ) ];
        }

        $core_url = function_exists( 'get_sitemap_url' ) ? get_sitemap_url( 'index' ) : false;
        if ( $core_url ) {
            $providers[] = [ 'label' => 'WordPress Core', 'url' => $core_url ];
        }

        return $providers;
    } // End detect_provider()


    /**
     * Get the main sitemap URLs (settings, then robots.txt, then detected provider)
     *
     * @return array [ url => label ]
     */
    public static function get_main_sitemaps() : array {
        $sitemaps = [];

        $main_path = sanitize_text_field( get_option( 'ddtt_seo_sitemap_path', '' ) );
        if ( $main_path ) {
            $sitemaps[ self::resolve_url( $main_path ) ] = __( 'Main Sitemap', 'dev-debug-tools' );
        } else {
            $core_provider = [];
            foreach ( self::detect_provider() as $provider ) {
                if ( $provider[ 'label' ] === 'WordPress Core' ) {
                    $core_provider = $provider;
                    continue;
                }

                $provider_url = esc_url_raw( $provider[ 'url' ] );
                $provider_response = self::fetch( $provider_url );
                if ( ! $provider_response[ 'error' ] && $provider_response[ 'code' ] === 200 && ! isset( $sitemaps[ $provider_url ] ) ) {
                    $sitemaps[ $provider_url ] = $provider[ 'label' ];
                }
            }

            $robots = self::fetch( self::get_robots_url() );
            foreach ( self::parse_robots( $robots[ 'body' ] )[ 'sitemaps' ] as $robots_sitemap ) {
                $robots_sitemap = esc_url_raw( $robots_sitemap );
                if ( preg_match( '#^https?://#i', $robots_sitemap ) && ! isset( $sitemaps[ $robots_sitemap ] ) ) {
                    $sitemaps[ $robots_sitemap ] = __( 'From robots.txt', 'dev-debug-tools' );
                }
            }

            if ( empty( $sitemaps ) && ! empty( $core_provider ) ) {
                $sitemaps[ esc_url_raw( $core_provider[ 'url' ] ) ] = $core_provider[ 'label' ];
            }
        }

        $extra = get_option( 'ddtt_seo_extra_sitemaps', [] );
        if ( is_array( $extra ) ) {
            foreach ( $extra as $extra_path ) {
                $extra_url = self::resolve_url( sanitize_text_field( $extra_path ) );
                if ( $extra_url && ! isset( $sitemaps[ $extra_url ] ) ) {
                    $sitemaps[ $extra_url ] = __( 'Additional Sitemap', 'dev-debug-tools' );
                }
            }
        }

        return $sitemaps;
    } // End get_main_sitemaps()


    /**
     * Get every sitemap the viewer is allowed to fetch, grouped by main sitemap
     *
     * @return array [ main_url => [ 'label' => string, 'children' => string[] ] ]
     */
    public static function get_sitemap_tree() : array {
        $tree = [];

        foreach ( self::get_main_sitemaps() as $url => $label ) {
            $children = [];
            $response = self::fetch( $url );
            if ( ! $response[ 'error' ] && $response[ 'code' ] === 200 ) {
                $parsed = self::parse_sitemap( $response[ 'body' ] );
                if ( ! is_wp_error( $parsed ) && $parsed[ 'type' ] === 'index' ) {
                    $children = array_values( array_filter( wp_list_pluck( $parsed[ 'items' ], 'loc' ) ) );
                }
            }

            $tree[ $url ] = [
                'label'    => $label,
                'children' => $children,
            ];
        }

        return $tree;
    } // End get_sitemap_tree()


    /**
     * Validate a requested sitemap URL against the allowed list
     *
     * @param string $url
     * @return string The allowed URL, or the first main sitemap
     */
    public static function validate_sitemap_url( string $url ) : string {
        $tree = self::get_sitemap_tree();
        if ( empty( $tree ) ) {
            return '';
        }

        foreach ( $tree as $main_url => $data ) {
            if ( $url === $main_url || in_array( $url, $data[ 'children' ], true ) ) {
                return $url;
            }
        }

        return (string) array_key_first( $tree );
    } // End validate_sitemap_url()


    /**
     * Fetch a URL with caching
     *
     * @param string $url
     * @param bool   $force Skip the cache
     * @return array
     */
    public static function fetch( string $url, bool $force = false ) : array {
        $result = [
            'url'          => $url,
            'final_url'    => $url,
            'redirects'    => 0,
            'code'         => 0,
            'content_type' => '',
            'headers'      => [],
            'body'         => '',
            'size'         => 0,
            'time'         => 0,
            'error'        => '',
            'fetched'      => time(),
        ];

        if ( ! $url ) {
            $result[ 'error' ] = __( 'No URL provided.', 'dev-debug-tools' );
            return $result;
        }

        $cache_minutes = absint( get_option( 'ddtt_seo_cache_minutes', 5 ) );
        $cache_key = 'ddtt_seo_' . md5( $url . '|' . get_current_blog_id() );

        if ( ! $force && $cache_minutes > 0 ) {
            $cached = get_transient( $cache_key );
            if ( is_array( $cached ) ) {
                return $cached;
            }
        }

        $start = microtime( true );
        $response = wp_remote_get( add_query_arg( 'ddtt_nocache', time(), $url ), [
            'timeout'             => 15,
            'redirection'         => 5,
            'sslverify'           => false,
            'limit_response_size' => 52428800,
            'headers'             => [ 'Cache-Control' => 'no-cache' ],
        ] );
        $result[ 'time' ] = round( ( microtime( true ) - $start ) * 1000 );

        if ( is_wp_error( $response ) ) {
            $result[ 'error' ] = $response->get_error_message();
            return $result;
        }

        if ( isset( $response[ 'http_response' ] ) && $response[ 'http_response' ] instanceof \WP_HTTP_Requests_Response ) {
            $response_object = $response[ 'http_response' ]->get_response_object();
            $result[ 'final_url' ] = remove_query_arg( 'ddtt_nocache', $response_object->url );
            $result[ 'redirects' ] = (int) $response_object->redirects;
        }

        $headers = wp_remote_retrieve_headers( $response );
        $result[ 'headers' ] = is_object( $headers ) && method_exists( $headers, 'getAll' ) ? $headers->getAll() : (array) $headers;
        $result[ 'code' ] = (int) wp_remote_retrieve_response_code( $response );
        $result[ 'content_type' ] = (string) wp_remote_retrieve_header( $response, 'content-type' );
        $result[ 'body' ] = (string) wp_remote_retrieve_body( $response );
        $result[ 'size' ] = strlen( $result[ 'body' ] );

        if ( $cache_minutes > 0 && $result[ 'size' ] <= self::MAX_CACHE_BYTES ) {
            set_transient( $cache_key, $result, $cache_minutes * MINUTE_IN_SECONDS );
        }

        return $result;
    } // End fetch()


    /**
     * Parse robots.txt into groups and sitemap lines
     *
     * @param string $body
     * @return array
     */
    public static function parse_robots( string $body ) : array {
        $parsed = [
            'rules'    => [],
            'sitemaps' => [],
            'issues'   => [],
        ];

        $known = [ 'user-agent', 'disallow', 'allow', 'sitemap', 'crawl-delay', 'host', 'clean-param' ];
        $current_agents = [];
        $last_was_agent = false;
        $lines = preg_split( '/\r\n|\r|\n/', $body );

        foreach ( $lines as $index => $line ) {
            $line_number = $index + 1;
            $line = trim( preg_replace( '/#.*$/', '', $line ) );
            if ( $line === '' ) {
                continue;
            }

            if ( ! preg_match( '/^([A-Za-z-]+)\s*:\s*(.*)$/', $line, $matches ) ) {
                /* translators: %d: line number */
                $parsed[ 'issues' ][] = [ 'warning', sprintf( __( 'Line %d is not a valid "directive: value" pair.', 'dev-debug-tools' ), $line_number ) ];
                continue;
            }

            $directive = strtolower( $matches[ 1 ] );
            $value = trim( $matches[ 2 ] );

            if ( ! in_array( $directive, $known, true ) ) {
                /* translators: 1: directive, 2: line number */
                $parsed[ 'issues' ][] = [ 'info', sprintf( __( 'Unknown directive "%1$s" on line %2$d. Most crawlers will ignore it.', 'dev-debug-tools' ), $matches[ 1 ], $line_number ) ];
                continue;
            }

            if ( $directive === 'sitemap' ) {
                $parsed[ 'sitemaps' ][] = $value;
                continue;
            }

            if ( $directive === 'user-agent' ) {
                $current_agents = $last_was_agent ? array_merge( $current_agents, [ $value ] ) : [ $value ];
                $last_was_agent = true;
                continue;
            }

            $last_was_agent = false;

            if ( empty( $current_agents ) ) {
                /* translators: 1: directive, 2: line number */
                $parsed[ 'issues' ][] = [ 'warning', sprintf( __( '"%1$s" on line %2$d appears before any User-agent line, so crawlers will ignore it.', 'dev-debug-tools' ), $matches[ 1 ], $line_number ) ];
                continue;
            }

            foreach ( $current_agents as $agent ) {
                $parsed[ 'rules' ][] = [
                    'agent'     => $agent,
                    'directive' => $directive,
                    'value'     => $value,
                    'line'      => $line_number,
                ];
            }
        }

        return $parsed;
    } // End parse_robots()


    /**
     * Parse sitemap XML
     *
     * @param string $body
     * @return array|\WP_Error
     */
    public static function parse_sitemap( string $body ) : array|\WP_Error {
        if ( trim( $body ) === '' ) {
            return new \WP_Error( 'empty', __( 'The sitemap is empty.', 'dev-debug-tools' ) );
        }

        if ( str_starts_with( $body, "\x1f\x8b" ) && function_exists( 'gzdecode' ) ) {
            $decoded = gzdecode( $body );
            $body = $decoded !== false ? $decoded : $body;
        }

        $max_mb = absint( get_option( 'ddtt_max_log_size', 10 ) );
        if ( $max_mb > 0 && strlen( $body ) > $max_mb * MB_IN_BYTES ) {
            /* translators: %d: max size in MB */
            return new \WP_Error( 'too_large', sprintf( __( 'The sitemap is larger than the %d MB display limit set in Logging settings.', 'dev-debug-tools' ), $max_mb ) );
        }

        $previous = libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA );
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );

        if ( $xml === false ) {
            $message = __( 'Invalid XML.', 'dev-debug-tools' );
            if ( ! empty( $errors ) ) {
                /* translators: 1: error message, 2: line number */
                $message = sprintf( __( 'Invalid XML: %1$s (line %2$d)', 'dev-debug-tools' ), trim( $errors[ 0 ]->message ), $errors[ 0 ]->line );
            }
            return new \WP_Error( 'invalid_xml', $message );
        }

        $root = $xml->getName();
        $type = $root === 'sitemapindex' ? 'index' : ( $root === 'urlset' ? 'urlset' : 'unknown' );
        $nodes = $type === 'index' ? $xml->sitemap : $xml->url;

        $items = [];
        if ( $nodes ) {
            foreach ( $nodes as $node ) {
                $items[] = [
                    'loc'        => trim( (string) $node->loc ),
                    'lastmod'    => trim( (string) $node->lastmod ),
                    'changefreq' => trim( (string) $node->changefreq ),
                    'priority'   => trim( (string) $node->priority ),
                ];
            }
        }

        return [
            'type'  => $type,
            'root'  => $root,
            'items' => $items,
            'xsl'   => (bool) preg_match( '/<\?xml-stylesheet[^>]*\?>/i', substr( $body, 0, 1000 ) ),
            'size'  => strlen( $body ),
        ];
    } // End parse_sitemap()


    /**
     * Shared response diagnostics
     *
     * @param array  $response
     * @param string $expected_type Partial content type expected
     * @return array
     */
    private static function diagnose_response( array $response, string $expected_type ) : array {
        $issues = [];

        if ( $response[ 'error' ] ) {
            /* translators: %s: error message */
            $issues[] = [ 'error', sprintf( __( 'Request failed: %s. If this is a staging site, check for basic auth or blocked loopback requests.', 'dev-debug-tools' ), $response[ 'error' ] ) ];
            return $issues;
        }

        if ( $response[ 'code' ] !== 200 ) {
            /* translators: %d: HTTP status code */
            $issues[] = [ 'error', sprintf( __( 'Returned HTTP %d instead of 200.', 'dev-debug-tools' ), $response[ 'code' ] ) ];
        }

        if ( $response[ 'content_type' ] && stripos( $response[ 'content_type' ], $expected_type ) === false ) {
            /* translators: 1: actual content type, 2: expected content type */
            $issues[] = [ 'warning', sprintf( __( 'Content-Type is "%1$s"; expected "%2$s".', 'dev-debug-tools' ), $response[ 'content_type' ], $expected_type ) ];
        }

        if ( $response[ 'redirects' ] > 0 ) {
            /* translators: 1: redirect count, 2: final URL */
            $issues[] = [ 'info', sprintf( __( 'Redirected %1$d time(s) to %2$s.', 'dev-debug-tools' ), $response[ 'redirects' ], $response[ 'final_url' ] ) ];
        }

        $headers = array_change_key_case( $response[ 'headers' ] );
        foreach ( [ 'age', 'x-cache', 'cf-cache-status', 'x-litespeed-cache', 'x-proxy-cache' ] as $cache_header ) {
            if ( isset( $headers[ $cache_header ] ) ) {
                $header_value = is_array( $headers[ $cache_header ] ) ? implode( ', ', $headers[ $cache_header ] ) : $headers[ $cache_header ];
                /* translators: 1: header name, 2: header value */
                $issues[] = [ 'info', sprintf( __( 'Cache header detected (%1$s: %2$s). Crawlers may see a cached copy.', 'dev-debug-tools' ), $cache_header, $header_value ) ];
            }
        }

        if ( isset( $headers[ 'x-robots-tag' ] ) && stripos( implode( ',', (array) $headers[ 'x-robots-tag' ] ), 'noindex' ) !== false && $expected_type === 'xml' ) {
            $issues[] = [ 'info', __( 'X-Robots-Tag: noindex is set. This is normal for sitemaps and does not stop them from being read.', 'dev-debug-tools' ) ];
        }

        return $issues;
    } // End diagnose_response()


    /**
     * Diagnose robots.txt
     *
     * @param array $response
     * @return array
     */
    public static function diagnose_robots( array $response ) : array {
        $issues = self::diagnose_response( $response, 'text/plain' );
        if ( $response[ 'error' ] ) {
            return $issues;
        }

        $home_path = (string) wp_parse_url( home_url(), PHP_URL_PATH );
        if ( trim( $home_path, '/' ) !== '' ) {
            $issues[] = [ 'warning', __( 'WordPress is installed in a subdirectory. Crawlers only read robots.txt at the root of the domain, so this file is likely ignored.', 'dev-debug-tools' ) ];
        }

        if ( file_exists( ABSPATH . 'robots.txt' ) ) {
            $issues[] = [ 'info', __( 'A physical robots.txt file exists. It overrides the virtual file, so the robots_txt filter and SEO plugin robots settings have no effect.', 'dev-debug-tools' ) ];
        } else {
            $issues[] = [ 'info', __( 'robots.txt is virtual (generated by WordPress or a plugin through the robots_txt filter).', 'dev-debug-tools' ) ];
        }

        if ( $response[ 'size' ] > 512000 ) {
            $issues[] = [ 'warning', __( 'File is larger than 500 KiB. Google ignores content past that limit.', 'dev-debug-tools' ) ];
        }

        if ( $response[ 'code' ] !== 200 ) {
            return $issues;
        }

        $parsed = self::parse_robots( $response[ 'body' ] );
        $issues = array_merge( $issues, $parsed[ 'issues' ] );

        $blocks_all = false;
        $blocks_admin = false;
        $allows_ajax = false;
        foreach ( $parsed[ 'rules' ] as $rule ) {
            $value = $rule[ 'value' ];

            if ( $rule[ 'agent' ] === '*' && $rule[ 'directive' ] === 'disallow' && $value === '/' ) {
                $blocks_all = true;
            }

            if ( $rule[ 'directive' ] === 'disallow' && $value !== '' ) {
                if ( str_starts_with( $value, '/wp-content' ) || str_starts_with( $value, '/wp-includes' ) ) {
                    /* translators: 1: path, 2: user agent */
                    $issues[] = [ 'warning', sprintf( __( 'Disallow "%1$s" for "%2$s" may block CSS, JS or images that crawlers need to render pages.', 'dev-debug-tools' ), $value, $rule[ 'agent' ] ) ];
                }
                if ( preg_match( '/\.(css|js)(\$|\*)?$/i', $value ) ) {
                    /* translators: %s: path */
                    $issues[] = [ 'warning', sprintf( __( 'Disallow "%s" blocks stylesheets or scripts.', 'dev-debug-tools' ), $value ) ];
                }
                if ( $value === '/wp-admin/' ) {
                    $blocks_admin = true;
                }
            }

            if ( $rule[ 'directive' ] === 'allow' && str_contains( $value, 'admin-ajax.php' ) ) {
                $allows_ajax = true;
            }

            if ( $rule[ 'directive' ] === 'crawl-delay' ) {
                $issues[] = [ 'info', __( 'Crawl-delay is set. Google ignores it; Bing and others respect it.', 'dev-debug-tools' ) ];
            }
        }

        $blog_public = get_option( 'blog_public' ) !== '0';
        if ( $blog_public && $blocks_all ) {
            $issues[] = [ 'error', __( 'All crawlers are blocked with "Disallow: /" but the site is set to be visible to search engines.', 'dev-debug-tools' ) ];
        } elseif ( ! $blog_public ) {
            $issues[] = [ 'warning', __( '"Discourage search engines from indexing this site" is enabled in Settings > Reading.', 'dev-debug-tools' ) ];
        }

        if ( $blocks_admin && ! $allows_ajax ) {
            $issues[] = [ 'info', __( '/wp-admin/ is blocked without "Allow: /wp-admin/admin-ajax.php". Front-end features that use admin-ajax may not render for crawlers.', 'dev-debug-tools' ) ];
        }

        if ( empty( $parsed[ 'sitemaps' ] ) ) {
            $issues[] = [ 'warning', __( 'No Sitemap line found.', 'dev-debug-tools' ) ];
        }

        foreach ( $parsed[ 'sitemaps' ] as $sitemap_url ) {
            if ( ! preg_match( '#^https?://#i', $sitemap_url ) ) {
                /* translators: %s: sitemap URL */
                $issues[] = [ 'error', sprintf( __( 'Sitemap URL "%s" must be absolute.', 'dev-debug-tools' ), $sitemap_url ) ];
                continue;
            }

            $sitemap_response = self::fetch( esc_url_raw( $sitemap_url ) );
            if ( $sitemap_response[ 'error' ] || $sitemap_response[ 'code' ] !== 200 ) {
                $status = $sitemap_response[ 'error' ] ? $sitemap_response[ 'error' ] : 'HTTP ' . $sitemap_response[ 'code' ];
                /* translators: 1: sitemap URL, 2: status */
                $issues[] = [ 'error', sprintf( __( 'Sitemap "%1$s" is not reachable (%2$s).', 'dev-debug-tools' ), $sitemap_url, $status ) ];
            }
        }

        return $issues;
    } // End diagnose_robots()


    /**
     * Diagnose a sitemap
     *
     * @param array $response
     * @param array|\WP_Error|null $parsed
     * @param bool $is_main
     * @return array
     */
    public static function diagnose_sitemap( array $response, $parsed, bool $is_main ) : array {
        $issues = self::diagnose_response( $response, 'xml' );
        if ( $response[ 'error' ] || $response[ 'code' ] !== 200 ) {
            return $issues;
        }

        if ( is_wp_error( $parsed ) ) {
            $issues[] = [ 'error', $parsed->get_error_message() ];
            return $issues;
        }

        if ( $parsed[ 'type' ] === 'unknown' ) {
            /* translators: %s: root element */
            $issues[] = [ 'error', sprintf( __( 'Root element is <%s>; expected <urlset> or <sitemapindex>.', 'dev-debug-tools' ), $parsed[ 'root' ] ) ];
            return $issues;
        }

        $count = count( $parsed[ 'items' ] );
        $label = $parsed[ 'type' ] === 'index' ? __( 'sitemaps', 'dev-debug-tools' ) : __( 'URLs', 'dev-debug-tools' );

        if ( $count === 0 ) {
            $issues[] = [ 'warning', __( 'Sitemap contains no entries.', 'dev-debug-tools' ) ];
        } elseif ( $count > 50000 ) {
            /* translators: 1: count, 2: label */
            $issues[] = [ 'error', sprintf( __( 'Contains %1$s %2$s. The limit is 50,000 per file.', 'dev-debug-tools' ), number_format_i18n( $count ), $label ) ];
        }

        if ( $parsed[ 'size' ] > 52428800 ) {
            $issues[] = [ 'error', __( 'Uncompressed size exceeds the 50 MB limit.', 'dev-debug-tools' ) ];
        }

        $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $home_scheme = wp_parse_url( home_url(), PHP_URL_SCHEME );
        $relative = 0;
        $wrong_host = 0;
        $wrong_scheme = 0;
        $no_lastmod = 0;
        $seen = [];
        $duplicates = 0;

        foreach ( $parsed[ 'items' ] as $item ) {
            $loc = $item[ 'loc' ];
            if ( ! preg_match( '#^https?://#i', $loc ) ) {
                $relative++;
                continue;
            }

            if ( wp_parse_url( $loc, PHP_URL_HOST ) !== $home_host ) {
                $wrong_host++;
            } elseif ( wp_parse_url( $loc, PHP_URL_SCHEME ) !== $home_scheme ) {
                $wrong_scheme++;
            }

            if ( $item[ 'lastmod' ] === '' ) {
                $no_lastmod++;
            }

            if ( isset( $seen[ $loc ] ) ) {
                $duplicates++;
            }
            $seen[ $loc ] = true;
        }

        if ( $relative ) {
            /* translators: %s: count */
            $issues[] = [ 'error', sprintf( __( '%s entries have a missing or relative <loc>. URLs must be absolute.', 'dev-debug-tools' ), number_format_i18n( $relative ) ) ];
        }

        if ( $wrong_host ) {
            /* translators: 1: count, 2: host */
            $issues[] = [ 'error', sprintf( __( '%1$s entries point to a host other than %2$s.', 'dev-debug-tools' ), number_format_i18n( $wrong_host ), $home_host ) ];
        }

        if ( $wrong_scheme ) {
            /* translators: 1: count, 2: scheme */
            $issues[] = [ 'warning', sprintf( __( '%1$s entries use a different protocol than the site (%2$s).', 'dev-debug-tools' ), number_format_i18n( $wrong_scheme ), $home_scheme ) ];
        }

        if ( $duplicates ) {
            /* translators: %s: count */
            $issues[] = [ 'warning', sprintf( __( '%s duplicate entries found.', 'dev-debug-tools' ), number_format_i18n( $duplicates ) ) ];
        }

        if ( $no_lastmod ) {
            /* translators: %s: count */
            $issues[] = [ 'info', sprintf( __( '%s entries have no <lastmod>.', 'dev-debug-tools' ), number_format_i18n( $no_lastmod ) ) ];
        }

        if ( $parsed[ 'type' ] === 'index' ) {
            $issues = array_merge( $issues, self::check_children( $parsed[ 'items' ] ) );
        } else {
            $issues = array_merge( $issues, self::sample_urls( $parsed[ 'items' ] ) );
        }

        if ( $is_main ) {
            $issues = array_merge( $issues, self::diagnose_main( $response[ 'url' ] ) );
        }

        return $issues;
    } // End diagnose_sitemap()


    /**
     * Status check child sitemaps of an index
     *
     * @param array $items
     * @return array
     */
    private static function check_children( array $items ) : array {
        $issues = [];
        $failed = [];

        foreach ( array_slice( $items, 0, self::MAX_CHILD_CHECKS ) as $item ) {
            if ( ! preg_match( '#^https?://#i', $item[ 'loc' ] ) ) {
                continue;
            }

            $child = self::fetch( esc_url_raw( $item[ 'loc' ] ) );
            if ( $child[ 'error' ] || $child[ 'code' ] !== 200 ) {
                $failed[] = $item[ 'loc' ] . ' (' . ( $child[ 'error' ] ? $child[ 'error' ] : 'HTTP ' . $child[ 'code' ] ) . ')';
                continue;
            }

            $child_parsed = self::parse_sitemap( $child[ 'body' ] );
            if ( ! is_wp_error( $child_parsed ) && empty( $child_parsed[ 'items' ] ) ) {
                /* translators: %s: sitemap URL */
                $issues[] = [ 'warning', sprintf( __( 'Child sitemap %s is empty.', 'dev-debug-tools' ), $item[ 'loc' ] ) ];
            }
        }

        foreach ( $failed as $failed_child ) {
            /* translators: %s: sitemap URL and status */
            $issues[] = [ 'error', sprintf( __( 'Child sitemap not reachable: %s', 'dev-debug-tools' ), $failed_child ) ];
        }

        if ( count( $items ) > self::MAX_CHILD_CHECKS ) {
            /* translators: %d: number checked */
            $issues[] = [ 'info', sprintf( __( 'Only the first %d child sitemaps were checked.', 'dev-debug-tools' ), self::MAX_CHILD_CHECKS ) ];
        }

        return $issues;
    } // End check_children()


    /**
     * Sample URLs from a urlset for status and noindex
     *
     * @param array $items
     * @return array
     */
    private static function sample_urls( array $items, string $context = 'sitemap' ) : array {
        $sample_size = min( absint( get_option( 'ddtt_seo_sample_urls', 0 ) ), 25 );
        if ( $sample_size === 0 || empty( $items ) ) {
            return [];
        }

        $issues = [];
        $checked = 0;

        foreach ( array_slice( $items, 0, $sample_size ) as $item ) {
            if ( ! preg_match( '#^https?://#i', $item[ 'loc' ] ) ) {
                continue;
            }

            $checked++;
            $response = wp_remote_get( $item[ 'loc' ], [ 'timeout' => 10, 'redirection' => 0, 'sslverify' => false, 'limit_response_size' => 262144 ] );

            if ( is_wp_error( $response ) ) {
                /* translators: 1: URL, 2: error */
                $issues[] = [ 'error', sprintf( __( '%1$s failed: %2$s', 'dev-debug-tools' ), $item[ 'loc' ], $response->get_error_message() ) ];
                continue;
            }

            $code = (int) wp_remote_retrieve_response_code( $response );
            if ( $code >= 300 && $code < 400 ) {
                /* translators: 1: URL, 2: status code */
                $issues[] = [ 'warning', sprintf( __( '%1$s redirects (HTTP %2$d). Link to the final URL instead.', 'dev-debug-tools' ), $item[ 'loc' ], $code ) ];
                continue;
            }

            if ( $code !== 200 ) {
                /* translators: 1: URL, 2: status code */
                $issues[] = [ 'error', sprintf( __( '%1$s returned HTTP %2$d.', 'dev-debug-tools' ), $item[ 'loc' ], $code ) ];
                continue;
            }

            $robots_header = implode( ',', (array) wp_remote_retrieve_header( $response, 'x-robots-tag' ) );
            $body = wp_remote_retrieve_body( $response );
            if ( stripos( $robots_header, 'noindex' ) !== false || preg_match( '/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex/i', $body ) ) {
                if ( $context === 'sitemap' ) {
                    /* translators: %s: URL */
                    $issues[] = [ 'error', sprintf( __( '%s is noindex but listed in the sitemap.', 'dev-debug-tools' ), $item[ 'loc' ] ) ];
                } else {
                    /* translators: %s: URL */
                    $issues[] = [ 'info', sprintf( __( '%s is noindex. AI tools may still read it from llms.txt.', 'dev-debug-tools' ), $item[ 'loc' ] ) ];
                }
            }
        }

        /* translators: %d: number of URLs checked */
        $issues[] = [ 'info', sprintf( __( 'Sampled %d URL(s) for status and noindex.', 'dev-debug-tools' ), $checked ) ];

        return $issues;
    } // End sample_urls()


    /**
     * Diagnostics for the main sitemap only
     *
     * @param string $url
     * @return array
     */
    private static function diagnose_main( string $url ) : array {
        $issues = [];
        $providers = self::detect_provider();
        $labels = wp_list_pluck( $providers, 'label' );

        if ( ! empty( $labels ) ) {
            /* translators: %s: provider names */
            $issues[] = [ 'info', sprintf( __( 'Sitemap provider(s) detected: %s.', 'dev-debug-tools' ), implode( ', ', $labels ) ) ];
        }

        if ( count( $providers ) > 1 ) {
            $issues[] = [ 'warning', __( 'More than one sitemap generator is active. Search engines may receive duplicate or conflicting sitemaps.', 'dev-debug-tools' ) ];
        }

        $robots = self::fetch( self::get_robots_url() );
        $listed = array_map( 'untrailingslashit', self::parse_robots( $robots[ 'body' ] )[ 'sitemaps' ] );
        if ( ! in_array( untrailingslashit( $url ), $listed, true ) ) {
            $issues[] = [ 'info', __( 'This sitemap is not listed in robots.txt.', 'dev-debug-tools' ) ];
        }

        return $issues;
    } // End diagnose_main()


    /**
     * Get the llms.txt file URLs
     *
     * @return array [ url => label ]
     */
    public static function get_llms_files() : array {
        $files = [];

        $llms_path = sanitize_text_field( get_option( 'ddtt_seo_llms_path', 'llms.txt' ) );
        $files[ self::resolve_url( $llms_path ? $llms_path : 'llms.txt' ) ] = 'llms.txt';

        $full_path = sanitize_text_field( get_option( 'ddtt_seo_llms_full_path', 'llms-full.txt' ) );
        if ( $full_path ) {
            $files[ self::resolve_url( $full_path ) ] = 'llms-full.txt';
        }

        return $files;
    } // End get_llms_files()


    /**
     * Validate a requested llms URL against the allowed list
     *
     * @param string $url
     * @return string
     */
    public static function validate_llms_url( string $url ) : string {
        $files = self::get_llms_files();
        return isset( $files[ $url ] ) ? $url : (string) array_key_first( $files );
    } // End validate_llms_url()


    /**
     * Parse llms.txt (llmstxt.org format)
     *
     * @param string $body
     * @return array
     */
    public static function parse_llms( string $body ) : array {
        $parsed = [
            'title'   => '',
            'summary' => '',
            'links'   => [],
            'issues'  => [],
        ];

        $lines = preg_split( '/\r\n|\r|\n/', $body );
        $section = '';
        $h1_count = 0;
        $first_content = true;

        foreach ( $lines as $index => $line ) {
            $line_number = $index + 1;
            $trimmed = trim( $line );
            if ( $trimmed === '' ) {
                continue;
            }

            if ( preg_match( '/^#\s+(.+)$/', $trimmed, $matches ) ) {
                $h1_count++;
                if ( $h1_count === 1 ) {
                    $parsed[ 'title' ] = $matches[ 1 ];
                }
                $first_content = false;
                continue;
            }

            if ( $first_content ) {
                /* translators: %d: line number */
                $parsed[ 'issues' ][] = [ 'warning', sprintf( __( 'The file should start with an H1 title (# Site Name), but line %d is not one.', 'dev-debug-tools' ), $line_number ) ];
                $first_content = false;
            }

            if ( str_starts_with( $trimmed, '>' ) && $parsed[ 'summary' ] === '' && $section === '' ) {
                $parsed[ 'summary' ] = trim( ltrim( $trimmed, '> ' ) );
                continue;
            }

            if ( preg_match( '/^##\s+(.+)$/', $trimmed, $matches ) ) {
                $section = $matches[ 1 ];
                continue;
            }

            if ( preg_match( '/^[-*]\s/', $trimmed ) ) {
                if ( preg_match( '/^[-*]\s*\[([^\]]+)\]\(([^)\s]+)\)\s*(?::\s*(.*))?$/', $trimmed, $matches ) ) {
                    $parsed[ 'links' ][] = [
                        'section' => $section,
                        'title'   => $matches[ 1 ],
                        'loc'     => $matches[ 2 ],
                        'notes'   => isset( $matches[ 3 ] ) ? $matches[ 3 ] : '',
                        'line'    => $line_number,
                    ];
                } elseif ( $section !== '' ) {
                    /* translators: %d: line number */
                    $parsed[ 'issues' ][] = [ 'info', sprintf( __( 'List item on line %d is not in the "- [name](url): notes" link format.', 'dev-debug-tools' ), $line_number ) ];
                }
            }
        }

        if ( $h1_count === 0 ) {
            $parsed[ 'issues' ][] = [ 'warning', __( 'No H1 title found. The llms.txt format requires one.', 'dev-debug-tools' ) ];
        } elseif ( $h1_count > 1 ) {
            $parsed[ 'issues' ][] = [ 'info', __( 'More than one H1 heading found. The format expects a single H1 title.', 'dev-debug-tools' ) ];
        }

        return $parsed;
    } // End parse_llms()


    /**
     * Diagnose llms.txt / llms-full.txt
     *
     * @param array      $response
     * @param array|null $parsed
     * @param bool       $is_full
     * @return array
     */
    public static function diagnose_llms( array $response, $parsed, bool $is_full ) : array {
        $filename = basename( (string) wp_parse_url( $response[ 'url' ], PHP_URL_PATH ) );

        if ( ! $response[ 'error' ] && $response[ 'code' ] === 404 ) {
            /* translators: %s: filename */
            $issues = [ [ 'info', sprintf( __( 'No %s found. This file is optional.', 'dev-debug-tools' ), $filename ) ] ];
            return array_merge( $issues, self::diagnose_llms_generators() );
        }

        $issues = self::diagnose_response( $response, 'text/' );
        if ( $response[ 'error' ] || $response[ 'code' ] !== 200 ) {
            return $issues;
        }

        $issues = array_merge( $issues, self::diagnose_llms_generators() );

        if ( file_exists( ABSPATH . $filename ) ) {
            /* translators: %s: filename */
            $issues[] = [ 'info', sprintf( __( 'A physical %s file exists in the site root, so it is served instead of any plugin-generated version.', 'dev-debug-tools' ), $filename ) ];
        }

        if ( $response[ 'size' ] > 5 * MB_IN_BYTES ) {
            /* translators: %s: file size */
            $issues[] = [ 'warning', sprintf( __( 'File is %s. Very large files may be truncated or skipped by AI tools.', 'dev-debug-tools' ), Helpers::format_bytes( $response[ 'size' ] ) ) ];
        }

        if ( ! $is_full && $parsed ) {
            $issues = array_merge( $issues, $parsed[ 'issues' ] );

            if ( $parsed[ 'summary' ] === '' ) {
                $issues[] = [ 'info', __( 'No summary blockquote (> ...) found after the title. It is optional but recommended.', 'dev-debug-tools' ) ];
            }

            if ( empty( $parsed[ 'links' ] ) ) {
                $issues[] = [ 'warning', __( 'No links found. llms.txt is meant to list your key pages under H2 sections.', 'dev-debug-tools' ) ];
            }
        }

        if ( $parsed && ! empty( $parsed[ 'links' ] ) ) {
            $issues = array_merge( $issues, self::diagnose_llms_links( $parsed[ 'links' ] ) );
        }

        $issues = array_merge( $issues, self::diagnose_llms_robots() );

        return $issues;
    } // End diagnose_llms()


    /**
     * Detect plugins that may generate llms.txt
     *
     * @return array
     */
    private static function diagnose_llms_generators() : array {
        $issues = [];
        $generators = [];

        foreach ( (array) get_option( 'active_plugins', [] ) as $plugin ) {
            if ( preg_match( '/llms[-_]?(full[-_]?)?txt/i', $plugin ) ) {
                $generators[] = dirname( $plugin );
            }
        }

        if ( ! empty( $generators ) ) {
            /* translators: %s: plugin folder names */
            $issues[] = [ 'info', sprintf( __( 'Active llms.txt plugin(s): %s.', 'dev-debug-tools' ), implode( ', ', $generators ) ) ];
        }

        $seo_plugins = wp_list_pluck( array_filter( self::detect_provider(), function( $provider ) {
            return $provider[ 'label' ] !== 'WordPress Core';
        } ), 'label' );

        if ( ! empty( $seo_plugins ) ) {
            /* translators: %s: SEO plugin names */
            $issues[] = [ 'info', sprintf( __( 'SEO plugin(s) that can generate llms.txt are active: %s. Check their settings if the file is unexpected or duplicated.', 'dev-debug-tools' ), implode( ', ', $seo_plugins ) ) ];
        }

        if ( count( $generators ) + count( $seo_plugins ) > 1 && ! empty( $generators ) ) {
            $issues[] = [ 'warning', __( 'More than one plugin may be generating llms.txt. Only one version will be served.', 'dev-debug-tools' ) ];
        }

        return $issues;
    } // End diagnose_llms_generators()


    /**
     * Diagnose the links listed in llms.txt
     *
     * @param array $links
     * @return array
     */
    private static function diagnose_llms_links( array $links ) : array {
        $issues = [];
        $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $home_scheme = wp_parse_url( home_url(), PHP_URL_SCHEME );
        $relative = 0;
        $external = 0;
        $wrong_scheme = 0;
        $duplicates = 0;
        $seen = [];

        foreach ( $links as $link ) {
            $loc = $link[ 'loc' ];
            if ( ! preg_match( '#^https?://#i', $loc ) ) {
                $relative++;
                continue;
            }

            if ( wp_parse_url( $loc, PHP_URL_HOST ) !== $home_host ) {
                $external++;
            } elseif ( wp_parse_url( $loc, PHP_URL_SCHEME ) !== $home_scheme ) {
                $wrong_scheme++;
            }

            if ( isset( $seen[ $loc ] ) ) {
                $duplicates++;
            }
            $seen[ $loc ] = true;
        }

        if ( $relative ) {
            /* translators: %s: count */
            $issues[] = [ 'warning', sprintf( __( '%s links are relative. Use absolute URLs so AI tools can follow them.', 'dev-debug-tools' ), number_format_i18n( $relative ) ) ];
        }

        if ( $external ) {
            /* translators: %s: count */
            $issues[] = [ 'info', sprintf( __( '%s links point to other domains.', 'dev-debug-tools' ), number_format_i18n( $external ) ) ];
        }

        if ( $wrong_scheme ) {
            /* translators: 1: count, 2: scheme */
            $issues[] = [ 'warning', sprintf( __( '%1$s links use a different protocol than the site (%2$s).', 'dev-debug-tools' ), number_format_i18n( $wrong_scheme ), $home_scheme ) ];
        }

        if ( $duplicates ) {
            /* translators: %s: count */
            $issues[] = [ 'info', sprintf( __( '%s duplicate links found.', 'dev-debug-tools' ), number_format_i18n( $duplicates ) ) ];
        }

        return array_merge( $issues, self::sample_urls( $links, 'llms' ) );
    } // End diagnose_llms_links()


    /**
     * Cross-check robots.txt for blocked AI crawlers
     *
     * @return array
     */
    private static function diagnose_llms_robots() : array {
        $ai_agents = [ 'gptbot', 'oai-searchbot', 'chatgpt-user', 'claudebot', 'claude-searchbot', 'claude-user', 'anthropic-ai', 'google-extended', 'perplexitybot', 'ccbot', 'applebot-extended', 'bytespider', 'meta-externalagent' ];
        $robots = self::fetch( self::get_robots_url() );
        if ( $robots[ 'error' ] || $robots[ 'code' ] !== 200 ) {
            return [];
        }

        $blocked = [];
        foreach ( self::parse_robots( $robots[ 'body' ] )[ 'rules' ] as $rule ) {
            if ( $rule[ 'directive' ] === 'disallow' && $rule[ 'value' ] === '/' && in_array( strtolower( $rule[ 'agent' ] ), $ai_agents, true ) ) {
                $blocked[] = $rule[ 'agent' ];
            }
        }

        if ( empty( $blocked ) ) {
            return [];
        }

        /* translators: %s: user agents */
        return [ [ 'info', sprintf( __( 'robots.txt blocks these AI crawlers from the whole site: %s. That conflicts with publishing an llms.txt for them.', 'dev-debug-tools' ), implode( ', ', array_unique( $blocked ) ) ) ] ];
    } // End diagnose_llms_robots()


    /**
     * Highlight llms.txt markdown (returns escaped HTML)
     *
     * @param string $body
     * @param array  $colors
     * @return string
     */
    public static function highlight_llms( string $body, array $colors ) : string {
        $lines = preg_split( '/\r\n|\r|\n/', $body );
        $output = [];
        $syntax = '<span class="ddtt-syntax" style="color: ' . esc_attr( $colors[ 'syntax' ] ) . '">';
        $comment = '<span class="ddtt-comment" style="color: ' . esc_attr( $colors[ 'comments' ] ) . '">';
        $fx_vars = '<span class="ddtt-fx-vars" style="color: ' . esc_attr( $colors[ 'fx_vars' ] ) . '">';
        $quotes = '<span class="ddtt-text-quotes" style="color: ' . esc_attr( $colors[ 'text_quotes' ] ) . '">';

        foreach ( $lines as $line ) {
            $escaped = esc_html( $line );

            if ( preg_match( '/^\s*#{1,6}\s/', $escaped ) ) {
                $output[] = $syntax . '<strong>' . $escaped . '</strong></span>';
                continue;
            }

            if ( preg_match( '/^\s*&gt;/', $escaped ) ) {
                $output[] = $comment . $escaped . '</span>';
                continue;
            }

            $output[] = preg_replace( '/\[([^\]]+)\]\(([^)\s]+)\)/', '[' . $fx_vars . '$1</span>](' . $quotes . '$2</span>)', $escaped );
        }

        return implode( "\n", $output );
    } // End highlight_llms()


    /**
     * Render the llms.txt output view
     *
     * @param array|null $parsed
     * @param array      $customizations
     * @param int        $page
     */
    private static function render_llms_output( $parsed, array $customizations, int $page ) {
        if ( ! $parsed ) {
            echo '<p>' . esc_html__( 'Nothing to display.', 'dev-debug-tools' ) . '</p>';
            return;
        }

        $links = $parsed[ 'links' ];
        $search = $customizations[ 'search' ];
        if ( $search !== '' ) {
            $links = array_values( array_filter( $links, function( $link ) use ( $search ) {
                return stripos( $link[ 'loc' ] . ' ' . $link[ 'title' ] . ' ' . $link[ 'section' ], $search ) !== false;
            } ) );
        }

        $per_page = max( 1, $customizations[ 'per_page' ] );
        $total = count( $links );
        $total_pages = max( 1, (int) ceil( $total / $per_page ) );
        $page = min( max( 1, $page ), $total_pages );
        $links = array_slice( $links, ( $page - 1 ) * $per_page, $per_page );
        ?>
        <?php if ( $parsed[ 'title' ] || $parsed[ 'summary' ] ) : ?>
            <div class="ddtt-seo-llms-header">
                <?php if ( $parsed[ 'title' ] ) : ?>
                    <h3><?php echo esc_html( $parsed[ 'title' ] ); ?></h3>
                <?php endif; ?>
                <?php if ( $parsed[ 'summary' ] ) : ?>
                    <blockquote><?php echo esc_html( $parsed[ 'summary' ] ); ?></blockquote>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <table class="ddtt-table ddtt-seo-output">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Section', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Title', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'URL', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Notes', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $links ) ) : ?>
                    <tr><td colspan="4"><?php esc_html_e( 'No links found.', 'dev-debug-tools' ); ?></td></tr>
                <?php endif; ?>
                <?php foreach ( $links as $link ) : ?>
                    <tr>
                        <td><?php echo esc_html( $link[ 'section' ] ); ?></td>
                        <td><?php echo esc_html( $link[ 'title' ] ); ?></td>
                        <td><a href="<?php echo esc_url( $link[ 'loc' ] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $link[ 'loc' ] ); ?></a></td>
                        <td><?php echo esc_html( $link[ 'notes' ] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="ddtt-seo-pagination" data-page="<?php echo esc_attr( $page ); ?>" data-total-pages="<?php echo esc_attr( $total_pages ); ?>">
            <button type="button" class="ddtt-button ddtt-seo-page" data-page="<?php echo esc_attr( $page - 1 ); ?>" <?php disabled( $page <= 1 ); ?>>&laquo;</button>
            <span>
                <?php
                /* translators: 1: current page, 2: total pages, 3: total entries */
                echo esc_html( sprintf( __( 'Page %1$d of %2$d (%3$s entries)', 'dev-debug-tools' ), $page, $total_pages, number_format_i18n( $total ) ) );
                ?>
            </span>
            <button type="button" class="ddtt-button ddtt-seo-page" data-page="<?php echo esc_attr( $page + 1 ); ?>" <?php disabled( $page >= $total_pages ); ?>>&raquo;</button>
        </div>
        <?php
    } // End render_llms_output()


    /**
     * Get the code viewer colors
     *
     * @return array
     */
    public static function colors() : array {
        $mode = Helpers::is_dark_mode() ? 'dark' : 'light';
        return FileEditor::DEFAULT_COLORS[ $mode ];
    } // End colors()


    /**
     * Highlight robots.txt (returns escaped HTML)
     *
     * @param string $body
     * @param array  $colors
     * @return string
     */
    public static function highlight_robots( string $body, array $colors ) : string {
        $lines = preg_split( '/\r\n|\r|\n/', $body );
        $output = [];

        foreach ( $lines as $line ) {
            $escaped = esc_html( $line );

            if ( preg_match( '/^(\s*)(#.*)$/', $escaped, $matches ) ) {
                $output[] = $matches[ 1 ] . '<span class="ddtt-comment" style="color: ' . esc_attr( $colors[ 'comments' ] ) . '">' . $matches[ 2 ] . '</span>';
                continue;
            }

            if ( preg_match( '/^(\s*)([A-Za-z-]+)(\s*:)([^#]*)(#.*)?$/', $escaped, $matches ) ) {
                $line_html = $matches[ 1 ] . '<span class="ddtt-syntax" style="color: ' . esc_attr( $colors[ 'syntax' ] ) . '">' . $matches[ 2 ] . '</span>' . $matches[ 3 ];
                $line_html .= '<span class="ddtt-text-quotes" style="color: ' . esc_attr( $colors[ 'text_quotes' ] ) . '">' . $matches[ 4 ] . '</span>';
                if ( ! empty( $matches[ 5 ] ) ) {
                    $line_html .= '<span class="ddtt-comment" style="color: ' . esc_attr( $colors[ 'comments' ] ) . '">' . $matches[ 5 ] . '</span>';
                }
                $output[] = $line_html;
                continue;
            }

            $output[] = $escaped;
        }

        return implode( "\n", $output );
    } // End highlight_robots()


    /**
     * Pretty print minified XML for the code view
     *
     * @param string $body
     * @return string
     */
    public static function maybe_format_xml( string $body ) : string {
        if ( substr_count( $body, "\n" ) > 5 || ! class_exists( '\DOMDocument' ) ) {
            return $body;
        }

        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $previous = libxml_use_internal_errors( true );
        $loaded = $dom->loadXML( $body, LIBXML_NONET );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );

        if ( ! $loaded ) {
            return $body;
        }

        $formatted = $dom->saveXML();
        return $formatted !== false ? $formatted : $body;
    } // End maybe_format_xml()


    /**
     * Highlight XML (returns escaped HTML)
     *
     * @param string $body
     * @param array  $colors
     * @return string
     */
    public static function highlight_xml( string $body, array $colors ) : string {
        $escaped = htmlspecialchars( $body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
        $comment = '<span class="ddtt-comment" style="color: ' . esc_attr( $colors[ 'comments' ] ) . '">';
        $syntax = '<span class="ddtt-syntax" style="color: ' . esc_attr( $colors[ 'syntax' ] ) . '">';
        $fx_vars = '<span class="ddtt-fx-vars" style="color: ' . esc_attr( $colors[ 'fx_vars' ] ) . '">';
        $quotes = '<span class="ddtt-text-quotes" style="color: ' . esc_attr( $colors[ 'text_quotes' ] ) . '">';

        $pattern = '/(&lt;!--.*?--&gt;)|(&lt;\?.*?\?&gt;)|(&lt;\/?)([\w:.-]+)((?:\s+[\w:.-]+=(?:&quot;.*?&quot;|&#039;.*?&#039;))*)(\s*\/?&gt;)/s';

        return preg_replace_callback( $pattern, function( $matches ) use ( $comment, $syntax, $fx_vars, $quotes ) {
            if ( ! empty( $matches[ 1 ] ) || ! empty( $matches[ 2 ] ) ) {
                return $comment . $matches[ 0 ] . '</span>';
            }

            $attributes = preg_replace( '/([\w:.-]+)=(&quot;.*?&quot;|&#039;.*?&#039;)/s', $fx_vars . '$1</span>=' . $quotes . '$2</span>', $matches[ 5 ] );

            return $syntax . $matches[ 3 ] . $matches[ 4 ] . '</span>' . $attributes . $syntax . $matches[ 6 ] . '</span>';
        }, $escaped ) ?? $escaped;
    } // End highlight_xml()


    /**
     * Render the info bar
     *
     * @param array  $response
     * @param string $source
     */
    public static function render_info_bar( array $response, string $source = '' ) {
        $items = [
            'url'      => [ __( 'URL', 'dev-debug-tools' ), '<a href="' . esc_url( $response[ 'url' ] ) . '" target="_blank" rel="noopener">' . esc_html( $response[ 'url' ] ) . '</a>' ],
            'status'   => [ __( 'Status', 'dev-debug-tools' ), esc_html( $response[ 'error' ] ? __( 'Failed', 'dev-debug-tools' ) : $response[ 'code' ] ) ],
            'size'     => [ __( 'Size', 'dev-debug-tools' ), esc_html( Helpers::format_bytes( $response[ 'size' ] ) ) ],
            'time'     => [ __( 'Response', 'dev-debug-tools' ), esc_html( $response[ 'time' ] . ' ms' ) ],
            'fetched'  => [ __( 'Fetched', 'dev-debug-tools' ), esc_html( Helpers::convert_timezone( $response[ 'fetched' ], 'g:i:s A' ) ) ],
        ];

        if ( $source ) {
            $items[ 'source' ] = [ __( 'Source', 'dev-debug-tools' ), esc_html( $source ) ];
        }

        $last_key = array_key_last( $items );
        ?>
        <div class="ddtt-file-info">
            <div class="ddtt-file-data">
                <?php foreach ( $items as $key => $item ) : ?>
                    <div class="ddtt-file-data-item <?php echo esc_attr( 'ddtt-file-data-' . $key ); ?>">
                        <span class="ddtt-file-data-label"><?php echo esc_html( $item[ 0 ] ); ?>:</span>
                        <strong><?php echo wp_kses_post( $item[ 1 ] ); ?></strong>
                    </div>
                    <?php if ( $key !== $last_key ) : ?>
                        <span class="ddtt-separator">|</span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    } // End render_info_bar()


    /**
     * Render the diagnostics table
     *
     * @param array $issues
     */
    public static function render_diagnostics( array $issues ) {
        $order = [ 'error' => 0, 'warning' => 1, 'info' => 2 ];
        usort( $issues, function( $a, $b ) use ( $order ) {
            return $order[ $a[ 0 ] ] <=> $order[ $b[ 0 ] ];
        } );

        $labels = [
            'error'   => __( 'Error', 'dev-debug-tools' ),
            'warning' => __( 'Warning', 'dev-debug-tools' ),
            'info'    => __( 'Info', 'dev-debug-tools' ),
        ];
        ?>
        <table class="ddtt-table ddtt-seo-diagnostics">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Severity', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Diagnostic', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $issues ) ) : ?>
                    <tr data-severity="success">
                        <td><span class="ddtt-seo-severity"><?php esc_html_e( 'OK', 'dev-debug-tools' ); ?></span></td>
                        <td><?php esc_html_e( 'No issues found.', 'dev-debug-tools' ); ?></td>
                    </tr>
                <?php endif; ?>
                <?php foreach ( $issues as $issue ) : ?>
                    <tr data-severity="<?php echo esc_attr( $issue[ 0 ] ); ?>">
                        <td><span class="ddtt-seo-severity"><?php echo esc_html( $labels[ $issue[ 0 ] ] ); ?></span></td>
                        <td><?php echo esc_html( $issue[ 1 ] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    } // End render_diagnostics()


    /**
     * Render the robots.txt output table
     *
     * @param string $body
     */
    private static function render_robots_output( string $body ) {
        $parsed = self::parse_robots( $body );
        ?>
        <table class="ddtt-table ddtt-seo-output">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Line', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'User-agent', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Directive', 'dev-debug-tools' ); ?></th>
                    <th><?php esc_html_e( 'Value', 'dev-debug-tools' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $parsed[ 'rules' ] as $rule ) : ?>
                    <tr>
                        <td><?php echo esc_html( $rule[ 'line' ] ); ?></td>
                        <td><code><?php echo esc_html( $rule[ 'agent' ] ); ?></code></td>
                        <td><?php echo esc_html( ucwords( $rule[ 'directive' ], '-' ) ); ?></td>
                        <td><code><?php echo esc_html( $rule[ 'value' ] !== '' ? $rule[ 'value' ] : __( '(empty)', 'dev-debug-tools' ) ); ?></code></td>
                    </tr>
                <?php endforeach; ?>
                <?php foreach ( $parsed[ 'sitemaps' ] as $sitemap_url ) : ?>
                    <tr>
                        <td>&mdash;</td>
                        <td>&mdash;</td>
                        <td><?php esc_html_e( 'Sitemap', 'dev-debug-tools' ); ?></td>
                        <td><a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $sitemap_url ); ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    } // End render_robots_output()


    /**
     * Render the sitemap output table
     *
     * @param array $parsed
     * @param array $customizations
     * @param int   $page
     */
    private static function render_sitemap_output( array $parsed, array $customizations, int $page ) {
        $items = $parsed[ 'items' ];
        $search = $customizations[ 'search' ];
        if ( $search !== '' ) {
            $items = array_values( array_filter( $items, function( $item ) use ( $search ) {
                return stripos( $item[ 'loc' ], $search ) !== false;
            } ) );
        }

        $per_page = max( 1, $customizations[ 'per_page' ] );
        $total = count( $items );
        $total_pages = max( 1, (int) ceil( $total / $per_page ) );
        $page = min( max( 1, $page ), $total_pages );
        $items = array_slice( $items, ( $page - 1 ) * $per_page, $per_page );
        $is_index = $parsed[ 'type' ] === 'index';
        ?>
        <table class="ddtt-table ddtt-seo-output">
            <thead>
                <tr>
                    <th><?php echo esc_html( $is_index ? __( 'Sitemap', 'dev-debug-tools' ) : __( 'URL', 'dev-debug-tools' ) ); ?></th>
                    <th><?php esc_html_e( 'Last Modified', 'dev-debug-tools' ); ?></th>
                    <?php if ( ! $is_index ) : ?>
                        <th><?php esc_html_e( 'Change Freq', 'dev-debug-tools' ); ?></th>
                        <th><?php esc_html_e( 'Priority', 'dev-debug-tools' ); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $items ) ) : ?>
                    <tr><td colspan="4"><?php esc_html_e( 'No entries found.', 'dev-debug-tools' ); ?></td></tr>
                <?php endif; ?>
                <?php foreach ( $items as $item ) : ?>
                    <tr>
                        <td>
                            <?php if ( $is_index ) : ?>
                                <a href="#" class="ddtt-seo-open-child" data-url="<?php echo esc_url( $item[ 'loc' ] ); ?>"><?php echo esc_html( $item[ 'loc' ] ); ?></a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( $item[ 'loc' ] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $item[ 'loc' ] ); ?></a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $item[ 'lastmod' ] ); ?></td>
                        <?php if ( ! $is_index ) : ?>
                            <td><?php echo esc_html( $item[ 'changefreq' ] ); ?></td>
                            <td><?php echo esc_html( $item[ 'priority' ] ); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="ddtt-seo-pagination" data-page="<?php echo esc_attr( $page ); ?>" data-total-pages="<?php echo esc_attr( $total_pages ); ?>">
            <button type="button" class="ddtt-button ddtt-seo-page" data-page="<?php echo esc_attr( $page - 1 ); ?>" <?php disabled( $page <= 1 ); ?>>&laquo;</button>
            <span>
                <?php
                /* translators: 1: current page, 2: total pages, 3: total entries */
                echo esc_html( sprintf( __( 'Page %1$d of %2$d (%3$s entries)', 'dev-debug-tools' ), $page, $total_pages, number_format_i18n( $total ) ) );
                ?>
            </span>
            <button type="button" class="ddtt-button ddtt-seo-page" data-page="<?php echo esc_attr( $page + 1 ); ?>" <?php disabled( $page >= $total_pages ); ?>>&raquo;</button>
        </div>
        <?php
    } // End render_sitemap_output()


    /**
     * Render the full view for a subsection
     *
     * @param string $subsection
     * @param array  $customizations
     * @param int    $page
     * @param bool   $force
     */
    public static function render_view( string $subsection, array $customizations, int $page = 1, bool $force = false ) {
        $colors = self::colors();

        if ( $subsection === 'robots' ) {
            $url = self::get_robots_url();
            $response = self::fetch( $url, $force );
            $source = file_exists( ABSPATH . 'robots.txt' ) ? __( 'Physical File', 'dev-debug-tools' ) : __( 'Virtual', 'dev-debug-tools' );
            $issues = self::diagnose_robots( $response );
            $parsed = null;
        } elseif ( $subsection === 'llms' ) {
            $url = self::validate_llms_url( $customizations[ 'llms' ] );
            $response = self::fetch( $url, $force );
            $parsed = $response[ 'code' ] === 200 ? self::parse_llms( $response[ 'body' ] ) : null;
            $source = file_exists( ABSPATH . basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ) ? __( 'Physical File', 'dev-debug-tools' ) : __( 'Virtual', 'dev-debug-tools' );
            $issues = self::diagnose_llms( $response, $parsed, $url !== (string) array_key_first( self::get_llms_files() ) );
        } else {
            $url = self::validate_sitemap_url( $customizations[ 'sitemap' ] );
            if ( ! $url ) {
                echo '<p>' . esc_html__( 'No sitemap found. Add one in the SEO settings.', 'dev-debug-tools' ) . '</p>';
                return;
            }

            $response = self::fetch( $url, $force );
            $parsed = $response[ 'code' ] === 200 ? self::parse_sitemap( $response[ 'body' ] ) : null;
            $is_main = isset( self::get_main_sitemaps()[ $url ] );
            $source = ! is_wp_error( $parsed ) && $parsed ? ( $parsed[ 'type' ] === 'index' ? __( 'Sitemap Index', 'dev-debug-tools' ) : __( 'URL Set', 'dev-debug-tools' ) ) : '';
            $issues = self::diagnose_sitemap( $response, $parsed, $is_main );
        }

        self::render_info_bar( $response, $source );
        self::render_diagnostics( $issues );

        if ( $response[ 'error' ] || $response[ 'body' ] === '' || $response[ 'code' ] === 404 ) {
            return;
        }
        ?>
        <div id="ddtt-seo-pane" class="ddtt-seo-pane-<?php echo esc_attr( $customizations[ 'type' ] ); ?>">
            <?php
            if ( $customizations[ 'type' ] === 'code' ) {
                if ( $subsection === 'robots' ) {
                    $highlighted = self::highlight_robots( $response[ 'body' ], $colors );
                } elseif ( $subsection === 'llms' ) {
                    $highlighted = self::highlight_llms( $response[ 'body' ], $colors );
                } else {
                    $highlighted = self::highlight_xml( self::maybe_format_xml( $response[ 'body' ] ), $colors );
                }
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in highlight_robots() / highlight_llms() / highlight_xml()
                echo '<pre id="ddtt-seo-code" class="ddtt-code-block" style="background: ' . esc_attr( $colors[ 'background' ] ) . '; color: ' . esc_attr( $colors[ 'text_quotes' ] ) . ';">' . $highlighted . '</pre>';
            } elseif ( $subsection === 'robots' ) {
                self::render_robots_output( $response[ 'body' ] );
            } elseif ( $subsection === 'llms' ) {
                self::render_llms_output( $parsed, $customizations, $page );
            } elseif ( $parsed && ! is_wp_error( $parsed ) ) {
                self::render_sitemap_output( $parsed, $customizations, $page );
            } else {
                echo '<p>' . esc_html__( 'Output view is unavailable because the sitemap could not be parsed. Switch to Code View.', 'dev-debug-tools' ) . '</p>';
            }
            ?>
        </div>
        <?php
    } // End render_view()


    /**
     * Handle download action
     */
    public function handle_button_actions() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! Helpers::is_dev() ) {
            return;
        }

        if ( ! isset( $_POST[ 'ddtt_seo_action' ] ) || sanitize_key( wp_unslash( $_POST[ 'ddtt_seo_action' ] ) ) !== 'download' ) {
            return;
        }

        check_admin_referer( 'ddtt_seo_action', 'ddtt_seo_nonce' );

        $subsection = isset( $_POST[ 'subsection' ] ) ? sanitize_key( wp_unslash( $_POST[ 'subsection' ] ) ) : 'robots';

        if ( $subsection === 'robots' ) {
            $url = self::get_robots_url();
            $filename = 'robots.txt';
        } elseif ( $subsection === 'llms' ) {
            $requested = isset( $_POST[ 'llms' ] ) ? esc_url_raw( wp_unslash( $_POST[ 'llms' ] ) ) : '';
            $url = self::validate_llms_url( $requested );
            $filename = sanitize_file_name( basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
            $filename = $filename ? $filename : 'llms.txt';
        } else {
            $requested = isset( $_POST[ 'sitemap' ] ) ? esc_url_raw( wp_unslash( $_POST[ 'sitemap' ] ) ) : '';
            $url = self::validate_sitemap_url( $requested );
            $filename = sanitize_file_name( basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
            $filename = $filename ? $filename : 'sitemap.xml';
        }

        $response = self::fetch( $url );
        if ( $response[ 'error' ] || $response[ 'body' ] === '' ) {
            wp_die( esc_html( $response[ 'error' ] ? $response[ 'error' ] : __( 'Nothing to download.', 'dev-debug-tools' ) ) );
        }

        if ( ob_get_length() ) {
            ob_end_clean();
        }

        $content_type = $response[ 'content_type' ] ? $response[ 'content_type' ] : ( $subsection === 'sitemap' ? 'application/xml' : 'text/plain' );

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: ' . sanitize_text_field( $content_type ) );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $response[ 'body' ] ) );
        header( 'Cache-Control: must-revalidate' );

        echo $response[ 'body' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw file output required for download
        exit;
    } // End handle_button_actions()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'seo' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-tool-seo', 'ddtt_seo', [
            'subsection' => self::get_current_subsection(),
            'nonce'      => wp_create_nonce( $this->nonce ),
            'i18n'       => [
                'loading'         => __( 'Please wait. Fetching', 'dev-debug-tools' ),
                'error'           => __( 'Error loading view. Please try again.', 'dev-debug-tools' ),
                'nonceExpiredMsg' => __( 'Your session has expired. Please reload the page.', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Render the view
     *
     * @return void
     */
    public function ajax_get_view() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        $stored = self::get_customizations();
        $customizations = [
            'type'     => isset( $_POST[ 'type' ] ) && sanitize_key( wp_unslash( $_POST[ 'type' ] ) ) === 'code' ? 'code' : 'output',
            'sitemap'  => isset( $_POST[ 'sitemap' ] ) ? esc_url_raw( wp_unslash( $_POST[ 'sitemap' ] ) ) : $stored[ 'sitemap' ],
            'llms'     => isset( $_POST[ 'llms' ] ) ? esc_url_raw( wp_unslash( $_POST[ 'llms' ] ) ) : $stored[ 'llms' ],
            'per_page' => isset( $_POST[ 'per_page' ] ) ? absint( wp_unslash( $_POST[ 'per_page' ] ) ) : $stored[ 'per_page' ],
            'search'   => isset( $_POST[ 'search' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'search' ] ) ) : $stored[ 'search' ],
        ];

        update_option( self::CUSTOMIZATIONS_KEY, $customizations, false );

        $subsection = isset( $_POST[ 'subsection' ] ) ? sanitize_key( wp_unslash( $_POST[ 'subsection' ] ) ) : 'robots';
        $subsection = isset( self::sections()[ $subsection ] ) ? $subsection : 'robots';
        $page = isset( $_POST[ 'page' ] ) ? absint( wp_unslash( $_POST[ 'page' ] ) ) : 1;
        $force = ! empty( $_POST[ 'refresh' ] );

        ob_start();
        self::render_view( $subsection, $customizations, $page, $force );
        echo ob_get_clean(); // phpcs:ignore

        wp_die();
    } // End ajax_get_view()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


Seo::instance();
