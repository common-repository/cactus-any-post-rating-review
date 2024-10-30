<?php
/*
 * add option page
 */
add_action('admin_menu', 'c_aprr_plugin_settings');
function c_aprr_plugin_settings(){
    add_menu_page('Rating Settings', 'Rating Settings', 'administrator', 'c_aprr_settings', 'c_aprr_display_settings', 'dashicons-star-filled', 75);
}
function register_c_aprr_setting() {
	register_setting( 'c_aprr_options_group', 'c_aprr_options_group', 'c_aprr_options_validate' );
	//rating settings
	
	$post_types = get_post_types( array( 'public' => true ) );
	$exclude_posttypes = array(
		'attachment',
		'revision',
		'nav_menu_item',
		'mediapage',
		'page',
		'ct_review'
	);
	$post_types = array_diff($post_types, $exclude_posttypes);
	
	// Create post_type field
	$field_args = array(
		'type'      => 'post_type',
		'id'        => 'ct_post_type',
		'value' 	=> $post_types,
	);

	add_settings_section('c_aprr_settings_rating','','c_aprr_settings_rating_html','c_aprr_settings');
	
	add_settings_field( 'c_aprr_postType', esc_html__( 'Post Type', 'cactus' ), 'c_aprr_postType_html', 'c_aprr_settings', 'c_aprr_settings_rating', $field_args );
	
	add_settings_field('c_aprr_add_postType',esc_html__('Add Review Post Type','cactus'),'c_aprr_add_postType_html','c_aprr_settings','c_aprr_settings_rating');
	
	add_settings_field('c_aprr_criteria',esc_html__('Review Criterias','cactus'),'c_aprr_criteria_html','c_aprr_settings','c_aprr_settings_rating');
	
	add_settings_field('c_aprr_position',esc_html__('Review Position','cactus'),'c_aprr_position_html','c_aprr_settings','c_aprr_settings_rating');
	
	add_settings_field('c_aprr_float',esc_html__('Review Float','cactus'),'c_aprr_float_html','c_aprr_settings','c_aprr_settings_rating');
	
	add_settings_field('c_aprr_title',esc_html__('Default Review Title','cactus'),'c_aprr_title_html','c_aprr_settings','c_aprr_settings_rating');

	//other settings
	add_settings_section('c_aprr_settings_other','','c_aprr_settings_other_html','c_aprr_settings');
	
	add_settings_field('c_aprr_fontawesome',esc_html__('Turn off Font Awesome','cactus'),'c_aprr_fontawesome_html','c_aprr_settings','c_aprr_settings_other');
	add_settings_field('c_aprr_user_rate',esc_html__('User rate option','cactus'),'c_aprr_user_rate_html','c_aprr_settings','c_aprr_settings_other');
	add_settings_field('c_aprr_rate_type',esc_html__('Rate type','cactus'),'c_aprr_rate_type_html','c_aprr_settings','c_aprr_settings_other');
} 
add_action( 'admin_init', 'register_c_aprr_setting' );
/*
 * render option page
 */
function c_aprr_display_settings(){
$c_aprr_options = get_option('c_aprr_options_group');
$c_aprr_heading_title_font = isset($c_aprr_options['c_aprr_heading_title_font'])?$c_aprr_options['c_aprr_heading_title_font']:'';
$c_aprr_heading_subtitle_font = isset($c_aprr_options['c_aprr_heading_subtitle_font'])?$c_aprr_options['c_aprr_heading_subtitle_font']:'';
$c_aprr_post_title_font = isset($c_aprr_options['c_aprr_post_title_font'])?$c_aprr_options['c_aprr_post_title_font']:'';
$c_aprr_post_excerpt_font = isset($c_aprr_options['c_aprr_post_excerpt_font'])?$c_aprr_options['c_aprr_post_excerpt_font']:'';
?>
</pre>

<div class="wrap">
	<div class="c_aprr-setting-page">
		<h1 class="c_aprr-head"><i class="fas fa-star-o"></i><?php echo esc_html__('Any Post Rating Review', 'cactus')?></h1>
		<div class="c_aprr-setting-content">
			<?php if(isset($_GET['settings-updated'])&&$_GET['settings-updated']==true) {?>
			<div class="form-group">
				<div class="form-label"></div>
				<div class="form-control">
					<i class="fas fa-check"></i>
					<?php esc_html_e('Settings were saved.','cactus')?>
				</div>
			</div>
			<?php } ?>
			<form action="options.php" method="post" name="options" id="c_aprr-form" class="tmr-data">
				<?php settings_errors('c_aprr_settings_errors'); ?>
				<?php
					settings_fields('c_aprr_options_group');
					do_settings_sections('c_aprr_settings');
				?>
				<div class="form-group">
					<div class="form-label"></div>
					<div class="form-control">
						<button type="submit" title="Update Default Setting" name="submit" class="button"><i class="fas fa-check"></i><?php esc_html_e('Update','cactus')?></button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<pre>
<?php
}
//header for setting section
function c_aprr_settings_rating_html(){ ?>
	<h2 class="option-group"><i class="fas fa-star"></i><?php echo esc_html__('Review settings.', 'cactus')?></h2>
<?php 
}

