<?php
/* Plugin Name: Require Featured Image Extended Plugin
 * URI: http://ampullate.com/wordpress-plugins/require-featured-image-extended/
 * Description: Requires configured posts to have a featured image set before they'll be published, with optional minimum sizes
 * Author: Delwin Vriend, based on work by Press Up
 * Version: 1.01
 * Author URI: http://ampullate.com
 * Text Domain: require-featured-image-extended
 */
require_once ('admin-options.php');

add_action('transition_post_status', 'rfi_guard', 10, 3);

function rfi_guard($new_status, $old_status, $post) {
	if (isset($_GET['_locale']) && $_GET['_locale'] == 'user') {
		return;
		/* EXPLANATION: The Block/Gutenberg editor works differently than classic, especially when a user has a a new post they're seeking to see published where the Featured Image wasn't already saved to a draft. Best I can tell in that condition they'll always have this weird `_locale=user` set on the URL so a quick-and-dirty hack on not enforcing on that post transition is going on here. Should probably have more expert eyes find a better solution than this. */
	}
	if ($new_status === 'publish' && rfi_should_stop_post_publishing($post)) {
		// transition_post_status comes after the post has changed statuses, so we must roll back here
		// because publish->publish->... is an infinite loop, move a published post without an image to draft
		if ($old_status == 'publish') {
			$old_status = 'draft';
		}
		$post->post_status = $old_status;
		wp_update_post($post);
		wp_die(rfi_get_warning_message($post));
	}
}

add_action('admin_enqueue_scripts', 'rfi_enqueue_edit_screen_js');

function rfi_enqueue_edit_screen_js($hook) {
	global $post;
	if ($hook !== 'post.php' && $hook !== 'post-new.php') {
		return;
	}

	if (rfi_is_supported_post_type($post) && (rfi_is_enforced_post_type($post) || rfi_is_in_enforcement_window($post))) {
		wp_enqueue_script('rfi-admin-js', plugins_url('/require-featured-image-on-edit.js', __FILE__), array (
			'jquery'
		), JS_CSS_VERSION);

		$minimum_size_dimensions = rfi_return_min_dimensions();
		$minimum_size_per_type = !get_option('rfi_post_types_same_dimensions', true);
		if ($minimum_size_per_type) {
			$minimum_size = $minimum_size_dimensions[$post->post_type];
		} else {
			$minimum_size = $minimum_size_dimensions[ALL_TYPES_TYPE_FLAG];
		}
		$supported_post_types = rfi_return_post_types_which_support_featured_images();
		wp_localize_script('rfi-admin-js', 'passedFromServer', array (
			'jsWarningHtml' => sprintf(_x('<strong>This %1$s has no featured image.</strong> A featured image with minimum dimensions of <strong>%2$s✕%3$s px</strong> must be set before publishing.', '%1$s will be replaced with the post-type name (e.g. "Post"); %2$s will be replaced with the minimum width in pixels, %3$s will be replaced with the minimum height in pixels', 'require-featured-image-extended'), $supported_post_types[$post->post_type]->labels->singular_name, $minimum_size['width'], $minimum_size['height']),
			'jsSmallHtml' => sprintf(_x('<strong>This %1$s has a featured image that is too small.</strong> Please use an image with minimum dimensions of <strong>%2$s✕%3$s px</strong>.', '%1$s will be replaced with the post-type name (e.g. "Post"); %2$s will be replaced with the minimum width in pixels, %3$s will be replaced with the minimum height in pixels', 'require-featured-image-extended'), $supported_post_types[$post->post_type]->labels->singular_name, $minimum_size['width'], $minimum_size['height']),
			'jsImageRequired' => sprintf(_x('Image Required (min %1$s✕%2$s px)', 'Notice used in Thumbnail box when it is missing and required. %1$s will be replaced with the minimum width in pixels, %2$s will be replaced with the minimum height in pixels', 'require-featured-image-extended'), $minimum_size['width'], $minimum_size['height']),
			'width' => $minimum_size['width'],
			'height' => $minimum_size['height']
		));
	}
}

register_activation_hook(__FILE__, 'rfi_set_default_on_activation');

