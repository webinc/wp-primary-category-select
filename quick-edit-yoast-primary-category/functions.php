<?php
add_filter( 'manage_post_posts_columns', 'wi_add_columns' );
/**
 * Add columns to management page
 *
 * @param array $columns
 *
 * @return array
 */
function wi_add_columns( $columns ) {
    $columns['_yoast_wpseo_primary_category'] = 'Category Slug';
    return $columns;
}

add_action( 'manage_posts_custom_column', 'wi_columns_content', 10, 2 );
 
/**
 * Set content for columns in management page
 *
 * @param string $column_name
 * @param int $post_id
 *
 * @return void
 */
function wi_columns_content( $column_name, $post_id ) {
    if ( '_yoast_wpseo_primary_category' != $column_name ) {
        return;
    }
 
    $term_id = get_post_meta( $post_id, '_yoast_wpseo_primary_category', true );
	$primary_category = 'not set';
	if(!empty($term_id)){
		//$primary_category = get_term_by('id', $term_id);
		$cat = get_term($term_id);		
		$primary_category = get_the_category_by_ID($term_id);
		$slug = $cat->slug;
	}
    echo $slug; //$primary_category ;
}

// Add to our admin_init function
add_action('quick_edit_custom_box',  'wi_add_quick_edit', 10, 2);

function wi_add_quick_edit($column_name, $post_type) {
    if ($column_name != '_yoast_wpseo_primary_category') return;
    ?>
    <fieldset class="inline-edit-col-left">
    <div class="inline-edit-col">
        <span class="title">Primary Category Set</span>
        <input type="hidden" name="wi_prim_cat_set_noncename" id="wi_prim_cat_set_noncename" value="" />		
        <?php 
			  $cats = get_categories( array(
					'orderby' => 'name',
					'order'   => 'ASC'
				) );
        ?>
        <select name='primary_cat_set' id='primary_cat_set'>
            <option class='p_category' value='0'>None</option>
            <?php 
            foreach ($cats as $cat) {
                echo "<option class='cat-option' value='{$cat->term_id}'>{$cat->slug}</option>\n";
            }
                ?>
        </select>
    </div>
    </fieldset>
    <?php
}

// Add to our admin_init function
add_action('save_post', 'wi_save_quick_edit_data');
 
function wi_save_quick_edit_data($post_id) {
    // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
    // to do anything
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
        return $post_id;    
    // Check permissions
    if ( 'page' == $_POST['post_type'] ) {
        if ( !current_user_can( 'edit_page', $post_id ) )
            return $post_id;
    } else {
        if ( !current_user_can( 'edit_post', $post_id ) )
        return $post_id;
    }   
    // OK, we're authenticated: we need to find and save the data
    $post = get_post($post_id);
    if (isset($_POST['primary_cat_set']) && ($post->post_type != 'revision')) {
        $cat_set_id = esc_attr($_POST['primary_cat_set']);
        if ($cat_set_id)
            update_post_meta( $post_id, '_yoast_wpseo_primary_category', $cat_set_id);     
        else
            delete_post_meta( $post_id, '_yoast_wpseo_primary_category');     
    }       
    return $cat_set_id;  
}

add_action('admin_footer', 'wi_quick_edit_javascript');
 
function wi_quick_edit_javascript() {
    global $current_screen;
    if (($current_screen->id != 'edit-post') || ($current_screen->post_type != 'post')) return; 
     
    ?>
    <script type="text/javascript">
    <!--
    function set_inline_widget_set(widgetSet, nonce) {
        // revert Quick Edit menu so that it refreshes properly
        inlineEditPost.revert();
        var widgetInput = document.getElementById('primary_cat_set');
        var nonceInput = document.getElementById('wi_prim_cat_set_noncename');
        nonceInput.value = nonce;
        // check option manually
        for (i = 0; i < widgetInput.options.length; i++) {
            if (widgetInput.options[i].value == widgetSet) { 
                widgetInput.options[i].setAttribute("selected", "selected"); 
            } else { widgetInput.options[i].removeAttribute("selected"); }
        }
    }
    //-->
    </script>
    <?php
}

// Add to our admin_init function
add_filter('post_row_actions', 'wi_expand_quick_edit_link', 10, 2);
 
function wi_expand_quick_edit_link($actions, $post) {
    global $current_screen;
    if (($current_screen->id != 'edit-post') || ($current_screen->post_type != 'post')) return $actions; 
 
    $nonce = wp_create_nonce( 'wi_primary_cat_set'.$post->ID);
    $widget_id = get_post_meta( $post->ID, '_yoast_wpseo_primary_category', TRUE); 
    $actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="';
    $actions['inline hide-if-no-js'] .= esc_attr( __( 'Edit this item inline' ) ) . '" ';
    $actions['inline hide-if-no-js'] .= " onclick=\"set_inline_widget_set('{$widget_id}', '{$nonce}')\">"; 
    $actions['inline hide-if-no-js'] .= __( 'Quick&nbsp;Edit' );
    $actions['inline hide-if-no-js'] .= '</a>';
    return $actions;    
}

?>