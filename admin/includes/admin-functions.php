<?php

function wpqrmono_current_action() {
	if ( isset( $_REQUEST['action'] ) and -1 != $_REQUEST['action'] ) {
		return $_REQUEST['action'];
	}

	if ( isset( $_REQUEST['action2'] ) and -1 != $_REQUEST['action2'] ) {
		return $_REQUEST['action2'];
	}

	return false;
}

function wpqrmono_admin_has_edit_cap() {
	return current_user_can( 'wpqrmono_edit_forms' );
}