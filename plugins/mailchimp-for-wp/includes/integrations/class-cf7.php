<?php

// prevent direct file access
if( ! defined( 'MC4WP_LITE_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

class MC4WP_CF7_Integration extends MC4WP_General_Integration {

	/**
	 * @var string
	 */
	protected $type = 'contact_form_7';

	/**
	 * Constructor
	 */
	public function __construct() {

		// make sure older checkbox names work for CF7 too
		$this->upgrade();

		add_action( 'init', array( $this, 'init') );

		add_action( 'wpcf7_mail_sent', array( $this, 'subscribe_from_cf7' ) );
		add_action( 'wpcf7_posted_data', array( $this, 'alter_cf7_data') );
	}

	/**
	* Registers the CF7 shortcode
	 *
	* @return boolean
	*/
	public function init() {

		if( ! function_exists( 'wpcf7_add_shortcode' ) ) {
			return false;
		}

		wpcf7_add_shortcode( 'mc4wp_checkbox', array( $this, 'get_checkbox' ) );
		return true;
	}

	/**
	* Alter Contact Form 7 data.
	*
	* Adds mc4wp_checkbox to post data so users can use `mc4wp_checkbox` in their email templates
	*
	* @param array $data
	* @return array
	*/
	public function alter_cf7_data( $data = array() ) {
		$data['mc4wp_checkbox'] = $this->checkbox_was_checked() ? __( 'Yes', 'mailchimp-for-wp' ) : __( 'No', 'mailchimp-for-wp' );
		return $data;
	}

	/**
	* Subscribe from Contact Form 7 Forms
	*/
	public function subscribe_from_cf7() {

		// was sign-up checkbox checked?
		if ( $this->checkbox_was_checked() === false ) {
			return false;
		}

		return $this->try_subscribe();
	}

}