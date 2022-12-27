<?php

class WPQRMONO_Form {
	const post_type = 'wpqrmono_form';

	private static $found_items = 0;
	private static $current = null;

	private $id;
	private $name;
	private $title;
	private $locale;
	private $properties = array();
	private $unit_tag;
	private $responses_count = 0;
	private $scanned_form_tags;
	private $shortcode_atts = array();


	/**
	 * Returns count of forms found by the previous retrieval.
	 *
	 * @return int Count of forms.
	 */
	public static function count() {
		return self::$found_items;
	}


	/**
	 * Returns the form that is currently processed.
	 *
	 * @return WPQRMONO_Form Current form object.
	 */
	public static function get_current() {
		return self::$current;
	}


	/**
	 * Registers the post type for forms.
	 */
	public static function register_post_type() {
		register_post_type( self::post_type, array(
			'labels' => array(
				'name' => __( 'Forms', 'qrmono-form' ),
				'singular_name' => __( 'Form', 'qrmono-form' ),
			),
			'rewrite' => false,
			'query_var' => false,
			'public' => false,
			'capability_type' => 'page',
			'capabilities' => array(
				'edit_post' => 'wpqrmono_edit_form',
				'read_post' => 'wpqrmono_read_form',
				'delete_post' => 'wpqrmono_delete_form',
				'edit_posts' => 'wpqrmono_edit_forms',
				'edit_others_posts' => 'wpqrmono_edit_forms',
				'publish_posts' => 'wpqrmono_edit_forms',
				'read_private_posts' => 'wpqrmono_edit_forms',
			),
		) );
	}