//header for setting section
function c_aprr_settings_other_html(){ ?>
	<h2 class="option-group"><i class="fas fa-plus-circle"></i><?php echo esc_html__('Other settings.', 'cactus')?></h2>
<?php 
}


function c_aprr_postType_html($args){
	extract( $args );
    $option_name = 'c_aprr_options_group';
    $options = get_option( $option_name, 'post' );
	
	switch ( $type ) {  
        case 'post_type':
			$options_value = isset($options[$id]) && !empty($options[$id]) ? $options[$id] : array('post');
			
	?>
		<?php foreach( $value as $type): ?>	

			<input type="checkbox" <?php if( !empty($options_value) ) {echo in_array( $type, $options_value ) ? 'checked' : '';} ?> name="<?php echo $option_name . '[' . $id . ']';?>[]" value="<?php echo $type?>" />
			<?php echo $type .'</br>'?>
		<?php endforeach; ?>
		<span class="desc"><?php echo esc_html__("Enable Any Post Rating Review for posts and custom post types. Note: By default Any Post Rating Review is available for posts", "cactus")?></span>
	<?php
		break;	
	}
}

function c_aprr_add_postType_html(){
	$c_aprr_options = get_option('c_aprr_options_group');
	$c_aprr_add_postType_html = isset($c_aprr_options['c_aprr_add_postType_html'])?$c_aprr_options['c_aprr_add_postType_html']:'0';
	?>
    <div class="c_aprr_fontawesome_checkbox">
    <input type="checkbox" <?php echo $c_aprr_add_postType_html ? 'checked' :'' ?> name="c_aprr_options_group[c_aprr_add_postType_html]" value="1" /><span class="desc"><?php echo esc_html__("Check it if you want add new Review post type", "cactus")?></span>
    </div>
    <?php
}


