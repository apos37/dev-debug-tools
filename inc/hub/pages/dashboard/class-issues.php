<?php
/**
 * Issues
 */

namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Issues {

    /**
     * Return the list of issues to check.
     *
     * @return array
     */
    public function get() : array {
        $issues = [
            'admin_email_domain_mismatch' => [
                'label'    => __( 'Admin Email Domain Mismatch', 'dev-debug-tools' ),
                'details'  => __( 'The domain of the site admin email address should be the same as the domain of the website. A mismatch can affect deliverability of system emails, especially from SPF/DKIM-protected servers.', 'dev-debug-tools' ),
                'severity' => 'warning',
                'actions'  => [
                    [
                        'label' => __( 'Update Admin Email', 'dev-debug-tools' ),
                        'url'   => admin_url( 'options-general.php#admin_email' ),
                    ],
                ],
                'callback' => [ $this, 'admin_email_domain_mismatch' ],
            ],
            'site_home_url_mismatch' => [
                'label'    => __( 'Site URL and Home URL Mismatch', 'dev-debug-tools' ),
                'details'  => __( 'The Site URL and Home URL should match. A mismatch can cause issues with content delivery and site functionality.', 'dev-debug-tools' ),
                'severity' => 'warning',
                'actions'  => [
                    [
                        'label' => __( 'Update URLs', 'dev-debug-tools' ),
                        'url'   => admin_url( 'options-general.php' ),
                    ],
                ],
                'callback' => [ $this, 'site_home_url_mismatch' ],
            ],
            'plain_permalink_structure' => [
                'label'    => __( 'Plain Permalink Structure', 'dev-debug-tools' ),
                'details'  => __( 'It is recommended to use a more descriptive permalink structure for better SEO and usability.', 'dev-debug-tools' ),
                'severity' => 'warning',
                'actions'  => [
                    [
                        'label' => __( 'Update Permalink Settings', 'dev-debug-tools' ),
                        'url'   => admin_url( 'options-permalink.php' ),
                    ],
                ],
                'callback' => [ $this, 'plain_permalink_structure' ],
            ],
            'not_using_child_theme' => [
                'label'    => __( 'Not Using Child Theme', 'dev-debug-tools' ),
                'details'  => __( 'Using a child theme is a best practice for customizing themes without losing changes when the parent theme is updated.', 'dev-debug-tools' ),
                'severity' => 'notice',
                'actions'  => [
                    [
                        'label' => __( 'Learn About Child Themes', 'dev-debug-tools' ),
                        'url'   => 'https://developer.wordpress.org/themes/advanced-topics/child-themes/',
                    ],
                ],
                'callback' => [ $this, 'not_using_child_theme' ],
            ],
        ];


        /**
         * Filter the list of issues.
         */
        $issues = apply_filters( 'ddtt_issues', $issues );

        return $issues;
    } // End get()


    /**
     * Check if the admin email domain matches the site domain.
     *
     * @return bool  true if there is an issue, false otherwise.
     */
    public function admin_email_domain_mismatch() : bool {
        $admin_email = get_option( 'admin_email' );
        $site_domain = wp_parse_url( home_url(), PHP_URL_HOST );
        $admin_domain = substr( strrchr( $admin_email, '@' ), 1 );
        if ( $site_domain && $admin_domain && $site_domain !== $admin_domain ) {
            return false;
        }
        return true;
    } // End admin_email_domain_mismatch()
    

    /**
     * Check if the site URL and home URL are mismatched.
     *
     * @return bool  true if there is an issue, false otherwise.
     */
    public function site_home_url_mismatch() : bool {
        $site_url = get_option( 'siteurl' );
        $home_url = get_option( 'home' );
        return ( $site_url !== $home_url );
    } // End site_home_url_mismatch()


    /**
     * Check if the permalink structure is set to plain.
     *
     * @return bool  true if there is an issue, false otherwise.
     */
    public function plain_permalink_structure() : bool {
        $permalink_structure = get_option( 'permalink_structure' );
        return empty( $permalink_structure );
    } // End plain_permalink_structure()


    /**
     * Detect if a child theme is not in use.
     *
     * @return bool  true if there is an issue, false otherwise.
     */
    public function not_using_child_theme() : bool {
        $is_child_theme = is_child_theme();
        return ! $is_child_theme;
    } // End not_using_child_theme()

}