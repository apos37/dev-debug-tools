<style>
#items-per-page-form {
    float: left;
    margin-top: 10px;
}

#items-per-page {
    margin-left: 10px;
    text-align: center;
    width: 70px;
    padding: 0 !important;
    height: 30px !important;
    min-height: 30px !important;
}

.pagination {
    text-align: center;
    margin-top: 1rem;
}

.page-num, .page-info {
    display: inline-block;
    margin: 0 20px;
}
</style>
<?php include 'header.php'; 

// Build the current URL
$page = ddtt_plugin_options_short_path();
$tab = 'media-library';
$current_url = ddtt_plugin_options_path( $tab );

// Limit
if ( $per_page = ddtt_get( 'per_page' ) ) {
    $limit = $per_page;
    update_option( DDTT_GO_PF.'media_per_page', $limit );
} elseif ( $db_per_page = get_option( DDTT_GO_PF.'media_per_page' ) ) {
    $limit = $db_per_page;
} else {
    $limit = 10;
}
$page_number = isset( $_GET[ 'page_num' ] ) ? intval( $_GET[ 'page_num' ] ) : 1;
$offset = ( $page_number - 1 ) * $limit;

// Query attachments using get_posts
$args = [
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'posts_per_page' => $limit,
    'offset'         => $offset,
];
$media_items = get_posts( $args );

// Total attachments
$all_attachments = get_posts( [
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'posts_per_page' => -1,
] );

// Total attachments
$total_attachments = count( $all_attachments );
$total_pages = ceil( $total_attachments / $limit );

// Count images, videos, audio, documents, fonts, and other media types
$count_images = 0;
$count_videos = 0;
$count_audio = 0;
$count_documents = 0;
$count_fonts = 0;
$count_others = 0;

foreach ( $all_attachments as $attachment ) {
    $type = get_post_mime_type( $attachment->ID );
    
    if ( strpos( $type, 'image' ) !== false ) {
        $count_images++;
    } elseif ( strpos( $type, 'video' ) !== false ) {
        $count_videos++;
    } elseif ( strpos( $type, 'audio' ) !== false ) {
        $count_audio++;
    } elseif ( strpos( $type, 'application' ) !== false ) {
        $count_documents++;
    } elseif ( strpos( $type, 'font' ) !== false ) {
        $count_fonts++;
    } else {
        $count_others++;
    }
}

// Get other sources to look for images
$site_logo = get_option( 'site_logo' );

// Uploads dir
$uploads_dir = wp_upload_dir()[ 'baseurl' ];
?>

<strong>Total Images:</strong> <?php echo esc_html( $count_images ); ?><br>
<strong>Total Videos:</strong> <?php echo esc_html( $count_videos ); ?><br>
<strong>Total Audio:</strong> <?php echo esc_html( $count_audio ); ?><br>
<strong>Total Documents:</strong> <?php echo esc_html( $count_documents ); ?><br>
<strong>Total Fonts:</strong> <?php echo esc_html( $count_fonts ); ?><br>
<strong>Total Others:</strong> <?php echo esc_html( $count_others ); ?><br><br><br>

<p><strong>Disclaimer:</strong> Table will not show where images are used in CSS backgrounds or in the header, footer or sidebars. It only searches featured images and content. </p>

