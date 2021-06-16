<?php
namespace CUMULUS\Wordpress\Testimonials\Libs;
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Custom post type creator
 * based on jjgrainger/wp-custom-post-type-class
 */
class CPT {

	/**
	 * Post type name
	 * @var string
	 */
	public $name;

	/**
	 * Optional alternative display forms of the CPT name.
	 *
	 * If not provided at construct, derived from $this->name
	 * 
	 * @var array
	 */
	public $display_names = array(
		'singular' => null,
		'plural' => null,
	);

	/**
	 * Internal name for the CPT, must be all lowercase,
	 * including only hypens or underscores.
	 *
	 * If not provided at construct, derived from $this->name
	 * 
	 * @var string
	 */
	public $slug;

	/**
	 * Additional options supplied on construct
	 * @var array
	 */
	public $options = array(
		'labels' => null,
		'public' => true,
		'rewrite' => array( 'slug' => null ),
		'disable_gutenberg' => false
	);

	/**
	 * Taxonomies
	 * @var array $taxonomies Holds an array of taxonomies associated with the post type.
	 */
	public $taxonomies = array();

	/**
	 * Taxonomy settings, an array of the taxonomies associated with the post
	 * type and their options used when registering the taxonomies.
	 * @var array $taxonomy_settings Holds the taxonomy settings.
	 */
	public $taxonomy_settings;

	/**
	 * Exisiting taxonomies to be registered after the posty has been registered
	 * @var array $exisiting_taxonomies holds exisiting taxonomies
	 */
	public $exisiting_taxonomies = array();

	/**
	 * Taxonomy filters. Defines which filters are to appear on admin edit
	 * screen used in add_taxonmy_filters().
	 * @var array $filters Taxonomy filters.
	 */
	public $filters = array();

	/**
	 * Defines which columns are to appear on the admin edit screen used
	 * in add_admin_columns().
	 * @var array $columns Columns visible in admin edit screen.
	 */
	public $columns = array();

	/**
	 * User defined functions to populate admin columns.
	 * @var array $custom_populate_columns User functions to populate columns.
	 */
	public $custom_populate_columns = array();

	/**
	 * Sortable columns.
	 * @var array $sortable Define which columns are sortable on the admin edit screen.
	 */
	public $sortable = array();

	/**
	 * Text domain for translations. Provided by construct,
	 * ::setDomain(), or defaults to $name.
	 * @var string
	 */
	public $txt;

	public function __construct( $name, $options = array() ) {

		// Handle submitting display names at instantiation
		if (is_array($name)) {

			$this->name = $name['name'];
			$this->display_names = array(
				'singular' => isset($name['singular']) ? $name['singular'] : $this->humanize($name['name']),
				'plural' => isset($name['plural']) ? $name['plural'] : $this->pluralize($name['name'])
			);
			$this->slug = isset($name['slug']) ? $name['slug'] : $this->get_slug($name['name']);
			$this->txt = isset($name['txt']) ? $name['txt'] : $name['name'];

		} else {
			$this->name = $name;
			$this->display_names = array(
				'singular' => $this->humanize($name),
				'plural' => $this->pluralize($name)
			);
			$this->slug = $this->get_slug($name);
			$this->txt = $name;
		}

		$this->options = $options;

		$this->add_action( 'init', array( &$this, 'register_taxonomies' ) );
		$this->add_action( 'init', array( &$this, 'register_post_type' ) );
		$this->add_action( 'init', array( &$this, 'register_existing_taxonomies' ), 11 );

		// Handle disabling gutenberg
		$this->add_filter( 'gutenberg_can_edit_post_type', array( &$this, 'disable_gutenberg' ), 10, 2 );
		$this->add_filter( 'use_block_editor_for_post_type', array( &$this, 'disable_gutenberg' ), 10, 2 );

		// Add taxonomy to admin edit column
		$this->add_filter( 'manage_edit-' . $this->name . '_columns', array( &$this, 'add_admin_columns' ) );

		// Populate the taxonomy columns with the posts terms.
		$this->add_action( 'manage_' . $this->name . '_posts_custom_column', array( &$this, 'populate_admin_columns' ), 10, 2 );
	
		// Add filter select option to admin edit.
		$this->add_action( 'restrict_manage_posts', array( &$this, 'add_taxonomy_filters' ) );

		// rewrite post update messages
		$this->add_filter( 'post_updated_messages', array( &$this, 'updated_messages' ) );
		$this->add_filter( 'bulk_post_updated_messages', array( &$this, 'bulk_updated_messages' ), 10, 2 );

	}