//render options fields
function c_aprr_criteria_html(){
	$c_aprr_options = get_option('c_aprr_options_group');
	$c_aprr_criteria = isset($c_aprr_options['c_aprr_criteria'])?$c_aprr_options['c_aprr_criteria']:'';
	?>
    <textarea name="c_aprr_options_group[c_aprr_criteria]" rows="4" cols="60"><?php echo $c_aprr_criteria ?></textarea>
    <span class="desc"><?php echo esc_html__('Enter default criterias (Ex: Story, Quality...)', 'cactus')?></span>
<?php
}
//render options fields
function c_aprr_position_html(){
	$c_aprr_options = get_option('c_aprr_options_group');
	$c_aprr_position = isset($c_aprr_options['c_aprr_position'])?$c_aprr_options['c_aprr_position']:'top';
	$allow_list  = array(
		 'code' => array(),
	);
	$html_str = "You can use shortcode <code>[ct_rating]</code> to display Review in manual mode";
	
	
	$array = array(
		array(
			'name'=>'c_aprr_options_group[c_aprr_position]',
			'value' => 'top',
			'label' => 'Top',
			'icon' => 'fas fa-arrow-circle-up fa-2x',
		),
		array(
			'name'=>'c_aprr_options_group[c_aprr_position]',
			'value' => 'bottom',
			'label' => 'Bottom',
			'icon' => 'fas fa-arrow-circle-down fa-2x',
		),
		array(
			'name'=>'c_aprr_options_group[c_aprr_position]',
			'value' => '0',
			'label' => 'Manual',
			'icon' => 'fas fa-code fa-2x',
		)
	);
	c_aprr_image_radio($c_aprr_position,$array);?>
    <span class="desc"><?php echo wp_kses($html_str, $allow_list);?></span>
    <?php
}
function c_aprr_float_html(){
	$c_aprr_options = get_option('c_aprr_options_group');
	$c_aprr_float = isset($c_aprr_options['c_aprr_float'])?$c_aprr_options['c_aprr_float']:'block';
	$array = array(
		array(
			'name'=>'c_aprr_options_group[c_aprr_float]',
			'value' => 'block',
			'label' => 'Block',
			'icon' => 'fas fa-align-justify fa-2x',
		),
		array(
			'name'=>'c_aprr_options_group[c_aprr_float]',
			'value' => 'left',
			'label' => 'Float left',
			'icon' => 'fas fa-align-left fa-2x',
		),
		array(
			'name'=>'c_aprr_options_group[c_aprr_float]',
			'value' => 'right',
			'label' => 'Float right',
			'icon' => 'fas fa-align-right fa-2x',
		)
	);
	c_aprr_image_radio($c_aprr_float,$array);?>
    <span class="desc"><?php echo esc_html__('How review display?', 'cactus')?></span>
    <?php
}
function c_aprr_title_html(){
	$c_aprr_options = get_option('c_aprr_options_group');
	$c_aprr_title = isset($c_aprr_options['c_aprr_title'])?$c_aprr_options['c_aprr_title']:'Review';
	?>
    <input type="text" size="60" name="c_aprr_options_group[c_aprr_title]" value="<?php echo $c_aprr_title ?>" /><span class="desc"><?php echo esc_html__('Default is Review', 'cactus')?></span>
<?php
}
function c_aprr_fontawesome_html(){
	$c_aprr_options = get_option('c_aprr_options_group');
	$c_aprr_fontawesome = isset($c_aprr_options['c_aprr_fontawesome'])?$c_aprr_options['c_aprr_fontawesome']:'0';
	?>
    <div class="c_aprr_fontawesome_checkbox">
    <input type="checkbox" <?php echo $c_aprr_fontawesome?'checked':'' ?> name="c_aprr_options_group[c_aprr_fontawesome]" value="1" /><span class="desc"><?php echo esc_html__("Turn off loading plugin's Font Awesome. Check if your theme has already loaded this library", "cactus")?></span>
    </div>
<?php
}

function c_aprr_user_rate_html(){
	$c_aprr_options = get_option('c_aprr_options_group');
	$c_aprr_user_rate = isset($c_aprr_options['c_aprr_user_rate'])?$c_aprr_options['c_aprr_user_rate']:'0';
	$c_aprr_user_rate = isset($c_aprr_options['c_aprr_user_rate'])?$c_aprr_options['c_aprr_user_rate']:'0';

	$all 				= $c_aprr_user_rate == 'all' ? 'selected="selected"' : '';
	$only_user 			= $c_aprr_user_rate == 'only_user' ? 'selected="selected"' : '';
	$none 				= $c_aprr_user_rate == 'none' ? 'selected="selected"' : '';
	?>
    <div class="c_aprr_user_rate_select_box">
    	<select name="c_aprr_options_group[c_aprr_user_rate]">
    		<option value="all" <?php echo $all;?>><?php echo esc_html__('All', 'urbannews');?></option>
    		<option value="only_user" <?php echo $only_user;?>><?php echo esc_html__('Only User', 'urbannews');?></option>
    		<option value="none" <?php echo $none;?>><?php echo esc_html__('Turn off', 'urbannews');?></option>
    	</select>
    </div>
<?php
}

function c_aprr_rate_type_html(){
	$c_aprr_options = get_option('c_aprr_options_group');
	$c_aprr_rate_type = isset($c_aprr_options['c_aprr_rate_type'])?$c_aprr_options['c_aprr_rate_type']:'0';

	$point 			= $c_aprr_rate_type == 'point' ? 'selected="selected"' : '';
	$star 			= $c_aprr_rate_type == 'star' ? 'selected="selected"' : '';
	$percent 		= $c_aprr_rate_type == 'percent' ? 'selected="selected"' : '';
	?>
    <div class="c_aprr_rate_type_select_box">
    	<select name="c_aprr_options_group[c_aprr_rate_type]">
    		<option value="point" <?php echo $point;?>><?php echo esc_html__('Point', 'urbannews');?></option>
    		<option value="star" <?php echo $star;?>><?php echo esc_html__('Star', 'urbannews');?></option>
    		<option value="percent" <?php echo $percent;?>><?php echo esc_html__('Percent', 'urbannews');?></option>
    	</select>
    </div>
<?php
}

