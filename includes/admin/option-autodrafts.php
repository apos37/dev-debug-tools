<style>
.clear-buttons {
    margin-right: 2px !important;
}
</style>

<?php include 'header.php'; ?>

<?php 
// Build the current url
$page = ddtt_plugin_options_short_path();
$tab = 'autodrafts';
$current_url = ddtt_plugin_options_path( $tab );

// Hidden path
$hidden_path = '<input type="hidden" name="page" value="'.$page.'">
<input type="hidden" name="tab" value="'.$tab.'">';

// Clear
if ( $clear = ddtt_get( 'clear-autodrafts' ) ) {
    if ( $clear == 'all' ) {
        ddtt_delete_autodrafts( true );
        $clear_notice = 'All auto-drafts, including changesets, have been deleted.';
    } elseif ( $clear == 'seven' ) {
        ddtt_delete_autodrafts( false );
        $clear_notice = 'Any auto-drafts older than 7 days, including changesets, have been .';
    }
    ddtt_remove_qs_without_refresh( 'clear-autodrafts' );
    ?>
    <div class="notice notice-success is-dismissible">
    <p><?php _e( $clear_notice, 'dev-debug-tools' ); ?></p>
    </div>
    <?php
} elseif ( $delete_post_id = ddtt_get( 'clear-autodraft' ) ) {
    if ( wp_delete_post( $delete_post_id, true ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Auto-Draft Post ID <strong>'.$delete_post_id.'</strong> has been deleted.', 'dev-debug-tools' ); ?></p>
        </div>
        <?php
    } else {
        ?>
        <div class="notice notice-error is-dismissible">
        <p><?php _e( 'Auto-Draft Post ID <strong>'.$delete_post_id.'</strong> cannot be deleted.', 'dev-debug-tools' ); ?></p>
        </div>
        <?php
    }
}

// Changesets
if ( ddtt_get( 'changesets' ) == 'true' ) {
    $changesets = true;
    $changeset_link = 'This list only includes changesets. <a href="'.$current_url.'#posts">Show non-changesets</a>.';
} else {
    $changesets = false;
    $changeset_link = 'This list does not include <a href="'.$current_url.'&changesets=true#posts">changesets</a>.';
}

// Get the posts
global $wpdb;
$post_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_status = 'auto-draft'" );

// Show clear buttons only if we have some
if ( !empty( $post_ids ) ) {
    ?>
    <h2>Clear All Auto-Draft Posts</h2>
    <p>Clearing also includes <a href="<?php echo esc_url( $current_url ); ?>&changesets=true#posts">changesets</a>.</p>
    <a href="<?php echo esc_url( $current_url ); ?>&clear-autodrafts=all" class="button button-primary clear-buttons">Clear All</a>
    <a href="<?php echo esc_url( $current_url ); ?>&clear-autodrafts=seven" class="button button-primary clear-buttons">Clear Older Than 7 Days</a>
    <br><br><hr><br></br>
    <?php 
    }
?>

<!-- The tables -->
<div class="full_width_container" id="posts">
    <h2>Posts</h2>
    <p><em>Note: some posts will not have a title yet. <?php echo wp_kses_post( $changeset_link ); ?></em></p>
    <table class="admin-large-table">
        <tr>
            <th>ID</th>
            <th style="width: 300px;">Title</th>
            <th>Post Type</th>
            <th>Created Date</th>
            <th>Author</th>
            <th style="width: 110px">Delete</th>
        </tr>
        <?php
        // Store author name here so we don't have to look it up more than once
        $author_names = [];

        // Iter the posts
        foreach( $post_ids as $post_id ) {

            // Get the post
            $post = get_post( $post_id );

            // Post type
            $post_type = $post->post_type;

            // Let's ignore changesets
            if ( ( !$changesets && $post_type == 'customize_changeset' ) ||
                 ( $changesets && $post_type != 'customize_changeset' ) ) {
                continue;
            }

            // Title
            $title = $post->post_title;
            if ( $title == '' ) {
                $title = '--';
            }

            // Post author id
            $author_id = $post->post_author;

            // Author name
            if ( !isset( $author_names[ $author_id ] ) ) {
                $author = get_user_by( 'ID', $author_id );
                if ( $author ) {
                    $author_name = $author->display_name;
                } else {
                    $author_name = 'Author Not Found (ID #'.$author_id.')';
                }
                $author_names[ $author_id ] = $author_name;
            } else {
                $author_name = $author_names[ $author_id ];
            }
            ?>
            <tr>
                <td><a href="<?php echo esc_url( ddtt_plugin_options_path( 'postmeta' ) ); ?>&post_id=<?php echo absint( $post_id ); ?>"><?php echo absint( $post_id ); ?></a></td>
                <td><?php echo esc_html( $title ); ?></td>
                <td><span class="highlight-variable"><?php echo esc_html( $post_type ); ?></span></td>
                <td><?php echo esc_html( date( 'n/j/Y', strtotime( $post->post_date ) ) ); ?></td>
                <td><?php echo esc_html( $author_name ); ?></td>
                <td><a href="<?php echo esc_url( $current_url ); ?>&clear-autodraft=<?php echo absint( $post_id ); ?>" class="button button-primary clear-buttons">Delete Post</a></td>
            </tr>
            <?php
        }
        ?>
    </table>
</div>
<br><br><br>

<?php