<div class="full_width_container">    
    <h3><?php echo esc_html( $total_attachments ); ?> media items found</h3>

    <table class="admin-large-table">
        <tr>
            <th>Media ID</th>
            <th>Thumbnail</th>
            <th>Title</th>
            <th>Type</th>
            <th>URL</th>
            <th>Added</th>
            <th>Alt Text</th>
            <th>File Size</th>
            <th>Dimensions</th>
            <th>Caption</th>
            <th>Description</th>
            <th>Used In Posts</th>
        </tr>

        <?php
        foreach ( $media_items as $media ) {
            // Convert IDs to WP_Post objects
            $media_post = get_post( $media );

            $media_url = wp_get_attachment_url( $media_post->ID );
            $type = $media_post->post_mime_type;

            // File size
            $file_size = size_format( filesize( get_attached_file( $media_post->ID ) ) );

            // Dimensions
            list( $width, $height ) = getimagesize( get_attached_file( $media_post->ID ) );
            $dimensions = "{$width} x {$height}";

            // Captions and Description
            $caption = $media_post->post_excerpt;
            $description = $media_post->post_content;

            // Use SQL query to count where this media is used
            global $wpdb;
            $image_filename = basename( get_attached_file( $media_post->ID ) );
            $sql = $wpdb->prepare(
                "SELECT ID, post_title, post_content FROM {$wpdb->prefix}posts
                WHERE post_content LIKE %s
                AND post_type != 'attachment'
                AND post_type != 'revision'",
                '%' . $wpdb->esc_like( $image_filename ) . '%'
            );
            $related_posts = $wpdb->get_results( $sql );

            // Find pages where the media is used as featured image
            $featured_pages = get_posts( [
                'post_type'      => 'any', 
                'posts_per_page' => -1, 
                'meta_key'       => '_thumbnail_id', 
                'meta_value'     => $media_post->ID, 
            ] );

            // Check the site logo
            if ( $site_logo && $site_logo == $media_post->ID ) {
                $related_posts[] = (object) [ 
                    'post_title' => 'Site Logo', 
                ];
            }

            // Thumbnail
            $thumbnail = wp_get_attachment_image( $media_post->ID, [ '75', '75' ] );

            // Date Added and Uploader details
            $date_added = gmdate( 'F j, Y', strtotime( $media_post->post_date ) );
            $uploader_name = get_the_author_meta( 'display_name', $media_post->post_author );
            $uploader_id = $media_post->post_author;

            // Alt Text
            $alt_text = get_post_meta( $media_post->ID, '_wp_attachment_image_alt', true );
            ?>
            <tr>
                <td><span class="highlight-variable"><?php echo esc_attr( $media_post->ID ); ?></span></td>
                <td><a href="<?php echo esc_url( $media_url ); ?>" target="_blank"><?php echo $thumbnail ? wp_kses_post( $thumbnail ) : 'No Thumbnail'; ?></a></td>
                <td><?php echo esc_html( $media_post->post_title ); ?></td>
                <td><code>(<?php echo esc_attr( $type ); ?>)</code></td>
                <td><a href="<?php echo esc_url( $media_url ); ?>" target="_blank"><?php echo esc_html( str_replace( $uploads_dir, '', $media_url ) ); ?></a></td>
                <td><?php echo wp_kses_post( $date_added . '<br>By ' . $uploader_name ); ?></td>
                <td><?php echo esc_html( $alt_text ); ?></td>
                <td><?php echo esc_html( $file_size ); ?></td>
                <td><?php echo esc_html( $dimensions ); ?></td>
                <td><?php echo esc_html( $caption ); ?></td>
                <td><?php echo esc_html( $description ); ?></td>
                <td>
                    <?php 
                    if ( !empty( $featured_pages ) ) {
                        foreach ( $featured_pages as $page ) {
                            echo '<a href="' . esc_url( get_permalink( $page->ID ) ) . '" target="_blank">' . esc_html( $page->post_title ) . ' (Featured)</a><br><br>';
                        }
                    } 

                    if ( !empty( $related_posts ) ) {
                        if ( !empty( $featured_pages ) ) {
                            echo '<br>';
                        }
                        foreach ( $related_posts as $post ) {
                            $link = isset( $post->ID ) ? '<a href="' . get_permalink( $post->ID ) . '" target="_blank">' . $post->post_title . '</a>' : '<strong>' . $post->post_title . '</strong>'; 
                            echo wp_kses_post( $link ) . '<br><br>';
                        }
                    } 

                    if ( empty( $featured_pages ) && empty( $related_posts ) ) {
                        echo 'None';
                    }
                    ?>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>

    <?php if ( $total_attachments > $default_limit ) : ?>
        <form id="items-per-page-form">
            <label for="items-per-page">Records per page:</label>
            <select id="items-per-page" name="per_page" onchange="window.location.href = '<?php echo esc_url( $current_url ); ?>&per_page=' + this.value;">
                <option value="10" <?php selected( $limit, 10 ); ?>>10</option>
                <option value="25" <?php selected( $limit, 25 ); ?>>25</option>
                <option value="50" <?php selected( $limit, 50 ); ?>>50</option>
            </select>
        </form>
    <?php endif; ?>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="pagination">
            <?php if ( $page_number > 1 ) : ?>
                <a class="page-num first" href="<?php echo esc_url( add_query_arg( 'page_num', 1, $current_url ) ); ?>">« First</a>
                <a class="page-num previous" href="<?php echo esc_url( add_query_arg( 'page_num', $page_number - 1, $current_url ) ); ?>">‹ Previous</a>
            <?php else : ?>
                <span class="page-num first disabled">« First</span>
                <span class="page-num previous disabled">‹ Previous</span>
            <?php endif; ?>

            <span class="page-info">Page <?php echo esc_html( $page_number ); ?> of <?php echo esc_html( $total_pages ); ?></span>

            <?php if ( $page_number < $total_pages ) : ?>
                <a class="page-num next" href="<?php echo esc_url( add_query_arg( 'page_num', $page_number + 1, $current_url ) ); ?>">Next ›</a>
                <a class="page-num last" href="<?php echo esc_url( add_query_arg( 'page_num', $total_pages, $current_url ) ); ?>">Last »</a>
            <?php else : ?>
                <span class="page-num next disabled">Next ›</span>
                <span class="page-num last disabled">Last »</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php
    // Restore the original post data
    wp_reset_postdata();
    ?>
</div>