//validate
function c_aprr_options_validate( $input ) {
    return $input;  
}

/*
 * build radio image select
 */
function c_aprr_image_radio($option,$array){
?>
<span class="image-select">
	<?php foreach($array as $item){ ?>
    <input type="radio" name="<?php echo $item['name'] ?>" id="<?php echo $item['name'] ?>-<?php echo $item['value'] ?>" value="<?php echo $item['value'] ?>" <?php echo ($option==$item['value'])?'checked':'' ?> />
    <label for="<?php echo $item['name'] ?>-<?php echo $item['value'] ?>" class="<?php echo ($option==$item['value'])?'selected':'' ?>" ><i class="<?php echo $item['icon'] ?> icon-large"></i><br>
    <?php echo $item['label'] ?></label>
    <?php } ?>
</span>
<?php
}
/*
 * enqueue admin scripts
 */
function c_aprr_admin_scripts() {
    wp_enqueue_script('jquery');
	//wp_enqueue_script('jscolor', CAPRR_PATH.'js/jscolor/jscolor.js', array('jquery'));
	wp_enqueue_script('rating_admin', plugins_url( 'admin.js', __FILE__ ), array('jquery'));
	wp_enqueue_style('rating_admin', plugins_url( 'admin.css', __FILE__ ));
	wp_enqueue_style('fontawesome', CAPRR_PATH.'font-awesome/css/fontawesome-all.min.css', array(), '5.0.7');
}
add_action( 'admin_enqueue_scripts', 'c_aprr_admin_scripts' );
/*
 * get list image sizes
 */
function c_aprr_list_thumbnail_sizes(){
	global $_wp_additional_image_sizes;
	$sizes = array();
	foreach( get_intermediate_image_sizes() as $s ){
		$sizes[ $s ] = array( 0, 0 );
		if( in_array( $s, array( 'thumbnail', 'medium', 'large' ) ) ){
			$sizes[ $s ][0] = get_option( $s . '_size_w' );
			$sizes[ $s ][1] = get_option( $s . '_size_h' );
		}else{
			if( isset( $_wp_additional_image_sizes ) && isset( $_wp_additional_image_sizes[ $s ] ) )
			$sizes[ $s ] = array( $_wp_additional_image_sizes[ $s ]['width'], $_wp_additional_image_sizes[ $s ]['height'], );
		}
	}
	return $sizes;
}

//add tinyMCE button
// init process for registering our button
add_action('init', 'c_aprr_shortcode_button_init');
function c_aprr_shortcode_button_init() {
	//Abort early if the user will never see TinyMCE
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
	   return;
	//Add a callback to regiser our tinymce plugin   
	//add_filter("mce_external_plugins", "c_aprr_register_tinymce_plugin"); 
	
	// Add a callback to add our button to the TinyMCE toolbar
	add_filter('mce_buttons', 'c_aprr_add_tinymce_button');
}
//This callback registers our plug-in
//function c_aprr_register_tinymce_plugin($plugin_array) {
//    $plugin_array['c_aprr_button'] = CAPRR_PATH . 'js/button.js';
//    return $plugin_array;
//}
//This callback adds our button to the toolbar
function c_aprr_add_tinymce_button($buttons) {
    //Add the button ID to the $button array
    $buttons[] = "c_aprr_button";
    return $buttons;
}

