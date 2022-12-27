<?php

add_filter( 'map_meta_cap', 'wpqrmono_map_meta_cap', 10, 4 );

function wpqrmono_map_meta_cap( $caps, $cap, $user_id, $args ) {
	$meta_caps = array(
		'wpqrmono_edit_form' => WPQRMONO_ADMIN_READ_WRITE_CAPABILITY,
		'wpqrmono_edit_forms' => WPQRMONO_ADMIN_READ_WRITE_CAPABILITY,
		'wpqrmono_read_form' => WPQRMONO_ADMIN_READ_CAPABILITY,
		'wpqrmono_read_forms' => WPQRMONO_ADMIN_READ_CAPABILITY,
		'wpqrmono_delete_form' => WPQRMONO_ADMIN_READ_WRITE_CAPABILITY,
		'wpqrmono_delete_forms' => WPQRMONO_ADMIN_READ_WRITE_CAPABILITY,
		'wpqrmono_submit' => 'read',
	);

	$meta_caps = apply_filters( 'wpqrmono_map_meta_cap', $meta_caps );

	$caps = array_diff( $caps, array_keys( $meta_caps ) );

	if ( isset( $meta_caps[$cap] ) ) {
		$caps[] = $meta_caps[$cap];
	}

	return $caps;
}
