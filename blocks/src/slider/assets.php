<?php
/**
 * Asset loader
 */
namespace CUMULUS\Wordpress\Testimonials\Blocks\Slider;
use CUMULUS\Wordpress\Testimonials\{BASEPATH, BASEURL};

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Editor Assets
function editor_assets(){
	// Splide Library
	\wp_enqueue_style(
		'cmls-testimonial-block-slider_splide-css',
		BASEURL . '/build/block_slider_splide.css'
	);
	$assets = require BASEPATH . '/build/block_slider_splide.asset.php';
	\wp_enqueue_script(
		'cmls-testimonial-block-slider_splide-js', // Handle.
		BASEURL . '/build/block_slider_splide.js',
		$assets['dependencies'],
		$assets['version'],
		true
	);
}
\add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\editor_assets' );



// Frontend Block Assets
function frontend_block_assets(){
	if (\has_block('cumulus-gutenberg/testimonials-slider') && ! \is_admin()) {

		// Splide Library
		\wp_enqueue_style(
			'cmls-testimonial-block-slider_splide-css',
			BASEURL . '/build/block_slider_splide.css'
		);
		$assets = require BASEPATH . '/build/block_slider_splide.asset.php';
		\wp_enqueue_script(
			'cmls-testimonial-block-slider_splide-js', // Handle.
			BASEURL . '/build/block_slider_splide.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		// Block assets
		\wp_enqueue_style(
			'cmls-testimonial-block-slider_frontend-css',
			BASEURL . '/build/block_slider_frontend.css'
		);
		$assets = require BASEPATH . '/build/block_slider_frontend.asset.php';
		\wp_enqueue_script(
			'cmls-testimonial-block-slider_frontend-js', // Handle.
			BASEURL . '/build/block_slider_frontend.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

	}
}
\add_action( 'enqueue_block_assets', __NAMESPACE__ . '\\frontend_block_assets' );