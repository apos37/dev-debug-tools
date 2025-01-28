<style>
.post-types-table ul {
    margin: 0 !important;
}
.post-types-table td.settings {
    max-width: 400px;
}
</style>

<?php include 'header.php'; 

// Get all registered post types, including private ones
$post_types = get_post_types( [], 'objects' );
$post_types_dropdown = $post_types;
global $_wp_post_type_features;

// Sort the post types alphabetically by their labels
usort( $post_types_dropdown, function( $a, $b ) {
    return strcmp( $a->labels->name, $b->labels->name );
} );
?>

<!-- Post Type dropdown -->
<div class="post-type-filter">
    <label for="post-type-select"><strong>Jump to Post Type:</strong></label>
    <select id="post-type-select" onchange="if (this.value) window.location.href=this.value;">
        <option value="">Select a post type</option>
        <?php foreach ( $post_types_dropdown as $post_type ) : ?>
            <option value="#post-type-<?php echo esc_attr( $post_type->name ); ?>">
                <?php echo esc_html( $post_type->labels->name . ' (' . $post_type->name . ')' ); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div><br><br>

<div class="full_width_container">
    <h3><?php echo count( $post_types ); ?> Registered Post Types</h3>

    <div class="table-container post-types-table-container">
        <table class="admin-large-table post-types-table">
            <thead>
                <tr>
                    <th>Post Type</th>
                    <th>Public</th>
                    <th>Settings</th>
                    <th>Labels</th>
                    <th>Associated Taxonomies</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( !empty( $post_types ) ) : ?>
                    <?php foreach ( $post_types as $post_type ) : ?>
                        <tr id="post-type-<?php echo esc_attr( $post_type->name ); ?>">
                            <td><span class="highlight-variable"><?php echo esc_html( $post_type->labels->name ); ?></span> (<code class="hl"><?php echo esc_attr( $post_type->name ); ?></code>)</td>
                            <td><?php echo esc_html( $post_type->public ? 'Yes' : 'No' ); ?></td>
                            <td class="settings">
                                <ul>
                                    <?php
                                    // Supports (since they are missing from the object)
                                    if ( isset( $_wp_post_type_features[ $post_type->name ] ) ) {
                                        $supports = array_keys( $_wp_post_type_features[ $post_type->name ] );
                                        if ( !property_exists( $post_type, 'supports' ) ) {
                                            $post_type->supports = [];
                                        }
                                        $post_type->supports = $supports;
                                    }                             

                                    // Caps
                                    $post_type->cap = array_keys( get_object_vars( $post_type->cap ) );

                                    // Return the value
                                    $excluded_keys = [ 'labels', 'taxonomies' ]; // Exclude these keys
                                    foreach ( $post_type as $key => $value ) {
                                        if ( in_array( $key, $excluded_keys, true ) ) {
                                            continue;
                                        }
                                        
                                        if ( $key == 'rest_namespace' ) {
                                            $output = $value;
                                        } else {
                                            $output = is_bool( $value ) ? ( $value ? 'Yes' : 'No' ) : json_encode( $value, JSON_PRETTY_PRINT );
                                        }
                                        
                                        echo '<li><strong><code class="hl">' . esc_attr( $key ) . '</code>:</strong> ' . esc_html( $output ) . '</li>';
                                    }
                                    ?>
                                </ul>
                            </td>
                            <td>
                                <ul>
                                    <?php foreach ( (array) $post_type->labels as $key => $label ) : ?>
                                        <li><strong><code class="hl"><?php echo esc_attr( $key ); ?></code>:</strong> <?php echo esc_html( $label ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                <ul>
                                    <?php
                                    $taxonomies = get_object_taxonomies( $post_type->name, 'objects' );
                                    if ( ! empty( $taxonomies ) ) {
                                        foreach ( $taxonomies as $taxonomy ) {
                                            echo '<li>' . wp_kses_post( $taxonomy->labels->name ) . ' (<code class="hl">' . esc_html( $taxonomy->name ) . '</code>)</li>';
                                        }
                                    } else {
                                        echo '<li>None</li>';
                                    }
                                    ?>
                                </ul>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php echo esc_html__( 'No post types found.', 'your-text-domain' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>