	/**
	 * Wrapper for Wordpress add_action
	 */
	private function add_action( $action, $func, $priority = 10, $args = 1 ) {
		\add_action( $action, $func, $priority, $args );
	}

	/**
	 * Wrapper for Wordpress add_filter
	 */
	private function add_filter( $action, $func, $priority = 10, $args = 1 ) {
		// Pass variables into Wordpress add_action function
		\add_filter( $action, $func, $priority, $args );
	}

	/**
	 * Returns a "humanized" version of provided string.
	 * Replaces - and _ with spaces, uppercases words.
	 * 
	 * @param  string $str
	 * @return string
	 */
	private function humanize($str = null) {
		if (is_null($str)) {
			$str = $this->name;
		}
		return ucwords(
			strtolower(
				str_replace(
					array('-', '_'),
					' ',
					$str
				)
			)
		);
	}

	/**
	 * Humanize and add an 's'
	 * @param  string $str
	 * @return string
	 */
	private function pluralize($str = null) {
		return $this->humanize($str) . 's';
	}

	/**
	 * Get slug
	 *
	 * Creates an url friendly slug.
	 *
	 * @param  string $name Name to slugify.
	 * @return string $name Returns the slug.
	 */
	public function get_slug( $name = null ) {

		// If no name set use the post type name.
		if (is_null($name)) {

			$name = $this->name;
		}

		// Name to lower case.
		return strtolower(
			str_replace(
				array(' ', '_'),
				'-',
				$name
			)
		);
	}

	public function register_post_type() {

		if ( \post_type_exists( $this->name ) ) {
			return;
		}

		$singular = $this->display_names['singular'];
		$plural = $this->display_names['plural'];
		$slug = $this->slug;

		$labels = array(
			'name'                     => sprintf( \__( '%s', $this->txt ), $plural ),
			'singular_name'            => sprintf( \__( '%s', $this->txt ), $singular ),
			'add_new'                  => \__( 'Add New', $this->txt ),
			'add_new_item'             => sprintf( \__( 'Add New %s', $this->txt ), $singular ),
			'edit_item'                => sprintf( \__( 'Edit %s', $this->txt ), $singular ),
			'new_item'                 => sprintf( \__( 'New %s', $this->txt ), $singular ),
			'view_item'                => sprintf( \__( 'View %s', $this->txt ), $singular ),
			'view_items'               => sprintf( \__( 'View %s', $this->txt ), $plural ),
			'search_items'             => sprintf( \__( 'Search %s', $this->txt ), $plural ),
			'not_found'                => sprintf( \__( 'No %s found', $this->txt ), $plural ),
			'not_found_in_trash'       => sprintf( \__( 'No %s found in Trash', $this->txt ), $plural ),
			'parent_item_colon'        => sprintf( \__( 'Parent %s:', $this->txt ), $singular ),
			'all_items'                => sprintf( \__( 'All %s', $this->txt ), $plural ),
			'archives'                 => sprintf( \__( '%s Archives', $this->txt ), $singular),
			'attributes'               => sprintf( \__( '%s Attributes', $this->txt ), $singular),
			'insert_into_item'         => sprintf( \__( 'Insert into %s', $this->txt ), $singular),
			'upload_to_this_item'      => sprintf( \__( 'Upload to this %s', $this->txt ), $singular),
			'menu_name'                => sprintf( \__( '%s', $this->txt ), $plural ),
			'filter_items_list'        => sprintf( \__( 'Filter %s list', $this->txt ), $plural ),
			'items_list_navigation'    => sprintf( \__( '%s list navigation', $this->txt ), $plural ),
			'items_list'               => sprintf( \__( '%s list', $this->txt ), $plural ),
			'item_published'           => sprintf( \__( '%s published', $this->txt ), $singular ),
			'item_published_privately' => sprintf( \__( '%s published privately', $this->txt ), $singular ),
			'item_reverted_to_draft'   => sprintf( \__( '%s reverted to draft', $this->txt ), $singular ),
			'item_scheduled'           => sprintf( \__( '%s scheduled', $this->txt ), $singular),
			'item_updated'             => sprintf( \__( '%s updated', $this->txt ), $singular ),
		);

		$defaults = array(
			'label' => $plural,
			'labels' => $labels,
			'public' => true,
			'rewrite' => array(
				'slug' => $slug
			)
		);

		$resolved_options = array_replace_recursive( $defaults, $this->options );
		$this->options = $resolved_options;

		//echo '<pre>';var_dump($this->name); var_dump($this->options);echo '</pre>';
		\register_post_type( $this->name, $this->options );

	}

