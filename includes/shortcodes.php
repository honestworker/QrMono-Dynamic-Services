<?php
/**
 * All the functions and classes in this file are deprecated.
 * You should not use them. The functions and classes will be
 * removed in a later version.
 */

function wpqrmono_add_shortcode( $callback, $has_name = false ) {
	return wpqrmono_add_form_tag( $callback, $has_name );
}

function wpqrmono_remove_shortcode() {
	return wpqrmono_remove_form_tag();
}

function wpqrmono_do_shortcode( $content ) {
	return wpqrmono_replace_all_form_tags( $content );
}

function wpqrmono_scan_shortcode( $cond = null ) {
	return wpqrmono_scan_form_tags( $cond );
}

class WPQRMONO_ShortcodeManager {
	private function __construct() {}

	public static function get_instance() {
		return new self;
	}

	public function remove_shortcode() {
	}

	public function normalize_shortcode( $content ) {
	}

	public function do_shortcode( $content, $exec = true ) {
		if ( $exec ) {
		} else {
		}
	}

	public function scan_shortcode() {
	}
}

class WPQRMONO_Shortcode {
	public function __construct( ) {
	}
}