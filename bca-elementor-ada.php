<?php

/**
 * Plugin Name: Blk Canvas - Elementor ADA Assistant
 * Plugin URI: https://github.com/henzlym/bca-elementor-ada
 * Description:
 * Author: Henzly Meghie
 * Author URI: https://henzlymeghie.com/
 * Version: 1.0.0
 * License: GPL3+
 * License URI: https://github.com/henzlym/bca-elementor-ada/blob/main/LICENSE
 *
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

define('BCA_ELEMENTOR_ADA_PATH', plugin_dir_path(__FILE__));
define('BCA_ELEMENTOR_ADA_URI', plugin_dir_url(__FILE__));

/**
 * Filters image_box widgets and change their content.
 *
 * @since 1.0.0
 * @param string                 $widget_content The widget HTML output.
 * @param \Elementor\Widget_Base $widget         The widget instance.
 * @return string The changed widget content.
 */
function blkcanvas_image_box_add_arialabel_render_content($widget_content, $widget)
{

	error_log(print_r($widget->get_name(), true));

	if ('call-to-action' === $widget->get_name()) {
		$settings = $widget->get_settings();
		// error_log(print_r($settings, true));
		if (empty($settings['link']['custom_attributes'])) {
			$aria_label = blkcanvas_check_suspicious_link_text($settings, 'call-to-action');
			if ($aria_label) {
				$widget_content = blkcanvas_add_aria_label_a_tag($widget_content, $aria_label);
			}
		}
	}

	if ('image-box' === $widget->get_name()) {
		$settings = $widget->get_settings();

		if (empty($settings['link']['custom_attributes'])) {
			$aria_label = empty($settings['title_text']) ? 'Image box' : $settings['title_text'];
			$widget_content = blkcanvas_add_aria_label_a_tag($widget_content, $aria_label);
		}
	}

	if ('icon-box' === $widget->get_name()) {
		$settings = $widget->get_settings();

		if (empty($settings['link']['custom_attributes'])) {
			$aria_label = empty($settings['title_text']) ? 'Icon box' : $settings['title_text'];
			$widget_content = blkcanvas_add_aria_label_a_tag($widget_content, $aria_label);
		}
	}

	return $widget_content;
}
add_filter('elementor/widget/render_content', 'blkcanvas_image_box_add_arialabel_render_content', 10, 2);



/**
 * Adds aria-label="Link" to <a> tags that do not already have an aria-label.
 *
 * @param string $html The input HTML string.
 * @return string The modified HTML string.
 */
function blkcanvas_add_aria_label_a_tag($html, $label)
{
	return preg_replace_callback('/<a\s+([^>]*?)>/', function ($matches) use ($label) {
		$tag = $matches[0];
		// Check if aria-label already exists
		if (strpos($tag, 'aria-label=') === false) {
			// Insert aria-label="Link" after <a
			$newTag = str_replace('<a ', '<a aria-label="' . $label . '" ', $tag);
			return $newTag;
		}
		return $tag;
	}, $html);
}

function blkcanvas_check_suspicious_link_text(array $settings, string $widget_name): ?string
{
	$suspicious_phrases = ['click here', 'here', 'read more', 'more', 'details', 'link'];
	$potential_keys = ['button', 'link_text', 'read_more_text', 'cta_text', 'text'];

	foreach ($potential_keys as $key) {
		if (!empty($settings[$key]) && in_array(strtolower($settings[$key]), $suspicious_phrases, true)) {
			error_log("[ADA Warning] Suspicious link text '{$settings[$key]}' found in widget {$widget_name}");
			return !empty($settings['title']) ? 'Learn more about ' . strip_tags($settings['title']) : 'Learn more';
		}
	}

	return null;
}

/**
 * Adjusts Elementor loop header attributes for accessibility.
 *
 * Specifically removes the `role` attribute if the element's class list
 * includes "swiper" — since Swiper sliders already define their own ARIA roles
 * and adding a conflicting role (e.g., `role="list"`) can confuse assistive tech.
 *
 * @since 1.1.0
 * @param array $render_attributes The current render attributes for the loop container.
 * @return array Modified render attributes.
 */
function blkcanvas_add_loop_header_attributes($render_attributes)
{
	// Log for debugging
	error_log('[ADA] Loop header attributes before: ' . print_r($render_attributes, true));

	// Ensure 'class' exists and is an array
	if (!empty($render_attributes['class']) && is_array($render_attributes['class'])) {

		// Check if any of the classes contain "swiper"
		$has_swiper_class = array_filter($render_attributes['class'], function ($class) {
			return strpos($class, 'swiper') !== false;
		});

		// If "swiper" class found, remove the role attribute
		if (!empty($has_swiper_class) && isset($render_attributes['role'])) {
			unset($render_attributes['role']);

			error_log('[ADA] Removed role attribute because element contains "swiper" class.');
		}
	}

	// Log modified attributes
	error_log('[ADA] Loop header attributes after: ' . print_r($render_attributes, true));

	return $render_attributes;
}
add_filter('elementor/skin/loop_header_attributes', 'blkcanvas_add_loop_header_attributes', 10, 1);
