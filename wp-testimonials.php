<?php
namespace CUMULUS\Wordpress\Testimonials;
/**
 * Plugin Name: Testimonial CPT and Blocks
 * Plugin URI: https://github.com/cumulus-digital/wp-testimonials/
 * GitHub Plugin URI: https://github.com/cumulus-digital/wp-testimonials/
 * Primary Branch: main
 * Description: Provides a Custom Post Type and Gutenberg blocks for creating and displaying Testimonials. NOTE: his plugin uses hooks and filters of the CMLS Base Theme and may not display content properly in any other theme or child of CMLS Base Theme.
 * Version: 0.1.2
 * Author: vena
 * License: UNLICENSED
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

const TXTDOMAIN = 'cmls-testimonial';

define('BASEPATH', \untrailingslashit(\plugin_dir_path(__FILE__)));
define('BASEURL', \untrailingslashit(\plugin_dir_url(__FILE__)));

# Required plugins
require __DIR__ . '/libs/TGM-Plugin-Activation/class-tgm-plugin-activation.php';

# Helper functions
require __DIR__ . '/libs/helpers.php';

# CPT library
require __DIR__ . '/libs/cpt.php';

# ACF setup
require __DIR__ . '/libs/acf-json.php';
$ACF_JSON = new Libs\ACF_JSON(array('group_60bfc39773309'));

# Initialize
require __DIR__ . '/init/index.php';