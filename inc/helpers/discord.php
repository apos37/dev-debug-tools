<?php
/**
 * Discord Webhook
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class DiscordWebhook {

    // $args = [
    //     'msg'           => 'This is a test',
    //     'embed'         => true,
    //     'author_name'   => 'Your Name',
    //     'author_url'    => 'https://yoururl.com',
    //     'title'         => 'My title',
    //     'title_url'     => 'https://mytitleurl.com',
    //     'desc'          => 'The description',
    //     'img_url'       => '',
    //     'thumbnail_url' => '',
    //     'disable_footer' => true,
    //     'fields' => [
    //          [
    //              'name' => 'Field #2 Name',
    //              'value' => 'Field #2 Value',
    //              'inline' => true
    //          ]
    //      ]
    // ];

    /**
     * Send a message to Discord
     * https://discord.com/developers/docs/resources/channel
     *
     * @param array $args
     * @param string $webhook
     * @return mixed
     */
    public static function send( $webhook, $args, $catch_response = false ) {
        // The webhook
        $webhook_prefix = 'https://discord.com/api/webhooks/';

        if ( ! str_starts_with( $webhook, $webhook_prefix ) && str_starts_with( $webhook, 'http' ) ) {
            Helpers::write_log(
                sprintf(
                    // translators: 1: webhook URL, 2: example Discord webhook URL
                    __( 'Could not send notification to Discord. Webhook URL (%1$s) is not valid. URL should look like this: %2$s', 'dev-debug-tools' ),
                    $webhook,
                    'https://discord.com/api/webhooks/xxx/xxx...'
                )
            );
            apply_filters( 'ddtt_log_error', 'discord_webhook_send', new \Exception( 'Could not send notification to Discord. Webhook URL is not valid.' ), [ 'webhook' => $webhook ] );
            return false;

        } elseif ( ! str_starts_with( $webhook, $webhook_prefix ) ) {
            $webhook_url = $webhook_prefix . $webhook;

        } else {
            $webhook_url = $webhook;
        }

        // Timestamp
        $timestamp = gmdate( 'c', strtotime( 'now' ) );

        // Message data
        $data = [
            // Text-to-speech
            'tts' => false,
        ];

        // Message
        if ( isset( $args[ 'msg'] ) && sanitize_textarea_field( $args[ 'msg' ] ) != '' ) {
            $data[ 'content' ] = sanitize_textarea_field( $args[ 'msg' ] );
        }

        // Change name of bot; default is Developer Debug Tools
        if ( isset( $args[ 'bot_name'] ) && sanitize_text_field( $args[ 'bot_name'] ) != '' ) {
            $data[ 'username' ] = sanitize_text_field( $args[ 'bot_name'] );
        } else {
            $data[ 'username' ] = Bootstrap::name();
        }

        // Change bot avatar url
        if ( isset( $args[ 'bot_avatar_url'] ) && filter_var( $args[ 'bot_avatar_url' ], FILTER_SANITIZE_URL ) != '' ) {
            $data[ 'avatar_url' ] = filter_var( $args[ 'bot_avatar_url' ], FILTER_SANITIZE_URL );
        } else {
            $data[ 'avatar_url' ] = Bootstrap::url( 'inc/hub/img/logo.png' );
        }

        // Embed
        if ( isset( $args[ 'embed' ] ) && filter_var( $args[ 'embed' ], FILTER_VALIDATE_BOOLEAN ) == true ) {
            $data[ 'embeds' ] = [
                [
                    // Embed Type
                    'type' => 'rich',

                    // Embed left border color in HEX
                    'color' => isset( $args[ 'color' ] ) ? hexdec( sanitize_hex_color_no_hash( $args[ 'color' ] ) ) : hexdec( '00ff00' ),

                    // Fields
                    'fields' => $args[ 'fields' ],
                ]
            ];

            // Are we adding the footer?
            if ( ! isset( $args[ 'disable_footer' ] ) || $args[ 'disable_footer' ] !== true ) {
                $data[ 'embeds' ][0][ 'timestamp' ] = $timestamp;
            }

            // Embed author
            if ( isset( $args[ 'author_name' ] ) && $args[ 'author_name' ] != '' && 
                isset( $args[ 'author_url' ] ) && $args[ 'author_url' ] != '' ) {
                $data[ 'embeds' ][0][ 'author' ][ 'name' ] = esc_attr( $args[ 'author_name' ] );
                $data[ 'embeds' ][0][ 'author' ][ 'url' ] = esc_url( $args[ 'author_url' ] );
            }

            // Embed title
            if ( isset( $args[ 'title' ] ) && $args[ 'title' ] != '' ) {
                $data[ 'embeds' ][0][ 'title' ] = esc_html( $args[ 'title' ] );
            }

            // Embed title link
            if ( isset( $args[ 'title_url' ] ) && $args[ 'title_url' ] != '' ) {
                $data[ 'embeds' ][0][ 'url' ] = sanitize_url( $args[ 'title_url' ] );
            }

            // Embed description
            if ( isset( $args[ 'desc' ] ) && $args[ 'desc' ] != '' ) {
                $data[ 'embeds' ][0][ 'description' ] = wp_kses_post( $args[ 'desc' ] );
            }

            // Embed attached image
            if ( isset( $args[ 'img_url' ] ) && $args[ 'img_url' ] != '' ) {
                $data[ 'embeds' ][0][ 'image' ][ 'url' ] = sanitize_url( $args[ 'img_url' ] );
            }

            // Embed thumbnail
            if ( isset( $args[ 'thumbnail_url' ] ) && $args[ 'thumbnail_url' ] != '' ) {
                $data[ 'embeds' ][0][ 'thumbnail' ][ 'url' ] = sanitize_url( $args[ 'thumbnail_url' ] );
            }
        }

        // Encode
        $json_data = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

        // Send it to discord
        $options = [
            'body'        => $json_data,
            'headers'     => [
                'Content-Type' => 'application/json',
            ],
            'timeout'     => 60,
            'redirection' => 5,
            'blocking'    => true,
            'httpversion' => '1.0',
            'sslverify'   => false,
            'data_format' => 'body',
        ];
        
        $send = wp_remote_post( esc_url( $webhook_url ), $options );
        if ( $catch_response ) {
            return $send;
        }

        if ( ! is_wp_error( $send ) && ! empty( $send ) ) {
            return true;

        } else {
            $e = new \Exception( 'Failed to send Discord webhook.' );
            $extra = [
                'webhook_url' => $webhook_url,
                'json_data'   => $json_data,
                'response'    => is_wp_error( $send ) ? $send->get_error_message() : (string) ( $send[ 'response' ][ 'code' ] ?? 'unknown' ),
                'type'        => 'discord_webhook',
            ];
            apply_filters( 'ddtt_log_error', 'discord_send', $e, $extra );

            return false;
        }
    } // End send()

}