	/**
	 * Retrieves form data that match given conditions.
	 *
	 * @param string|array $args Optional. Arguments to be passed to WP_Query.
	 * @return array Array of WPQRMONO_Form objects.
	 */
	public static function find( $args = '' ) {
		$defaults = array(
			'post_status' => 'any',
			'posts_per_page' => -1,
			'offset' => 0,
			'orderby' => 'ID',
			'order' => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		$args['post_type'] = self::post_type;

		$q = new WP_Query();
		$posts = $q->query( $args );

		self::$found_items = $q->found_posts;

		$objs = array();

		foreach ( (array) $posts as $post ) {
			$objs[] = new self( $post );
		}

		return $objs;
	}


	/**
	 * Returns a form data filled by default template contents.
	 *
	 * @param string|array $args Optional. Form options.
	 * @return WPQRMONO_Form A new form object.
	 */
	public static function get_template( $args = '' ) {
		$args = wp_parse_args( $args, array(
			'locale' => determine_locale(),
			'title' => __( 'Untitled', 'qrmono-form' ),
		) );

		$callback = function ( $args ) {
			$form = new self;
			$form->title = $args['title'];
			$form->locale = $args['locale'];

			$properties = $form->get_properties();

			foreach ( $properties as $key => $value ) {
				$default_template = WPQRMONO_FormTemplate::get_default( $key );

				if ( isset( $default_template ) ) {
					$properties[$key] = $default_template;
				}
			}

			$form->properties = $properties;

			return $form;
		};

		$form = wpqrmono_switch_locale(
			$args['locale'],
			$callback,
			$args
		);

		self::$current = apply_filters( 'wpqrmono_form_default_pack',
			$form, $args
		);

		return self::$current;
	}


	/**
	 * Returns an instance of WPQRMONO_Form.
	 *
	 * @return WPQRMONO_Form A new form object.
	 */
	public static function get_instance( $post ) {
		$post = get_post( $post );

		if ( ! $post
		or self::post_type != get_post_type( $post ) ) {
			return false;
		}

		return self::$current = new self( $post );
	}


	/**
	 * Generates a "unit-tag" for the given form ID.
	 *
	 * @return string Unit-tag.
	 */
	private static function generate_unit_tag( $id = 0 ) {
		static $global_count = 0;

		$global_count += 1;

		if ( in_the_loop() ) {
			$unit_tag = sprintf( 'wpqrmono-f%1$d-p%2$d-o%3$d',
				absint( $id ),
				get_the_ID(),
				$global_count
			);
		} else {
			$unit_tag = sprintf( 'wpqrmono-f%1$d-o%2$d',
				absint( $id ),
				$global_count
			);
		}

		return $unit_tag;
	}


	/**
	 * Constructor.
	 */
	private function __construct( $post = null ) {
		$post = get_post( $post );

		if ( $post
		and self::post_type == get_post_type( $post ) ) {
			$this->id = $post->ID;
			$this->name = $post->post_name;
			$this->title = $post->post_title;
			$this->locale = get_post_meta( $post->ID, '_locale', true );

			$this->construct_properties( $post );
			$this->upgrade();
		} else {
			$this->construct_properties();
		}

		do_action( 'wpqrmono_form', $this );
	}


	/**
	 * Magic method for property overloading.
	 */
	public function __get( $name ) {
		$message = __( '<code>%1$s</code> property of a <code>WPQRMONO_Form</code> object is <strong>no longer accessible</strong>. Use <code>%2$s</code> method instead.', 'qrmono-form' );

		if ( 'id' == $name ) {
			if ( WP_DEBUG ) {
				trigger_error(
					sprintf( $message, 'id', 'id()' ),
					E_USER_DEPRECATED
				);
			}

			return $this->id;
		} elseif ( 'title' == $name ) {
			if ( WP_DEBUG ) {
				trigger_error(
					sprintf( $message, 'title', 'title()' ),
					E_USER_DEPRECATED
				);
			}

			return $this->title;
		} elseif ( $prop = $this->prop( $name ) ) {
			if ( WP_DEBUG ) {
				trigger_error(
					sprintf( $message, $name, 'prop(\'' . $name . '\')' ),
					E_USER_DEPRECATED
				);
			}

			return $prop;
		}
	}


	/**
	 * Returns true if this form is not yet saved to the database.
	 */
	public function initial() {
		return empty( $this->id );
	}


	/**
	 * Constructs form properties. This is called only once
	 * from the constructor.
	 */
	private function construct_properties( $post = null ) {
		$builtin_properties = array(
			'form' => '',
			'mail' => array(),
			'mail_2' => array(),
			'messages' => array(),
			'additional_settings' => '',
		);

		$properties = apply_filters(
			'wpqrmono_pre_construct_form_properties',
			$builtin_properties, $this
		);

		// Filtering out properties with invalid name
		$properties = array_filter(
			$properties,
			function ( $key ) {
				$sanitized_key = sanitize_key( $key );
				return $key === $sanitized_key;
			},
			ARRAY_FILTER_USE_KEY
		);

		foreach ( $properties as $name => $val ) {
			$prop = $this->retrieve_property( $name );

			if ( isset( $prop ) ) {
				$properties[$name] = $prop;
			}
		}

		$this->properties = $properties;

		foreach ( $properties as $name => $val ) {
			$properties[$name] = apply_filters(
				"wpqrmono_form_property_{$name}",
				$val, $this
			);
		}

		$this->properties = $properties;

		$properties = (array) apply_filters(
			'wpqrmono_form_properties',
			$properties, $this
		);

		$this->properties = $properties;
	}


	/**
	 * Retrieves form property of the specified name from the database.
	 *
	 * @param string $name Property name.
	 * @return array|string|null Property value. Null if property does not exist.
	 */
	private function retrieve_property( $name ) {
		$property = null;

		if ( ! $this->initial() ) {
			$post_id = $this->id;

			if ( metadata_exists( 'post', $post_id, '_' . $name ) ) {
				$property = get_post_meta( $post_id, '_' . $name, true );
			} elseif ( metadata_exists( 'post', $post_id, $name ) ) {
				$property = get_post_meta( $post_id, $name, true );
			}
		}

		return $property;
	}


	/**
	 * Returns the value for the given property name.
	 *
	 * @param string $name Property name.
	 * @return array|string|null Property value. Null if property does not exist.
	 */
	public function prop( $name ) {
		$props = $this->get_properties();
		return isset( $props[$name] ) ? $props[$name] : null;
	}


	/**
	 * Returns all the properties.
	 *
	 * @return array This form's properties.
	 */
	public function get_properties() {
		return (array) $this->properties;
	}


	/**
	 * Updates properties.
	 *
	 * @param array $properties New properties.
	 */
	public function set_properties( $properties ) {
		$defaults = $this->get_properties();

		$properties = wp_parse_args( $properties, $defaults );
		$properties = array_intersect_key( $properties, $defaults );

		$this->properties = $properties;
	}


	/**
	 * Returns ID of this form.
	 *
	 * @return int The ID.
	 */
	public function id() {
		return $this->id;
	}


	/**
	 * Returns unit-tag for this form.
	 *
	 * @return string Unit-tag.
	 */
	public function unit_tag() {
		return $this->unit_tag;
	}


	/**
	 * Returns name (slug) of this form.
	 *
	 * @return string Name.
	 */
	public function name() {
		return $this->name;
	}


	/**
	 * Returns title of this form.
	 *
	 * @return string Title.
	 */
	public function title() {
		return $this->title;
	}


	/**
	 * Set a title for this form.
	 *
	 * @param string $title Title.
	 */
	public function set_title( $title ) {
		$title = strip_tags( $title );
		$title = trim( $title );

		if ( '' === $title ) {
			$title = __( 'Untitled', 'qrmono-form' );
		}

		$this->title = $title;
	}


	/**
	 * Returns the locale code of this form.
	 *
	 * @return string Locale code. Empty string if no valid locale is set.
	 */
	public function locale() {
		if ( wpqrmono_is_valid_locale( $this->locale ) ) {
			return $this->locale;
		} else {
			return '';
		}
	}


	/**
	 * Sets a locale for this form.
	 *
	 * @param string $locale Locale code.
	 */
	public function set_locale( $locale ) {
		$locale = trim( $locale );

		if ( wpqrmono_is_valid_locale( $locale ) ) {
			$this->locale = $locale;
		} else {
			$this->locale = 'en_US';
		}
	}


	/**
	 * Returns the specified shortcode attribute value.
	 *
	 * @param string $name Shortcode attribute name.
	 * @return string|null Attribute value. Null if the attribute does not exist.
	 */
	public function shortcode_attr( $name ) {
		if ( isset( $this->shortcode_atts[$name] ) ) {
			return (string) $this->shortcode_atts[$name];
		}
	}


	/**
	 * Returns true if this form is identical to the submitted one.
	 */
	public function is_posted() {
		if ( ! WPQRMONO_Submission::get_instance() ) {
			return false;
		}

		if ( empty( $_POST['_wpqrmono_unit_tag'] ) ) {
			return false;
		}

		return $this->unit_tag() === $_POST['_wpqrmono_unit_tag'];
	}


	/**
	 * Generates HTML that represents a form.
	 *
	 * @param string|array $args Optional. Form options.
	 * @return string HTML output.
	 */
	public function form_html( $args = '' ) {
		$args = wp_parse_args( $args, array(
			'html_id' => '',
			'html_name' => '',
			'html_title' => '',
			'html_class' => '',
			'output' => 'form',
		) );

		$this->shortcode_atts = $args;

		if ( 'raw_form' == $args['output'] ) {
			return sprintf(
				'<pre class="wpqrmono-raw-form"><code>%s</code></pre>',
				esc_html( $this->prop( 'form' ) )
			);
		}

		if ( $this->is_true( 'subscribers_only' )
		and ! current_user_can( 'wpqrmono_submit', $this->id() ) ) {
			$notice = __(
				"This form is available only for logged in users.",
				'qrmono-form'
			);

			$notice = sprintf(
				'<p class="wpqrmono-subscribers-only">%s</p>',
				esc_html( $notice )
			);

			return apply_filters( 'wpqrmono_subscribers_only_notice', $notice, $this );
		}

		$this->unit_tag = self::generate_unit_tag( $this->id );

		$lang_tag = str_replace( '_', '-', $this->locale );

		if ( preg_match( '/^([a-z]+-[a-z]+)-/i', $lang_tag, $matches ) ) {
			$lang_tag = $matches[1];
		}

		$html = sprintf( '<div %s>',
			wpqrmono_format_atts( array(
				'class' => 'wpqrmono no-js',
				'id' => $this->unit_tag(),
				( get_option( 'html_type' ) == 'text/html' ) ? 'lang' : 'xml:lang'
					=> $lang_tag,
				'dir' => wpqrmono_is_rtl( $this->locale ) ? 'rtl' : 'ltr',
			) )
		);

		$html .= "\n" . $this->screen_reader_response() . "\n";

		$url = wpqrmono_get_request_uri();

		if ( $frag = strstr( $url, '#' ) ) {
			$url = substr( $url, 0, -strlen( $frag ) );
		}

		$url .= '#' . $this->unit_tag();

		$url = apply_filters( 'wpqrmono_form_action_url', $url );

		$id_attr = apply_filters( 'wpqrmono_form_id_attr',
			preg_replace( '/[^A-Za-z0-9:._-]/', '', $args['html_id'] )
		);

		$name_attr = apply_filters( 'wpqrmono_form_name_attr',
			preg_replace( '/[^A-Za-z0-9:._-]/', '', $args['html_name'] )
		);

		$title_attr = apply_filters( 'wpqrmono_form_title_attr', $args['html_title'] );

		$class = 'wpqrmono-form';

		if ( $this->is_posted() ) {
			$submission = WPQRMONO_Submission::get_instance();

			$data_status_attr = $this->form_status_class_name(
				$submission->get_status()
			);

			$class .= sprintf( ' %s', $data_status_attr );
		} else {
			$data_status_attr = 'init';
			$class .= ' init';
		}

		if ( $args['html_class'] ) {
			$class .= ' ' . $args['html_class'];
		}

		if ( $this->in_demo_mode() ) {
			$class .= ' demo';
		}

		$class = explode( ' ', $class );
		$class = array_map( 'sanitize_html_class', $class );
		$class = array_filter( $class );
		$class = array_unique( $class );
		$class = implode( ' ', $class );
		$class = apply_filters( 'wpqrmono_form_class_attr', $class );

		$enctype = wpqrmono_enctype_value( apply_filters( 'wpqrmono_form_enctype', '' ) );
		$autocomplete = apply_filters( 'wpqrmono_form_autocomplete', '' );

		$atts = array(
			'action' => esc_url( $url ),
			'method' => 'post',
			'class' => ( '' !== $class ) ? $class : null,
			'id' => ( '' !== $id_attr ) ? $id_attr : null,
			'name' => ( '' !== $name_attr ) ? $name_attr : null,
			'aria-label' => ( '' !== $title_attr )
				? $title_attr : __( 'Form', 'qrmono-form' ),
			'enctype' => ( '' !== $enctype ) ? $enctype : null,
			'autocomplete' => ( '' !== $autocomplete ) ? $autocomplete : null,
			'novalidate' => true,
			'data-status' => $data_status_attr,
		);

		$atts = wpqrmono_format_atts( $atts );

		$html .= sprintf( '<form %s>', $atts ) . "\n";
		$html .= $this->form_hidden_fields();
		$html .= $this->form_elements();

		if ( ! $this->responses_count ) {
			$html .= $this->form_response_output();
		}

		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}


	/**
	 * Returns the class name that matches the given form status.
	 */
	private function form_status_class_name( $status ) {
		switch ( $status ) {
			case 'init':
				$class = 'init';
				break;
			case 'validation_failed':
				$class = 'invalid';
				break;
			case 'acceptance_missing':
				$class = 'unaccepted';
				break;
			case 'spam':
				$class = 'spam';
				break;
			case 'aborted':
				$class = 'aborted';
				break;
			case 'mail_sent':
				$class = 'sent';
				break;
			case 'mail_failed':
				$class = 'failed';
				break;
			default:
				$class = sprintf(
					'custom-%s',
					preg_replace( '/[^0-9a-z]+/i', '-', $status )
				);
		}

		return $class;
	}


	/**
	 * Returns a set of hidden fields.
	 */
	private function form_hidden_fields() {
		$hidden_fields = array(
			'_wpqrmono' => $this->id(),
			'_wpqrmono_version' => WPQRMONO_VERSION,
			'_wpqrmono_locale' => $this->locale(),
			'_wpqrmono_unit_tag' => $this->unit_tag(),
			'_wpqrmono_container_post' => 0,
			'_wpqrmono_posted_data_hash' => '',
		);

		if ( in_the_loop() ) {
			$hidden_fields['_wpqrmono_container_post'] = (int) get_the_ID();
		}

		if ( $this->nonce_is_active() and is_user_logged_in() ) {
			$hidden_fields['_wpnonce'] = wpqrmono_create_nonce();
		}

		$hidden_fields += (array) apply_filters(
			'wpqrmono_form_hidden_fields', array()
		);

		$content = '';

		foreach ( $hidden_fields as $name => $value ) {
			$content .= sprintf(
				'<input type="hidden" name="%1$s" value="%2$s" />',
				esc_attr( $name ),
				esc_attr( $value )
			) . "\n";
		}

		return '<div style="display: none;">' . "\n" . $content . '</div>' . "\n";
	}


	/**
	 * Returns the visible response output for a form submission.
	 */
	public function form_response_output() {
		$status = 'init';
		$class = 'wpqrmono-response-output';
		$content = '';

		if ( $this->is_posted() ) { // Post response output for non-AJAX
			$submission = WPQRMONO_Submission::get_instance();
			$status = $submission->get_status();
			$content = $submission->get_response();
		}

		$atts = array(
			'class' => trim( $class ),
			'aria-hidden' => 'true',
		);

		$output = sprintf( '<div %1$s>%2$s</div>',
			wpqrmono_format_atts( $atts ),
			esc_html( $content )
		);

		$output = apply_filters( 'wpqrmono_form_response_output',
			$output, $class, $content, $this, $status
		);

		$this->responses_count += 1;

		return $output;
	}


	/**
	 * Returns the response output that is only accessible from screen readers.
	 */
	public function screen_reader_response() {
		$primary_response = '';
		$validation_errors = array();

		if ( $this->is_posted() ) { // Post response output for non-AJAX
			$submission = WPQRMONO_Submission::get_instance();
			$primary_response = $submission->get_response();

			if ( $invalid_fields = $submission->get_invalid_fields() ) {
				foreach ( (array) $invalid_fields as $name => $field ) {
					$list_item = esc_html( $field['reason'] );

					if ( $field['idref'] ) {
						$list_item = sprintf(
							'<a href="#%1$s">%2$s</a>',
							esc_attr( $field['idref'] ),
							$list_item
						);
					}

					$validation_error_id = wpqrmono_get_validation_error_reference(
						$name,
						$this->unit_tag()
					);

					if ( $validation_error_id ) {
						$list_item = sprintf(
							'<li id="%1$s">%2$s</li>',
							esc_attr( $validation_error_id ),
							$list_item
						);

						$validation_errors[] = $list_item;
					}
				}
			}
		}

		$primary_response = sprintf(
			'<p role="status" aria-live="polite" aria-atomic="true">%s</p>',
			esc_html( $primary_response )
		);

		$validation_errors = sprintf(
			'<ul>%s</ul>',
			implode( "\n", $validation_errors )
		);

		$output = sprintf(
			'<div class="screen-reader-response">%1$s %2$s</div>',
			$primary_response,
			$validation_errors
		);

		return $output;
	}


	/**
	 * Returns a validation error for the specified input field.
	 *
	 * @param string $name Input field name.
	 */
	public function validation_error( $name ) {
		$error = '';

		if ( $this->is_posted() ) {
			$submission = WPQRMONO_Submission::get_instance();

			if ( $invalid_field = $submission->get_invalid_field( $name ) ) {
				$error = trim( $invalid_field['reason'] );
			}
		}

		if ( ! $error ) {
			return $error;
		}

		$atts = array(
			'class' => 'wpqrmono-not-valid-tip',
			'aria-hidden' => 'true',
		);

		$error = sprintf(
			'<span %1$s>%2$s</span>',
			wpqrmono_format_atts( $atts ),
			esc_html( $error )
		);

		return apply_filters( 'wpqrmono_validation_error', $error, $name, $this );
	}


	/**
	 * Replaces all form-tags in the form template with corresponding HTML.
	 *
	 * @return string Replaced form content.
	 */
	public function replace_all_form_tags() {
		// $manager = WPQRMONO_FormTagsManager::get_instance();
		$form = $this->prop( 'form' );

		if ( wpqrmono_autop_or_not() ) {
			// $form = $manager->replace_with_placeholders( $form );
			// $form = wpqrmono_autop( $form );
			// $form = $manager->restore_from_placeholders( $form );
		}

		// $form = $manager->replace_all( $form );
		// $this->scanned_form_tags = $manager->get_scanned_tags();

		return $form;
	}


	/**
	 * Replaces all form-tags in the form template with corresponding HTML.
	 *
	 * @deprecated 4.6 Use replace_all_form_tags()
	 *
	 * @return string Replaced form content.
	 */
	public function form_do_shortcode() {
		wpqrmono_deprecated_function( __METHOD__, '4.6',
			'WPQRMONO_Form::replace_all_form_tags'
		);

		return $this->replace_all_form_tags();
	}


	/**
	 * Scans form-tags from the form template.
	 *
	 * @param string|array|null $cond Optional. Filters. Default null.
	 * @return array Form-tags matching the given filter conditions.
	 */
	public function scan_form_tags( $cond = null ) {
		$manager = WPQRMONO_FormTagsManager::get_instance();

		if ( empty( $this->scanned_form_tags ) ) {
			$this->scanned_form_tags = $manager->scan( $this->prop( 'form' ) );
		}

		$tags = $this->scanned_form_tags;

		return $manager->filter( $tags, $cond );
	}


	/**
	 * Scans form-tags from the form template.
	 *
	 * @deprecated 4.6 Use scan_form_tags()
	 *
	 * @param string|array|null $cond Optional. Filters. Default null.
	 * @return array Form-tags matching the given filter conditions.
	 */
	public function form_scan_shortcode( $cond = null ) {
		wpqrmono_deprecated_function( __METHOD__, '4.6',
			'WPQRMONO_Form::scan_form_tags'
		);

		return $this->scan_form_tags( $cond );
	}


	/**
	 * Replaces all form-tags in the form template with corresponding HTML.
	 *
	 * @return string Replaced form content. wpqrmono_form_elements filters applied.
	 */
	public function form_elements() {
		return apply_filters( 'wpqrmono_form_elements',
			$this->replace_all_form_tags()
		);
	}


	/**
	 * Collects mail-tags available for this form.
	 *
	 * @param string|array $args Optional. Search options.
	 * @return array Mail-tag names.
	 */
	public function collect_mail_tags( $args = '' ) {
		$manager = WPQRMONO_FormTagsManager::get_instance();

		$args = wp_parse_args( $args, array(
			'include' => array(),
			'exclude' => $manager->collect_tag_types( 'not-for-mail' ),
		) );

		$tags = $this->scan_form_tags();
		$mailtags = array();

		foreach ( (array) $tags as $tag ) {
			$type = $tag->basetype;

			if ( empty( $type ) ) {
				continue;
			} elseif ( ! empty( $args['include'] ) ) {
				if ( ! in_array( $type, $args['include'] ) ) {
					continue;
				}
			} elseif ( ! empty( $args['exclude'] ) ) {
				if ( in_array( $type, $args['exclude'] ) ) {
					continue;
				}
			}

			$mailtags[] = $tag->name;
		}

		$mailtags = array_unique( $mailtags );
		$mailtags = array_filter( $mailtags );
		$mailtags = array_values( $mailtags );

		return apply_filters( 'wpqrmono_collect_mail_tags', $mailtags, $args, $this );
	}


	/**
	 * Prints a mail-tag suggestion list.
	 *
	 * @param string $template_name Optional. Mail template name. Default 'mail'.
	 */
	public function suggest_mail_tags( $template_name = 'mail' ) {
		$mail = wp_parse_args( $this->prop( $template_name ),
			array(
				'active' => false,
				'recipient' => '',
				'sender' => '',
				'subject' => '',
				'body' => '',
				'additional_headers' => '',
				'attachments' => '',
				'use_html' => false,
				'exclude_blank' => false,
			)
		);

		$mail = array_filter( $mail );

		foreach ( (array) $this->collect_mail_tags() as $mail_tag ) {
			$pattern = sprintf(
				'/\[(_[a-z]+_)?%s([ \t]+[^]]+)?\]/',
				preg_quote( $mail_tag, '/' )
			);

			$used = preg_grep( $pattern, $mail );

			echo sprintf(
				'<span class="%1$s">[%2$s]</span>',
				'mailtag code ' . ( $used ? 'used' : 'unused' ),
				esc_html( $mail_tag )
			);
		}
	}


	/**
	 * Submits this form.
	 *
	 * @param string|array $args Optional. Submission options. Default empty.
	 * @return array Result of submission.
	 */
	public function submit( $args = '' ) {
		$args = wp_parse_args( $args, array(
			'skip_mail' =>
				( $this->in_demo_mode()
				|| $this->is_true( 'skip_mail' )
				|| ! empty( $this->skip_mail ) ),
		) );

		if ( $this->is_true( 'subscribers_only' )
		and ! current_user_can( 'wpqrmono_submit', $this->id() ) ) {
			$result = array(
				'form_id' => $this->id(),
				'status' => 'error',
				'message' => __(
					"This form is available only for logged in users.",
					'qrmono-form'
				),
			);

			return $result;
		}

		$submission = WPQRMONO_Submission::get_instance( $this, array(
			'skip_mail' => $args['skip_mail'],
		) );

		$result = array(
			'form_id' => $this->id(),
		);

		$result += $submission->get_result();

		if ( $this->in_demo_mode() ) {
			$result['demo_mode'] = true;
		}

		do_action( 'wpqrmono_submit', $this, $result );

		return $result;
	}


	/**
	 * Returns message used for given status.
	 *
	 * @param string $status Status.
	 * @param bool $filter Optional. Whether filters are applied. Default true.
	 * @return string Message.
	 */
	public function message( $status, $filter = true ) {
		$messages = $this->prop( 'messages' );
		$message = isset( $messages[$status] ) ? $messages[$status] : '';

		if ( $filter ) {
			$message = $this->filter_message( $message, $status );
		}

		return $message;
	}


	/**
	 * Filters a message.
	 *
	 * @param string $message Message to filter.
	 * @param string $status Optional. Status. Default empty.
	 * @return string Filtered message.
	 */
	public function filter_message( $message, $status = '' ) {
		$message = wpqrmono_mail_replace_tags( $message );
		$message = apply_filters( 'wpqrmono_display_message', $message, $status );
		$message = wp_strip_all_tags( $message );

		return $message;
	}


	/**
	 * Returns the additional setting value searched by name.
	 *
	 * @param string $name Name of setting.
	 * @return string Additional setting value.
	 */
	public function pref( $name ) {
		$settings = $this->additional_setting( $name );

		if ( $settings ) {
			return $settings[0];
		}
	}


	/**
	 * Returns additional setting values searched by name.
	 *
	 * @param string $name Name of setting.
	 * @param int $max Maximum result item count.
	 * @return array Additional setting values.
	 */
	public function additional_setting( $name, $max = 1 ) {
		$settings = (array) explode( "\n", $this->prop( 'additional_settings' ) );

		$pattern = '/^([a-zA-Z0-9_]+)[\t ]*:(.*)$/';
		$count = 0;
		$values = array();

		foreach ( $settings as $setting ) {
			if ( preg_match( $pattern, $setting, $matches ) ) {
				if ( $matches[1] != $name ) {
					continue;
				}

				if ( ! $max or $count < (int) $max ) {
					$values[] = trim( $matches[2] );
					$count += 1;
				}
			}
		}

		return $values;
	}


	/**
	 * Returns true if the specified setting has a truthy string value.
	 *
	 * @param string $name Name of setting.
	 * @return bool True if the setting value is 'on', 'true', or '1'.
	 */
	public function is_true( $name ) {
		return in_array(
			$this->pref( $name ),
			array( 'on', 'true', '1' ),
			true
		);
	}


	/**
	 * Returns true if this form is in the demo mode.
	 */
	public function in_demo_mode() {
		return $this->is_true( 'demo_mode' );
	}


	/**
	 * Returns true if nonce is active for this form.
	 */
	public function nonce_is_active() {
		$is_active = WPQRMONO_VERIFY_NONCE;

		if ( $this->is_true( 'subscribers_only' ) ) {
			$is_active = true;
		}

		return (bool) apply_filters( 'wpqrmono_verify_nonce', $is_active, $this );
	}


	/**
	 * Returns true if the specified setting has a falsey string value.
	 *
	 * @param string $name Name of setting.
	 * @return bool True if the setting value is 'off', 'false', or '0'.
	 */
	public function is_false( $name ) {
		return in_array(
			$this->pref( $name ),
			array( 'off', 'false', '0' ),
			true
		);
	}


	/**
	 * Upgrades this form properties.
	 */
	private function upgrade() {
		$mail = $this->prop( 'mail' );

		if ( is_array( $mail )
		and ! isset( $mail['recipient'] ) ) {
			$mail['recipient'] = get_option( 'admin_email' );
		}

		$this->properties['mail'] = $mail;

		$messages = $this->prop( 'messages' );

		if ( is_array( $messages ) ) {
			foreach ( wpqrmono_messages() as $key => $arr ) {
				if ( ! isset( $messages[$key] ) ) {
					$messages[$key] = $arr['default'];
				}
			}
		}

		$this->properties['messages'] = $messages;
	}


	/**
	 * Stores this form properties to the database.
	 *
	 * @return int The post ID on success. The value 0 on failure.
	 */
	public function save() {
		$title = wp_slash( $this->title );
		$props = wp_slash( $this->get_properties() );

		$post_content = implode( "\n", wpqrmono_array_flatten( $props ) );

		if ( $this->initial() ) {
			$post_id = wp_insert_post( array(
				'post_type' => self::post_type,
				'post_status' => 'publish',
				'post_title' => $title,
				'post_content' => trim( $post_content ),
			) );
		} else {
			$post_id = wp_update_post( array(
				'ID' => (int) $this->id,
				'post_status' => 'publish',
				'post_title' => $title,
				'post_content' => trim( $post_content ),
			) );
		}

		if ( $post_id ) {
			foreach ( $props as $prop => $value ) {
				update_post_meta( $post_id, '_' . $prop,
					wpqrmono_normalize_newline_deep( $value )
				);
			}

			if ( wpqrmono_is_valid_locale( $this->locale ) ) {
				update_post_meta( $post_id, '_locale', $this->locale );
			}

			if ( $this->initial() ) {
				$this->id = $post_id;
				do_action( 'wpqrmono_after_create', $this );
			} else {
				do_action( 'wpqrmono_after_update', $this );
			}

			do_action( 'wpqrmono_after_save', $this );
		}

		return $post_id;
	}


	/**
	 * Makes a copy of this form.
	 *
	 * @return WPQRMONO_Form New form object.
	 */
	public function copy() {
		$new = new self;
		$new->title = $this->title . '_copy';
		$new->locale = $this->locale;
		$new->properties = $this->properties;

		return apply_filters( 'wpqrmono_copy', $new, $this );
	}


	/**
	 * Deletes this form.
	 */
	public function delete() {
		if ( $this->initial() ) {
			return;
		}

		if ( wp_delete_post( $this->id, true ) ) {
			$this->id = 0;
			return true;
		}

		return false;
	}


	/**
	 * Returns a WordPress shortcode for this form.
	 */
	public function shortcode( $args = '' ) {
		$title = str_replace( array( '"', '[', ']' ), '', $this->title );

		$shortcode = sprintf(
			'[qrmono-form id="%1$d" title="%2$s"]',
			$this->id,
			$title
		);

		return apply_filters( 'wpqrmono_form_shortcode',
			$shortcode, $args, $this
		);
	}
}
