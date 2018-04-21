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
						'description' => __( 'The session state for this conversation.', 'arniebot' ),
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

		/**
		 * A new session is being requested.
		 */
		if ( $request->get_method() == 'POST' ) {
		}

		/**
		 * Resume and old session.
		 */
		else if ( $request->get_method() == 'PUT' ) {
		}

		/**
		 * Method not supported.
		 */
		else {
			return WP_Error( 'rest_no_such_method', __( 'This method is not supported.', 'arniebot' ) );
		}
	}
}
