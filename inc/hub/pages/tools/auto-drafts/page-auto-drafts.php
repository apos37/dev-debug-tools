<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

// Query auto-draft posts
$args = [
    'post_type'      => 'any',
    'post_status'    => 'auto-draft',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
];
$posts = get_posts( $args );

$auto_drafts = [];
foreach ( $posts as $post ) {
    if ( ! $post ) continue;
    $auto_drafts[] = [
        'ID'        => $post->ID,
        'title'     => $post->post_title,
        'type'      => $post->post_type,
        'date'      => $post->post_date,
        'author'    => get_the_author_meta( 'display_name', $post->post_author ),
    ];
}

$tool_settings = AutoDrafts::settings();
?>

<div id="ddtt-page-title-section">
    <div id="ddtt-page-title-left">
        <h2><?php esc_html_e( 'Auto Drafts', 'dev-debug-tools' ); ?></h2>
        <p>
            <strong><?php esc_html_e( 'What are auto-drafts?', 'dev-debug-tools' ); ?></strong>
            <?php esc_html_e( 'Auto-drafts are posts automatically created by WordPress when you start writing a new post but have not yet saved it. They help prevent data loss and allow recovery of unsaved content.', 'dev-debug-tools' ); ?>
        </p>
    </div>
</div>

<?php Settings::render_settings_section( $tool_settings ); ?>

<section id="ddtt-tool-section" class="ddtt-auto-drafts ddtt-section-content">
    <h3><?php echo esc_html__( 'Total # of Auto Drafts:', 'dev-debug-tools' ); ?> <span id="ddtt-total-auto-drafts"><?php echo esc_html( count( $auto_drafts ) ); ?></span></h3>

    <table class="ddtt-table">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'ID', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Title', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Post Type', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Created Date', 'dev-debug-tools' ); ?></th>
                <th><?php echo esc_html__( 'Author', 'dev-debug-tools' ); ?></th>
                <th style="width: 100px; text-align: right; padding-right: 2rem;"><?php echo esc_html__( 'Clear', 'dev-debug-tools' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach( $auto_drafts as $draft ) { ?>
                <tr>
                    <td><a class="ddtt-highlight-variable" href="<?php echo esc_url( Metadata::post_lookup_url( $draft['ID'] ) ); ?>" target="_blank"><?php echo esc_html( $draft['ID'] ); ?></a></td>
                    <td><?php echo esc_html( $draft['title'] ); ?></td>
                    <td><?php echo esc_html( $draft['type'] ); ?></td>
                    <td><?php echo esc_html( $draft['date'] ); ?></td>
                    <td><?php echo esc_html( $draft['author'] ); ?></td>
                    <td style="text-align: right;"><a class="ddtt-clear-auto-draft ddtt-button" href="#">Clear</a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>