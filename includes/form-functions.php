<?php
/**
 * QrMono form helper functions
 */

/**
 * Wrapper function of WPQRMONO_Form::get_instance().
 *
 * @param int|WP_Post $post Post ID or post object.
 * @return WPQRMONO_Form QrMono form object.
 */
function wpqrmono_form( $post ) {
	return WPQRMONO_Form::get_instance( $post );
}

/**
 * Searches for a form by title.
 *
 * @param string $title Title of form.
 * @return WPQRMONO_Form|null QrMono form object if found, null otherwise.
 */
function wpqrmono_get_form_by_title( $title ) {
	$page = get_page_by_title( $title, OBJECT, WPQRMONO_Form::post_type );

	if ( $page ) {
		return wpqrmono_form( $page->ID );
	}

	return null;
}

/**
 * Wrapper function of WPQRMONO_Form::get_current().
 *
 * @return WPQRMONO_Form QrMono form object.
 */
function wpqrmono_get_current_form() {
	if ( $current = WPQRMONO_Form::get_current() ) {
		return $current;
	}
}

/**
 * Returns true if it is in the state that a non-Ajax submission is accepted.
 */
function wpqrmono_is_posted() {
	if ( ! $form = wpqrmono_get_current_form() ) {
		return false;
	}

	return $form->is_posted();
}

/**
 * Retrieves the user input value through a non-Ajax submission.
 *
 * @param string $name Name of form control.
 * @param string $default_value Optional default value.
 * @return string The user input value through the form-control.
 */
function wpqrmono_get_hangover( $name, $default_value = null ) {
	if ( ! wpqrmono_is_posted() ) {
		return $default_value;
	}

	$submission = WPCF7_Submission::get_instance();

	if ( ! $submission
	or $submission->is( 'mail_sent' ) ) {
		return $default_value;
	}

	return isset( $_POST[$name] ) ? wp_unslash( $_POST[$name] ) : $default_value;
}

/**
 * Retrieves an HTML snippet of validation error on the given form control.
 *
 * @param string $name Name of form control.
 * @return string Validation error message in a form of HTML snippet.
 */
function wpqrmono_get_validation_error( $name ) {
	if ( ! $form = wpqrmono_get_current_form() ) {
		return '';
	}

	return $form->validation_error( $name );
}

/**
 * Returns a reference key to a validation error message.
 *
 * @param string $name Name of form control.
 * @param string $unit_tag Optional. Unit tag of the form.
 * @return string Reference key code.
 */
function wpqrmono_get_validation_error_reference( $name, $unit_tag = '' ) {
	if ( '' === $unit_tag ) {
		$form = wpqrmono_get_current_form();

		if ( $form and $form->validation_error( $name ) ) {
			$unit_tag = $form->unit_tag();
		} else {
			return null;
		}
	}

	return preg_replace( '/[^0-9a-z_-]+/i', '',
		sprintf(
			'%1$s-ve-%2$s',
			$unit_tag,
			$name
		)
	);
}

/**
 * Retrieves a message for the given status.
 */
function wpqrmono_get_message( $status ) {
	if ( ! $form = wpqrmono_get_current_form() ) {
		return '';
	}

	return $form->message( $status );
}

/**
 * Returns a class names list for a form-tag of the specified type.
 *
 * @param string $type Form-tag type.
 * @param string $default_classes Optional default classes.
 * @return string Whitespace-separated list of class names.
 */
function wpqrmono_form_controls_class( $type, $default_classes = '' ) {
	$type = trim( $type );
	$default_classes = array_filter( explode( ' ', $default_classes ) );

	$classes = array_merge( array( 'wpqrmono-form-control' ), $default_classes );

	$typebase = rtrim( $type, '*' );
	$required = ( '*' == substr( $type, -1 ) );

	$classes[] = 'wpqrmono-' . $typebase;

	if ( $required ) {
		$classes[] = 'wpqrmono-validates-as-required';
	}

	$classes = array_unique( $classes );

	return implode( ' ', $classes );
}

/**
 * Callback function for the qrmono-form shortcode.
 */
function wpqrmono_form_tag_func( $atts, $content = null, $code = '' ) {
	if ( is_feed() ) {
		return '[qrmono-form]';
	}

	if ( 'qrmono-form' == $code ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
				'title' => '',
				'html_id' => '',
				'html_name' => '',
				'html_title' => '',
				'html_class' => '',
				'output' => 'form',
			),
			$atts, 'wpqrmono'
		);

		$id = (int) $atts['id'];
		$title = trim( $atts['title'] );

		if ( ! $form = wpqrmono_form( $id ) ) {
			$form = wpqrmono_get_form_by_title( $title );
		}

	} else {
		if ( is_string( $atts ) ) {
			$atts = explode( ' ', $atts, 2 );
		}

		$id = (int) array_shift( $atts );
	}

	if ( ! $form ) {
		return sprintf(
			'[qrmono-form 404 "%s"]',
			esc_html( __( 'Not Found', 'qrmono-form' ) )
		);
	}

	$callback = function ( $form, $atts ) {
		return $form->form_html( $atts );
	};

	return wpqrmono_switch_locale(
		$form->locale(),
		$callback,
		$form, $atts
	);
}

