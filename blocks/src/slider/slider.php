<?php

namespace CUMULUS\Wordpress\Testimonials\Blocks\Slider;

// Exit if accessed directly.
\defined('ABSPATH') || exit('No direct access allowed.');

require __DIR__ . '/assets.php';

class TestimonialSliderBlock
{
    public $block;

    public $class = 'cmls-wp-testimonials-slider';

    private $template = './template.php';

    public function __construct()
    {
        \add_action('init', array($this, 'registerBlock'));
    }

    public function registerBlock()
    {
        if (! function_exists('register_block_type_from_metadata')) {
            return;
        }

        $this->block = \register_block_type_from_metadata(
            __DIR__,
            array(
                'render_callback' => array($this, 'renderBlock')
            )
        );
    }

    public function renderBlock($attr, $content)
    {
        $class = $this->class;

        // Load Testimonial posts
        $query = array(
            'post_type' => 'cmls-testimonial',
            'post_status' => 'publish',
            'posts_per_page' => $attr['postsToShow'] === 0 ? -1 : $attr['postsToShow'],
            'tax_query' => array(),
            'orderby' => $attr['orderBy'],
            'order' => $attr['order']
        );

        if (isset($attr['categories']) && count($attr['categories'])) {
            $categories = array();
            foreach ($attr['categories'] as $cat) {
                $categories[] = $cat['id'];
            }
            $query['tax_query'][] = array(
                'taxonomy' => 'cmls_testimonial-category',
                'field' => 'term_id',
                'terms' => $categories
            );
        }

        if (isset($attr['tags']) && count($attr['tags'])) {
            $tags = array();
            foreach ($attr['tags'] as $tag) {
                $tags[] = $tag['id'];
            }
            $query['tax_query']['relation'] = 'AND';
            $query['tax_query'][] = array(
                'taxonomy' => 'cmls_testimonial-tags',
                'field' => 'term_id',
                'terms' => $tags
            );
        }

        $posts = \get_posts($query);

        // Fetch ACF for each post
        foreach ($posts as $post) {
            $post->acf_fields = \get_fields($post->ID);
        }

        // Display options
        $sliderOptions = array(
            // Mandatory options
            'autoWidth' => false,
            'pauseOnHover' => true,
            'pauseOnFocus' => true,
            'lazyLoad' => 'nearby',
            'slideFocus' => false,
            'gap' => '2em',
            'arrowPath' => 'm15.5 0.932-4.3 4.38 14.5 14.6-14.5 14.5 4.3 4.4 14.6-14.6 4.4-4.3-4.4-4.4-14.6-14.6z',
            'easing' => 'ease-out',

            // User-defined options
            'type' => $attr['animationType'] === 'fade' ? 'fade' : (count($posts) > 1 ? 'loop' : 'slide'),
            'speed' => $attr['transitionSpeed'] * 1000,
            'perPage' => $attr['slidesToShow'],
            'perMove' => $attr['slidesToScroll'],
            'arrows' => count($posts) > 1 ? $attr['showArrows'] : false,
            'pagination' => count($posts) > 1 ? $attr['showDots'] : false,
            'autoplay' => $attr['autoplay'],
            'interval' => $attr['autoplaySpeed'] * 1000,
            'focus' => $attr['slidesToShow'] > 1 && $attr['slidesToShow'] % 2 ? true : false,
        );

        ob_start();
        include __DIR__ . '/template.php';
        return ob_get_clean();
    }
}
$TestimonialSlider = new TestimonialSliderBlock();
