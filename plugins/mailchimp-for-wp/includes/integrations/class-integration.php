<?php

if( ! defined( 'MC4WP_LITE_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

abstract class MC4WP_Integration {

	/**
	 * @var string
	 */
	protected $type = 'integration';

	/**
	 * @var string
	 */
	protected $checkbox_name = '_mc4wp_subscribe';

	/**
	 * @var array
	 */
	private $options;

	/**
	* Constructor
	*/
	public function __construct() {
		$this->checkbox_name = '_mc4wp_subscribe' . '_' . $this->type;
	}

	/**
	 * Get the checkbox options
	 *
	 * @return array
	 */
	public function get_options() {

		if( $this->options === null ) {
			$this->options = mc4wp_get_options( 'checkbox' );
		}

		return $this->options;
	}

	/**
	 * Is this a spam request?
	 *
	 * @return bool
	 */
	protected function is_spam() {

		// check if honeypot was filled
		if( $this->is_honeypot_filled() ) {
			return true;
		}

		// check user agent
		$user_agent = substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 );
		if( strlen( $user_agent ) < 2 ) {
			return true;
		}

		/**
		 * @filter `mc4wp_is_spam`
		 * @expects boolean True if this is a spam request
		 * @default false
		 */
		return apply_filters( 'mc4wp_is_spam', false );
	}

	/**
	 * Was the honeypot filled?
	 *
	 * @return bool
	 */
	protected function is_honeypot_filled() {

		// Check if honeypot was filled (by spam bots)
		if( isset( $_POST['_mc4wp_required_but_not_really'] ) && ! empty( $_POST['_mc4wp_required_but_not_really'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Should the checkbox be pre-checked?
	 *
	 * @return bool
	 */
	public function is_prechecked() {
		$opts = $this->get_options();
		return (bool) $opts['precheck'];
	}

	/**
	 * Get the text for the label element
	 *
	 * @return string
	 */
	public function get_label_text() {

		$opts = $this->get_options();

		// Get general label text
		$label = $opts['label'];

		// Override label text if a specific text for this integration is set
		if ( isset( $opts['text_' . $this->type . '_label'] ) && ! empty( $opts['text_' . $this->type . '_label'] ) ) {
			// custom label text was set
			$label = $opts['text_' . $this->type . '_label'];
		}

		// replace label variables
		$label = mc4wp_replace_variables( $label, $opts['lists'] );

		return $label;
	}

	/**
	* @return bool
	*/
	public function checkbox_was_checked() {
		return ( isset( $_POST[ $this->checkbox_name ] ) && $_POST[ $this->checkbox_name ] == 1 );
	}

	/**
	* Outputs a checkbox
	*/
	public function output_checkbox() {
		echo $this->get_checkbox();
	}

	/**
	* @param mixed $args Array or string
	* @return string
	*/
	public function get_checkbox( $args = array() ) {

		$checked = ( $this->is_prechecked() ) ? 'checked ' : '';

		// set label text
		if ( isset( $args['labels'][0] ) ) {
			// cf 7 shortcode
			$label = $args['labels'][0];
		} else {
			$label = $this->get_label_text();
		}

		// CF7 checkbox?
		if( is_array( $args ) && isset( $args['type'] ) ) {

			// check for default:0 or default:1 to set the checked attribute
		 	if( in_array( 'default:1', $args['options'] ) ) {
		 		$checked = 'checked';
		 	} else if( in_array( 'default:0', $args['options'] ) ) {
		 		$checked = '';
		 	}

		}

		// before checkbox HTML (comment, ...)
		$before = '<!-- MailChimp for WordPress v'. MC4WP_LITE_VERSION .' - https://mc4wp.com/ -->';
		$before .= apply_filters( 'mc4wp_before_checkbox', '', $this->type );

		// checkbox
		$content = '<p id="mc4wp-checkbox" class="mc4wp-checkbox-' . $this->type .'">';
		$content .= '<label>';
		$content .= '<input type="checkbox" name="'. esc_attr( $this->checkbox_name ) .'" value="1" '. $checked . ' /> ';
		$content .= $label;
		$content .= '</label>';
		$content .= '</p>';

		// after checkbox HTML (..., honeypot, closing comment)
		$after = apply_filters( 'mc4wp_after_checkbox', '', $this->type );
		$after .= '<textarea name="_mc4wp_required_but_not_really" style="display: none !important;"></textarea>';
		$after .= '<!-- / MailChimp for WordPress -->';

		return $before . $content . $after;
	}

	/**
	 * @return array
	 */
	protected function get_lists() {

		// get checkbox lists options
		$opts = $this->get_options();
		$lists = $opts['lists'];

		// get lists from form, if set.
		if( isset( $_POST['_mc4wp_lists'] ) && ! empty( $_POST['_mc4wp_lists'] ) ) {

			$lists = $_POST['_mc4wp_lists'];

			// make sure lists is an array
			if( ! is_array( $lists ) ) {

				// sanitize value
				$lists = sanitize_text_field( $lists );
				$lists = array( $lists );
			}

		}

		// allow plugins to filter final
		$lists = apply_filters( 'mc4wp_lists', $lists );

		return (array) $lists;
	}


	/**
	 * Makes a subscription request
	 *
	 * @param string $email
	 * @param array  $merge_vars
	 * @param string $type
	 * @param null   $related_object_id
	 *
	 * @return bool
	 */
	protected function subscribe( $email, array $merge_vars = array(), $type = '', $related_object_id = null ) {

		$type = ( '' !== $type ) ? $type : $this->type;

		$api = mc4wp_get_api();
		$opts = $this->get_options();
		$lists = $this->get_lists();

		if( empty( $lists) ) {
			if( $this->show_error_messages() ) {
				wp_die(
					'<h3>' . __( 'MailChimp for WordPress - Error', 'mailchimp-for-wp' ) . '</h3>' .
					'<p>' . sprintf( __( 'Please select a list to subscribe to in the <a href="%s">checkbox settings</a>.', 'mailchimp-for-wp' ), admin_url( 'admin.php?page=mailchimp-for-wp-checkbox-settings' ) ) . '</p>' .
					'<p style="font-style:italic; font-size:12px;">' . __( 'This message is only visible to administrators for debugging purposes.', 'mailchimp-for-wp' ) . '</p>',
					__( 'MailChimp for WordPress - Error', 'mailchimp-for-wp' ),
					array( 'back_link' => true )
				);
			}

			return 'no_lists_selected';
		}

		// maybe guess first and last name
		if ( isset( $merge_vars['NAME'] ) && ! isset( $merge_vars['FNAME'] ) && ! isset( $merge_vars['LNAME'] ) ) {

			$strpos = strpos( $merge_vars['NAME'], ' ' );
			if ( $strpos !== false ) {
				$merge_vars['FNAME'] = substr( $merge_vars['NAME'], 0, $strpos );
				$merge_vars['LNAME'] = substr( $merge_vars['NAME'], $strpos );
			} else {
				$merge_vars['FNAME'] = $merge_vars['NAME'];
			}
		}

		// set ip address
		if( ! isset( $merge_vars['OPTIN_IP'] ) && isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$merge_vars['OPTIN_IP'] = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		}

		$result = false;

		/**
		 * @filter `mc4wp_merge_vars`
		 * @expects array
		 * @param array $merge_vars
		 * @param string $type
		 *
		 * Use this to filter the final merge vars before the request is sent to MailChimp
		 */
		$merge_vars = apply_filters( 'mc4wp_merge_vars', $merge_vars, $type );

		/**
		 * @filter `mc4wp_merge_vars`
		 * @expects string
		 * @param string $email_type
		 *
		 * Use this to change the email type this users should receive
		 */
		$email_type = apply_filters( 'mc4wp_email_type', 'html' );

		/**
		 * @action `mc4wp_before_subscribe`
		 * @param string $email
		 * @param array $merge_vars
		 *
		 * Runs before the request is sent to MailChimp
		 */
		do_action( 'mc4wp_before_subscribe', $email, $merge_vars );

		foreach( $lists as $list_id ) {
			$result = $api->subscribe( $list_id, $email, $merge_vars, $email_type, $opts['double_optin'], false, true, $opts['send_welcome'] );
			do_action( 'mc4wp_subscribe', $email, $list_id, $merge_vars, $result, 'checkbox', $type, $related_object_id );
		}

		/**
		 * @action `mc4wp_after_subscribe`
		 * @param string $email
		 * @param array $merge_vars
		 * @param boolean $result
		 *
		 * Runs after the request is sent to MailChimp
		 */
		do_action( 'mc4wp_after_subscribe', $email, $merge_vars, $result );

		// check if result succeeded, show debug message to administrators (only in NON-AJAX requests)
		if ( $result !== true && $api->has_error() && $this->show_error_messages() ) {
			wp_die( '<h3>' . __( 'MailChimp for WordPress - Error', 'mailchimp-for-wp' ) . '</h3>' .
				'<p>' . __( 'The MailChimp server returned the following error message as a response to our sign-up request:', 'mailchimp-for-wp' ) . '</p>' .
				'<pre>' . $api->get_error_message() . '</pre>' .
				'<p>' . __( 'This is the data that was sent to MailChimp:', 'mailchimp-for-wp' ) . '</p>' .
				'<strong>' . __( 'Email address:', 'mailchimp-for-wp' ) . '</strong>' .
				'<pre>' . esc_html( $email ) . '</pre>' .
				'<strong>' . __( 'Merge variables:', 'mailchimp-for-wp' ) . '</strong>' .
				'<pre>' . esc_html( print_r( $merge_vars, true ) ) . '</pre>' .
				'<p style="font-style:italic; font-size:12px;">' . __( 'This message is only visible to administrators for debugging purposes.', 'mailchimp-for-wp' ) . '</p>',
			__( 'MailChimp for WordPress - Error', 'mailchimp-for-wp' ), array( 'back_link' => true ) );
		}

		return $result;
	}

	/**
	 * Should we show error messages?
	 * - Not for AJAX requests
	 * - Not for non-admins
	 * - Not for CF7 requests (which uses a different AJAX mechanism)
	 *
	 * @return bool
	 */
	protected function show_error_messages() {
		return ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
		       && ( ! isset( $_POST['_wpcf7_is_ajax_call'] ) || $_POST['_wpcf7_is_ajax_call'] != 1 )
		       && current_user_can( 'manage_options' );
	}
}