	public function register_taxonomy($tax_names, $options = array()) {

		if (is_array($tax_names)) {
			$name = $tax_names['name'];
			$singular = isset($tax_names['singular']) ? $tax_names['singular'] : $this->humanize($name);
			$plural = isset($tax_names['plural']) ? $tax_names['plural'] : $this->pluralize($name);
			$slug = isset($tax_names['slug']) ? $tax_names['slug'] : $this->get_slug($name);
		} else {
			$name = $tax_names;
			$singular = $this->humanize($tax_names);
			$plural = $this->pluralize($tax_names);	
			$slug = $this->get_slug($tax_names);
		}

		// Default labels.
		$labels = array(
			'name'                       => sprintf( \__( '%s', $this->txt ), $plural ),
			'singular_name'              => sprintf( \__( '%s', $this->txt ), $singular ),
			'search_items'               => sprintf( \__( 'Search %s', $this->txt ), $plural ),
			'popular_items'              => sprintf( \__( 'Popular %s', $this->txt ), $plural ),
			'all_items'                  => sprintf( \__( 'All %s', $this->txt ), $plural ),
			'parent_item'                => sprintf( \__( 'Parent %s', $this->txt ), $plural ),
			'parent_item_colon'          => sprintf( \__( 'Parent %s:', $this->txt ), $plural ),
			'edit_item'                  => sprintf( \__( 'Edit %s', $this->txt ), $singular ),
			'view_item'                  => sprintf( \__( 'View %s', $this->txt ), $singular ),
			'update_item'                => sprintf( \__( 'Update %s', $this->txt ), $singular ),
			'add_new_item'               => sprintf( \__( 'Add New %s', $this->txt ), $singular ),
			'new_item_name'              => sprintf( \__( 'New %s Name', $this->txt ), $singular ),
			'separate_items_with_commas' => sprintf( \__( 'Seperate %s with commas', $this->txt ), $plural ),
			'add_or_remove_items'        => sprintf( \__( 'Add or remove %s', $this->txt ), $plural ),
			'choose_from_most_used'      => sprintf( \__( 'Choose from most used %s', $this->txt ), $plural ),
			'not_found'                  => sprintf( \__( 'No %s found', $this->txt ), $plural ),
			'no_terms'                   => sprintf( \__( 'No %s', $this->txt ), $plural ),
			'filter_by_item'             => sprintf( \__( 'Filter by %s', $this->txt ), $singular ),
			'menu_name'                  => sprintf( \__( '%s', $this->txt ), $plural ),
		);

		$defaults = array(
			'labels' => $labels,
			'public' => true,
			'show_in_rest' => true,
			'hierarchical' => true,
			'rewrite' => array(
				'slug' => $slug
			)
		);
		$resolved_options = array_replace_recursive( $defaults, $options );

		$this->taxonomies[$name] = $resolved_options;
	}

	/**
	 * Hook function for actually registering defined taxonomies in WP
	 */
	public function register_taxonomies() {
		foreach($this->taxonomies as $name => $options) {

			if (\taxonomy_exists($name)) {
				$this->exisiting_taxonomies[] = $name;
				continue;
			}

			\register_taxonomy($name, $this->name, $options);
		}
	}

	/**
	 * Assign any discovered existing taxonomies to our CPT
	 */
	public function register_existing_taxonomies() {
		foreach($this->exisiting_taxonomies as $name) {
			\register_taxonomy_for_object_type( $name, $this->name );
		}
	}

