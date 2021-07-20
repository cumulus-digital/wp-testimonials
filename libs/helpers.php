<?php

namespace CUMULUS\Wordpress\Testimonials\Libs;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

class jsonCache {

	public static $cache = [];

	public static function set( $key, $val ) {
		self::$cache[$key] = $val;
	}

	public static function has( $key ) {
		return \array_key_exists( $key, self::$cache );
	}

	public static function get( $key ) {
		if ( self::has( $key ) ) {
			return self::$cache[$key];
		}

		return false;
	}
}

function processJSON( $file ) {
	if ( jsonCache::has( $file ) ) {
		return jsonCache::get( $file );
	}
	$json = \file_get_contents( $file );
	$ret  = false;

	if ( $json ) {
		$ret = \json_decode( $json, true );
	}
	jsonCache::set( $file, $ret );

	return $ret;
}

function getDefaultsFromAttributes( $arr ) {
	if ( ! \array_key_exists( 'attributes', $arr ) ) {
		return false;
	}
	$defaults = [];

	foreach ( $arr['attributes'] as $key => $options ) {
		if ( \array_key_exists( 'default', $options ) ) {
			$defaults[$key] = $options['default'];

			continue;
		} elseif ( \array_key_exists( 'type', $options ) ) {
		}
		$defaults[$key] = null;
	}

	return $defaults;
}
