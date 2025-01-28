<style>
.taxonomies-table ul {
    margin: 0 !important;
}
.taxonomies-table td.settings {
    max-width: 400px;
}
</style>

<?php include 'header.php'; 

// Get all registered taxonomies, including private ones
$taxonomies = get_taxonomies( [], 'objects' );
$taxonomies_dropdown = $taxonomies;

// Sort the taxonomies alphabetically by their labels
usort( $taxonomies_dropdown, function( $a, $b ) {
    return strcmp( $a->labels->name, $b->labels->name );
} );
?>

<!-- Taxonomy dropdown -->
<div class="taxonomy-filter">
    <label for="taxonomy-select"><strong>Jump to Taxonomy:</strong></label>
    <select id="taxonomy-select" onchange="if (this.value) window.location.href=this.value;">
        <option value="">Select a taxonomy</option>
        <?php foreach ( $taxonomies_dropdown as $taxonomy ) : ?>
            <option value="#taxonomy-<?php echo esc_attr( $taxonomy->name ); ?>">
                <?php echo esc_html( $taxonomy->labels->name . ' (' . $taxonomy->name . ')' ); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div><br><br>

<div class="full_width_container">
    <h3><?php echo count( $taxonomies ); ?> Registered Taxonomies</h3>

    <div class="table-container taxonomies-table-container">
        <table class="admin-large-table taxonomies-table">
            <thead>
                <tr>
                    <th>Taxonomy</th>
                    <th>Public</th>
                    <th>Settings</th>
                    <th>Labels</th>
                    <th>Associated Post Types</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( !empty( $taxonomies ) ) : ?>
                    <?php foreach ( $taxonomies as $taxonomy ) : ?>
                        <tr id="taxonomy-<?php echo esc_attr( $taxonomy->name ); ?>">
                            <td><span class="highlight-variable"><?php echo esc_html( $taxonomy->labels->name ); ?></span> (<code class="hl"><?php echo esc_attr( $taxonomy->name ); ?></code>)</td>
                            <td><?php echo esc_html( $taxonomy->public ? 'Yes' : 'No' ); ?></td>
                            <td class="settings">
                                <ul>
                                    <?php
                                    // Caps
                                    $taxonomy->cap = array_keys( get_object_vars( $taxonomy->cap ) );

                                    // Return the value
                                    $excluded_keys = [ 'labels', 'object_type' ]; // Exclude these keys
                                    foreach ( $taxonomy as $key => $value ) {
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
                                    <?php foreach ( (array) $taxonomy->labels as $key => $label ) : ?>
                                        <li><strong><code class="hl"><?php echo esc_attr( $key ); ?></code>:</strong> <?php echo esc_html( $label ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                <ul>
                                    <?php
                                    $object_types = $taxonomy->object_type;
                                    if ( ! empty( $object_types ) ) {
                                        foreach ( $object_types as $object_type ) {
                                            $post_type_object = get_post_type_object( $object_type );
                                            echo '<li>' . esc_html( $post_type_object ? $post_type_object->labels->name : $object_type ) . ' (<code class="hl">' . esc_html( $object_type ) . '</code>)</li>';
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
                        <td colspan="5"><?php echo esc_html__( 'No taxonomies found.', 'your-text-domain' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
