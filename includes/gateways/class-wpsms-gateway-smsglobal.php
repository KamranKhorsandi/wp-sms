<?php

namespace WP_SMS\Gateway;

class smsglobal extends \WP_SMS\Gateway {
	private $wsdl_link = "https://api.smsglobal.com/";
	public $tariff = "https://api.smsglobal.com/v2/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "The number starting with country code.";
		$this->has_key        = true;
	}

	public function SendSMS() {

		/**
		 * Modify sender number
		 *
		 * @since 3.4
		 *
		 * @param string $this ->from sender number.
		 */
		$this->from = apply_filters( 'wp_sms_from', $this->from );

		/**
		 * Modify Receiver number
		 *
		 * @since 3.4
		 *
		 * @param array $this ->to receiver number
		 */
		$this->to = apply_filters( 'wp_sms_to', $this->to );

		/**
		 * Modify text message
		 *
		 * @since 3.4
		 *
		 * @param string $this ->msg text message.
		 */
		$this->msg = apply_filters( 'wp_sms_msg', $this->msg );

		// Get the credit.
		$credit = $this->GetCredit();

		// Check gateway credit
		if ( is_wp_error( $credit ) ) {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $credit->get_error_message(), 'error' );

			return $credit;
		}

		$body = array(
			'destination' => $this->to,
			'message'     => $this->msg,
			'origin'      => $this->from,
		);

		$response = wp_remote_post( $this->wsdl_link . 'v2/sms', [
			'headers' => array(
				'Authorization' => 'MAC id="' . $this->has_key . ', ts="' . time() . '", nonce="' . base64_encode( "$this->username:$this->has_key" ) . '", mac="base64-encoded-hash"',
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json'
			),
			'body'    => json_encode( $body )
		] );

		// Check gateway credit
		if ( is_wp_error( $response ) ) {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $response->get_error_message(), 'error' );

			return new \WP_Error( 'account-credit', $response->get_error_message() );
		}

		$result        = json_decode( $response['body'] );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == '200' ) {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $result );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 */
			do_action( 'wp_sms_send', $response['body'] );

			return $result;
		} else {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $result->error, 'error' );

			return new \WP_Error( 'send-sms', print_r( $result->error, 1 ) );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->has_key ) {
			return new \WP_Error( 'account-credit', __( 'Username/API-Key does not set for this gateway', 'wp-sms' ) );
		}

		$response = wp_remote_get( $this->wsdl_link . 'v2/user/credit-balance', [
			'headers' => array(
				'Authorization' => 'MAC id="' . $this->has_key . ', ts="' . time() . '", nonce="' . base64_encode( "$this->username:$this->has_key" ) . '", mac="base64-encoded-hash"',
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json'
			)
		] );

		// Check gateway credit
		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'account-credit', $response->get_error_message() );
		}

		$result        = json_decode( $response['body'] );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == '200' ) {
			return $result->balance;
		} else {
			return new \WP_Error( 'credit', $result->error->message );
		}
	}
}