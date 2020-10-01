<?php

defined( 'ABSPATH' ) or die();

/**
 * Gravity Forms CleverReach API library.
 *
 * @since     1.4
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2017, Rocketgenius
 */
class GF_CleverReach_API {

	/**
	 * Base CleverReach API URL.
	 *
	 * @since  1.4
	 * @var    string
	 * @access protected
	 */
	protected static $api_url = 'https://rest.cleverreach.com/v2/';

	/**
	 * CleverReach token.
	 *
	 * @since  1.4
	 * @var    string
	 * @access protected
	 */
	protected static $token = '';

	/**
	 * Initialize API library.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param string $token API token.
	 */
	public function __construct( $token = null ) {

		self::$token = $token;

	}




	// # ATTRIBUTES ----------------------------------------------------------------------------------------------------

	/**
	 * Create CleverReach attribute.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param string $name     Attribute name.
	 * @param string $type     Attribute type.
	 * @param int    $group_id Group to assign attribute to.
	 *
	 * @uses   GF_CleverReach_API::make_request()
	 *
	 * @return array|WP_Error
	 */
	public function create_attribute( $name = '', $type = 'text', $group_id = null ) {

		// Prepare payload.
		$payload = array(
			'name'        => $name,
			'description' => $name,
			'type'        => $type,
		);

		// Add group ID to payload.
		if ( $group_id ) {
			$payload['group_id'] = $group_id;
		}

		return $this->make_request( 'attributes.json', $payload, 'POST' );

	}

	/**
	 * Get attributes of a CleverReach group.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param string $group_id Group ID.
	 *
	 * @uses   GF_CleverReach_API::make_request()
	 *
	 * @return array|WP_Error
	 */
	public function get_attributes( $group_id = '' ) {

		return $this->make_request( 'attributes.json', array( 'group_id' => $group_id ) );

	}





	// # AUTHENTICATION ------------------------------------------------------------------------------------------------

	/**
	 * Get authentication token for user.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param string $client_id CleverReach client ID.
	 * @param string $username  CleverReach username.
	 * @param string $password  CleverReach password.
	 *
	 * @return bool|WP_Error
	 */
	public static function authenticate( $client_id = '', $username = '', $password = '' ) {

		// If any of the authentication parameters are empty, return.
		if ( rgblank( $client_id ) || rgblank( $username ) || rgblank( $password ) ) {
			return false;
		}

		// Prepare authentication arguments.
		$args = array(
			'client_id' => $client_id,
			'login'     => $username,
			'password'  => $password,
		);

		// Authenticate.
		$token = self::make_request( 'login', $args, 'POST' );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		return trim( $token, '"' );

	}





	// # FORMS ---------------------------------------------------------------------------------------------------------

	/**
	 * Send a subscribe/unsubscribe mail.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param int    $form_id Form ID.
	 * @param string $type    Mail type.
	 * @param string $email   Email address.
	 * @param string $data    Double opt-in email data.
	 *
	 * @uses   GF_CleverReach_API::make_request()
	 *
	 * @return array|WP_Error
	 */
	public function send_form( $form_id = '', $type = '', $email = '', $data = '' ) {

		// Prepare request options.
		$options = array( 'email' => $email, 'doidata' => $data );

		return $this->make_request( 'forms.json/' . $form_id . '/send/' . $type, $options, 'POST' );

	}





	// # GROUPS --------------------------------------------------------------------------------------------------------

	/**
	 * Add a receiver to a CleverReach group.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param int   $group_id Group ID.
	 * @param array $receiver Receiver arguments.
	 *
	 * @uses   GF_CleverReach_API::make_request()
	 *
	 * @return array|WP_Error
	 */
	public function add_group_receiver( $group_id = '', $receiver = array() ) {

		return $this->make_request( 'groups.json/' . $group_id . '/receivers/insert', array( $receiver ), 'POST' );

	}

