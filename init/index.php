<?php

namespace CUMULUS\Wordpress\Testimonials;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

require __DIR__ . '/required.php';
require __DIR__ . '/cpt-testimonial.php';
require __DIR__ . '/config.php';
require __DIR__ . '/blocks.php';

// Frontend misc assets
function frontend_assets() {
	\wp_enqueue_style(
		'cmls-testimonial-frontend-css',
		\CUMULUS\Wordpress\Testimonials\BASEURL . '/build/frontend.css'
	);
}
\add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\frontend_assets' );
