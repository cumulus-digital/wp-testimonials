<?php
namespace CUMULUS\Wordpress\Testimonials\Libs;

/**
 * Overrides ACF to load our own JSON for a group, and to save that group
 * within our own plugin.
 */
class ACF_JSON {
	
	/** @var array holder for defined groups */
	private $groups = array();

	public function __construct($groups) {
		$this->groups = $groups;
		\add_action(
			'acf/update_field_group',
			array(&$this, 'update_field_group'),
			1, 1
		);
	}

	public function update_field_group($group) {
		if (in_array($group['key'], $this->groups)) {
			\add_filter(
				'acf/settings/save_json',
				array(&$this, 'override_location'),
				99999
			);
		}
		return $group;
	}

	public function override_location($path) {
		\remove_filter(
			'acf/settings/save_json',
			array(&$this, 'override_location'),
			99999
		);
		$path = dirname(\plugin_dir_path(__FILE__)) . '/acf-json';
		return $path;
	}
}