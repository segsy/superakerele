<?php

// Add Category Fields
function rosetta_add_category_fields($taxonomy){

	$layout_options = array(
    	'default'          => esc_html__('Default Category Layout', 'rosetta'),
    	'grid'              => esc_html__('Grid 2 Columns', 'rosetta'),
        'grid_col_3'        => esc_html__('Grid 3 Columns', 'rosetta'),
        'box'               => esc_html__('Box 2 Columns', 'rosetta'), 
        'box_col_3'         => esc_html__('Box 3 Columns', 'rosetta'),  
        'masonry'           => esc_html__('Masonry 2 Columns', 'rosetta'),      
        'masonry_col_3'     => esc_html__('Masonry 3 Columns', 'rosetta'),
        'box_masonry'       => esc_html__('Box Masonry 2 Columns', 'rosetta'),      
        'box_masonry_col_3' => esc_html__('Box Masonry 3 Columns', 'rosetta'),
        'standard'          => esc_html__('Standard', 'rosetta'),
    );
    ?>
    <div class="form-field term-group">
        <label for="rosetta_cat_layout"><?php esc_html_e('Category Layout', 'rosetta'); ?></label>
        <select name="rosetta_cat_layout" id="rosetta_cat_layout">
        	<?php
        	foreach ($layout_options as $key => $value) {
        		echo '<option value="'. esc_attr($key) .'">'. esc_html($value) .'</option>';
        	}
        	?>
        </select>
        <p><?php esc_html_e( 'Select Category Layout', 'rosetta' ); ?></p>
    </div>
    <?php
}
add_filter('category_add_form_fields', 'rosetta_add_category_fields');

// Edit Category Fields
function rosetta_edit_category_fields($term) {
    $layout = get_term_meta($term->term_id, 'rosetta_cat_layout', true);

    $layout_options = array(
    	'default'          => esc_html__('Default Category Layout', 'rosetta'),
    	'grid'              => esc_html__('Grid 2 Columns', 'rosetta'),
        'grid_col_3'        => esc_html__('Grid 3 Columns', 'rosetta'),
        'box'               => esc_html__('Box 2 Columns', 'rosetta'), 
        'box_col_3'         => esc_html__('Box 3 Columns', 'rosetta'),  
        'masonry'           => esc_html__('Masonry 2 Columns', 'rosetta'),      
        'masonry_col_3'     => esc_html__('Masonry 3 Columns', 'rosetta'),
        'box_masonry'       => esc_html__('Box Masonry 2 Columns', 'rosetta'),      
        'box_masonry_col_3' => esc_html__('Box Masonry 3 Columns', 'rosetta'),
        'standard'          => esc_html__('Standard', 'rosetta'),
    );
    ?>
    <table class="form-table">
        <tr class="form-field">
            <th scope="row" valign="top">
            	<label for="rosetta_cat_layout"><?php esc_html_e('Category Layout', 'rosetta'); ?></label>
            </th>
            <td>
                <select name="rosetta_cat_layout" id="rosetta_cat_layout">
                	<?php
                	foreach ($layout_options as $key => $option) {
                		echo '<option value="'. esc_attr($key) .'"'. selected( $key, $layout ) .'>'. esc_html($option) .'</option>';
                	}
                	?>
                </select>
                <p class="description"><?php esc_html_e( 'Select Category Layout', 'rosetta' ); ?></p>
            </td>
        </tr>
    </table>
<?php
}
add_filter('category_edit_form_fields', 'rosetta_edit_category_fields');

// Save Category Fields
function rosetta_save_category_fields($term_id) {
    if (isset($_POST['rosetta_cat_layout']) && '' !== $_POST['rosetta_cat_layout']){
        add_term_meta($term_id, 'rosetta_cat_layout', $_POST['rosetta_cat_layout'], true);
    }
}
add_action ( 'create_category', 'rosetta_save_category_fields');

// Update Category Fields
function rosetta_update_category_fields( $term_id ) {
    if (isset($_POST['rosetta_cat_layout']) && '' !== $_POST['rosetta_cat_layout']){
        update_term_meta($term_id, 'rosetta_cat_layout', $_POST['rosetta_cat_layout']);
    }
}
add_action ( 'edited_category', 'rosetta_update_category_fields');
