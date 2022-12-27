<?php

require_once WPQRMONO_PLUGIN_DIR . '/includes/l10n.php';
require_once WPQRMONO_PLUGIN_DIR . '/includes/capabilities.php';
require_once WPQRMONO_PLUGIN_DIR . '/includes/form.php';
require_once WPQRMONO_PLUGIN_DIR . '/includes/functions.php';
require_once WPQRMONO_PLUGIN_DIR . '/includes/shortcodes.php';
require_once WPQRMONO_PLUGIN_DIR . '/includes/form-functions.php';
require_once WPQRMONO_PLUGIN_DIR . '/includes/form-template.php';

if ( is_admin() ) {
	require_once WPQRMONO_PLUGIN_DIR . '/admin/admin.php';
} else {
	require_once WPQRMONO_PLUGIN_DIR . '/includes/controller.php';
}

class WPQRMONO {
	/**
	 * Loads sections from the sections directory.
	 */
	public static function load_sections() {
		
	}

	/**
	 * Loads the specified section.
	 *
	 * @param string $mod Name of section.
	 * @return bool True on success, false on failure.
	 */
	protected static function load_section( $section ) {
		return false
			|| wpqrmono_include_section_file( $mod . '/' . $section . '.php' )
			|| wpqrmono_include_section_file( $mod . '.php' );
	}


	/**
	 * Retrieves a named entry from the option array of Form.
	 *
	 * @param string $name Array item key.
	 * @param mixed $default_value Optional. Default value to return if the entry
	 *                             does not exist. Default false.
	 * @return mixed Array value tied to the $name key. If nothing found,
	 *               the $default_value value will be returned.
	 */
	public static function get_option( $name, $default_value = false ) {
		$option = get_option( 'wpqrmono' );

		if ( false === $option ) {
			return $default_value;
		}

		if ( isset( $option[$name] ) ) {
			return $option[$name];
		} else {
			return $default_value;
		}
	}


	/**
	 * Update an entry value on the option array of Form.
	 *
	 * @param string $name Array item key.
	 * @param mixed $value Option value.
	 */
	public static function update_option( $name, $value ) {
		$option = get_option( 'wpqrmono' );
		$option = ( false === $option ) ? array() : (array) $option;
		$option = array_merge( $option, array( $name => $value ) );
		update_option( 'wpqrmono', $option );
	}
}


add_action( 'plugins_loaded', 'wpqrmono', 10, 0 );

/**
 * Loads sections and registers WordPress shortcodes.
 */
function wpqrmono() {
	WPQRMONO::load_sections();

	add_shortcode( 'qrmono-form', 'wpqrmono_form_tag_func' );
}


add_action( 'init', 'wpqrmono_init', 10, 0 );

/**
 * Registers post types for forms.
 */
function wpqrmono_init() {
	wpqrmono_get_request_uri();
	wpqrmono_register_post_types();

	do_action( 'wpqrmono_init' );
}


add_action( 'admin_init', 'wpqrmono_upgrade', 10, 0 );

/**
 * Upgrades option data when necessary.
 */
function wpqrmono_upgrade() {
	$old_ver = WPQRMONO::get_option( 'version', '0' );
	$new_ver = WPQRMONO_VERSION;

	if ( $old_ver == $new_ver ) {
		return;
	}

	do_action( 'wpqrmono_upgrade', $new_ver, $old_ver );

	WPQRMONO::update_option( 'version', $new_ver );
}


add_action( 'activate_' . WPQRMONO_PLUGIN_BASENAME, 'wpqrmono_install', 10, 0 );

/**
 * Callback tied to plugin activation action hook. Attempts to create
 * initial user dataset.
 */
function wpqrmono_install() {
	if ( $opt = get_option( 'wpqrmono' ) ) {
		return;
	}

	wpqrmono_register_post_types();
	wpqrmono_upgrade();

	if ( get_posts( array( 'post_type' => 'wpqrmono_form' ) ) ) {
		return;
	}

	$form = WPQRMONO_Form::get_template(
		array(
			'title' =>
				/* translators: title of your first form. %d: number fixed to '1' */
				sprintf( __( 'Form %d', 'qrmono-form' ), 1 ),
		)
	);

	$form->save();

	WPQRMONO::update_option( 'bulk_validate',
		array(
			'timestamp' => time(),
			'version' => WPQRMONO_VERSION,
			'count_valid' => 1,
			'count_invalid' => 0,
		)
	);
}
