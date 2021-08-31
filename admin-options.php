<?php
define('JS_CSS_VERSION', '1.01');
define('ALL_TYPES_TYPE_FLAG', '**all**');

add_action( 'admin_menu', 'rfi_admin_add_page' );
function rfi_admin_add_page() {
	add_options_page( 'Require Featured Image Options', 'Req Featured Image', 'manage_options', 'rfi', 'rfi_options_page' );
}

function rfi_options_page() {
	wp_enqueue_script('rfi-admin-js', plugins_url( '/require-featured-image-options.js', __FILE__ ), array(), JS_CSS_VERSION, true);
	wp_enqueue_style('rfi-admin-css', plugins_url( '/require-featured-image-options.css', __FILE__ ), array(), JS_CSS_VERSION); ?>
	<div class="wrap">
		<h2><?php _e( 'Require Featured Image', 'require-featured-image-extended' ) ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields( 'rfi' ); ?>
			<?php do_settings_sections( 'rfi' ); ?>
			<input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes', 'require-featured-image-extended' ); ?>" class="button button-primary" />
		</form>
	</div>
<?php
}

add_action( 'admin_init', 'rfi_admin_init' );
function rfi_admin_init(){
	// Create Settings
	$option_group = 'rfi';
	$option_name = 'rfi_post_types';
	register_setting( $option_group, $option_name );

	$option_enforced_name = 'rfi_post_types_enforced';
	register_setting( $option_group, $option_enforced_name );

	$option_same_dimesions_name = 'rfi_post_types_same_dimensions';
	register_setting( $option_group, $option_same_dimesions_name );

	$minimum_size_option = 'rfi_minimum_size';
	register_setting( $option_group, $minimum_size_option );

	$post_types = rfi_return_post_types_which_support_featured_images();
	foreach ( $post_types as $type => $obj ) {
		$minimum_size_option_per_post = 'rfi_minimum_size_' . $type;
		register_setting( $option_group, $minimum_size_option_per_post );
	}

	// Create section of Page
	$settings_section = 'rfi_main';
	$page = 'rfi';
	add_settings_section( $settings_section, __( 'Post Types', 'require-featured-image-extended' ), 'rfi_main_section_text_output', $page );

	// Add fields to that section
	add_settings_field( $option_name, __('Post Types that require featured images ', 'require-featured-image-extended' ), 'rfi_post_types_input_renderer', $page, $settings_section );

	// Minimum Image requirements
	$size_section = 'rfi_size';
	add_settings_section($size_section, __('Image Size', 'require-featured-image-extended'), 'rfi_size_text_output', $page);

	add_settings_field($minimum_size_option, __('Minimum size of featured images', 'require-featured-image-extended'), 'rfi_size_option_renderer', $page, $size_section);
}

function rfi_main_section_text_output() {
	$enforcement_time = rfi_enforcement_start_time(); ?>
	<p><?php _e( 'You can specify the post type for Require Featured Image to work on. By default it works on Posts only.', 'require-featured-image-extended'); ?></p>
	<p><?php _e( 'If you\'re not seeing a post type here that you think should be, it probably does not have support for featured images. Only post types that support featured images will appear on this list.', 'require-featured-image-extended' ); ?></p>
	<p><?php echo sprintf(_x( 'By default, this plugin enforces thumbnails only for items published from two weeks before this plugin was initially enabled (so from %1$s, onwards). You can override this behavior, below.', '%1$s will be replaced with the date in the current date format set by WordPress, in WordPress\' current language', 'require-featured-image-extended' ), date_i18n(get_option('date_format'), $enforcement_time)); ?></p><?php
}

function rfi_size_text_output() { ?>
	<p><?php _e('The minimum acceptable size can be set for featured images. This size means that posts with images smaller than the specified dimensions cannot be published. By default the sizes are zero, so any image size will be accepted.','require-featured-image-extended'); ?></p><?php
}

function rfi_return_post_types_which_support_featured_images() {
	$supported_post_types = array();
	$post_types = get_post_types( array( 'public' => true ), 'objects' );
	foreach ( $post_types as $type => $obj ) {
		if ( post_type_supports( $type, 'thumbnail' ) ) {
			$supported_post_types[$type] = $obj;
		}
	}
	return $supported_post_types;
}

