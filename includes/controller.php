<?php

/**
 * Controller for front-end requests, scripts, and styles
 */
add_action(
	'parse_request',
	'wpmono_control_init',
	20, 0
);

/**
 * Handles a submission in non-Ajax mode.
 */
function wpmono_control_init() {
	if ( WPQRMONO_Submission::is_restful() ) {
		return;
	}

	if ( isset( $_POST['_wpqrmono'] ) ) {
		$form = wpmono_form( (int) $_POST['_wpqrmono'] );

		if ( $form ) {
			$form->submit();
		}
	}
}


/**
 * Registers main scripts and styles.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$assets = array();
		$asset_file = wpmono_plugin_path( 'includes/js/index.asset.php' );

		if ( file_exists( $asset_file ) ) {
			$assets = include( $asset_file );
		}

		$assets = wp_parse_args( $assets, array(
			'dependencies' => array(),
			'version' => WPQRMONO_VERSION,
		) );

		wp_register_script(
			'qrmono-form',
			wpmono_plugin_url( 'includes/js/index.js' ),
			array_merge(
				$assets['dependencies'],
				array( 'swv' )
			),
			$assets['version'],
			true
		);

		if ( wpmono_load_js() ) {
			wpmono_enqueue_scripts();
		}

		wp_register_style(
			'qrmono-form',
			wpmono_plugin_url( 'includes/css/styles.css' ),
			array(),
			WPQRMONO_VERSION,
			'all'
		);

		wp_register_style(
			'qrmono-form-rtl',
			wpmono_plugin_url( 'includes/css/styles-rtl.css' ),
			array( 'qrmono-form' ),
			WPQRMONO_VERSION,
			'all'
		);

		wp_register_style(
			'jquery-ui',
			wpmono_plugin_url(
				'includes/js/jquery-ui/jquery-ui.min.css'
			),
			array(),
			'1.12.1',
			'screen'
		);

		if ( wpmono_load_css() ) {
			wpmono_enqueue_styles();
		}
	},
	10, 0
);


/**
 * Enqueues scripts.
 */
function wpmono_enqueue_scripts() {
	wp_enqueue_script( 'qrmono-form' );

	$wpqrmono = array(
		'api' => array(
			'root' => sanitize_url( get_rest_url() ),
			'namespace' => 'qrmono-form/v1',
		),
	);

	if ( defined( 'WP_CACHE' ) and WP_CACHE ) {
		$wpqrmono['cached'] = 1;
	}

	wp_localize_script( 'qrmono-form', 'wpqrmono', $wpqrmono );

	do_action( 'wpmono_enqueue_scripts' );
}


/**
 * Returns true if the main script is enqueued.
 */
function wpmono_script_is() {
	return wp_script_is( 'qrmono-form' );
}


/**
 * Enqueues styles.
 */
function wpmono_enqueue_styles() {
	wp_enqueue_style( 'qrmono-form' );

	if ( wpmono_is_rtl() ) {
		wp_enqueue_style( 'qrmono-form-rtl' );
	}

	do_action( 'wpmono_enqueue_styles' );
}


/**
 * Returns true if the main stylesheet is enqueued.
 */
function wpmono_style_is() {
	return wp_style_is( 'qrmono-form' );
}


add_action(
	'wp_enqueue_scripts',
	'wpmono_html5_fallback',
	20, 0
);

/**
 * Enqueues scripts and styles for the HTML5 fallback.
 */
function wpmono_html5_fallback() {
	if ( ! wpmono_support_html5_fallback() ) {
		return;
	}

	if ( wpmono_script_is() ) {
		wp_enqueue_script( 'qrmono-form-html5-fallback' );
	}

	if ( wpmono_style_is() ) {
		wp_enqueue_style( 'jquery-ui-smoothness' );
	}
}
