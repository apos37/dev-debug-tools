<?php
/**
 * Plugins Page
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Plugins {

    /**
     * Time period to consider a plugin "old" if not updated by author.
     *
     * @var string
     */
    const OLD_WARNING_STRING = '-1 year';
    const OLD_DANGER_STRING = '-2 years';
    const TOO_LARGE_MB = 10; // 10 MB


    /**
     * Recommended plugins
     *
     * @var array
     */
    public function recommended_plugins() {
        $our_plugins = [
            // Our plugins
            'admin-help-docs',
            'broken-link-notifier',
            'clear-cache-everywhere',
            'do-nothing',
            'simple-maintenance-redirect',
            'user-account-monitor',
            'wcag-admin-accessibility-tools',
        ];

        if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
            $our_plugins[] = 'gf-tools';
            $our_plugins[] = 'gf-discord';
            $our_plugins[] = 'gf-msteams';
        }

        $other_plugins = [
            'another-show-hooks',
            'aryo-activity-log',
            'asgaros-forum',
            'child-theme-configurator',
            'code-snippets',
            'debug-bar',
            'debug-this',
            'debugpress',
            'disk-usage-sunburst',
            'fakerpress',
            'go-live-update-urls',
            'heartbeat-control', // WP Dashboard: 60, Frontend: Disable, Post Editor: 30
            'import-users-from-csv-with-meta',
            'ns-cloner-site-copier',
            'post-type-switcher',
            'query-monitor',
            'redirection',
            'string-locator',
            'user-menus',
            'user-role-editor',
            'wp-crontrol',
            'wp-downgrade',
            'wp-mail-logging',
            'wp-optimize',
            'wp-rollback',
        ];

        sort( $our_plugins );
        sort( $other_plugins );
        $recommended = array_merge( $our_plugins, $other_plugins );


        /**
         * Filter the list of recommended plugins.
         */
        $recommended = apply_filters( 'ddtt_recommended_plugins', $recommended );

        return $recommended;
    } // End recommended_plugins()


    /**
     * Text domain
     *
     * @var string
     */
    public $text_domain = 'dev-debug-tools';


    /**
     * Current plugin file
     *
     * @var string
     */
    public $plugin_file = 'dev-debug-tools/dev-debug-tools.php';


    /**
     * Whether we are hiding the plugin
     *
     * @var boolean
     */
    public $hiding_plugin = false;


    /**
     * Whether access is restricted to developers only
     *
     * @var boolean
     */
    public $dev_access_only = false;


    /**
     * Nonce for AJAX
     *
     * @var string
     */
    private $nonce = 'ddtt_plugins_nonce';


    /**
     * Constructor
     */
    public function __construct() {

        // Add links to the website and discord
        add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

        // Vars
        $this->hiding_plugin = get_option( 'ddtt_hide_plugin' );
        $this->dev_access_only = get_option( 'ddtt_dev_access_only' );

         // If hiding the plugin, or restricting access to devs only, hide settings link
        if ( $this->hiding_plugin || ( $this->dev_access_only && ! Helpers::is_dev() ) ) {
            add_filter( 'all_plugins', [ $this, 'hide_plugin_from_list' ] );
        } else {
            add_filter( 'plugin_action_links_' . $this->plugin_file, [ $this, 'add_settings_link' ] );
        }

        // Add plugins to featured plugins list
        add_filter( 'install_plugins_tabs', [ $this, 'add_plugins_tab' ] );
        add_action( 'install_plugins_dev_debug_tools', [ $this, 'render_add_plugins_tab' ] );

        
        /**
         * Plugins Page Enhancements
         */
        if ( ! get_option( 'ddtt_plugins_page_data', true ) ) {
            return;
        }

        // Add custom columns
        add_filter( 'manage_plugins_columns', [ $this, 'add_columns' ] );
        add_action( 'manage_plugins_custom_column', [ $this, 'render_column' ], 10, 3 );
        
        // Make columns sortable for size and last modified
        if ( get_option( 'ddtt_plugins_page_size', true ) || 
             get_option( 'ddtt_plugins_page_last_modified', true ) ) {
            add_filter( 'manage_plugins_sortable_columns', [ $this, 'register_sortable_columns' ] );
            add_action( 'pre_current_active_plugins', [ $this, 'prepare_plugin_sorting' ] );
        }

        // Update plugin sizes when plugins are activated, updated or deleted
        if ( get_option( 'ddtt_plugins_page_size', true ) ) {
            add_action( 'upgrader_process_complete', [ $this , 'update_plugin_size_bulk' ], 10, 2 );
            add_action( 'activated_plugin', [ $this, 'update_plugin_size' ] );
            add_action( 'delete_plugin', [ $this, 'remove_plugin_size' ], 10, 1 );
        }

        // Update installer name
        if ( get_option( 'ddtt_plugins_page_installed_by', true ) ) {
            add_action( 'activated_plugin', [ $this, 'maybe_record_installer' ], 10, 2 );
            add_action( 'wp_ajax_ddtt_update_installer', [ $this, 'ajax_update_installer' ] );
            add_action( 'delete_plugin', [ $this, 'remove_plugin_installer' ], 10, 1 );
        }

        // Add notes action link
        if ( get_option( 'ddtt_plugins_page_notes', true ) ) {
            add_filter( 'plugin_row_meta', [ $this, 'add_notes_meta_link' ], 9999, 3 );
            add_action( 'wp_ajax_ddtt_save_plugin_note', [ $this, 'ajax_save_plugin_note' ] );
        }

        // Enqueue assets
        if ( get_option( 'ddtt_plugins_page_last_modified', true ) ||
             get_option( 'ddtt_plugins_page_installed_by', true ) ||
             get_option( 'ddtt_plugins_page_notes', true ) ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        }
        
    } // End __construct()


    /**
     * Add links to plugin row meta.
     *
     * @param array  $links Existing plugin meta links.
     * @param string $file  Plugin file.
     * @return array Modified plugin meta links.
     */
    public function plugin_row_meta( $links, $file ) {
        if ( $this->plugin_file !== $file ) {
            return (array) $links;
        }

        if ( ! $this->hiding_plugin ) {

            $plugin_name = Bootstrap::name();
            $base_url    = Bootstrap::author_uri();
            $our_links   = [
                'guide' => [
                    'label' => __( 'How-To Guide', 'dev-debug-tools' ),
                    'url'   => "{$base_url}/guide/plugin/{$this->text_domain}",
                ],
                'docs' => [
                    'label' => __( 'Developer Docs', 'dev-debug-tools' ),
                    'url'   => "{$base_url}/docs/plugin/{$this->text_domain}",
                ],
                'support' => [
                    'label' => __( 'Support', 'dev-debug-tools' ),
                    'url'   => "{$base_url}/support/plugin/{$this->text_domain}",
                ],
            ];

            foreach ( $our_links as $key => $link ) {
                $aria_label = sprintf(
                    // translators: %1$s: Link label, %2$s: Plugin name
                    __( '%1$s for %2$s', 'dev-debug-tools' ),
                    $link[ 'label' ],
                    $plugin_name
                );
                $links[ $key ] = '<a href="' . esc_url( $link[ 'url' ] ) . '" target="_blank" aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $link[ 'label' ] ) . '</a>';
            }

        } else {

            foreach ( $links as $key => $link ) {
                if ( strpos( $link, 'TB_iframe' ) !== false ) {
                    unset( $links[ $key ] );
                }
            }
        }

        return $links;
    } // End plugin_row_meta()


    /**
     * Add settings link to plugin action links.
     *
     * @param array $links Existing action links.
     * @return array Modified action links.
     */
    public function add_settings_link( $links ) {
        $url = Bootstrap::page_url( 'settings' );

        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( $url ),
            esc_html__( 'Settings', 'dev-debug-tools' )
        );

        array_unshift( $links, $settings_link );

        return $links;
    } // End add_settings_link()


    /**
     * Hide this plugin from the plugins list.
     *
     * @param array $all_plugins
     * @return array
     */
    public function hide_plugin_from_list( $all_plugins ) {
        if ( ( $this->dev_access_only && ! Helpers::is_dev() ) ) {
            unset( $all_plugins[ $this->plugin_file ] );
            return $all_plugins;

        } elseif ( $this->hiding_plugin && isset( $all_plugins[ $this->plugin_file ] ) ) {
            $name = sanitize_text_field( get_option( 'ddtt_plugin_alias', __( 'Developer Notifications', 'dev-debug-tools' ) ) );
            $desc = sanitize_textarea_field( get_option( 'ddtt_plugin_desc', 'Provides developer-focused system notifications.' ) );
            $author = sanitize_text_field( get_option( 'ddtt_plugin_author', 'WordPress Core Team' ) );

            $all_plugins[ $this->plugin_file ][ 'Name' ] = $name;
            $all_plugins[ $this->plugin_file ][ 'Description' ] = $desc;
            $all_plugins[ $this->plugin_file ][ 'Author' ] = $author;
            $all_plugins[ $this->plugin_file ][ 'AuthorURI' ] = 'https://wordpress.org/';
        }

        return $all_plugins;
    } // End hide_plugin_from_list()


    /**
     * Add a new tab for Dev Debug Tools.
     *
     * @param array $tabs Existing plugin install tabs.
     * @return array Modified tabs with custom "Dev Debug Tools" tab.
     */
    public function add_plugins_tab( $tabs ) {
        $tabs[ 'dev_debug_tools' ] = __( 'Dev Debug Tools', 'dev-debug-tools' );
        return $tabs;
    } // End add_plugins_tab()


    /**
     * Render the custom "Dev Debug Tools" tab content
     *
     * @return void
     */
    public function render_add_plugins_tab() {
        echo '<div class="wrap">';

            require_once ABSPATH . 'wp-admin/includes/class-wp-plugin-install-list-table.php';
            $table = new \WP_Plugin_Install_List_Table();

            $plugin_slugs = $this->recommended_plugins();

            $plugins = [];
            foreach ( $plugin_slugs as $slug ) {
                $response = plugins_api( 'plugin_information', [
                    'slug'   => $slug,
                    'is_ssl' => is_ssl(),
                    'fields' => [
                        'banners'           => true,
                        'reviews'           => true,
                        'downloaded'        => true,
                        'active_installs'   => true,
                        'icons'             => true,
                        'short_description' => true,
                    ],
                ]);

                if ( is_wp_error( $response ) ) {
                    continue;
                }

                if ( $response ) {
                    $plugins[] = $response;
                }
            }

            $table->items = $plugins;
            $table->set_pagination_args([
                'total_items' => count( $plugins ),
                'per_page'    => count( $plugins ),
                'total_pages' => 1,
            ]);

            $table->display();

        echo '</div>';
    } // End render_add_plugins_tab()


    /**
     * Add custom columns to plugins table.
     *
     * @param array $columns
     * @return array
     */
    public function add_columns( $columns ) {
        $is_dev = Helpers::is_dev();

        if ( $is_dev && get_option( 'ddtt_plugins_page_size', true ) ) {
            $columns[ 'ddtt_size' ] = __( 'Size', 'dev-debug-tools' );
        }

        if ( $is_dev && get_option( 'ddtt_plugins_page_path', true ) ) {
            $columns[ 'ddtt_path' ] = __( 'Path', 'dev-debug-tools' );
        }

        if ( get_option( 'ddtt_plugins_page_last_modified', true ) ) {
            $columns[ 'ddtt_last_modified' ] = __( 'Last Updated', 'dev-debug-tools' );
        }

        if ( $is_dev && get_option( 'ddtt_plugins_page_installed_by', true ) ) {
            $columns[ 'ddtt_installed_by' ] = __( 'Installed By', 'dev-debug-tools' );
        }

        return $columns;
    } // End add_columns()


    /**
     * Render content for custom columns.
     *
     * @param string $column_name
     * @param string $plugin_file
     * @param array  $plugin_data
     */
    public function render_column( $column_name, $plugin_file, $plugin_data ) {
        switch ( $column_name ) {
            case 'ddtt_size':
                $this->render_size( $plugin_file );
                break;

            case 'ddtt_path':
                echo esc_html( $plugin_file );
                break;

            case 'ddtt_last_modified':
                $this->render_last_modified( $plugin_file );
                break;

            case 'ddtt_installed_by':
                $this->render_installed_by( $plugin_file );
                break;
        }
    } // End render_column()


    /**
     * Register sortable columns.
     *
     * @param array $columns
     * @return array
     */
    public function register_sortable_columns( $columns ) {
        // Make Name, Size, and Last Updated sortable
        $columns[ 'name' ] = 'name';

        if ( get_option( 'ddtt_plugins_page_size', true ) ) {
            $columns[ 'ddtt_size' ] = 'ddtt_size';
        }

        if ( get_option( 'ddtt_plugins_page_last_modified', true ) ) {
            $columns[ 'ddtt_last_modified' ] = 'ddtt_last_modified';
        }
        
        return $columns;
    } // End register_sortable_columns()


    /**
     * Prepare plugin data for sorting by custom columns.
     */
    public function prepare_plugin_sorting() {
        global $wp_list_table;

        if ( ! isset( $wp_list_table->items ) || ! is_array( $wp_list_table->items ) ) {
            return;
        }

        // Attach size and last modified info to each plugin row
        $sizes = $this->get_plugin_sizes();
        foreach ( $wp_list_table->items as $file => &$plugin ) {
            $plugin[ 'plugin_size' ] = $sizes[ $file ] ?? 0;

            $plugin_dir   = trailingslashit( WP_PLUGIN_DIR ) . dirname( $file );
            $last_mod     = Helpers::folder_last_modified( $plugin_dir );
            $last_stamp   = $last_mod ? strtotime( $last_mod ) : 0;
            $plugin[ 'plugin_last_mod' ] = $last_stamp;
        }

        // Handle sorting
        if ( isset( $_GET[ 'orderby' ] ) ) { // phpcs:ignore
            $orderby = sanitize_text_field( wp_unslash( $_GET[ 'orderby' ] ) ); // phpcs:ignore
            $order   = ( isset( $_GET[ 'order' ] ) && 'desc' === strtolower( wp_unslash( $_GET[ 'order' ] ) ) ) ? 'desc' : 'asc'; // phpcs:ignore 

            if ( 'ddtt_size' === $orderby ) {
                uasort( $wp_list_table->items, function( $a, $b ) use ( $order ) {
                    $a_size = $a[ 'plugin_size' ] ?? 0;
                    $b_size = $b[ 'plugin_size' ] ?? 0;
                    $result = $a_size <=> $b_size;
                    return ( 'desc' === $order ) ? -$result : $result;
                } );
            } elseif ( 'ddtt_last_modified' === $orderby ) {
                uasort( $wp_list_table->items, function( $a, $b ) use ( $order ) {
                    $a_mod = $a[ 'plugin_last_mod' ] ?? 0;
                    $b_mod = $b[ 'plugin_last_mod' ] ?? 0;
                    $result = $a_mod <=> $b_mod;
                    return ( 'desc' === $order ) ? -$result : $result;
                } );
            } elseif ( 'name' === $orderby ) {
                uasort( $wp_list_table->items, function( $a, $b ) use ( $order ) {
                    $result = strcasecmp( $a[ 'Name' ], $b[ 'Name' ] );
                    return ( 'desc' === $order ) ? -$result : $result;
                } );
            }
        }
    } // End prepare_plugin_sorting()


    /**
     * Get stored plugin sizes.
     *
     * @return array
     */
    private function get_plugin_sizes() {
        $sizes = get_option( 'ddtt_plugin_sizes', [] );
        if ( ! is_array( $sizes ) ) {
            $sizes = [];
        }

        // Ensure all active plugins are in the cache
        if ( function_exists( 'get_plugins' ) ) {
            $all_plugins = get_plugins();
            foreach ( $all_plugins as $file => $data ) {
                if ( ! isset( $sizes[ $file ] ) ) {
                    $sizes[ $file ] = $this->calculate_plugin_size( $file );
                }
            }
            update_option( 'ddtt_plugin_sizes', $sizes, false );
        }

        return $sizes;
    } // End get_plugin_sizes()


    /**
     * Calculate the size of a plugin directory.
     *
     * @param string $plugin_file
     * @return int
     */
    private function calculate_plugin_size( $plugin_file ) {
        $plugin_dir = trailingslashit( WP_PLUGIN_DIR ) . dirname( $plugin_file );
        if ( ! is_dir( $plugin_dir ) ) {
            return 0;
        }
        return Helpers::get_directory_size( $plugin_dir );
    } // End calculate_plugin_size()


    /**
     * Update the stored size for a specific plugin.
     *
     * @param string $plugin_file
     */
    public function update_plugin_size( $plugin_file ) {
        $sizes = $this->get_plugin_sizes();
        $sizes[ $plugin_file ] = $this->calculate_plugin_size( $plugin_file );
        update_option( 'ddtt_plugin_sizes', $sizes, false );
    } // End update_plugin_size()


    /**
     * Update sizes for multiple plugins after bulk actions.
     *
     * @param WP_Upgrader $upgrader
     * @param array       $hook_extra
     */
    public function update_plugin_size_bulk( $upgrader, $hook_extra ) {
        if ( isset( $hook_extra[ 'plugins' ] ) && is_array( $hook_extra[ 'plugins' ] ) ) {
            foreach ( $hook_extra[ 'plugins' ] as $plugin_file ) {
                $this->update_plugin_size( $plugin_file );
            }
        }
    } // End update_plugin_size_bulk()


    /**
     * Remove a plugin from the size cache when deleted.
     *
     * @param string $plugin_file
     */
    public function remove_plugin_size( $plugin_file ) {
        $sizes = $this->get_plugin_sizes();

        if ( isset( $sizes[ $plugin_file ] ) ) {
            unset( $sizes[ $plugin_file ] );
            update_option( 'ddtt_plugin_sizes', $sizes, false );
        }
    } // End remove_plugin_size()


    /**
     * Render Size column content.
     *
     * @param string $plugin_file
     */
    protected function render_size( $plugin_file ) {
        $sizes = $this->get_plugin_sizes();
        $size_bytes = $sizes[ $plugin_file ] ?? 0;

        if ( $size_bytes === 0 ) {
            echo esc_html__( '—', 'dev-debug-tools' );
            return;
        }

        $class = ( $size_bytes > self::TOO_LARGE_MB * 1024 * 1024 ) ? ' ddtt-very-large' : '';

        echo '<span class="ddtt-plugin-size' . esc_attr( $class ) . '">' . esc_html( size_format( $size_bytes, 2 ) ) . '</span>';
    } // End render_size()


    /**
     * Record the current user as the installer of a plugin if not already set.
     *
     * @param string  $plugin_file   Path to the plugin file.
     * @param boolean $network_wide  Whether the plugin is activated network-wide.
     */
    public function maybe_record_installer( $plugin_file, $network_wide ) {
        // Get existing installer data
        $installers = filter_var_array( get_option( 'ddtt_plugin_installers', [] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if ( ! is_array( $installers ) ) {
            $installers = [];
        }

        // If plugin already has an installer, do nothing
        if ( ! empty( $installers[ $plugin_file ] ) && strtolower( trim( html_entity_decode( (string) $installers[ $plugin_file ], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) !== 'unknown' ) {
            return;
        }

        // Record the current user as installer
        $current_user = wp_get_current_user();
        if ( $current_user && $current_user->exists() ) {
            $name = $current_user->display_name;
        } else {
            $name = 'Unknown';
        }

        $installers[ $plugin_file ] = $name;

        update_option( 'ddtt_plugin_installers', $installers );
    } // End maybe_record_installer()


    /**
     * Remove a plugin from the installers option when deleted.
     *
     * @param string $plugin_file The plugin file path relative to plugins directory.
     */
    public function remove_plugin_installer( $plugin_file ) {
        $installers = filter_var_array( get_option( 'ddtt_plugin_installers', [] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if ( isset( $installers[ $plugin_file ] ) ) {
            unset( $installers[ $plugin_file ] );
            update_option( 'ddtt_plugin_installers', $installers, false );
        }
    } // End remove_plugin_installer()


    /**
     * Render Last Modified column content.
     *
     * @param string $plugin_file
     */
    protected function render_last_modified( $plugin_file ) {
        $plugin_dir = trailingslashit( WP_PLUGIN_DIR ) . dirname( $plugin_file );
        $last_mod   = Helpers::folder_last_modified( $plugin_dir );

        if ( ! $last_mod || strpos( $last_mod, 'Directory does not exist' ) !== false ) {
            echo esc_html__( '—', 'dev-debug-tools' );
            return;
        }

        $last_timestamp = strtotime( $last_mod );
        if ( ! $last_timestamp ) {
            echo esc_html( $last_mod );
            return;
        }
        
        // Compute thresholds
        $warning_timestamp = strtotime( self::OLD_WARNING_STRING );
        $danger_timestamp  = strtotime( self::OLD_DANGER_STRING );

        // Determine class
        $class = '';
        if ( $last_timestamp < $danger_timestamp ) {
            $class = ' ddtt-very-old';
        } elseif ( $last_timestamp < $warning_timestamp ) {
            $class = ' ddtt-old';
        }

        echo '<span class="ddtt-last-modified' . esc_attr( $class ) . '">' . esc_html( $last_mod ) . '</span>';
    } // End render_last_modified()


    /**
     * Render the Installed By column.
     *
     * @param string $plugin_file Plugin file path.
     */
    protected function render_installed_by( $plugin_file ) {
        // Get stored data
        $data = filter_var_array( get_option( 'ddtt_plugin_installers', [] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        // Default display name
        $display_name = isset( $data[ $plugin_file ] ) ? $data[ $plugin_file ] : esc_html__( 'Unknown', 'dev-debug-tools' );

        // Only Devs can edit
        if ( Helpers::is_dev() ) {
            $incl_edit_btn = '<span class="ddtt-edit-installed-by" data-plugin="' . esc_attr( $plugin_file ) . '" style="cursor:pointer; color:#0073aa;" title="' . esc_attr__( 'Edit Installer', 'dev-debug-tools' ) . '">✎</span>';
        } else {
            $incl_edit_btn = '';
        }

        // Always output editable span
        echo '<div class="ddtt-installed-by-wrapper" style="display:inline-block;">
            <span class="ddtt-installed-by">' . esc_html( $display_name ) . '</span>
            ' . wp_kses_post( $incl_edit_btn ) . '
        </div>
        <a href="#" class="ddtt-duplicate-unknown-installers" style="display:none;">[' . esc_html__( 'Save for All Unknown Installers', 'dev-debug-tools' ) . ']</a>';
    } // End render_installed_by()


    /**
     * Add Add/Edit Notes link below plugin description (row meta).
     *
     * @param array  $links       Existing row meta links.
     * @param string $plugin_file Path to the plugin file.
     * @param array  $plugin_data Plugin data.
     * @return array
     */
    public function add_notes_meta_link( $links, $plugin_file, $plugin_data ) {
        $notes = filter_var_array( get_option( 'ddtt_plugin_notes', [] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $note  = isset( $notes[ $plugin_file ] ) ? $notes[ $plugin_file ] : '';

        // Always show "Edit Note" link and include the static note inside it
        $link_html = '<a href="#" class="ddtt-notes-link" data-plugin="' . esc_attr( $plugin_file ) . '">'
            . esc_html__( 'Edit Note', 'dev-debug-tools' ) . '</a>';

        if ( ! empty( $note ) ) {
            $link_html .= '<div class="ddtt-note-display" data-plugin="' . esc_attr( $plugin_file ) . '" style="margin-top:6px; font-style:italic; color:#444;">' 
                . esc_html( $note ) 
                . '</div>';
        }

        $links[] = $link_html;

        return $links;
    } // End add_notes_meta_link()
    

    /**
     * Enqueue JS and CSS for Plugins page enhancements.
     */
    public function enqueue_assets( $hook ) {
        if ( $hook !== 'plugins.php' ) {
            return;
        }

        // Notes
        $doing_notes = get_option( 'ddtt_plugins_page_notes', true );
        $notes = $doing_notes ? get_option( 'ddtt_plugin_notes', [] ) : [];
        
        // Pass translations and dynamic data
        wp_localize_script( 'ddtt-plugins-page', 'ddtt_plugins', [
            'is_dev'       => Helpers::is_dev(),
            'doing_update' => get_option( 'ddtt_plugins_page_author_update', true ),
            'doing_notes'  => $doing_notes,
            'notes'        => $notes,
            'nonce'        => wp_create_nonce( $this->nonce ),
            'i18n'         => [
                'tooltip_updated_warning' => __( 'This plugin has not been updated by the author in over a year.', 'dev-debug-tools' ),
                'tooltip_updated_danger'  => __( 'This plugin has not been updated by the author in over 2 years.', 'dev-debug-tools' ),
                'tooltip_compat'          => __( 'This plugin may not be compatible with your current WordPress version.', 'dev-debug-tools' ),
                'note_edit'               => __( 'Edit Note', 'dev-debug-tools' ),
                'note_save'               => __( 'Save Note', 'dev-debug-tools' ),
                'unknown'                 => __( 'Unknown', 'dev-debug-tools' ),
            ]
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Update installer name.
     */
    public function ajax_update_installer() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'dev-debug-tools' ) );
        }

        $plugin = isset( $_POST[ 'plugin' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'plugin' ] ) ) : '';
        $name   = isset( $_POST[ 'name' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'name' ] ) ) : '';

        // Properly sanitize do_all as boolean
        $do_all = false;
        if ( isset( $_POST[ 'do_all' ] ) ) {
            $do_all = filter_var( wp_unslash( $_POST[ 'do_all' ] ), FILTER_VALIDATE_BOOLEAN );
        }

        if ( empty( $plugin ) ) {
            wp_send_json_error( __( 'Missing plugin identifier.', 'dev-debug-tools' ) );
        }

        // Get existing data
        $data = filter_var_array( get_option( 'ddtt_plugin_installers', [] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if ( ! is_array( $data ) ) {
            $data = [];
        }

        $updated = [];

        if ( $do_all ) {
            $unknown_label = 'unknown';

            foreach ( get_plugins() as $file => $plugin_info ) {
                $current = isset( $data[ $file ] ) ? html_entity_decode( $data[ $file ], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '';
                if ( empty( $current ) || strtolower( trim( $current ) ) === $unknown_label ) {
                    $data[ $file ] = $name;
                    $updated[] = $file;
                }
            }
        } else {
            $data[ $plugin ] = $name;
            $updated[] = $plugin;
        }

        update_option( 'ddtt_plugin_installers', $data );

        wp_send_json_success( [ 'updated' => $updated ] );
    } // End ajax_update_installer()


    /**
     * Save plugin note via AJAX
     *
     * @return void
     */
    public function ajax_save_plugin_note() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'dev-debug-tools' ) );
        }

        $plugin = isset( $_POST[ 'plugin' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'plugin' ] ) ) : '';
        $note   = isset( $_POST[ 'note' ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ 'note' ] ) ) : '';

        if ( empty( $plugin ) ) {
            wp_send_json_error( __( 'Missing plugin identifier.', 'dev-debug-tools' ) );
        }

        $notes = filter_var_array( get_option( 'ddtt_plugin_notes', [] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if ( ! is_array( $notes ) ) {
            $notes = [];
        }

        $notes[ $plugin ] = $note;
        update_option( 'ddtt_plugin_notes', $notes, false );

        wp_send_json_success( [
            'plugin' => $plugin,
            'note'   => $note,
        ] );
    } // End ajax_save_plugin_note()

}


new Plugins();