	/**
	 * Add admin columns
	 *
	 * Adds columns to the admin edit screen. Function is used with add_action
	 *
	 * @param array $columns Columns to be added to the admin edit screen.
	 * @return array
	 */
	function add_admin_columns( $columns ) {

		// If user supplied columns, use those
		if (count($this->columns)) {
			$columns = $this->columns;
		} else {

			// Use taxonomies instead

			$new_columns = array();

			// determine which column to add custom taxonomies after
			if ( is_array($this->taxonomies) ) {
				if (array_key_exists('post_tag', $this->taxonomies)) {
					$after = 'tags';
				}
				if (array_key_exists('category', $this->taxonomies)) {
					$after = 'categories';
				}
			} elseif ( \post_type_supports( $this->name, 'author' ) ) {
				$after = 'author';
			} else {
				$after = 'title';
			}

			// foreach exisiting columns
			foreach( $columns as $key => $title ) {

				// add exisiting column to the new column array
				$new_columns[$key] = $title;

				// we want to add taxonomy columns after a specific column
				if( $key === $after ) {

					// If there are taxonomies registered to the post type.
					if ( is_array( $this->taxonomies ) ) {

						// Create a column for each taxonomy.
						foreach( $this->taxonomies as $tax => $options ) {

							// WordPress adds Categories and Tags automatically, ignore these
							if( $tax !== 'category' && $tax !== 'post_tag' ) {
								// Column key is the slug, value is friendly name.
								$new_columns[ $tax ] = sprintf( \__( '%s', $this->txt ), $options['labels']['name'] );
							}
						}
					}
				}
			}

			// overide with new columns
			$columns = $new_columns;

		}
		return $columns;
	}

	/**
	 * Populate admin columns
	 *
	 * Populate custom columns on the admin edit screen.
	 *
	 * @param string $column The name of the column.
	 * @param integer $post_id The post ID.
	 */
	function populate_admin_columns( $column, $post_id ) {

		// Get wordpress $post object.
		global $post;

		// determine the column
		switch( $column ) {

			// If column is a taxonomy associated with the post type.
			case ( \taxonomy_exists( $column ) ) :

				// Get the taxonomy for the post
				$terms = \get_the_terms( $post_id, $column );

				// If we have terms.
				if ( ! empty( $terms ) ) {

					$output = array();

					// Loop through each term, linking to the 'edit posts' page for the specific term.
					foreach( $terms as $term ) {

						// Output is an array of terms associated with the post.
						$output[] = sprintf(

							// Define link.
							'<a href="%s">%s</a>',

							// Create filter url.
							\esc_url( \add_query_arg( array( 'post_type' => $post->post_type, $column => $term->slug ), 'edit.php' ) ),

							// Create friendly term name.
							\esc_html( \sanitize_term_field( 'name', $term->name, $term->term_id, $column, 'display' ) )
						);

					}

					// Join the terms, separating them with a comma.
					echo join( ', ', $output );

				// If no terms found.
				} else {

					// Get the taxonomy object for labels
					$taxonomy_object = \get_taxonomy( $column );

					// Echo no terms.
					printf( __( 'No %s', $this->txt ), $taxonomy_object->labels->name );
				}

			break;

			// If column is for the post ID.
			case 'post_id' :

				echo $post->ID;

			break;

			// if the column is prepended with 'meta_', this will automagically retrieve the meta values and display them.
			case ( preg_match( '/^meta_/', $column ) ? true : false ) :

				// meta_book_author (meta key = book_author)
				$x = substr( $column, 5 );

				$meta = \get_post_meta( $post->ID, $x );

				echo join( ", ", $meta );

			break;

			// If the column is post thumbnail.
			case 'icon' :

				// Create the edit link.
				$link = \esc_url( \add_query_arg( array( 'post' => $post->ID, 'action' => 'edit' ), 'post.php' ) );

				// If it post has a featured image.
				if ( \has_post_thumbnail() ) {

					// Display post featured image with edit link.
					echo '<a href="' . $link . '">';
						\the_post_thumbnail( array(60, 60) );
					echo '</a>';

				} else {

					// Display default media image with link.
					echo '<a href="' . $link . '"><img src="'. \site_url( '/wp-includes/images/crystal/default.png' ) .'" alt="' . $post->post_title . '" /></a>';

				}

			break;

			// Default case checks if the column has a user function, this is most commonly used for custom fields.
			default :

				// If there are user custom columns to populate.
				if ( isset( $this->custom_populate_columns ) && is_array( $this->custom_populate_columns ) ) {

					// If this column has a user submitted function to run.
					if ( isset( $this->custom_populate_columns[ $column ] ) && is_callable( $this->custom_populate_columns[ $column ] ) ) {

						// Run the function.
						call_user_func_array(  $this->custom_populate_columns[ $column ], array( $column, $post ) );

					}
				}

			break;
		} // end switch( $column )
	}

