<?php

namespace CUMULUS\Wordpress\Testimonials;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

// Register CPT
$Testimonials = new Libs\CPT(
	[
		'name'     => 'cmls-testimonial',
		'singular' => 'Testimonial',
		'plural'   => 'Testimonials',
		'slug'     => 'testimonials',
	],
	[
		'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes'],
		'labels'   => [
			'featured_image'        => 'Company Logo',
			'set_featured_image'    => 'Set the Company Logo',
			'remove_featured_image' => 'Remove the Company Logo',
			'use_featured_image'    => 'Use as Company Logo',
		],
		'menu_icon'           => 'dashicons-star-half',
		'has_archive'         => true,
		'show_in_rest'        => true,
		'disable_gutenberg'   => true,
		'exclude_from_search' => true,
		'show_in_nav_menus'   => false,
	]
);

// Add custom taxonomies for category and tags
$Testimonials->register_taxonomy(
	[
		'name'     => 'cmls_testimonial-category',
		'singular' => 'Testimonial Category',
		'plural'   => 'Testimonial Categories',
		'slug'     => 'testimonial-categories',
	]
);
$Testimonials->register_taxonomy(
	[
		'name'     => 'cmls_testimonial-tags',
		'singular' => 'Testimonial Tag',
		'plural'   => 'Testimonial Tags',
		'slug'     => 'testimonial-tags',
	],
	[
		'hierarchical'      => false,
		'show_in_nav_menus' => false,
	]
);

// Admin list columns
$Testimonials->columns( [
	'cb'                        => \__( 'Select All', $Testimonials->txt ),
	'title'                     => \__( 'Title', $Testimonials->txt ),
	'cmls_testimonial-category' => \__( 'Categories', $Testimonials->txt ),
	'cmls_testimonial-tags'     => \__( 'Tags', $Testimonials->txt ),
	'date'                      => \__( 'Date', $Testimonials->txt ),
] );

// Disable the title field in the editor
if ( \is_admin() ) {
	\add_filter( 'enter_title_here', function ( $text, $post ) use ( $Testimonials ) {
		if ( $post->post_type === $Testimonials->name ) {
			return 'Title Placeholder';
		}

		return $text;
	}, 10, 2 );
	\add_action( 'edit_form_after_title', function ( $post ) use ( $Testimonials ) {
		if ( $post->post_type === $Testimonials->name ) {
			if ( ! $post->post_title ) {
				?>
				<blockquote class="notice notice-info">
					<p>
						The title will be generated from the Customer and Company names below and is only used internally.
					</p>
				</blockquote>
				<?php
			} ?>
			<script>
				var title = document.getElementById('title');
				title.setAttribute('disabled', 'disabled');
			</script>
			<?php
		}
	}, 10, 1 );
}

// On save, set the title to Customer @ Company
if ( \is_admin() ) {
	\add_filter( 'wp_insert_post_data', function ( $data, $postarr ) use ( $Testimonials ) {
		if ( $data['post_type'] === $Testimonials->name && $data['post_status'] !== 'trash' && $postarr['ID'] ) {
			//echo '<pre>'; var_dump($data); echo '</pre>';
			if ( isset( $postarr['acf'] ) ) {
				$newTitle = [];
				// Customer name
				if ( isset( $postarr['acf']['field_60c00c514b58b'] ) ) {
					$newTitle[] = $postarr['acf']['field_60c00c514b58b'];
				}
				// Company name
				if ( isset( $postarr['acf']['field_60bfc884bfc9e'] ) ) {
					$newTitle[] = $postarr['acf']['field_60bfc884bfc9e'];
				}

				if ( \count( $newTitle ) ) {
					$data['post_title'] = \implode( ' @ ', $newTitle );
				} else {
					$data['post_title'] = 'Anonymous Testimonial';
				}

				// Set slug
				if ( ! $data['post_name'] || $data['post_name'] === 'auto_draft' ) {
					$data['post_name'] = \sanitize_title( $data['post_title'] );
				}
			}
		}

		return $data;
	}, 99, 2 );
}

// Move the featured image box up
if ( \is_admin() ) {
	\add_action( 'add_meta_boxes', function ( $post_type, $post ) use ( $Testimonials ) {
		if ( $post_type === $Testimonials->name ) {
			\add_meta_box( 'submitdiv', \__( 'Publish', $Testimonials->txt ), 'post_submit_meta_box', null, 'side', 'high' );
			\add_meta_box( 'postimagediv', \__( 'Company Logo', $Testimonials->txt ), 'post_thumbnail_meta_box', null, 'side', 'high' );
		}
	}, 10, 2 );
}

// Disable single testimonial pages
\add_action( 'template_redirect', function () use ( $Testimonials ) {
	if ( \is_singular( $Testimonials->name ) && ! \is_user_logged_in() ) {
		\wp_redirect( \get_post_type_archive_link( $Testimonials->name ) );
	}
} );

// Tell base theme where our templates are
\add_filter( 'cmls-locate_template_path', function ( $paths ) use ( $Testimonials ) {
	if (
		\is_post_type_archive( $Testimonials->name )
		|| \is_singular( $Testimonials->name )
	) {
		$paths[] = \CUMULUS\Wordpress\Testimonials\BASEPATH;
	}

	return $paths;
} );

// Override archive title
\add_filter( 'post_type_archive_title', function ( $title ) use ( $Testimonials ) {
	if ( ! \is_post_type_archive( $Testimonials->name ) ) {
		return $title;
	}

	return \__( 'Customer Testimonials', $Testimonials->txt );
}, 99, 1 );