/**
 * Saves the form data.
 */
function wpqrmono_save_form( $args = '', $context = 'save' ) {
	$args = wp_parse_args( $args, array(
		'id' => -1,
		'title' => null,
		'locale' => null,
		'form' => null,
		'mail' => null,
		'mail_2' => null,
		'messages' => null,
		'additional_settings' => null,
	) );

	$args = wp_unslash( $args );

	$args['id'] = (int) $args['id'];

	if ( -1 == $args['id'] ) {
		$form = WPQRMONO_Form::get_template();
	} else {
		$form = wpqrmono_form( $args['id'] );
	}

	if ( empty( $form ) ) {
		return false;
	}

	if ( null !== $args['title'] ) {
		$form->set_title( $args['title'] );
	}

	if ( null !== $args['locale'] ) {
		$form->set_locale( $args['locale'] );
	}

	$properties = array();

	if ( null !== $args['form'] ) {
		$properties['form'] = wpqrmono_sanitize_form( $args['form'] );
	}

	if ( null !== $args['mail'] ) {
		$properties['mail'] = wpqrmono_sanitize_mail( $args['mail'] );
		$properties['mail']['active'] = true;
	}

	if ( null !== $args['mail_2'] ) {
		$properties['mail_2'] = wpqrmono_sanitize_mail( $args['mail_2'] );
	}

	if ( null !== $args['messages'] ) {
		$properties['messages'] = wpqrmono_sanitize_messages( $args['messages'] );
	}

	if ( null !== $args['additional_settings'] ) {
		$properties['additional_settings'] = wpqrmono_sanitize_additional_settings(
			$args['additional_settings']
		);
	}

	$form->set_properties( $properties );

	do_action( 'wpqrmono_save_form', $form, $args, $context );

	if ( 'save' == $context ) {
		$form->save();
	}

	return $form;
}

/**
 * Sanitizes the form property data.
 */
function wpqrmono_sanitize_form( $input, $default_template = '' ) {
	if ( null === $input ) {
		return $default_template;
	}

	$output = trim( $input );

	if ( ! current_user_can( 'unfiltered_html' ) ) {
		$output = wpqrmono_kses( $output, 'form' );
	}

	return $output;
}

/**
 * Sanitizes the mail property data.
 */
function wpqrmono_sanitize_mail( $input, $defaults = array() ) {
	$input = wp_parse_args( $input, array(
		'active' => false,
		'subject' => '',
		'sender' => '',
		'recipient' => '',
		'body' => '',
		'additional_headers' => '',
		'attachments' => '',
		'use_html' => false,
		'exclude_blank' => false,
	) );

	$input = wp_parse_args( $input, $defaults );

	$output = array();
	$output['active'] = (bool) $input['active'];
	$output['subject'] = trim( $input['subject'] );
	$output['sender'] = trim( $input['sender'] );
	$output['recipient'] = trim( $input['recipient'] );
	$output['body'] = trim( $input['body'] );

	if ( ! current_user_can( 'unfiltered_html' ) ) {
		$output['body'] = wpqrmono_kses( $output['body'], 'mail' );
	}

	$output['additional_headers'] = '';

	$headers = str_replace( "\r\n", "\n", $input['additional_headers'] );
	$headers = explode( "\n", $headers );

	foreach ( $headers as $header ) {
		$header = trim( $header );

		if ( '' !== $header ) {
			$output['additional_headers'] .= $header . "\n";
		}
	}

	$output['additional_headers'] = trim( $output['additional_headers'] );
	$output['attachments'] = trim( $input['attachments'] );
	$output['use_html'] = (bool) $input['use_html'];
	$output['exclude_blank'] = (bool) $input['exclude_blank'];

	return $output;
}

/**
 * Sanitizes the messages property data.
 */
function wpqrmono_sanitize_messages( $input, $defaults = array() ) {
	$output = array();

	foreach ( wpqrmono_messages() as $key => $val ) {
		if ( isset( $input[$key] ) ) {
			$output[$key] = trim( $input[$key] );
		} elseif ( isset( $defaults[$key] ) ) {
			$output[$key] = $defaults[$key];
		}
	}

	return $output;
}

/**
 * Sanitizes the additional settings property data.
 */
function wpqrmono_sanitize_additional_settings( $input, $default_template = '' ) {
	if ( null === $input ) {
		return $default_template;
	}

	$output = trim( $input );
	return $output;
}