<?php 

/**
 * Get posts
 * More info: https://kinsta.com/blog/wordpress-get_posts/
 */
// Setup the args
// Available compare parameters: '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'NOT EXISTS', 'REGEXP', 'NOT REGEXP' or 'RLIKE'
// Orderby params: 'ID', 'author', 'title', 'name', 'type', 'date', 'modified', 'parent', 'rand', 'comment_count'
function ddtt_example_get_posts() {
    $args = [
        'include'           => [ '3', '7' ],
        'exclude'           => [ '6', '42' ],
        'posts_per_page'    => -1,
        'post_status'       => 'publish',
        'post_type'         => 'sfwd-courses',
        'orderby'           => 'title',
        'order'             => 'ASC',
        'offset'            => 30,
        'post_parent'       => 37,
        'category'          => 1,3,5,
        'tag'               => 'example_slug',

        'meta_query'        => [
            'relation'		=> 'AND',
            [
                'key'		=> 'year_published',
                'value'		=> 2010,
                'type'		=> 'numeric',
                'compare'	=> '>',
            ],
            [
                'key'		=> 'price',
                'value'		=> [ 10, 25 ],
                'type'		=> 'numeric',
                'compare'	=> 'BETWEEN',
            ]
        ],

        'meta_key'		=> 'cover',
        'meta_value'	=> 'paperback',
        'meta_compare'	=> '=',

        'relation'		=> 'AND',
        'tax_query'         => [
            [
                'taxonomy'  => 'book_category',
                'field'     => 'slug',
                'terms'     => [ 'sci-fi', 'history' ],
            ],
            [
                'taxonomy'  => 'book_author',
                'field'     => 'term_id',
                'terms'     => 22,
            ]
        ],

        'fields'            => 'ids',
    ];
    
    // Get the posts
    // Note: no need to clean up/reset post data with get_posts() unless you are using setup_postdata()
    $posts = get_posts( $args );
    
    // Stop if no posts are found
    if ( !$posts ) {
        return 'No posts found.';
    }
    
    // Loop through each post
    foreach ( $posts as $post ) {
    
        // Get the post title
        $post_title = $post->post_title;
    
        // Console.log title
        ddtt_console( $post_title );
    }
}


/**
 * Shortcode description
 * Usage: [my_shortcode param="Goodbye" param2="John"]
 *
 * @param array $atts
 * @return string
 */
add_shortcode( 'my_shortcode', 'my_shortcode_function' );
function my_shortcode_function( $atts ) {
    // Get the parameters
    $atts = shortcode_atts(
        [
            'param' => 'Hello',
            'param2' => 'world'
        ], $atts
    );

    // Do some stuff, while passing the parameter values
    $results = $atts[ 'param' ].' '.$atts[ 'param2' ].'!';
    return $results;
}


/**
 * Shortcode description
 * Usage: [my_shortcode param="Goodbye" param2="John"]
 *
 * @param array $atts
 * @return string
 */
add_shortcode( 'my_shortcode', 'veru_shortcode_function' );
function veru_shortcode_function( $atts ) {
    // Get the parameters if any
    // If none, remove $atts above and below
    $atts = shortcode_atts(
        [
            'param' => 'Hello',
            'param2' => 'world'
        ], $atts
    );

    // Do some stuff, while passing the parameter values
    $results = $atts[ 'param' ].' '.$atts[ 'param2' ].'!';

    // Query args
    $args = [
        'posts_per_page'    => -1,              // Get all of the posts
        'post_status'       => 'publish',       // Only the published ones
        'post_type'         => 'ranks',         // Your post type slug
    ];
    
    // Get the posts
    $posts = get_posts( $args );
    
    // Stop if no posts are found
    if ( !$posts ) {
        return 'No posts found.';
    }

    // Start the outer container
    $results = '<div class="my-outer-container">';
    
    // Loop through each post
    foreach ( $posts as $post ) {

        // Get the ID
        $post_id = $post->ID;

        // Get a field value from ACF
        if ( get_field( 'key_name', $post_id ) && get_field( 'key_name', $post_id ) == 'region' ) {

            // Get the post title
            $title = $post->post_title;

            // Get the post excerpt
            $excerpt = $post->post_excerpt;
        
            // Add a card 
            $results .= '<div class="my-card-container">
                <h3>'.$title.'</h3>
                <p>'.$excerpt.'</p>
            </div>';
        }
    }

    // End the outer container
    $results .= '</div>';

    // Return the results
    return $results;
} // End veru_shortcode_function()