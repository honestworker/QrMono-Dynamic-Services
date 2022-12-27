<?php

class WPQRMONO_Help_Tabs {

	private $screen;

	public function __construct( WP_Screen $screen ) {
		$this->screen = $screen;
	}

	public function set_help_tabs( $screen_type ) {
		switch ( $screen_type ) {
			case 'list':
				$this->screen->add_help_tab( array(
					'id' => 'list_overview',
					'title' => __( 'Overview', 'qrmono-form' ),
					'content' => $this->content( 'list_overview' ),
				) );

				$this->screen->add_help_tab( array(
					'id' => 'list_available_actions',
					'title' => __( 'Available Actions', 'qrmono-form' ),
					'content' => $this->content( 'list_available_actions' ),
				) );

				$this->sidebar();

				return;
			case 'edit':
				$this->screen->add_help_tab( array(
					'id' => 'edit_overview',
					'title' => __( 'Overview', 'qrmono-form' ),
					'content' => $this->content( 'edit_overview' ),
				) );

				$this->screen->add_help_tab( array(
					'id' => 'edit_form_tags',
					'title' => __( 'Form-tags', 'qrmono-form' ),
					'content' => $this->content( 'edit_form_tags' ),
				) );

				$this->screen->add_help_tab( array(
					'id' => 'edit_mail_tags',
					'title' => __( 'Mail-tags', 'qrmono-form' ),
					'content' => $this->content( 'edit_mail_tags' ),
				) );

				$this->sidebar();

				return;
		}
	}

	private function content( $name ) {
		$content = array();

		$content['list_overview'] = '<p>' . __( "On this screen, you can manage forms provided by QrMono Form. You can manage an unlimited number of forms. Each form has a unique ID and QrMono Form shortcode ([qrmono-form ...]). To insert a form into a post or a text widget, insert the shortcode into the target.", 'qrmono-form' ) . '</p>';

		$content['list_available_actions'] = '<p>' . __( "Hovering over a row in the forms list will display action links that allow you to manage your form. You can perform the following actions:", 'qrmono-form' ) . '</p>';
		$content['list_available_actions'] .= '<p>' . __( "<strong>Edit</strong> - Navigates to the editing screen for that form. You can also reach that screen by clicking on the form title.", 'qrmono-form' ) . '</p>';
		$content['list_available_actions'] .= '<p>' . __( "<strong>Duplicate</strong> - Clones that form. A cloned form inherits all content from the original, but has a different ID.", 'qrmono-form' ) . '</p>';

		$content['edit_overview'] = '<p>' . __( "On this screen, you can edit a form. A form is comprised of the following components:", 'qrmono-form' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Title</strong> is the title of a form. This title is only used for labeling a form, and can be edited.", 'qrmono-form' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Form</strong> is a content of HTML form. You can use arbitrary HTML, which is allowed inside a form element. You can also use QrMono Form &#8217;s form-tags here.", 'qrmono-form' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Mail</strong> manages a mail template (headers and message body) that this form will send when users submit it. You can use QrMono Form &#8217;s mail-tags here.", 'qrmono-form' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Mail (2)</strong> is an additional mail template that works similar to Mail. Mail (2) is different in that it is sent only when Mail has been sent successfully.", 'qrmono-form' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "In <strong>Messages</strong>, you can edit various types of messages used for this form. These messages are relatively short messages, like a validation error message you see when you leave a required field blank.", 'qrmono-form' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Additional Settings</strong> provides a place where you can customize the behavior of this form by adding code snippets.", 'qrmono-form' ) . '</p>';

		$content['edit_form_tags'] = '<p>' . __( "A form-tag is a short code enclosed in square brackets used in a form content. A form-tag generally represents an input field, and its components can be separated into four parts: type, name, options, and values. QrMono Form supports several types of form-tags including text fields, number fields, date fields, checkboxes, radio buttons, menus, file-uploading fields, CAPTCHAs, and quiz fields.", 'qrmono-form' ) . '</p>';
		$content['edit_form_tags'] .= '<p>' . __( "While form-tags have a comparatively complex syntax, you do not need to know the syntax to add form-tags because you can use the straightforward tag generator (<strong>Generate Tag</strong> button on this screen).", 'qrmono-form' ) . '</p>';

		$content['edit_mail_tags'] = '<p>' . __( "A mail-tag is also a short code enclosed in square brackets that you can use in every Mail and Mail (2) field. A mail-tag represents a user input value through an input field of a corresponding form-tag.", 'qrmono-form' ) . '</p>';
		$content['edit_mail_tags'] .= '<p>' . __( "There are also special mail-tags that have specific names, but do not have corresponding form-tags. They are used to represent meta information of form submissions like the submitter&#8217;s IP address or the URL of the page.", 'qrmono-form' ) . '</p>';

		if ( ! empty( $content[$name] ) ) {
			return $content[$name];
		}
	}

	public function sidebar() {
		$content = '<p><strong>' . __( 'For more information:', 'qrmono-form' ) . '</strong></p>';
		$content .= '<p>' . wpqrmono_link( __( 'https://contactform7.com/docs/', 'qrmono-form' ), __( 'Docs', 'qrmono-form' ) ) . '</p>';
		$content .= '<p>' . wpqrmono_link( __( 'https://contactform7.com/faq/', 'qrmono-form' ), __( 'FAQ', 'qrmono-form' ) ) . '</p>';
		$content .= '<p>' . wpqrmono_link( __( 'https://contactform7.com/support/', 'qrmono-form' ), __( 'Support', 'qrmono-form' ) ) . '</p>';

		$this->screen->set_help_sidebar( $content );
	}
}