	/**
	 * Filters
	 *
	 * User function to define which taxonomy filters to display on the admin page.
	 *
	 * @param array $filters An array of taxonomy filters to display.
	 */
	function filters( $filters = array() ) {

		$this->filters = $filters;
	}

	/**
	 *  Add taxtonomy filters
	 *
	 * Creates select fields for filtering posts by taxonomies on admin edit screen.
	*/
	public function add_taxonomy_filters() {

		global $typenow;
		global $wp_query;

		// Must set this to the post type you want the filter(s) displayed on.
		if ( $typenow == $this->name ) {

			// if custom filters are defined use those
			if ( is_array( $this->filters ) ) {

				$filters = $this->filters;

			// else default to use all taxonomies associated with the post
			} else {

				$filters = $this->taxonomies;

			}

			// Foreach of the taxonomies we want to create filters for...
			foreach ( $filters as $tax_slug => $options ) {

				// ...object for taxonomy, doesn't contain the terms.
				$tax = \get_taxonomy( $tax_slug );

				// Get taxonomy terms and order by name.
				$args = array(
					'orderby' => 'name',
					'hide_empty' => false
				);

				// Get taxonomy terms.
				$terms = \get_terms( $tax_slug, $args );

				// If we have terms.
				if ( $terms ) {

					// Set up select box.
					printf( ' &nbsp;<select name="%s" class="postform">', $tax_slug );

					// Default show all.
					printf( '<option value="0">%s</option>', sprintf( __( 'Show all %s', $this->txt ), $tax->label ) );

					// Foreach term create an option field...
					foreach ( $terms as $term ) {

						// ...if filtered by this term make it selected.
						if ( isset( $_GET[ $tax_slug ] ) && $_GET[ $tax_slug ] === $term->slug ) {

							printf( '<option value="%s" selected="selected">%s (%s)</option>', $term->slug, $term->name, $term->count );

						// ...create option for taxonomy.
						} else {

							printf( '<option value="%s">%s (%s)</option>', $term->slug, $term->name, $term->count );
						}
					}
					// End the select field.
					print( '</select>&nbsp;' );
				}
			}
		}
	}

	/**
	 * Columns
	 *
	 * Choose columns to be displayed on the admin edit screen.
	 *
	 * @param array $columns An array of columns to be displayed.
	 */
	function columns( $columns ) {

		// If columns is set.
		if( isset( $columns ) ) {

			// Assign user submitted columns to object.
			$this->columns = $columns;

		}
	}

	/**
	 * Populate columns
	 *
	 * Define what and how to populate a speicific admin column.
	 *
	 * @param string $column_name The name of the column to populate.
	 * @param mixed $callback An anonyous function or callable array to call when populating the column.
	 */
	function populate_column( $column_name, $callback ) {

		$this->custom_populate_columns[ $column_name ] = $callback;

	}

	/**
	 * Sortable
	 *
	 * Define what columns are sortable in the admin edit screen.
	 *
	 * @param array $columns An array of columns that are sortable.
	 */
	function sortable( $columns = array() ) {

		// Assign user defined sortable columns to object variable.
		$this->sortable = $columns;

		// Run filter to make columns sortable.
		$this->add_filter( 'manage_edit-' . $this->name . '_sortable_columns', array( &$this, 'make_columns_sortable' ) );

		// Run action that sorts columns on request.
		$this->add_action( 'load-edit.php', array( &$this, 'load_edit' ) );
	}

	/**
	 * Make columns sortable
	 *
	 * Internal function that adds user defined sortable columns to WordPress default columns.
	 *
	 * @param array $columns Columns to be sortable.
	 *
	 */
	function make_columns_sortable( $columns ) {

		// For each sortable column.
		foreach ( $this->sortable as $column => $values ) {

			// Make an array to merge into wordpress sortable columns.
			$sortable_columns[ $column ] = $values[0];
		}

		// Merge sortable columns array into wordpress sortable columns.
		$columns = array_merge( $sortable_columns, $columns );

		return $columns;
	}

	/**
	 * Load edit
	 *
	 * Sort columns only on the edit.php page when requested.
	 *
	 * @see http://codex.wordpress.org/Plugin_API/Filter_Reference/request
	 */
	function load_edit() {

		// Run filter to sort columns when requested
		$this->add_filter( 'request', array( &$this, 'sort_columns' ) );

	}

