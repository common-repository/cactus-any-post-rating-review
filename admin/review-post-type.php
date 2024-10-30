<?php
/*
* Review post type
*/

add_action('init', 'ct_review_register_post_type');
function ct_review_register_post_type() {
			
	$labels = array(
		'name'               => esc_html__('Review', 'cactus'),
		'singular_name'      => esc_html__('Review', 'cactus'),
		'add_new'            => esc_html__('Add New Review', 'cactus'),
		'add_new_item'       => esc_html__('Add New Review', 'cactus'),
		'edit_item'          => esc_html__('Edit Review', 'cactus'),
		'new_item'           => esc_html__('New Review', 'cactus'),
		'all_items'          => esc_html__('All Reviews', 'cactus'),
		'view_item'          => esc_html__('View Review', 'cactus'),
		'search_items'       => esc_html__('Search Review', 'cactus'),
		'not_found'          => esc_html__('No Review found', 'cactus'),
		'not_found_in_trash' => esc_html__('No Review found in Trash', 'cactus'),
		'parent_item_colon'  => '',
		'menu_name'          => esc_html__('Reviews', 'cactus'),
	);

	$slug = 'review';
	$rewrite =  array( 'slug' => untrailingslashit( $slug ), 'with_front' => false, 'feeds' => true );

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => $rewrite,
		'capability_type'    => 'post',
		'capabilities'       => array( 'create_posts' => false ),       
		'map_meta_cap'       => true,
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'menu_icon' 		 => 'dashicons-star-filled',
		'exclude_from_search' => true,
		'add_new_item' => false,
		'supports'           => array('')
	);

	register_post_type( 'ct_review', $args );

}

add_action( 'admin_init', 'ct_review_register_metadata' );
/**
 * Register metadata for ct_review post type, using Options Tree API. It has list-item type option
 */
function ct_review_register_metadata() {

	$meta_review_info = array(
		'id' => 'meta_box_member_info',
		'title' => esc_html__( 'Review Info', 'cactus' ),
		'pages' => array( 'ct_review' ),
		'context' => 'normal',
		'priority' => 'high',
		'fields' => array(
			array(
				'id'        => 'ct_review_score',
				'label'     => esc_html__('Review Score', 'cactus'),
				'std'       => '',
				'type'      => 'text',
			),
			array(
				'id'        => 'ct_review_username',
				'label'     => esc_html__('Review User Name', 'cactus'),
				'std'       => '',
				'type'      => 'text',
			),
			array(
				'id'           => 'ct_review_ip',
				'label'        => esc_html__('Review IP Address', 'cactus'),
				'std'          => '',
				'type'         => 'text',
			),
			array(
				'id'           => 'ct_review_content',
				'label'        => esc_html__('Review Content', 'cactus'),
				'std'          => '',
				'type'         => 'textarea',
			),
		),
	);

	if ( function_exists( 'ot_register_meta_box' ) ) {
		ot_register_meta_box($meta_review_info);
	} else {
		// show admin notices
		add_action( 'admin_notices', 'admin_notice_requirements' );
	}

}

function admin_notice_requirements() {
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php echo wp_kses( sprintf(__('Cactus Review plugin requires Options Tree for custom meta fields. Click <a href="%s">here</a> to install', 'cactus' ), admin_url('plugin-install.php')), array('a' => array('href' => array()))); ?></p>
	</div>
	<?php
}