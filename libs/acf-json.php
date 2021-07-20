<?php

namespace CUMULUS\Wordpress\Testimonials\Libs;

use const CUMULUS\Wordpress\Testimonials\BASEPATH as MYBASEPATH;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

/**
 * Overrides ACF to load our own JSON for a group, and to save that group
 * within our own plugin.
 */
class ACF_JSON {

	/** @var array holder for defined groups */
	private $groups = [];

	public function __construct( $groups ) {
		$this->groups = $groups;
		\add_action(
			'acf/update_field_group',
			[&$this, 'update_field_group'],
			1,
			1
		);
		\add_filter(
			'acf/settings/load_json',
			[&$this, 'load_json'],
			1,
			1
		);
		\add_filter(
			'acf/settings/save_json',
			[&$this, 'save_json'],
			1,
			1
		);
	}

	public function update_field_group( $group ) {
		if ( \in_array( $group['key'], $this->groups ) ) {
			\add_filter(
				'acf/settings/save_json',
				[&$this, 'override_location'],
				99999
			);
		}

		return $group;
	}

	public function override_location( $path ) {
		\remove_filter(
			'acf/settings/save_json',
			[&$this, 'override_location'],
			99999
		);
		$path = \dirname( \plugin_dir_path( __FILE__ ) ) . '/acf-json';

		return $path;
	}

	public function save_json( $path ) {
		if ( isset( $_POST['acf_field_group']['key'] ) ) {
			if ( \in_array( $_POST['acf_field_group']['key'], $this->groups ) ) {
				return MYBASEPATH . '/acf-json';
			}
		}

		return $path;
	}

	public function load_json( $paths ) {
		$paths[] = MYBASEPATH . '/acf-json';

		return $paths;
	}
}