	/**
	 * Sort columns
	 *
	 * Internal function that sorts columns on request.
	 *
	 * @see load_edit()
	 *
	 * @param array $vars The query vars submitted by user.
	 * @return array A sorted array.
	 */
	function sort_columns( $vars ) {

		if ( ! (isset($vars['post_type']) && $this->name === $vars['post_type']) ) {
			return;
		}

		// Cycle through all sortable columns submitted by the user
		foreach ( $this->sortable as $column => $values ) {

			// Retrieve the meta key from the user submitted array of sortable columns
			$meta_key = $values[0];

			// If the meta_key is a taxonomy
			if( \taxonomy_exists( $meta_key ) ) {

				// Sort by taxonomy.
				$key = "taxonomy";

			} else {

				// else by meta key.
				$key = "meta_key";
			}

			// If the optional parameter is set and is set to true
			if ( isset( $values[1] ) && true === $values[1] ) {

				// Vaules needed to be ordered by integer value
				$orderby = 'meta_value_num';

			} else {

				// Values are to be order by string value
				$orderby = 'meta_value';
			}

			// Check if we're viewing this post type
			if ( isset( $vars['post_type'] ) && $this->name == $vars['post_type'] ) {

				// find the meta key we want to order posts by
				if ( isset( $vars['orderby'] ) && $meta_key == $vars['orderby'] ) {

					// Merge the query vars with our custom variables
					$vars = array_merge(
						$vars,
						array(
							'meta_key' => $meta_key,
							'orderby' => $orderby
						)
					);
				}
			}
		}
		return $vars;
	}

	/**
	 * Updated messages
	 *
	 * Internal function that modifies the post type names in updated messages
	 *
	 * @param array $messages an array of post updated messages
	 */
	public function updated_messages( $messages ) {

		$post = \get_post();
		$singular = $this->display_names['singular'];

		$messages[$this->name] = array(
			0 => '',
			1 => sprintf( \__( '%s updated.', $this->txt ), $singular ),
			2 => \__( 'Custom field updated.', $this->txt ),
			3 => \__( 'Custom field deleted.', $this->txt ),
			4 => sprintf( \__( '%s updated.', $this->txt ), $singular ),
			5 => isset( $_GET['revision'] ) ? sprintf( \__( '%2$s restored to revision from %1$s', $this->txt ), \wp_post_revision_title( (int) $_GET['revision'], false ), $singular ) : false,
			6 => sprintf( \__( '%s updated.', $this->txt ), $singular ),
			7 => sprintf( \__( '%s saved.', $this->txt ), $singular ),
			8 => sprintf( \__( '%s submitted.', $this->txt ), $singular ),
			9 => sprintf(
				\__( '%2$s scheduled for: <strong>%1$s</strong>.', $this->txt ),
				date_i18n( \__( 'M j, Y @ G:i', $this->txt ), strtotime( $post->post_date ) ),
				$singular
			),
			10 => sprintf( \__( '%s draft updated.', $this->txt ), $singular ),
		);

		return $messages;
	}

	/**
	 * Bulk updated messages
	 *
	 * Internal function that modifies the post type names in bulk updated messages
	 *
	 * @param array $messages an array of bulk updated messages
	 */
	public function bulk_updated_messages( $bulk_messages, $bulk_counts ) {

		$singular = $this->display_names['singular'];
		$plural = $this->display_names['plural'];

		$bulk_messages[ $this->name ] = array(
			'updated'   => \_n( "%s {$singular} updated.", "%s {$plural} updated.", $bulk_counts['updated'], $this->txt),
			'locked'    => \_n( "%s {$singular} not updated, somebody is editing it.", "%s {$plural} not updated, somebody is editing them.", $bulk_counts['locked'], $this->txt ),
			'deleted'   => _n( "%s {$singular} permanently deleted.", "%s {$plural} permanently deleted.", $bulk_counts['deleted'], $this->txt ),
			'trashed'   => _n( "%s {$singular} moved to the Trash.", "%s {$plural} moved to the Trash.", $bulk_counts['trashed'], $this->txt ),
			'untrashed' => _n( "%s {$singular} restored from the Trash.", "%s {$plural} restored from the Trash.", $bulk_counts['untrashed'], $this->txt ),
		);

		return $bulk_messages;
	}

	/**
	 * Filter handler for disabling gutenberg
	 */
	public function disable_gutenberg($current, $type = null) {
		if ($type === $this->name && $this->options['disable_gutenberg']) {
			return false;
		}
		return $current;
	}

}