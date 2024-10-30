<?php
/*
Plugin Name: Cactus Any Post Rating & Review
Description: Enable Rating & Review for any post type
Version: 1.0
Author: CactusThemes
Author URI: https://www.cactusthemes.com
License: GPL/GNU v2
*/

define( 'CAPRR_PATH', plugin_dir_url( __FILE__ ) );

if( ! class_exists( 'OT_Loader' ) ) {
	require_once( 'option-tree/ot-loader.php' );
}

require_once( 'admin/plugin-options.php' );
// Make sure we don't expose any info if called directly

if ( !function_exists( 'add_action' ) ) {
	echo esc_html__("Hi there!  I\'m just a plugin, not much I can do when called directly.", "cactus");
	exit;
}

class CactusAnyPostRating {
	
	private static $instance;
	
	const LANGUAGE_DOMAIN = 'cactus';
			
	public static function getInstance(){
		if(null == self::$instance){
			self::$instance = new CactusAnyPostRating();
		}
		
		return self::$instance;
		
		load_plugin_textdomain( self::LANGUAGE_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	
	public $c_aprr_id = 1;
	
	//construct
	public function __construct() {
		
		$c_aprr_options = $this->c_aprr_get_all_option();
		$c_aprr_add_postType = isset($c_aprr_options[ 'c_aprr_add_postType_html' ]) && $c_aprr_options[ 'c_aprr_add_postType_html' ] != '' ? $c_aprr_options[ 'c_aprr_add_postType_html' ] : 0 ;
		if($c_aprr_add_postType == 1){
			require_once( 'admin/review-post-type.php' );
		}
		
		add_shortcode( 'ct_rating', array( $this, 'c_aprr_shortcode' ) );
		add_shortcode('ct_rating_list', array($this, 'rating_list'));
		add_action( 'wp_enqueue_scripts', array( $this, 'c_aprr_frontend_scripts' ) );
		add_action( 'after_setup_theme', array( $this, 'c_aprr_post_meta' ) );
		add_action( 'save_post', array( $this, 'tm_review_save_post' ) );
		
		add_action('anypost_rating_after_user_rate', array( $this,'ct_review_insert_post'));

		if ( is_admin() ) {
			add_action( 'wp_ajax_add_user_rate', array( $this, 'ct_wp_ajax_add_user_rate' ) );
			add_action( 'wp_ajax_nopriv_add_user_rate', array( $this, 'ct_wp_ajax_add_user_rate' ) );
		}

		add_filter( 'the_content', array( $this, 'c_aprr_the_content_filter' ), 20 );
		add_filter( 'get_the_content', array( $this, 'c_aprr_the_content_filter' ), 20 );
		add_filter( 'mce_external_plugins', array( & $this, 'regplugins' ) );
		add_filter( 'mce_buttons_3', array( & $this, 'regbtns' ) );
	}
	
	function rating_list($atts, $content = ''){
		$args = array(
					'post_type' => 'ct_review',
					'post_per_page' => -1
				);
		
		$reviews = new WP_Query($args);
		
		ob_start();
		if($reviews->have_posts()){
			?>
			<ul class="reviews">
			<?php
			while($reviews->have_posts()){
				$reviews->the_post();
				
				$reviewer = get_user_by('login', get_post_meta(get_the_ID(), 'ct_review_username', true));
				
				$avg_score_rate_meta = get_post_meta( get_the_ID(), 'avg_score_rate', true );
				$avg_score_rate = $avg_score_rate_meta != '' ? $avg_score_rate_meta : 0;
				
				global $c_aprr_options;
				$c_aprr_options = get_option( 'c_aprr_options_group' );
				?>
				<li class="review">
					<?php if($reviewer) { ?>
					<div class="reviewer">
						<?php echo get_avatar($reviewer->ID);?>
						<h4 class="name"><?php echo $reviewer->display_name;?></h4></div>
					<?php } ?>
					<div class="review-text">
						<div class="review-date">
							<?php echo get_the_date();?>
						</div>
						<div class="review-stars-rated">
							<div class="review-stars empty"></div>
							<div class="review-stars filled" style="width:<?php echo esc_attr($c_aprr_options['c_aprr_rate_type'] == 'point' ? $avg_score_rate : ($avg_score_rate * 10) .'%' );?>;"></div>
						</div>
						<div class="content"><?php echo get_post_meta(get_the_ID(), 'ct_review_content', true);?></div>
					</div>
				</li>
				<?php
			}
			
			wp_reset_postdata();
			?>
			</ul>
			<?php
		}
		
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
	}

	/*
	 * Setup and do shortcode
	 */
	function c_aprr_shortcode( $atts, $content = "" ) {
		$ct_review_form = get_post_meta( get_the_ID(), 'ct_review_content', true );
		$c_aprr_options = $this->c_aprr_get_all_option();
		$c_aprr_criteria = $c_aprr_options[ 'c_aprr_criteria' ] ? explode( ",", $c_aprr_options[ 'c_aprr_criteria' ] ) : '';
		$c_aprr_float = isset( $atts[ 'float' ] ) ? $atts[ 'float' ] : $c_aprr_options[ 'c_aprr_float' ];
		$c_aprr_title = isset( $atts[ 'title' ] ) ? $atts[ 'title' ] : ( get_post_meta( get_the_ID(), 'review_title', true ) ? get_post_meta( get_the_ID(), 'review_title', true ) : $c_aprr_options[ 'c_aprr_title' ] );
		$c_aprr_options[ 'c_aprr_rate_type' ] = ( get_post_meta( get_the_ID(), 'rate_type', true ) != '' ? get_post_meta( get_the_ID(), 'rate_type', true ) : $c_aprr_options[ 'c_aprr_rate_type' ] );
		$only_user_rate = isset($atts['only_user_rate']) ? intval($atts['only_user_rate']) : 0;
		ob_start();
		if ( isset( $atts[ 'post_id' ] ) ) {
			$post_id = $atts[ 'post_id' ];
		} else {
			global $post;
			$post_id = $post->ID;
		}
		
		$ct_content = '';
		$ct_editor_id = 'ct_review_form';
		$ct_settings = array(
			'wpautop' => false,
			'media_buttons' => false,
			'textarea_name' => $ct_editor_id,
			'textarea_rows' => 4,
			'teeny' => true
		);
		
		$enable_rating = get_post_meta($post_id, 'enable_rating', true);
		if($enable_rating == ''){
			$enable_rating = 'off';
		}
		
		if ( $enable_rating == 'on' ) {
			
			$slide = 'slideInLeft';
			if ( ot_get_option( 'rtl', 'off' ) == 'on' ){
				$slide = 'slideInRight';
			}
			
			?>
			<?php if ($c_aprr_options['c_aprr_rate_type'] == 'point' || $c_aprr_options['c_aprr_rate_type'] == 'percent'): ?>
				
				<div class="item item-review module cactus-rating" id="tmr<?php echo esc_attr($this->c_aprr_id); ?>">

					<h4><?php echo esc_html($c_aprr_title); ?></h4>

					<div class="box-text clearfix">
						<?php $final_review = get_post_meta($post_id, 'final_review', true); ?>

						<?php if(get_post_meta( $post_id, 'taq_review_score', true )){?>
							<div class="score-box <?php echo $final_review == '' ? 'no-review' : ''; ?>">
								<span class="score">
								<?php
								if ($c_aprr_options['c_aprr_rate_type'] == 'point')
								if (get_post_meta($post_id, 'taq_review_score', true) / 10 == '10.0') {
								echo number_format(get_post_meta($post_id, 'taq_review_score', true) / 10, 0);
								} else {
								echo number_format(get_post_meta($post_id, 'taq_review_score', true) / 10, 1);
								}
								else
								echo number_format(get_post_meta($post_id, 'taq_review_score', true), 0) . '%';
								?>
								</span>
								<?php if ($final_review != '') {
									echo '<span class="final-review">' . get_post_meta($post_id, 'final_review', true) . '</span>'; 
								}?>
							</div>
						<?php }?>

						<p><?php echo get_post_meta($post_id, 'final_summary', true); ?></p>
					</div><!-- box-text clearfix -->
					
					<div class="tmr-criteria">
						<?php if ($c_aprr_criteria) {
							foreach ($c_aprr_criteria as $criteria) {
								$point = get_post_meta($post_id, 'review_' . sanitize_title($criteria), true);
								if ($point) {
								?>
									<div class="box-progress">
										<h5><?php echo esc_html($criteria); ?>
											<span class="score">
												<?php
												if ($c_aprr_options['c_aprr_rate_type'] == 'point'){
													if ($point / 10 == '10.0') {
														echo number_format($point / 10, 0);
													} else {
														echo number_format($point / 10, 1);
													}
												}else{
													echo number_format($point, 0) . '%';
												}
												?>
										</span>
										</h5>

										<div class="progress">
											<div class="inner wow <?php echo esc_attr($slide); ?>" style="visibility: hidden; -webkit-animation-name: none; -moz-animation-name: none; animation-name: none;">
												<div class="progress-bar" style="width: <?php echo esc_html($point); ?>%"></div>
											</div>
										</div>
									</div>
								<?php
								}
							}
						}

						if ($custom_review = get_post_meta($post_id, 'custom_review', true)) {
							foreach ($custom_review as $review) {
								if ($review['review_point']) { ?>
								<div class="box-progress">
									<h5><?php echo esc_html($review['title']); ?>
										<span class="score">
											<?php
											$process_point = $review['review_point'];
											if ($c_aprr_options['c_aprr_rate_type'] == 'point') {
												if ($process_point / 10 == '10.0') {
													echo number_format($process_point / 10, 0);
												} else {
													echo number_format($process_point / 10, 1);
												}
											} else {
												echo number_format($process_point, 0) . '%';
											}
											?>
										</span>
									</h5>

									<div class="progress">
										<div class="inner wow <?php echo esc_attr($slide); ?>" style="visibility: hidden; -webkit-animation-name: none; -moz-animation-name: none; animation-name: none;">
											<div class="progress-bar" style="width: <?php echo $review['review_point']; ?>%"></div>
										</div>
									</div>
								</div>
								<?php 
								}
							}
						}
						?>
					</div><!-- tmr-criteria -->

					<?php
					$post_user_rate = get_post_meta(get_the_ID(), 'user_rate_option', true);
					if($post_user_rate == 'on'){
						if ($c_aprr_options['c_aprr_user_rate'] == 'all' || ($c_aprr_options['c_aprr_user_rate'] == 'only_user' && is_user_logged_in())) {
							$this->user_rate_html($c_aprr_options);
							wp_editor( $ct_content,  $ct_editor_id, $ct_settings );?>
							<input id="ct_rating_submit" type="submit" name="submit" class="submit submit btn btn-default" value="<?php echo esc_attr__('Submit Vote','cactus')?>" onClick="ct_rating_submit()">
							<?php
						}
					}?>
				</div><!--/tmr-wrap-->
				
			<?php 
			// .end if(rate type is point or percent)
			else: 
			// now if rate type is star
			?>
				
					<div class="star-rating-block cactus-rating" id="tmr<?php echo esc_attr($this->c_aprr_id); ?>">
						<?php
						
						if(! $only_user_rate) { ?>
						<div class="rating-title"><?php echo esc_html($c_aprr_title); ?></div>

						<div class="rating-summary-block">
							<?php if(get_post_meta( $post_id, 'taq_review_score', true )){?>
							
							<span class="rating-stars">
								<?php $this->c_aprr_draw_star(get_post_meta($post_id, 'taq_review_score', true)); ?>
								<span class="final-review"><?php echo get_post_meta($post_id, 'final_review', true) ?></span>
							</span>
							
							<?php }?>

							<div class="rating-summary">
								<?php echo get_post_meta($post_id, 'final_summary', true) ?>
							</div>
						</div>

						<div class="rating-criteria-block">
							<?php if ($c_aprr_criteria) {
								foreach ($c_aprr_criteria as $criteria) {
									$point = get_post_meta($post_id, 'review_' . sanitize_title($criteria), true);
									if ($point) {
									?>
										<div class="rating-item">
											<div class="criteria-title"><?php echo esc_html($criteria); ?></div>
											<span class="rating-stars">
												<?php $this->c_aprr_draw_star($point); ?>
											</span>
										</div>
									<?php
									}
								}
							}

							if ($custom_review = get_post_meta($post_id, 'custom_review', true)) {
								foreach ($custom_review as $review) {
									if ($review['review_point']) { ?>
										<div class="rating-item">
											<div class="criteria-title"><?php echo esc_html($review['title']); ?></div>
											<span class="rating-stars">
												<?php $this->c_aprr_draw_star($review['review_point']); ?>
											</span>
										</div>
									<?php 
									}
								}
							}
							?>
						</div>
						
						<?php } ?>

						<?php
						if ($c_aprr_options['c_aprr_user_rate'] == 'all' || ($c_aprr_options['c_aprr_user_rate'] == 'only_user' && is_user_logged_in())) {
							$this->user_rate_html($c_aprr_options);
							?>
							<?php wp_editor( $ct_content,  $ct_editor_id, $ct_settings );?>
							<input id="ct_rating_submit" type="submit" name="submit" class="submit submit btn btn-default" value="<?php echo esc_attr__('Submit Vote','cactus')?>" onClick="ct_rating_submit()">
							<?php

						}
						?>
					</div><!-- star-rating-block -->
					
			<?php endif; ?>
				
			<?php
			$this->c_aprr_id++;
		}
		$output_string = ob_get_contents();
		ob_end_clean();

		return $output_string;
    }

	function user_rate_html( $c_aprr_options = array() ) {
		
		$rtl = false;
		$direction = '';
		if ( ot_get_option( 'rtl', 'off' ) == 'on' ) {
			$direction = 'dir="ltr"';
			$rtl = true;
		}

		global $post;
		$post_id = $post->ID;
		
		$total_user_rate_meta = get_post_meta( $post_id, 'total_user_rate', true );
		$avg_score_rate_meta = get_post_meta( $post_id, 'avg_score_rate', true );

		$total_user_rate = $total_user_rate_meta != '' ? $total_user_rate_meta : 0;
		$avg_score_rate = $avg_score_rate_meta != '' ? $avg_score_rate_meta : 0;

		$user_rate_option_meta = get_post_meta( $post_id, 'user_rate_option', true );
		$user_rate_option = $user_rate_option_meta != '' ? $user_rate_option_meta : 'on';

		if ( $user_rate_option == 'on' ) {
			if ( $c_aprr_options[ 'c_aprr_rate_type' ] == 'point' || $c_aprr_options[ 'c_aprr_rate_type' ] == 'percent' ) {
			?>
				<div class="box-progress ct-vote">
					<h5>
						<span class="rating_title"><?php echo esc_html__('Reader Rating', 'cactus'); ?>: </span>
						
						<span class="total_user_rate" <?php echo esc_attr($direction); ?>>(<?php $vote_str = $total_user_rate > 1 ? esc_html__('votes', 'cactus') : esc_html__('vote', 'cactus'); ?><?php echo esc_html($total_user_rate); ?> <?php echo esc_html($vote_str); ?>)
						</span>
						
						<span class="score">
							<?php
							if ($c_aprr_options['c_aprr_rate_type'] == 'point'){
								echo esc_html($avg_score_rate);
							}else{
								echo esc_html($avg_score_rate * 10 . '%');
							}
							?>
						</span>
					</h5>

				<div class="progress ct-progress">
					<div class="inner wow slideInLeft" style="visibility: hidden; -webkit-animation-name: none; -moz-animation-name: none; animation-name: none;">
						<div class="progress-bar" style="width: <?php echo esc_html($avg_score_rate * 10); ?>%"></div>
					</div>
				</div>
				<p class="msg"></p>
				</div>
			<?php } else {
			?>
			<div class="user-rating-block">
				<div class="rating-item">
					<div class="criteria-title"> <?php echo esc_html__('USER RATING', 'cactus') ?></div>
					
					<span class="rating-stars">
						<div class="rating-block">
							<div id="rating-id" data-score="<?php echo ($avg_score_rate * 10) / 20; ?>"></div>
							<p class="msg"></p>
						</div>
					</span>
				</div>
			</div>
			<?php
			}

			$flag = false;
			if (is_user_logged_in()) {
				$user_ID           = get_current_user_id();
				$user_meta         = get_user_meta($user_ID, 'post_id_voted', true);
				$post_id_voted_arr = $user_meta != '' ? explode(',', $user_meta) : array();
				foreach ($post_id_voted_arr as $id) {
					if ($id == $post_id)
					$flag = true;
				}
				echo '<input type="hidden" name="hidden_flag" value="' . $flag . ' "/>';
			}

			$static_text = esc_html__('Your Rating', 'cactus') . ',' . esc_html__('Reader Rating', 'cactus') . ',' . esc_html__('votes', 'cactus') . ',' . esc_html__('You have already voted', 'cactus') . ',' . esc_html__('vote', 'cactus');
			?>
			<input type="hidden" name="hidden_rtl" value="<?php echo esc_html($rtl); ?>"/>
			<input type="hidden" name="post_id" value="<?php echo esc_html($post_id); ?>"/>
			<input type="hidden" name="hidden_total_user_rate" value="<?php echo esc_html($total_user_rate); ?>"/>
			<input type="hidden" name="hidden_avg_score_rate" value="<?php echo esc_html($avg_score_rate); ?>"/>
			<input type="hidden" name="hidden_static_text" value="<?php echo esc_html($static_text); ?>"/>
			<input type="hidden" name="rating_type" value="<?php echo esc_html($c_aprr_options['c_aprr_rate_type']); ?>"/>
			<?php
		}
	}

    function c_aprr_draw_star( $point ) {
    	for ( $i = 1; $i <= 5; $i++ ) {
    		$class = '';
    		if ( round( $point / 20, 1 ) < ( $i - 0.5 ) ) {
    			$class = '-o';
    		} elseif ( round( $point / 20, 1 ) < $i ) {
    			$class = '-half-o';
    		}
    		echo '<i class="fas fa-star' . $class . '"></i> ';
    	}
    }

    /*
     * Get all plugin options
     */
    public static
    function c_aprr_get_all_option() {
    	global $c_aprr_options;
    	$c_aprr_options = get_option( 'c_aprr_options_group' );
    	$c_aprr_options[ 'c_aprr_criteria' ] = isset( $c_aprr_options[ 'c_aprr_criteria' ] ) ? $c_aprr_options[ 'c_aprr_criteria' ] : '';
    	$c_aprr_options[ 'c_aprr_position' ] = isset( $c_aprr_options[ 'c_aprr_position' ] ) ? $c_aprr_options[ 'c_aprr_position' ] : 'bottom';
    	$c_aprr_options[ 'c_aprr_float' ] = isset( $c_aprr_options[ 'c_aprr_float' ] ) ? $c_aprr_options[ 'c_aprr_float' ] : 'block';
    	$c_aprr_options[ 'c_aprr_fontawesome' ] = isset( $c_aprr_options[ 'c_aprr_fontawesome' ] ) ? $c_aprr_options[ 'c_aprr_fontawesome' ] : 0;
    	$c_aprr_options[ 'c_aprr_title' ] = isset( $c_aprr_options[ 'c_aprr_title' ] ) ? $c_aprr_options[ 'c_aprr_title' ] : '';
    	$c_aprr_options[ 'c_aprr_user_rate' ] = isset( $c_aprr_options[ 'c_aprr_user_rate' ] ) ? $c_aprr_options[ 'c_aprr_user_rate' ] : 'all';
    	$c_aprr_options[ 'c_aprr_rate_type' ] = isset( $c_aprr_options[ 'c_aprr_rate_type' ] ) ? $c_aprr_options[ 'c_aprr_rate_type' ] : 'point';

    	return $c_aprr_options;
    }

    /*
     * Load js and css
     */
    function c_aprr_frontend_scripts() {
    	wp_enqueue_script( 'ct_rating-ajax', CAPRR_PATH . 'js/main.js', array( 'jquery' ), 1, true );
    	wp_enqueue_script( 'wow', CAPRR_PATH . 'js/wow.min.js', array( 'jquery' ), 1, true );

    	wp_enqueue_style( 'ct_rating', CAPRR_PATH . 'css/style.css' );
    	wp_enqueue_style( 'animate', CAPRR_PATH . 'css/animate.min.css' );

    	//star rating

    	wp_enqueue_script( 'raty', CAPRR_PATH . 'js/jquery.raty-fa.js', array( 'jquery' ), 1, true );

    	$c_aprr_options = $this->c_aprr_get_all_option();
    	if ( $c_aprr_options[ 'c_aprr_fontawesome' ] == 0 ) {
    		wp_enqueue_style('fontawesome', CAPRR_PATH.'font-awesome/css/fontawesome-all.min.css', array(), '5.0.7');
    	}
		
		$js_params = array('ajaxurl' => admin_url('admin-ajax.php'));
		global $wp_query, $wp;
		$js_params['query_vars']  = $wp_query->query_vars;
		$js_params['current_url'] = home_url($wp->request);

		wp_localize_script('ct_rating-ajax', 'ct_rating', $js_params);
		
    }


    //review save
    function tm_review_save_post( $post_ID ) {
    	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
    		return;
    	if ( !current_user_can( 'edit_post', $post_ID ) )
    		return;

    	$review_total = 0;
    	$review_count = 0;
    	$c_aprr_options = $this->c_aprr_get_all_option();
    	$c_aprr_criteria = $c_aprr_options[ 'c_aprr_criteria' ] ? explode( ",", $c_aprr_options[ 'c_aprr_criteria' ] ) : '';
    	if ( $c_aprr_criteria ) {
    		foreach ( $c_aprr_criteria as $criteria ) {
    			if ( isset( $_POST[ 'review_' . sanitize_title( $criteria ) ] ) ) {
    				$review_total += intval($_POST[ 'review_' . sanitize_title( $criteria ) ]);
    				$review_count++;
    			}
    		}
    	}
    	if ( isset( $_POST[ 'custom_review' ] ) ) {
    		foreach ( $_POST[ 'custom_review' ] as $review ) {
    			if ( $review[ 'review_point' ] ) {
    				$review_total += intval($review[ 'review_point' ]);
    				$review_count++;
    			}
    		}
    	}
    	if ( $review_count ) {
    		update_post_meta( $post_ID, 'taq_review_score', round( $review_total / $review_count, 10 ) );
    	}
    }

    //the_content filter
    function c_aprr_the_content_filter( $content ) {
    	if ( is_single() ) {
    		$c_aprr_options = $this->c_aprr_get_all_option();
    		if ( $c_aprr_options[ 'c_aprr_position' ] == 'top' ) {
    			$content = '[ct_rating /]' . $content;
    		} elseif ( $c_aprr_options[ 'c_aprr_position' ] == 'bottom' ) {
    			$content .= '[ct_rating /]';
    		}
    	}

    	// Returns the content.
    	return do_shortcode( $content );
    }

    function c_aprr_post_meta() {
		
		$c_aprr_options = $this->c_aprr_get_all_option();
    	$c_aprr_criteria = $c_aprr_options[ 'c_aprr_criteria' ] ? explode( ",", $c_aprr_options[ 'c_aprr_criteria' ] ) : '';
		$c_aprr_post_type = isset($c_aprr_options[ 'ct_post_type' ]) && !empty($c_aprr_options[ 'ct_post_type' ]) ? $c_aprr_options[ 'ct_post_type' ]  : array('post');
		
    	//option tree
    	$meta_box_review = array(
    		'id' => 'meta_box_review',
    		'title' => esc_html__( 'Review', 'cactus' ),
    		'desc' => '',
    		'pages' => $c_aprr_post_type,
    		'context' => 'normal',
    		'priority' => 'high',
    		'fields' => array(
				array(
					'id' => 'enable_rating',
					'label' => esc_html__( 'Enable Rating Feature', 'cactus' ),
					'desc' => esc_html__( 'Enable Rating Feature', 'cactus' ),
					'std' => 'off',
					'type' => 'on-off',
					'class' => ''
				),
    			array(
    				'label' => esc_html__( 'Review Title', 'cactus' ),
    				'id' => 'review_title',
    				'type' => 'text',
    				'class' => '',
    				'desc' => esc_html__( 'Review title for this post', 'cactus' ),
    				'choices' => array(),
    				'settings' => array(),
					'condition' => 'enable_rating:is(on)',
    			),
    		)
    	);
    	
    	if ( $c_aprr_criteria ) {
    		foreach ( $c_aprr_criteria as $criteria ) {
    			$meta_box_review[ 'fields' ][] = array(
    				'id' => 'review_' . sanitize_title( $criteria ),
    				'label' => $criteria,
    				'desc' => esc_html__( 'Point (Ex: 95)', 'cactus' ),
    				'std' => '',
    				'type' => 'text',
    				'class' => '',
    				'choices' => array(),
					'condition' => 'enable_rating:is(on)',
    			);
    		}
    	}
    	$meta_box_review[ 'fields' ][] = array(
    		'label' => esc_html__( 'Custom Review Criterias', 'cactus' ),
    		'id' => 'custom_review',
    		'type' => 'list-item',
    		'class' => '',
    		'desc' => esc_html__( 'Add custom reviews', 'cactus' ),
    		'choices' => array(),
    		'settings' => array(
    			array(
    				'label' => esc_html__( 'Point', 'cactus' ),
    				'id' => 'review_point',
    				'type' => 'text',
    				'desc' => '',
    				'std' => '',
    				'rows' => '',
    				'post_type' => '',
    				'taxonomy' => '',
					'condition' => 'enable_rating:is(on)',
    			),
    		),
			'condition' => 'enable_rating:is(on)',
    	);
    	$meta_box_review[ 'fields' ][] = array(
    		'id' => 'final_review',
    		'label' => 'Final Review Word',
    		'desc' => 'Ex: Good, Bad...',
    		'std' => '',
    		'type' => 'text',
    		'class' => '',
    		'choices' => array(),
			'condition' => 'enable_rating:is(on)',
    	);
    	$meta_box_review[ 'fields' ][] = array(
    		'id' => 'final_summary',
    		'label' => esc_html__( 'Final Review Summary', 'cactus' ),
    		'desc' => esc_html__( 'Ex: This is must-watch movie of this year', 'cactus' ),
    		'std' => '',
    		'type' => 'textarea',
    		'class' => '',
    		'choices' => array(),
			'condition' => 'enable_rating:is(on)',
    	);
    	$meta_box_review[ 'fields' ][] = array(
    		'id' => 'user_rate_option',
    		'label' => esc_html__( 'User Rate Option', 'cactus' ),
    		'desc' => esc_html__( 'Enable user rate option', 'cactus' ),
    		'std' => 'on',
    		'type' => 'on-off',
    		'class' => '',
			'condition' => 'enable_rating:is(on)',
    	);
    	$meta_box_review[ 'fields' ][] = array(
    		'id' => 'rate_type',
    		'label' => esc_html__( 'Rate type', 'cactus' ),
    		'desc' => esc_html__( 'Choose default to use setting in Rating Config', 'cactus' ),
    		'std' => '',
    		'type' => 'select',
    		'class' => '',
    		'choices' => array(
    			array(
    				'value' => '',
    				'label' => esc_html__( 'Default', 'cactus' ),
    				'src' => ''
    			),
    			array(
    				'value' => 'point',
    				'label' => esc_html__( 'Point', 'cactus' ),
    				'src' => ''
    			),
    			array(
    				'value' => 'star',
    				'label' => esc_html__( 'Star', 'cactus' ),
    				'src' => ''
    			),
    			array(
    				'value' => 'percent',
    				'label' => esc_html__( 'Percent', 'cactus' ),
    				'src' => ''
    			)
    		),
			'condition' => 'enable_rating:is(on)',
    	);
    	if ( function_exists( 'ot_register_meta_box' ) ) {
    		ot_register_meta_box( $meta_box_review );
    	}
    }

    function regbtns( $buttons ) {
    	array_push( $buttons, 'tm_rating' );

    	return $buttons;
    }

    function regplugins( $plgs ) {
    	$plgs[ 'tm_rating' ] = CAPRR_PATH . 'js/button.js';

    	return $plgs;
    }
	
	
	function ct_review_get_the_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		//check ip from share internet
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		//to check ip is pass from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		return $ip;
	}

	
	function ct_review_insert_post(){
		$user_info = '';
		if(is_user_logged_in()){
			$user = wp_get_current_user();
			$user_info = $user->user_login;
		}else{
			$user_info = $this->ct_review_get_the_user_ip();
		}
		
		$post_title = current_time('Y-m-d-G-i-s').'-review-'.rand(1,9999).'-'.$user_info;
		
		$postArr = array(
			'post_type' => 'ct_review',
			'post_title' => $post_title,
			'post_status' => 'private', 
		);
		
		$post_meta_key = array(
			'ct_review_score',
			'ct_review_username',
			'ct_review_ip',
			'ct_review_content'
		);
		
		$result_id = wp_insert_post($postArr);
				
		if($result_id){
			
			foreach ($post_meta_key as $meta_key){
				if($meta_key == 'ct_review_score'){
					$meta_value = isset( $_POST[ 'score' ] ) ? intval($_POST[ 'score' ]) : 0;
				}else if($meta_key == 'ct_review_username'){
					if(is_user_logged_in()){
						$meta_value = $user_info;
					}else{
						$meta_value = '';
					}
				}else if($meta_key == 'ct_review_ip'){
					if(!is_user_logged_in()){
						$meta_value = $user_info;
					}else{
						$meta_value = '';
					}
				}else if($meta_key == 'ct_review_content'){
					$meta_value = isset( $_POST[ 'ct_review_form' ] ) ? wp_kses_post($_POST[ 'ct_review_form' ]) : '';
				}else {
					$meta_value = '';
				}
				update_post_meta( $result_id, $meta_key, $meta_value );
			}
		} 
		
	}
	

    function ct_wp_ajax_add_user_rate() {
		
    	$score = isset( $_POST[ 'score' ] ) ? intval($_POST[ 'score' ]) : 0;
		$ct_review_form = isset( $_POST[ 'ct_review_form' ] ) ? wp_kses_post($_POST[ 'ct_review_form' ]) : '';

    	//get all user rated of posts
    	if ( isset( $_POST[ 'post_id' ] ) ) {
    		//get post id from ajax
    		$post_id = intval($_POST[ 'post_id' ]);

    		$total_user_rate = get_post_meta( $post_id, 'total_user_rate', true );
    		$avg_score_rate = get_post_meta( $post_id, 'avg_score_rate', true );

    		//first time
    		if ( $total_user_rate == '' && $avg_score_rate == '' ) {
    			add_post_meta( $post_id, 'total_user_rate', 1 );
    			add_post_meta( $post_id, 'avg_score_rate', $score );
    		}
    		//if database had record
    		else {
    			update_post_meta( $post_id, 'total_user_rate', $total_user_rate + 1 );
    			update_post_meta( $post_id, 'avg_score_rate', round( ( $total_user_rate * $avg_score_rate + $score ) / ( $total_user_rate + 1 ), 1 ) );
    		}

    		//if logged in
    		if ( is_user_logged_in() ) {
    			$user_ID = get_current_user_id();

    			$user_meta = get_user_meta( $user_ID, 'post_id_voted', true );

    			if ( $user_meta == '' ) {
    				//save to user_metadata
    				add_user_meta( $user_ID, 'post_id_voted', $post_id );
    			} else {
    				$data = $user_meta . ',' . $post_id;
    				update_user_meta( $user_ID, 'post_id_voted', $data );
    			}
    		} else {
    			//first vote
    			if ( !isset( $_COOKIE[ 'post_id_voted' ] ) ) {
    				//save to cookie
    				setcookie( 'post_id_voted', $post_id, time() + 60 * 60 * 24 * 30, '/' );
    			} else {
    				$cookie_post_id_voted = $_COOKIE[ 'post_id_voted' ];
    				setcookie( 'post_id_voted', $cookie_post_id_voted . '-' . $post_id, time() + 60 * 60 * 24 * 30, '/' );
    			}
    		}

    		$args = array(
    			'score' => $score
    		);

    		do_action( 'anypost_rating_after_user_rate', $post_id, $args );
    	}
    }
}

CactusAnyPostRating::getInstance();

//convert hex 2 rgba
function c_aprr_hex2rgba( $hex, $opacity ) {
	$hex = str_replace( "#", "", $hex );

	if ( strlen( $hex ) == 3 ) {
		$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
		$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
		$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
	} else {
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
	}
	$opacity = $opacity / 100;
	$rgba = array( $r, $g, $b, $opacity );

	return implode( ",", $rgba ); // returns the rgb values separated by commas
	//return $rgba; // returns an array with the rgb values
}