function rfi_set_default_on_activation() {
	add_option('rfi_post_types', array (
		'post'
	));
	// We added the 86400 (one day) below, because without it
	//      first run behavior was confusing
	add_option('rfi_enforcement_start', time() - 86400);
}

add_action('plugins_loaded', 'rfi_textdomain_init');

function rfi_textdomain_init() {
	load_plugin_textdomain('require-featured-image-extended', false, dirname(plugin_basename(__FILE__)) . '/lang');
}

/**
 * These are helpers that aren't ever registered with events
 */
function rfi_should_stop_post_publishing($post) {
	$is_watched_post_type = rfi_is_supported_post_type($post);
	$is_after_enforcement_time = rfi_is_in_enforcement_window($post);
	$large_enough_image_attached = rfi_post_has_large_enough_image_attached($post);

	if ($is_after_enforcement_time && $is_watched_post_type) {
		return !$large_enough_image_attached;
	}
	return false;
}

function rfi_is_supported_post_type($post) {
	return in_array($post->post_type, rfi_return_post_types());
}

function rfi_is_enforced_post_type($post) {
	return in_array($post->post_type, rfi_return_post_type_enforcements());
}

function rfi_return_post_types() {
	$option = get_option('rfi_post_types', 'default');
	if ($option === 'default') {
		$option = array (
			'post'
		);
		add_option('rfi_post_types', $option);
	} elseif ($option === '') {
		// For people who want the plugin on, but doing nothing
		$option = array ();
	}
	return apply_filters('rfi_post_types', $option);
}

function rfi_return_post_type_enforcements() {
	$option = get_option('rfi_post_types_enforced', 'default');
	if ($option === 'default') {
		$option = array (
			'post'
		);
		add_option('rfi_post_types_enforced', $option);
	} elseif ($option === '') {
		// For people who want the plugin on, but doing nothing
		$option = array ();
	}
	return apply_filters('rfi_post_types_enforced', $option);
}

function rfi_is_in_enforcement_window($post) {
	return strtotime($post->post_date) > rfi_enforcement_start_time();
}

function rfi_enforcement_start_time() {
	$option = get_option('rfi_enforcement_start', 'default');
	if ($option === 'default') {
		// added in 1.1.0, activation times for installations before
		//  that release are set to two weeks prior to the first call
		$existing_install_guessed_time = time() - (86400 * 14);
		add_option('rfi_enforcement_start', $existing_install_guessed_time);
		$option = $existing_install_guessed_time;
	}
	return apply_filters('rfi_enforcement_start', (int)$option);
}

function rfi_post_has_large_enough_image_attached($post) {
	$image_id = get_post_thumbnail_id($post->ID);
	if ($image_id === null) {
		return false;
	}
	$image_meta = wp_get_attachment_image_src($image_id, 'full');
	$width = $image_meta[1];
	$height = $image_meta[2];
	$minimum_size = (array)get_option('rfi_minimum_size_' . $post->type);

	if ((!key_exist('width', $minimum_size) || $width >= $minimum_size['width'])
			&& (!key_exist('height', $minimum_size) || $height >= $minimum_size['height'])) {
		return true;
	}
	return false;
}

function rfi_get_warning_message($post) {
	$minimum_size_dimensions = rfi_return_min_dimensions();
	$minimum_size_per_type = !get_option('rfi_post_types_same_dimensions', true);
	if ($minimum_size_per_type) {
		$minimum_size = $minimum_size_dimensions[$post->post_type];
	} else {
		$minimum_size = $minimum_size_dimensions[ALL_TYPES_TYPE_FLAG];
	}
	// Legacy case
	if ($minimum_size['width'] == 0 && $minimum_size['height'] == 0) {
		return __('You cannot publish without a featured image.', 'require-featured-image-extended');
	}
	return sprintf(_x('You cannot publish without a featured image that is at least %1$s✕%2$s px', '%1$s will be replaced with the minimum width in pixels, %2$s will be replaced with the minimum height in pixels', 'require-featured-image-extended'), $minimum_size['width'], $minimum_size['height']);
}