function rfi_return_min_dimensions() {
	$post_types = rfi_return_post_types_which_support_featured_images();

	$minimum_size = array();

	$all_type_minimum_size = (array)get_option('rfi_minimum_size');
	if (!key_exists('width', $all_type_minimum_size) || !isset($all_type_minimum_size['width'])) {
		$all_type_minimum_size['width'] = 0;
	}
	if (!key_exists('height', $all_type_minimum_size) || !isset($all_type_minimum_size['height'])) {
		$all_type_minimum_size['height'] = 0;
	}
	$minimum_size[ALL_TYPES_TYPE_FLAG] = $all_type_minimum_size;

	foreach ( $post_types as $type => $obj ) {
		$this_type_minimum_size = (array)get_option('rfi_minimum_size_'. $type);
		if (!key_exists('width', $this_type_minimum_size) || !isset($this_type_minimum_size['width'])) {
			$this_type_minimum_size['width'] = 0;
		}
		if (!key_exists('height', $this_type_minimum_size) || !isset($this_type_minimum_size['height'])) {
			$this_type_minimum_size['height'] = 0;
		}
		$minimum_size[$type] = $this_type_minimum_size;
	}
	return $minimum_size;
}

function rfi_post_types_input_renderer() {
	$post_type_option = rfi_return_post_types();
	$post_type_enforced_option = rfi_return_post_type_enforcements();
	$post_types = rfi_return_post_types_which_support_featured_images();
	$enforcement_time = rfi_enforcement_start_time();

	foreach ( $post_types as $type => $obj ) {
		$is_currently_selected = in_array($type, $post_type_option);
		$is_currently_enforced = in_array($type, $post_type_enforced_option); ?>
		<fieldset class="type type-<?php echo $type; ?>">
			<input type="checkbox" class="post-type" id="rfi_post_type_<?php echo $type; ?>" name="rfi_post_types[]" value="<?php echo $type; ?>"<?php echo $is_currently_selected ? ' checked="checked"' : ''; ?> /><label for="rfi_post_type_<?php echo $type; ?>"><?php echo $obj->label; ?></label><br />
			<input type="checkbox" id="rfi_post_type_<?php echo $type; ?>_enforced" class="enforced" name="rfi_post_types_enforced[]" value="<?php echo $type; ?>"<?php echo $is_currently_enforced ? ' checked="checked"' : ''; ?> /><label for="rfi_post_type_<?php echo $type; ?>_enforced"><?php echo sprintf(__('Also enforce for existing %s published before %s'), $obj->label, date_i18n(get_option('date_format'), $enforcement_time)); ?></label>
		</fieldset><?php
	}
}

function rfi_size_option_renderer() {
	$post_types = rfi_return_post_types_which_support_featured_images();
	$is_all_type_same_selected = get_option('rfi_post_types_same_dimensions', 'true');
	$type_dimensions = rfi_return_min_dimensions(); ?>
	<label for="rfi_post_types_same_dimensions"><input type="checkbox" class="same-dims" name="rfi_post_types_same_dimensions" value="true" id="rfi_post_types_same_dimensions"<?php echo $is_all_type_same_selected ? ' checked="checked"' : ''; ?> /><?php _e('All types use the same minimum dimensions'); ?></label><br /><?php
	foreach($type_dimensions as $type => $dimensions) {
		if ($type == ALL_TYPES_TYPE_FLAG) { ?>
			<fieldset class="dimensions for-all" id="dimensions-for-rfi_post_type_all"><legend><?php _e("For all post types"); ?></legend>
				<label><span><?php _e('Minimum width:'); ?> </span><input type="number" name="rfi_minimum_size[width]" value="<?php echo $dimensions["width"]; ?>" /><?php _e('px'); ?></label>
				<label><span><?php _e('Minimum height:'); ?> </span><input type="number" name="rfi_minimum_size[height]" value="<?php echo $dimensions["height"]; ?>" /><?php _e('px'); ?></label>
			</fieldset><?php
		} else { ?>
			<fieldset class="dimensions for-<?php echo $type; ?>" id="dimensions-for-rfi_post_type_<?php echo $type; ?>"><legend><?php echo sprintf(__("For %s"), $post_types[$type]->label); ?></legend>
				<label><span><?php _e('Minimum width:'); ?> </span><input type="number" name="rfi_minimum_size_<?php echo $type; ?>[width]" value="<?php echo $dimensions["width"]; ?>" /><?php _e('px'); ?></label>
				<label><span><?php _e('Minimum height:'); ?> </span><input type="number" name="rfi_minimum_size_<?php echo $type; ?>[height]" value="<?php echo $dimensions["height"]; ?>" /><?php _e('px'); ?></label>
			</fieldset><?php
		}
	}
}
