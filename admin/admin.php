<?php

require_once WPQRMONO_PLUGIN_DIR . '/admin/includes/admin-functions.php';
require_once WPQRMONO_PLUGIN_DIR . '/admin/includes/help-tabs.php';
// require_once WPQRMONO_PLUGIN_DIR . '/admin/includes/tag-generator.php';

add_action(
	'admin_init',
	function () {
		do_action( 'wpqrmono_admin_init' );
	},
	10, 0
);

add_action(
	'admin_menu',
	'wpqrmono_admin_menu',
	9, 0
);

function wpqrmono_admin_menu() {
	do_action( 'wpqrmono_admin_menu' );

	add_menu_page(
		__( 'Form', 'qrmono-form' ),
		__( 'QrMono Form', 'qrmono-form' )
			. wpqrmono_admin_menu_change_notice(),
		'wpqrmono_read_forms',
		'wpqrmono',
		'wpqrmono_admin_management_page',
		'dashicons-email',
		30
	);

	$edit = add_submenu_page( 'wpqrmono',
		__( 'Forms', 'qrmono-form' ),
		__( 'Forms', 'qrmono-form' )
			. wpqrmono_admin_menu_change_notice( 'wpqrmono' ),
		'wpqrmono_read_forms',
		'wpqrmono',
		'wpqrmono_admin_management_page'
	);

	add_action( 'load-' . $edit, 'wpqrmono_load_form_admin', 10, 0 );

	$addnew = add_submenu_page( 'wpqrmono',
		__( 'Add New Form', 'qrmono-form' ),
		__( 'Add New', 'qrmono-form' )
			. wpqrmono_admin_menu_change_notice( 'wpqrmono-new' ),
		'wpqrmono_edit_forms',
		'wpqrmono-new',
		'wpqrmono_admin_add_new_page'
	);

	add_action( 'load-' . $addnew, 'wpqrmono_load_form_admin', 10, 0 );
}

function wpqrmono_admin_menu_change_notice( $menu_slug = '' ) {
	$counts = apply_filters( 'wpqrmono_admin_menu_change_notice',
		array(
			'wpqrmono' => 0,
			'wpqrmono-new' => 0,
		)
	);

	if ( empty( $menu_slug ) ) {
		$count = absint( array_sum( $counts ) );
	} elseif ( isset( $counts[$menu_slug] ) ) {
		$count = absint( $counts[$menu_slug] );
	} else {
		$count = 0;
	}

	if ( $count ) {
		return sprintf(
			' <span class="update-plugins %1$d"><span class="plugin-count">%2$s</span></span>',
			$count,
			esc_html( number_format_i18n( $count ) )
		);
	}

	return '';
}

add_action(
	'admin_enqueue_scripts',
	'wpqrmono_admin_enqueue_scripts',
	10, 1
);

function wpqrmono_admin_enqueue_scripts( $hook_suffix ) {
	if ( false === strpos( $hook_suffix, 'wpqrmono' ) ) {
		return;
	}

	wp_enqueue_style( 'qrmono-form-admin',
		wpqrmono_plugin_url( 'admin/css/styles.css' ),
		array(), WPQRMONO_VERSION, 'all'
	);

	if ( wpqrmono_is_rtl() ) {
		wp_enqueue_style( 'qrmono-form-admin-rtl',
			wpqrmono_plugin_url( 'admin/css/styles-rtl.css' ),
			array(), WPQRMONO_VERSION, 'all'
		);
	}

	wp_enqueue_script( 'wpqrmono-admin',
		wpqrmono_plugin_url( 'admin/js/scripts.js' ),
		array( 'jquery', 'jquery-ui-tabs' ),
		WPQRMONO_VERSION, true
	);

	$args = array(
		'apiSettings' => array(
			'root' => sanitize_url( rest_url( 'qrmono-form/v1' ) ),
			'namespace' => 'qrmono-form/v1',
			'nonce' => ( wp_installing() && ! is_multisite() )
				? '' : wp_create_nonce( 'wp_rest' ),
		),
		'pluginUrl' => wpqrmono_plugin_url(),
		'saveAlert' => __(
			"The changes you made will be lost if you navigate away from this page.",
			'qrmono-form' ),
		'activeTab' => isset( $_GET['active-tab'] )
			? (int) $_GET['active-tab'] : 0,
		'configValidator' => array(
			'errors' => array(),
			'howToCorrect' => __( "How to resolve?", 'qrmono-form' ),
			'oneError' => __( '1 configuration error detected', 'qrmono-form' ),
			'manyErrors' => __( '%d configuration errors detected', 'qrmono-form' ),
			'oneErrorInTab' => __( '1 configuration error detected in this tab panel', 'qrmono-form' ),
			'manyErrorsInTab' => __( '%d configuration errors detected in this tab panel', 'qrmono-form' ),
			/* translators: screen reader text */
			'iconAlt' => __( '(configuration error)', 'qrmono-form' ),
		),
	);

	wp_localize_script( 'wpqrmono-admin', 'wpqrmono', $args );

	add_thickbox();
}

