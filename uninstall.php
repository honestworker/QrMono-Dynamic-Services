<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

function wpqrmono_delete_plugin() {
	global $wpdb;

	delete_option( 'wpqrmono' );

	$posts = get_posts(
		array(
			'numberposts' => -1,
			'post_type' => 'wpqrmono_form',
			'post_status' => 'any',
		)
	);

	foreach ( $posts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	$wpdb->query( sprintf(
		"DROP TABLE IF EXISTS %s",
		$wpdb->prefix . 'wpqrmono_form'
	) );
}

if ( ! defined( 'WPQRMONO_VERSION' ) ) {
	wpqrmono_delete_plugin();
}
