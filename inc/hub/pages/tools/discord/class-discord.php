<?php
/**
 * Discord
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Discord {

    /**
     * Get the options for tool.
     *
     * @return array
     */
    public static function settings() : array {
        return [
            'general' => [
                'label'  => __( 'Send a test message', 'dev-debug-tools' ),
                'fields' => [
                    'discord_webhook_url' => [
                        'title'       => __( "Discord Webhook URL", 'dev-debug-tools' ),
                        'desc'        => __( "Enter your Discord webhook URL to send test messages.", 'dev-debug-tools' ),
                        'type'        => 'url',
                        'placeholder' => 'https://discord.com/api/webhooks/xxx/xxx',
                    ],
                    'discord_embed_title' => [
                        'title'   => __( "Embed Title", 'dev-debug-tools' ),
                        'desc'    => __( "Enter the title for the embed message.", 'dev-debug-tools' ),
                        'type'    => 'text',
                        'default' => __( 'Test Message', 'dev-debug-tools' ),
                    ],
                    'discord_title_url' => [
                        'title'       => __( "Title URL", 'dev-debug-tools' ),
                        'desc'        => __( "Enter a title URL.", 'dev-debug-tools' ),
                        'type'        => 'url',
                        'default'     => home_url(),
                    ],
                    'discord_message_body' => [
                        'title'   => __( "Message Body", 'dev-debug-tools' ),
                        'desc'    => __( "Enter the body for the embed message.", 'dev-debug-tools' ),
                        'type'    => 'textarea',
                        'default' => __( 'This is a test message sent from the Dev Debug Tools plugin.', 'dev-debug-tools' ),
                    ],
                    'discord_embed_color' => [
                        'title'   => __( "Embed Color", 'dev-debug-tools' ),
                        'desc'    => __( "Set the embed border color in HEX (without #).", 'dev-debug-tools' ),
                        'type'    => 'text',
                        'default' => '00ff00',
                    ],
                    'discord_bot_name' => [
                        'title'   => __( "Bot Name", 'dev-debug-tools' ),
                        'desc'    => __( "Set a custom name for the bot sending the message. This will override the one you set up in your Discord channel.", 'dev-debug-tools' ),
                        'type'    => 'text',
                        'default' => Bootstrap::name(),
                    ],
                    'discord_bot_avatar_url' => [
                        'title'   => __( "Bot Avatar URL", 'dev-debug-tools' ),
                        'desc'    => __( "Enter a URL for the bot avatar.", 'dev-debug-tools' ),
                        'type'    => 'url',
                        'default' => Bootstrap::url( 'inc/hub/img/logo.png' ),
                    ],
                    'discord_image_url' => [
                        'title'   => __( "Embed Image URL", 'dev-debug-tools' ),
                        'desc'    => __( "URL for an image to display in the embed.", 'dev-debug-tools' ),
                        'type'    => 'url',
                        'default' => '',
                    ],
                    'discord_thumbnail_url' => [
                        'title'   => __( "Embed Thumbnail URL", 'dev-debug-tools' ),
                        'desc'    => __( "URL for a thumbnail to display in the embed.", 'dev-debug-tools' ),
                        'type'    => 'url',
                        'default' => '',
                    ],
                ],
            ],
        ];
    } // End settings()


    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'ddtt_discord_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Discord $instance = null;


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
        add_action( 'wp_ajax_ddtt_send_message', [ $this, 'ajax_send_message' ] );
        add_action( 'wp_ajax_nopriv_ddtt_send_message', '__return_false' );
    } // End __construct()


    /**
     * Enqueue assets
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) : void {
        if ( ! AdminMenu::is_current_screen( $hook, 'tools', 'discord' ) ) {
            return;
        }

        wp_localize_script( 'ddtt-tool-discord', 'ddtt_discord', [
            'nonce' => wp_create_nonce( $this->nonce ),
            'i18n'  => [
                'send'    => __( 'Send Message', 'dev-debug-tools' ),
                'sending' => __( 'Sending message', 'dev-debug-tools' ),
                'sent'    => __( 'Message sent!', 'dev-debug-tools' ),
                'error'   => __( 'There was an error sending the message.', 'dev-debug-tools' ),
                'empty'   => __( 'Please fill in all required fields.', 'dev-debug-tools' ),
            ],
        ] );
    } // End enqueue_assets()


    /**
     * AJAX: Send test message to Discord
     *
     * @return void
     */
    public function ajax_send_message() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'unauthorized' );
        }

        // Required
        $webhook = isset( $_POST[ 'webhook' ] ) ? filter_var( wp_unslash( $_POST[ 'webhook' ] ), FILTER_SANITIZE_URL ) : '';
        if ( empty( $webhook ) ) {
            apply_filters( 'ddtt_log_error', 'ajax_send_message', new \Exception( 'No webhook URL provided in AJAX request.' ), [ 'step' => 'empty_webhook' ] );
            wp_send_json_error( 'empty_webhook' );
        }

        // Map of AJAX keys => [option name, sanitize type]
        $fields = [
            'webhook'         => [ 'ddtt_discord_webhook_url', 'url' ],
            'title'           => [ 'ddtt_discord_embed_title', 'text' ],
            'title_url'       => [ 'ddtt_discord_title_url', 'url' ],
            'message'         => [ 'ddtt_discord_message_body', 'textarea' ],
            'color'           => [ 'ddtt_discord_embed_color', 'text' ],
            'bot_name'        => [ 'ddtt_discord_bot_name', 'text' ],
            'bot_avatar_url'  => [ 'ddtt_discord_bot_avatar_url', 'url' ],
            'img_url'         => [ 'ddtt_discord_image_url', 'url' ],
            'thumbnail_url'   => [ 'ddtt_discord_thumbnail_url', 'url' ],
        ];

        $sanitized_values = [ ];

        foreach ( $fields as $ajax_key => $info ) {
            list( $option_name, $type ) = $info;

            if ( isset( $_POST[ $ajax_key ] ) ) {
                $value = wp_unslash( $_POST[ $ajax_key ] ); // phpcs:ignore

                if ( $ajax_key === 'color' ) {
                    // Special handling for color to remove # if present and limit to 6 chars
                    $value = ltrim( $value, '#' );
                    if ( preg_match( '/^[a-fA-F0-9]{6}$/', $value ) !== 1 ) {
                        $value = '00ff00';
                    }
                }

                switch ( $type ) {
                    case 'url':
                        $value = esc_url_raw( $value );
                        break;
                    case 'textarea':
                        $value = sanitize_textarea_field( $value );
                        break;
                    default:
                        $value = sanitize_text_field( $value );
                }

                // Save sanitized value to DB using proper option name
                update_option( $option_name, $value );

                // Keep for DiscordWebhook args
                $sanitized_values[ $ajax_key ] = $value;
            } else {
                $sanitized_values[ $ajax_key ] = '';
            }
        }

        // Prepare arguments for DiscordWebhook
        $args = [
            'embed'          => true,
            'title'          => $sanitized_values[ 'title' ],
            'title_url'      => $sanitized_values[ 'title_url' ],
            'desc'           => $sanitized_values[ 'message' ],
            'color'          => $sanitized_values[ 'color' ],
            'bot_name'       => $sanitized_values[ 'bot_name' ],
            'bot_avatar_url' => $sanitized_values[ 'bot_avatar_url' ],
            'img_url'        => $sanitized_values[ 'img_url' ],
            'thumbnail_url'  => $sanitized_values[ 'thumbnail_url' ],
            'disable_footer' => true,
        ];

        $send = DiscordWebhook::send( $sanitized_values[ 'webhook' ], $args, true );

        if ( is_wp_error( $send ) ) {
            wp_send_json_error( $send->get_error_message() );
        } elseif ( ! empty( $send[ 'response' ] ) && isset( $send[ 'response' ][ 'code' ] ) && $send[ 'response' ][ 'code' ] >= 400 ) {
            wp_send_json_error( 'HTTP error: ' . $send[ 'response' ][ 'code' ] );
        } else {
            wp_send_json_success( 'Message sent' );
        }
    } // End ajax_send_message()


    /**
     * Prevent cloning and unserializing
     */
    public function __clone() {}
    public function __wakeup() {}
    
}


Discord::instance();