add_filter(
	'set_screen_option_wpqrmono_forms_per_page',
	function ( $result, $option, $value ) {
		$wpqrmono_screens = array(
			'wpqrmono_forms_per_page',
		);

		if ( in_array( $option, $wpqrmono_screens ) ) {
			$result = $value;
		}

		return $result;
	},
	10, 3
);


function wpqrmono_load_form_admin() {
	global $plugin_page;

	$action = wpqrmono_current_action();

	do_action( 'wpqrmono_admin_load',
		isset( $_GET['page'] ) ? trim( $_GET['page'] ) : '',
		$action
	);

	if ( 'save' == $action ) {
		$id = isset( $_POST['post_ID'] ) ? $_POST['post_ID'] : '-1';
		check_admin_referer( 'wpqrmono-save-form_' . $id );

		if ( ! current_user_can( 'wpqrmono_edit_form', $id ) ) {
			wp_die(
				__( "You are not allowed to edit this item.", 'qrmono-form' )
			);
		}

		$args = $_REQUEST;
		$args['id'] = $id;

		$args['title'] = isset( $_POST['post_title'] )
			? $_POST['post_title'] : null;

		$args['locale'] = isset( $_POST['wpqrmono-locale'] )
			? $_POST['wpqrmono-locale'] : null;

		$args['form'] = isset( $_POST['wpqrmono-form'] )
			? $_POST['wpqrmono-form'] : '';

		$args['mail'] = isset( $_POST['wpqrmono-mail'] )
			? $_POST['wpqrmono-mail'] : array();

		$args['messages'] = isset( $_POST['wpqrmono-messages'] )
			? $_POST['wpqrmono-messages'] : array();

		$args['additional_settings'] = isset( $_POST['wpqrmono-additional-settings'] )
			? $_POST['wpqrmono-additional-settings'] : '';

		$form = wpqrmono_save_form( $args );

		$query = array(
			'post' => $form ? $form->id() : 0,
			'active-tab' => isset( $_POST['active-tab'] )
				? (int) $_POST['active-tab'] : 0,
		);

		if ( ! $form ) {
			$query['message'] = 'failed';
		} elseif ( -1 == $id ) {
			$query['message'] = 'created';
		} else {
			$query['message'] = 'saved';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'wpqrmono', false ) );
		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'copy' == $action ) {
		$id = empty( $_POST['post_ID'] )
			? absint( $_REQUEST['post'] )
			: absint( $_POST['post_ID'] );

		check_admin_referer( 'wpqrmono-copy-form_' . $id );

		if ( ! current_user_can( 'wpqrmono_edit_form', $id ) ) {
			wp_die(
				__( "You are not allowed to edit this item.", 'qrmono-form' )
			);
		}

		$query = array();

		if ( $form = wpqrmono_form( $id ) ) {
			$new_form = $form->copy();
			$new_form->save();

			$query['post'] = $new_form->id();
			$query['message'] = 'created';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'wpqrmono', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete' == $action ) {
		if ( ! empty( $_POST['post_ID'] ) ) {
			check_admin_referer( 'wpqrmono-delete-form_' . $_POST['post_ID'] );
		} elseif ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer( 'wpqrmono-delete-form_' . $_REQUEST['post'] );
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$posts = empty( $_POST['post_ID'] )
			? (array) $_REQUEST['post']
			: (array) $_POST['post_ID'];

		$deleted = 0;

		foreach ( $posts as $post ) {
			$post = WPQRMONO_Form::get_instance( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can( 'wpqrmono_delete_form', $post->id() ) ) {
				wp_die(
					__( "You are not allowed to delete this item.", 'qrmono-form' )
				);
			}

			if ( ! $post->delete() ) {
				wp_die( __( "Error in deleting.", 'qrmono-form' ) );
			}

			$deleted += 1;
		}

		$query = array();

		if ( ! empty( $deleted ) ) {
			$query['message'] = 'deleted';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'wpqrmono', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	$post = null;

	if ( 'wpqrmono-new' == $plugin_page ) {
		$post = WPQRMONO_Form::get_template( array(
			'locale' => isset( $_GET['locale'] ) ? $_GET['locale'] : null,
		) );
	} elseif ( ! empty( $_GET['post'] ) ) {
		$post = WPQRMONO_Form::get_instance( $_GET['post'] );
	}

	$current_screen = get_current_screen();

	$help_tabs = new WPQRMONO_Help_Tabs( $current_screen );

	if ( $post
	and current_user_can( 'wpqrmono_edit_form', $post->id() ) ) {
		$help_tabs->set_help_tabs( 'edit' );
	} else {
		$help_tabs->set_help_tabs( 'list' );

		if ( ! class_exists( 'WPQRMONO_Form_List_Table' ) ) {
			require_once WPQRMONO_PLUGIN_DIR . '/admin/includes/class-forms-list-table.php';
		}

		add_filter(
			'manage_' . $current_screen->id . '_columns',
			array( 'WPQRMONO_Form_List_Table', 'define_columns' ),
			10, 0
		);

		add_screen_option( 'per_page', array(
			'default' => 20,
			'option' => 'wpqrmono_forms_per_page',
		) );
	}
}

function wpqrmono_admin_management_page() {
	if ( $post = wpqrmono_get_current_form() ) {
		$post_id = $post->initial() ? -1 : $post->id();

		require_once WPQRMONO_PLUGIN_DIR . '/admin/includes/editor.php';
		require_once WPQRMONO_PLUGIN_DIR . '/admin/edit-form.php';
		return;
	}

	if ( 'validate' == wpqrmono_current_action()
	and current_user_can( 'wpqrmono_edit_forms' ) ) {
		wpqrmono_admin_bulk_validate_page();
		return;
	}

	$list_table = new WPQRMONO_Form_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap" id="wpqrmono-form-list-table">

<h1 class="wp-heading-inline"><?php
	echo esc_html( __( 'Forms', 'qrmono-form' ) );
?></h1>

<?php
	if ( current_user_can( 'wpqrmono_edit_forms' ) ) {
		echo wpqrmono_link(
			menu_page_url( 'wpqrmono-new', false ),
			__( 'Add New', 'qrmono-form' ),
			array( 'class' => 'page-title-action' )
		);
	}

	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf(
			'<span class="subtitle">'
			/* translators: %s: search keywords */
			. __( 'Search results for &#8220;%s&#8221;', 'qrmono-form' )
			. '</span>',
			esc_html( $_REQUEST['s'] )
		);
	}
?>

<hr class="wp-header-end">

<?php
	do_action( 'wpqrmono_admin_warnings',
		'wpqrmono', wpqrmono_current_action(), null
	);

	do_action( 'wpqrmono_admin_notices',
		'wpqrmono', wpqrmono_current_action(), null
	);
?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search Forms', 'qrmono-form' ), 'wpqrmono-contact' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function wpqrmono_admin_add_new_page() {
	$post = wpqrmono_get_current_form();

	if ( ! $post ) {
		$post = WPQRMONO_Form::get_template();
	}

	$post_id = -1;

	require_once WPQRMONO_PLUGIN_DIR . '/admin/includes/editor.php';
	require_once WPQRMONO_PLUGIN_DIR . '/admin/edit-form.php';
}

add_action( 'wpqrmono_admin_notices', 'wpqrmono_admin_updated_message', 10, 3 );
function wpqrmono_admin_updated_message( $page, $action, $object ) {
	if ( ! in_array( $page, array( 'wpqrmono', 'wpqrmono-new' ) ) ) {
		return;
	}

	if ( empty( $_REQUEST['message'] ) ) {
		return;
	}

	if ( 'created' == $_REQUEST['message'] ) {
		$updated_message = __( "Form created.", 'qrmono-form' );
	} elseif ( 'saved' == $_REQUEST['message'] ) {
		$updated_message = __( "Form saved.", 'qrmono-form' );
	} elseif ( 'deleted' == $_REQUEST['message'] ) {
		$updated_message = __( "Form deleted.", 'qrmono-form' );
	}

	if ( ! empty( $updated_message ) ) {
		echo sprintf(
			'<div id="message" class="notice notice-success"><p>%s</p></div>',
			esc_html( $updated_message )
		);

		return;
	}

	if ( 'failed' == $_REQUEST['message'] ) {
		$updated_message =
			__( "There was an error saving the form.", 'qrmono-form' );

		echo sprintf(
			'<div id="message" class="notice notice-error"><p>%s</p></div>',
			esc_html( $updated_message )
		);

		return;
	}

	if ( 'validated' == $_REQUEST['message'] ) {
		$bulk_validate = WPQRMONO::get_option( 'bulk_validate', array() );
		$count_invalid = isset( $bulk_validate['count_invalid'] )
			? absint( $bulk_validate['count_invalid'] ) : 0;

		if ( $count_invalid ) {
			$updated_message = sprintf(
				_n(
					/* translators: %s: number of forms */
					"Configuration validation completed. %s invalid form was found.",
					"Configuration validation completed. %s invalid forms were found.",
					$count_invalid, 'qrmono-form'
				),
				number_format_i18n( $count_invalid )
			);

			echo sprintf(
				'<div id="message" class="notice notice-warning"><p>%s</p></div>',
				esc_html( $updated_message )
			);
		} else {
			$updated_message = __( "Configuration validation completed. No invalid form was found.", 'qrmono-form' );

			echo sprintf(
				'<div id="message" class="notice notice-success"><p>%s</p></div>',
				esc_html( $updated_message )
			);
		}

		return;
	}
}

add_filter( 'plugin_action_links', 'wpqrmono_plugin_action_links', 10, 2 );
function wpqrmono_plugin_action_links( $links, $file ) {
	if ( $file != WPQRMONO_PLUGIN_BASENAME ) {
		return $links;
	}

	if ( ! current_user_can( 'wpqrmono_read_forms' ) ) {
		return $links;
	}

	$settings_link = wpqrmono_link(
		menu_page_url( 'wpqrmono', false ),
		__( 'Settings', 'qrmono-form' )
	);

	array_unshift( $links, $settings_link );

	return $links;
}

add_action( 'wpqrmono_admin_warnings', 'wpqrmono_not_allowed_to_edit', 10, 3 );
function wpqrmono_not_allowed_to_edit( $page, $action, $object ) {
	if ( $object instanceof WPQRMONO_Form ) {
		$form = $object;
	} else {
		return;
	}

	if ( current_user_can( 'wpqrmono_edit_form', $form->id() ) ) {
		return;
	}

	$message = __( "You are not allowed to edit this form.", 'qrmono-form' );

	echo sprintf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html( $message )
	);
}