function c_aprr_font_awesome_option($default=''){
	$icons = array(
		'icon-glass' => '&#xf000;',
		'icon-music' => '&#xf001;',
		'icon-search' => '&#xf002;',
		'icon-envelope-alt' => '&#xf003;',
		'icon-heart' => '&#xf004;',
		'icon-star' => '&#xf005;',
		'icon-star-empty' => '&#xf006;',
		'icon-user' => '&#xf007;',
		'icon-film' => '&#xf008;',
		'icon-th-large' => '&#xf009;',
		'icon-th' => '&#xf00a;',
		'icon-th-list' => '&#xf00b;',
		'icon-ok' => '&#xf00c;',
		'icon-remove' => '&#xf00d;',
		'icon-zoom-in' => '&#xf00e;',
		'icon-zoom-out' => '&#xf010;',
		'icon-off' => '&#xf011;',
		'icon-signal' => '&#xf012;',
		'icon-cog' => '&#xf013;',
		'icon-trash' => '&#xf014;',
		'icon-home' => '&#xf015;',
		'icon-file-alt' => '&#xf016;',
		'icon-time' => '&#xf017;',
		'icon-road' => '&#xf018;',
		'icon-download-alt' => '&#xf019;',
		'icon-download' => '&#xf01a;',
		'icon-upload' => '&#xf01b;',
		'icon-inbox' => '&#xf01c;',
		'icon-play-circle' => '&#xf01d;',
		'icon-repeat' => '&#xf01e;',
		'icon-refresh' => '&#xf021;',
		'icon-list-alt' => '&#xf022;',
		'icon-lock' => '&#xf023;',
		'icon-flag' => '&#xf024;',
		'icon-headphones' => '&#xf025;',
		'icon-volume-off' => '&#xf026;',
		'icon-volume-down' => '&#xf027;',
		'icon-volume-up' => '&#xf028;',
		'icon-qrcode' => '&#xf029;',
		'icon-barcode' => '&#xf02a;',
		'icon-tag' => '&#xf02b;',
		'icon-tags' => '&#xf02c;',
		'icon-book' => '&#xf02d;',
		'icon-bookmark' => '&#xf02e;',
		'icon-print' => '&#xf02f;',
		'icon-camera' => '&#xf030;',
		'icon-font' => '&#xf031;',
		'icon-bold' => '&#xf032;',
		'icon-italic' => '&#xf033;',
		'icon-text-height' => '&#xf034;',
		'icon-text-width' => '&#xf035;',
		'icon-align-left' => '&#xf036;',
		'icon-align-center' => '&#xf037;',
		'icon-align-right' => '&#xf038;',
		'icon-align-justify' => '&#xf039;',
		'icon-list' => '&#xf03a;',
		'icon-indent-left' => '&#xf03b;',
		'icon-indent-right' => '&#xf03c;',
		'icon-facetime-video' => '&#xf03d;',
		'icon-picture' => '&#xf03e;',
		'icon-pencil' => '&#xf040;',
		'icon-map-marker' => '&#xf041;',
		'icon-adjust' => '&#xf042;',
		'icon-tint' => '&#xf043;',
		'icon-edit' => '&#xf044;',
		'icon-share' => '&#xf045;',
		'icon-check' => '&#xf046;',
		'icon-move' => '&#xf047;',
		'icon-step-backward' => '&#xf048;',
		'icon-fast-backward' => '&#xf049;',
		'icon-backward' => '&#xf04a;',
		'icon-play' => '&#xf04b;',
		'icon-pause' => '&#xf04c;',
		'icon-stop' => '&#xf04d;',
		'icon-forward' => '&#xf04e;',
		'icon-fast-forward' => '&#xf050;',
		'icon-step-forward' => '&#xf051;',
		'icon-eject' => '&#xf052;',
		'icon-chevron-left' => '&#xf053;',
		'icon-chevron-right' => '&#xf054;',
		'icon-plus-sign' => '&#xf055;',
		'icon-minus-sign' => '&#xf056;',
		'icon-remove-sign' => '&#xf057;',
		'icon-ok-sign' => '&#xf058;',
		'icon-question-sign' => '&#xf059;',
		'icon-info-sign' => '&#xf05a;',
		'icon-screenshot' => '&#xf05b;',
		'icon-remove-circle' => '&#xf05c;',
		'icon-ok-circle' => '&#xf05d;',
		'icon-ban-circle' => '&#xf05e;',
		'icon-arrow-left' => '&#xf060;',
		'icon-arrow-right' => '&#xf061;',
		'icon-arrow-up' => '&#xf062;',
		'icon-arrow-down' => '&#xf063;',
		'icon-share-alt' => '&#xf064;',
		'icon-resize-full' => '&#xf065;',
		'icon-resize-small' => '&#xf066;',
		'icon-plus' => '&#xf067;',
		'icon-minus' => '&#xf068;',
		'icon-asterisk' => '&#xf069;',
		'icon-exclamation-sign' => '&#xf06a;',
		'icon-gift' => '&#xf06b;',
		'icon-leaf' => '&#xf06c;',
		'icon-fire' => '&#xf06d;',
		'icon-eye-open' => '&#xf06e;',
		'icon-eye-close' => '&#xf070;',
		'icon-warning-sign' => '&#xf071;',
		'icon-plane' => '&#xf072;',
		'icon-calendar' => '&#xf073;',
		'icon-random' => '&#xf074;',
		'icon-comment' => '&#xf075;',
		'icon-magnet' => '&#xf076;',
		'icon-chevron-up' => '&#xf077;',
		'icon-chevron-down' => '&#xf078;',
		'icon-retweet' => '&#xf079;',
		'icon-shopping-cart' => '&#xf07a;',
		'icon-folder-close' => '&#xf07b;',
		'icon-folder-open' => '&#xf07c;',
		'icon-resize-vertical' => '&#xf07d;',
		'icon-resize-horizontal' => '&#xf07e;',
		'icon-bar-chart' => '&#xf080;',
		'icon-twitter-sign' => '&#xf081;',
		'icon-facebook-sign' => '&#xf082;',
		'icon-camera-retro' => '&#xf083;',
		'icon-key' => '&#xf084;',
		'icon-cogs' => '&#xf085;',
		'icon-comments' => '&#xf086;',
		'icon-thumbs-up-alt' => '&#xf087;',
		'icon-thumbs-down-alt' => '&#xf088;',
		'icon-star-half' => '&#xf089;',
		'icon-heart-empty' => '&#xf08a;',
		'icon-signout' => '&#xf08b;',
		'icon-linkedin-sign' => '&#xf08c;',
		'icon-pushpin' => '&#xf08d;',
		'icon-external-link' => '&#xf08e;',
		'icon-signin' => '&#xf090;',
		'icon-trophy' => '&#xf091;',
		'icon-github-sign' => '&#xf092;',
		'icon-upload-alt' => '&#xf093;',
		'icon-lemon' => '&#xf094;',
		'icon-phone' => '&#xf095;',
		'icon-check-empty' => '&#xf096;',
		'icon-bookmark-empty' => '&#xf097;',
		'icon-phone-sign' => '&#xf098;',
		'icon-twitter' => '&#xf099;',
		'icon-facebook' => '&#xf09a;',
		'icon-github' => '&#xf09b;',
		'icon-unlock' => '&#xf09c;',
		'icon-credit-card' => '&#xf09d;',
		'icon-rss' => '&#xf09e;',
		'icon-hdd' => '&#xf0a0;',
		'icon-bullhorn' => '&#xf0a1;',
		'icon-bell' => '&#xf0a2;',
		'icon-certificate' => '&#xf0a3;',
		'icon-hand-right' => '&#xf0a4;',
		'icon-hand-left' => '&#xf0a5;',
		'icon-hand-up' => '&#xf0a6;',
		'icon-hand-down' => '&#xf0a7;',
		'icon-circle-arrow-left' => '&#xf0a8;',
		'icon-circle-arrow-right' => '&#xf0a9;',
		'icon-circle-arrow-up' => '&#xf0aa;',
		'icon-circle-arrow-down' => '&#xf0ab;',
		'icon-globe' => '&#xf0ac;',
		'icon-wrench' => '&#xf0ad;',
		'icon-tasks' => '&#xf0ae;',
		'icon-filter' => '&#xf0b0;',
		'icon-briefcase' => '&#xf0b1;',
		'icon-fullscreen' => '&#xf0b2;',
		'icon-group' => '&#xf0c0;',
		'icon-link' => '&#xf0c1;',
		'icon-cloud' => '&#xf0c2;',
		'icon-beaker' => '&#xf0c3;',
		'icon-cut' => '&#xf0c4;',
		'icon-copy' => '&#xf0c5;',
		'icon-paper-clip' => '&#xf0c6;',
		'icon-save' => '&#xf0c7;',
		'icon-sign-blank' => '&#xf0c8;',
		'icon-reorder' => '&#xf0c9;',
		'icon-list-ul' => '&#xf0ca;',
		'icon-list-ol' => '&#xf0cb;',
		'icon-strikethrough' => '&#xf0cc;',
		'icon-underline' => '&#xf0cd;',
		'icon-table' => '&#xf0ce;',
		'icon-magic' => '&#xf0d0;',
		'icon-truck' => '&#xf0d1;',
		'icon-pinterest' => '&#xf0d2;',
		'icon-pinterest-sign' => '&#xf0d3;',
		'icon-google-plus-sign' => '&#xf0d4;',
		'icon-google-plus' => '&#xf0d5;',
		'icon-money' => '&#xf0d6;',
		'icon-caret-down' => '&#xf0d7;',
		'icon-caret-up' => '&#xf0d8;',
		'icon-caret-left' => '&#xf0d9;',
		'icon-caret-right' => '&#xf0da;',
		'icon-columns' => '&#xf0db;',
		'icon-sort' => '&#xf0dc;',
		'icon-sort-down' => '&#xf0dd;',
		'icon-sort-up' => '&#xf0de;',
		'icon-envelope' => '&#xf0e0;',
		'icon-linkedin' => '&#xf0e1;',
		'icon-undo' => '&#xf0e2;',
		'icon-legal' => '&#xf0e3;',
		'icon-dashboard' => '&#xf0e4;',
		'icon-comment-alt' => '&#xf0e5;',
		'icon-comments-alt' => '&#xf0e6;',
		'icon-bolt' => '&#xf0e7;',
		'icon-sitemap' => '&#xf0e8;',
		'icon-umbrella' => '&#xf0e9;',
		'icon-paste' => '&#xf0ea;',
		'icon-lightbulb' => '&#xf0eb;',
		'icon-exchange' => '&#xf0ec;',
		'icon-cloud-download' => '&#xf0ed;',
		'icon-cloud-upload' => '&#xf0ee;',
		'icon-user-md' => '&#xf0f0;',
		'icon-stethoscope' => '&#xf0f1;',
		'icon-suitcase' => '&#xf0f2;',
		'icon-bell-alt' => '&#xf0f3;',
		'icon-coffee' => '&#xf0f4;',
		'icon-food' => '&#xf0f5;',
		'icon-file-text-alt' => '&#xf0f6;',
		'icon-building' => '&#xf0f7;',
		'icon-hospital' => '&#xf0f8;',
		'icon-ambulance' => '&#xf0f9;',
		'icon-medkit' => '&#xf0fa;',
		'icon-fighter-jet' => '&#xf0fb;',
		'icon-beer' => '&#xf0fc;',
		'icon-h-sign' => '&#xf0fd;',
		'icon-plus-sign-alt' => '&#xf0fe;',
		'icon-double-angle-left' => '&#xf100;',
		'icon-double-angle-right' => '&#xf101;',
		'icon-double-angle-up' => '&#xf102;',
		'icon-double-angle-down' => '&#xf103;',
		'icon-angle-left' => '&#xf104;',
		'icon-angle-right' => '&#xf105;',
		'icon-angle-up' => '&#xf106;',
		'icon-angle-down' => '&#xf107;',
		'icon-desktop' => '&#xf108;',
		'icon-laptop' => '&#xf109;',
		'icon-tablet' => '&#xf10a;',
		'icon-mobile-phone' => '&#xf10b;',
		'icon-circle-blank' => '&#xf10c;',
		'icon-quote-left' => '&#xf10d;',
		'icon-quote-right' => '&#xf10e;',
		'icon-spinner' => '&#xf110;',
		'icon-circle' => '&#xf111;',
		'icon-reply' => '&#xf112;',
		'icon-github-alt' => '&#xf113;',
		'icon-folder-close-alt' => '&#xf114;',
		'icon-folder-open-alt' => '&#xf115;',
		'icon-expand-alt' => '&#xf116;',
		'icon-collapse-alt' => '&#xf117;',
		'icon-smile' => '&#xf118;',
		'icon-frown' => '&#xf119;',
		'icon-meh' => '&#xf11a;',
		'icon-gamepad' => '&#xf11b;',
		'icon-keyboard' => '&#xf11c;',
		'icon-flag-alt' => '&#xf11d;',
		'icon-flag-checkered' => '&#xf11e;',
		'icon-terminal' => '&#xf120;',
		'icon-code' => '&#xf121;',
		'icon-reply-all' => '&#xf122;',
		'icon-mail-reply-all' => '&#xf122;',
		'icon-star-half-empty' => '&#xf123;',
		'icon-location-arrow' => '&#xf124;',
		'icon-crop' => '&#xf125;',
		'icon-code-fork' => '&#xf126;',
		'icon-unlink' => '&#xf127;',
		'icon-question' => '&#xf128;',
		'icon-info' => '&#xf129;',
		'icon-exclamation' => '&#xf12a;',
		'icon-superscript' => '&#xf12b;',
		'icon-subscript' => '&#xf12c;',
		'icon-eraser' => '&#xf12d;',
		'icon-puzzle-piece' => '&#xf12e;',
		'icon-microphone' => '&#xf130;',
		'icon-microphone-off' => '&#xf131;',
		'icon-shield' => '&#xf132;',
		'icon-calendar-empty' => '&#xf133;',
		'icon-fire-extinguisher' => '&#xf134;',
		'icon-rocket' => '&#xf135;',
		'icon-maxcdn' => '&#xf136;',
		'icon-chevron-sign-left' => '&#xf137;',
		'icon-chevron-sign-right' => '&#xf138;',
		'icon-chevron-sign-up' => '&#xf139;',
		'icon-chevron-sign-down' => '&#xf13a;',
		'icon-html5' => '&#xf13b;',
		'icon-css3' => '&#xf13c;',
		'icon-anchor' => '&#xf13d;',
		'icon-unlock-alt' => '&#xf13e;',
		'icon-bullseye' => '&#xf140;',
		'icon-ellipsis-horizontal' => '&#xf141;',
		'icon-ellipsis-vertical' => '&#xf142;',
		'icon-rss-sign' => '&#xf143;',
		'icon-play-sign' => '&#xf144;',
		'icon-ticket' => '&#xf145;',
		'icon-minus-sign-alt' => '&#xf146;',
		'icon-check-minus' => '&#xf147;',
		'icon-level-up' => '&#xf148;',
		'icon-level-down' => '&#xf149;',
		'icon-check-sign' => '&#xf14a;',
		'icon-edit-sign' => '&#xf14b;',
		'icon-external-link-sign' => '&#xf14c;',
		'icon-share-sign' => '&#xf14d;',
		'icon-compass' => '&#xf14e;',
		'icon-collapse' => '&#xf150;',
		'icon-collapse-top' => '&#xf151;',
		'icon-expand' => '&#xf152;',
		'icon-eur' => '&#xf153;',
		'icon-gbp' => '&#xf154;',
		'icon-usd' => '&#xf155;',
		'icon-inr' => '&#xf156;',
		'icon-jpy' => '&#xf157;',
		'icon-cny' => '&#xf158;',
		'icon-krw' => '&#xf159;',
		'icon-btc' => '&#xf15a;',
		'icon-file' => '&#xf15b;',
		'icon-file-text' => '&#xf15c;',
		'icon-sort-by-alphabet' => '&#xf15d;',
		'icon-sort-by-alphabet-alt' => '&#xf15e;',
		'icon-sort-by-attributes' => '&#xf160;',
		'icon-sort-by-attributes-alt' => '&#xf161;',
		'icon-sort-by-order' => '&#xf162;',
		'icon-sort-by-order-alt' => '&#xf163;',
		'icon-thumbs-up' => '&#xf164;',
		'icon-thumbs-down' => '&#xf165;',
		'icon-youtube-sign' => '&#xf166;',
		'icon-youtube' => '&#xf167;',
		'icon-xing' => '&#xf168;',
		'icon-xing-sign' => '&#xf169;',
		'icon-youtube-play' => '&#xf16a;',
		'icon-dropbox' => '&#xf16b;',
		'icon-stackexchange' => '&#xf16c;',
		'icon-instagram' => '&#xf16d;',
		'icon-flickr' => '&#xf16e;',
		'icon-adn' => '&#xf170;',
		'icon-bitbucket' => '&#xf171;',
		'icon-bitbucket-sign' => '&#xf172;',
		'icon-tumblr' => '&#xf173;',
		'icon-tumblr-sign' => '&#xf174;',
		'icon-long-arrow-down' => '&#xf175;',
		'icon-long-arrow-up' => '&#xf176;',
		'icon-long-arrow-left' => '&#xf177;',
		'icon-long-arrow-right' => '&#xf178;',
		'icon-apple' => '&#xf179;',
		'icon-windows' => '&#xf17a;',
		'icon-android' => '&#xf17b;',
		'icon-linux' => '&#xf17c;',
		'icon-dribbble' => '&#xf17d;',
		'icon-skype' => '&#xf17e;',
		'icon-foursquare' => '&#xf180;',
		'icon-trello' => '&#xf181;',
		'icon-female' => '&#xf182;',
		'icon-male' => '&#xf183;',
		'icon-gittip' => '&#xf184;',
		'icon-sun' => '&#xf185;',
		'icon-moon' => '&#xf186;',
		'icon-archive' => '&#xf187;',
		'icon-bug' => '&#xf188;',
		'icon-vk' => '&#xf189;',
		'icon-weibo' => '&#xf18a;',
		'icon-renren' => '&#xf18b;',
	);
	ksort($icons);
	foreach($icons as $name=>$icon){
		$selected = $default==$name?'selected="selected"':'';
		echo '<option value="'.$name.'" '.$selected.' >'.$icon.' '.$name.'</option>';
	}
}