	/**
	 * Get details of a CleverReach group.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param int $group_id Group ID.
	 *
	 * @uses   GF_CleverReach_API::make_request()
	 *
	 * @return array|WP_Error
	 */
	public function get_group( $group_id = '' ) {

		return $this->make_request( 'groups.json/' . $group_id );

	}

	/**
	 * Get forms for a CleverReach group.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param int $group_id Group ID.
	 *
	 * @uses   GF_CleverReach_API::make_request()
	 *
	 * @return array|WP_Error
	 */
	public function get_group_forms( $group_id = '' ) {

		return $this->make_request( 'groups.json/' . $group_id . '/forms' );

	}

	/**
	 * Get a receiver from a CleverReach group.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param int    $group_id    Group ID.
	 * @param string $receiver_id Receiver ID or email address.
	 *
	 * @uses   GF_CleverReach_API::make_request()
	 *
	 * @return array|WP_Error
	 */
	public function get_group_receiver( $group_id = '', $receiver_id = '' ) {

		return $this->make_request( 'groups.json/' . $group_id . '/receivers/' . $receiver_id );

	}

	/**
	 * Get list of CleverReach groups.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @uses   GF_CleverReach_API::make_request()
	 *
	 * @return array|WP_Error
	 */
	public function get_groups() {

		return $this->make_request( 'groups' );

	}

	/**
	 * Update an existing receiver in a CleverReach group.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param int    $group_id    Group ID.
	 * @param string $receiver_id Receiver ID or email address.
	 * @param array  $receiver    Receiver arguments.
	 *
	 * @uses   GF_CleverReach_API::make_request()
	 *
	 * @return array|WP_Error
	 */
	public function update_group_receiver( $group_id = '', $receiver_id = '', $receiver = array() ) {

		return $this->make_request( 'groups.json/' . $group_id . '/receivers/' . $receiver_id, $receiver, 'PUT' );

	}

	/**
	 * Add or update a receiver to a CleverReach group.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param int   $group_id Group ID.
	 * @param array $receiver Receiver arguments.
	 *
	 * @uses   GF_CleverReach_API::make_request()
	 *
	 * @return array|WP_Error
	 */
	public function upsert_group_receiver( $group_id = '', $receiver = array() ) {

		return $this->make_request( 'groups.json/' . $group_id . '/receivers/upsert', $receiver, 'POST' );

	}





	// # REQUEST METHODS -----------------------------------------------------------------------------------------------

	/**
	 * Make API request.
	 *
	 * @since  1.4
	 * @access private
	 *
	 * @param string $action     Request action.
	 * @param array  $options    Request options.
	 * @param string $method     HTTP method. Defaults to GET.
	 * @param string $return_key Array key from response to return. Defaults to null (return full response).
	 *
	 * @return array|string|WP_Error
	 */
	private static function make_request( $action, $options = array(), $method = 'GET', $return_key = null ) {

		// Build request options string.
		$request_options = 'GET' === $method && ! empty( $options ) ? '&' . http_build_query( $options ) : null;

		// Build request URL.
		$request_url = self::$api_url . $action . ( self::$token ? '?token=' . self::$token : '' ) . $request_options;

		// Build request headers.
		$headers = array(
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json',
		);

		// Add token to request headers.
		if ( ! empty( self::$token ) ) {
			$headers['Authorization'] = 'Bearer ' . base64_encode( self::$token );
		}

		// Build request arguments.
		$args = array(
			'body'    => $method !== 'GET' ? json_encode( $options ) : null,
			'headers' => $headers,
			'method'  => $method,
		);

		// Execute request.
		$response = wp_remote_request( $request_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Decode response.
		$result = gf_cleverreach()->maybe_decode_json( $response['body'] );

		// If response is not an array, return it.
		if ( ! is_array( $result ) ) {
			return $result;
		}

		if ( rgar( $result, 'error' ) ) {
			return new WP_Error( rgars( $result, 'error/code' ), rgars( $result, 'error/message' ) );
		}

		// If a return key is defined and array item exists, return it.
		if ( ! empty( $return_key ) && rgar( $result, $return_key ) ) {
			return $result[ $return_key ];
		}

		return $result;

	}

}
