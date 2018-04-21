<?php
namespace ARNIE_Chat_Bot;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Core {
	/**
	 * Main initialization.
	 *
	 * Very early, not all plugins have been loaded yet.
	 *
	 * @return void
	 */
	public static function bootstrap() {
		add_action( 'after_setup_theme', array( '\Carbon_Fields\Carbon_Fields', 'boot' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'rest_api_init' ) );

		Bot::bootstrap();
	}

	/**
	 * REST initialization.
	 *
	 * A bot is accessed via the WordPress REST API arnie/v1/bots/$id endpoint.
	 *
	 * A POST method will create a new session with the bot, resetting the state.
	 * A PUT method requires that a session is supplied.
	 *
	 * @return void
	 */
	public static function rest_api_init() {
		register_rest_route( 'arnie/v1', '/bots/(?P<id>[\d]+)', array(
			array(
				'methods'             => array( 'POST', 'PUT' ),
				'callback'            => array( __CLASS__, 'rest_handle' ),
				'args'                => array(
					'state'           => array(
						'description' => __( 'The session state for this conversation JSON in base64.', 'arniebot' ),
						'type'        => 'string',
					),
					'message'         => array(
						'description' => __( 'The message to the bot.', 'arniebot' ),
						'type'        => 'string',
					),
				),
			),
		) );
	}

	/**
	 * A wrapper around the ARNIE_Chat_Bot::handle call with sanity checks.
	 *
	 * @param WP_REST_Request   $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public static function rest_handle( $request ) {
		$bot_id     = $request->get_param( 'id' );
		$state      = $request->get_param( 'state' );
		$message    = $request->get_param( 'message' );

		if ( ! $bot = Bot::get( $bot_id ) ) {
			return new \WP_Error( 'not_found', 'Unknown bot.' );
		}

		/**
		 * A new session is being requested.
		 */
		if ( $request->get_method() == 'POST' ) {
		}

		/**
		 * Resume and old session.
		 */
		else if ( $request->get_method() == 'PUT' ) {
			if ( ! $state = @base64_decode( $state ) ) {
				return new \WP_Error( 'invalid_state', 'Invalid state supplied.' );
			}

			if ( ! $state = @json_decode( $state ) ) {
				return new \WP_Error( 'invalid_state', 'Invalid state supplied.' );
			}

			try {
				$bot->load_state( $state );
			} catch ( \Exception $e ) {
				return new \WP_Error( 'invalid_state', $e->getMessage() );
			}
		}

		/**
		 * Method not supported.
		 */
		else {
			return new \WP_Error( 'rest_no_such_method', __( 'This method is not supported.', 'arniebot' ) );
		}
	}
}
