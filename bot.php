<?php
namespace ARNIE_Chat_Bot;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

use \Carbon_Fields\Carbon_Fields;
use \Carbon_Fields\Container;
use \Carbon_Fields\Field;

/**
 * A chat bot.
 *
 * An interface to a custom \WP_Post type.
 */
class Bot {
	/**
	 * @var int The ID.
	 */
	public $ID = 0;

	/**
	 * @var array The state of this bot. Fields:
	 *                bid    The bot ID.
	 *                cuid   A universally unique identifier for this conversation.
	 *                topic  The current topic.
	 *                last   The last time anything was handled.
	 *                log    All that's been said.
	 *                idle   We've done the idle part.
	 */
	private $state = array();

	/**
	 * @var string The custom post type identifier.
	 */
	const POST_TYPE = 'arniebot';

	/**
	 * @var string[] Field IDs for post meta:
	 *                   description The internal bot description
	 *                   generics
	 *                       hello   Hello container
	 *                           responses The text responses
	 *                           response  A single text response
	 *                           line      A singe line
	 *                       bye     Bye container
	 *                           responses The text responses
	 *                           patterns  Matching patterns
	 *                           pattern   A single matching pattern
	 *                           response  A single text response
	 *                           line      A singe line
	 *                       yes     Yes container
	 *                           patterns  Matching patterns
	 *                           pattern   A single matching pattern
	 *                       no      No container
	 *                           patterns  Matching patterns
	 *                           pattern   A single matching pattern
	 *                       idle    Idle container
	 *                           responses The text responses
	 *                       udc     Undefined container
	 *                           responses The text responses
	 *                   topic       Topics/categories container
	 */
	public static $FIELDS = array(
		'description'     => self::POST_TYPE . '_description',

		'generics'        => array(
			'hello'       => self::POST_TYPE . '_generics_hello',
				'hello_responses'       => self::POST_TYPE . '_generics_hello_responses',
				'hello_response'        => self::POST_TYPE . '_generics_hello_response',
				'hello_response_line'   => self::POST_TYPE . '_generics_hello_response_line',

			'bye'         => self::POST_TYPE . '_generics_bye',
				'bye_responses'         => self::POST_TYPE . '_generics_bye_responses',
				'bye_patterns'          => self::POST_TYPE . '_generics_bye_patterns',
				'bye_pattern'           => self::POST_TYPE . '_generics_bye_pattern',
				'bye_response'          => self::POST_TYPE . '_generics_bye_response',
				'bye_response_line'     => self::POST_TYPE . '_generics_bye_response_line',

			'yes'         => self::POST_TYPE . '_generics_yes',
				'yes_patterns'          => self::POST_TYPE . '_generics_yes_patterns',
				'yes_pattern'           => self::POST_TYPE . '_generics_yes_pattern',

			'no'          => self::POST_TYPE . '_generics_no',
				'no_patterns'           => self::POST_TYPE . '_generics_no_patterns',
				'no_pattern'            => self::POST_TYPE . '_generics_no_pattern',

			'idle'        => self::POST_TYPE . '_generics_idle',
				'idle_responses'        => self::POST_TYPE . '_generics_idle_responses',
				'idle_response'         => self::POST_TYPE . '_generics_idle_response',
				'idle_response_line'    => self::POST_TYPE . '_generics_idle_response_line',

			'udc'         => self::POST_TYPE . '_generics_udc',
				'udc_responses'         => self::POST_TYPE . '_generics_udc_responses',
				'udc_response'          => self::POST_TYPE . '_generics_udc_response',
				'udc_response_line'     => self::POST_TYPE . '_generics_udc_response_line',
		),

		'topics'          => self::POST_TYPE . '_topics',
	);

	public function __construct() {
		$this->state = array(
			'bid'    => $this->ID,
			'cuid'   => '',
			'topic'  => '',
			'last'   => null,
			'idle'   => false,
			'log'    => array(),
		);
	}

	/**
	 * Main initialization.
	 *
	 * Very early, not all plugins have been loaded yet.
	 *
	 * @return void
	 */
	public static function bootstrap() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );

		add_action( 'carbon_fields_register_fields', array( __CLASS__, 'register_metaboxes' ) );

		add_shortcode( 'arniebot', array( __CLASS__, 'do_shortcode' ) );
	}

	/**
	 * Create an internal post type.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type( self::POST_TYPE, array(
			'description'       => __( 'A patient chat bot.' ),
			'label'  => __( 'Bots', 'arniebot' ),
			'labels' => array(
				'name'                  => __( 'Bots', 'arniebot' ),
				'singular_name'         => __( 'Bot', 'arniebot' ),
				'add_new'               => _x( 'Add New', self::POST_TYPE, 'arniebot' ),
				'add_new_item'          => __( 'Add New Bot', 'arniebot' ),
				'edit_item'             => __( 'Edit Bot', 'arniebot' ),
				'new_item'              => __( 'New Bot', 'arniebot' ),
				'not_found'             => __( 'No bots found', 'arniebot' ),
				'not_found_in_trash'    => __( 'No bots found', 'arniebot' ),
				'all_items'             => __( 'All Bots', 'arniebot' ),
			),
			'hierarchical'      => false,
			'show_ui'           => true,
			'menu_icon'         => 'dashicons-smiley',
			'supports'          => array( 'title' ),
			'can_export'        => false,
			'rewrite'           => array( 'with_front' => false ),
		) );
	}

	/**
	 * The scenario constructor UI.
	 *
	 * Carbon Fields-based stuff.
	 *
	 * @return void
	 */
	public static function register_metaboxes() {
		Container::make( 'post_meta', __( 'General', 'arniebot' ) )
			->where( 'post_type', '=', self::POST_TYPE )
			->add_fields( array(
				Field::make( 'text', self::$FIELDS['description'], __( 'Bot description', 'arniebot' ) )
					->set_help_text( __( 'A short description for this bot.', 'arniebot' ) ),
			) );

		Container::make( 'post_meta', __( 'Script Generics / Bot Introduction', 'arniebot' ) )
			->where( 'post_type', '=', self::POST_TYPE )
			->add_fields( array(
				Field::make( 'complex', self::$FIELDS['generics']['hello_responses'], __( 'Bot Introductions', 'arniebot' ) )
					->set_help_text( __( 'The bot will randomly pick one of these when starting a conversation.', 'arniebot' ) )
					->add_fields( array(
						Field::make( 'complex', self::$FIELDS['generics']['hello_response'], __( 'Responses', 'arniebot' ) )
							->add_fields( array(
								Field::make( 'text', self::$FIELDS['generics']['hello_response_line'], __( 'Response line', 'arniebot' ) )
							) )
							->setup_labels( array( 'plural_name' => __( 'Responses', 'arniebot' ), 'singular_name' => __( 'Response', 'arniebot' ) ) ),
					) )
					->setup_labels( array( 'plural_name' => __( 'Introductions', 'arniebot' ), 'singular_name' => __( 'Introduction', 'arniebot' ) ) )
					->set_layout( 'tabbed-vertical' ),
			) );

        Container::make( 'post_meta', __( 'Script Generics / Bot Farewells', 'arniebot' ) )
            ->where( 'post_type', '=', self::POST_TYPE )
            ->add_fields( array(
                Field::make( 'complex', self::$FIELDS['generics']['bye_responses'], __( 'Bot Farewells', 'arniebot' ) )
                    ->set_help_text( __( 'The bot will randomly pick one of these to say goodbye.', 'arniebot' ) )
                    ->add_fields( array(
                        Field::make( 'complex', self::$FIELDS['generics']['bye_response'], __( 'Responses', 'arniebot' ) )
                            ->add_fields( array(
                                Field::make( 'text', self::$FIELDS['generics']['bye_response_line'], __( 'Response line', 'arniebot' ) )
                             ) )
                            ->setup_labels( array( 'plural_name' => __( 'Responses', 'arniebot' ), 'singular_name' => __( 'Response', 'arniebot' ) ) ),
                        Field::make( 'complex', self::$FIELDS['generics']['bye_patterns'], __( 'Patterns', 'arniebot' ) )
                            ->add_fields( array(
                                Field::make( 'text', self::$FIELDS['generics']['bye_pattern'], __( 'Pattern', 'arniebot' ) ),
                             ) )
                            ->setup_labels( array( 'plural_name' => __( 'Patterns', 'arniebot' ), 'singular_name' => __( 'Pattern', 'arniebot' ) ) )
                     ) )
                    ->setup_labels( array( 'plural_name' => __( 'Farewells', 'arniebot' ), 'singular_name' => __( 'Farewell', 'arniebot' ) ) )
                    ->set_layout( 'tabbed-vertical' )
             ) );


        
        Container::make( 'post_meta', __( 'Script Generics / Bot UDC', 'arniebot' ) )
            ->where( 'post_type', '=', self::POST_TYPE )
            ->add_fields( array(
                Field::make( 'complex', self::$FIELDS['generics']['udc_responses'], __( 'Bot UDC Responses', 'arniebot' ) )
                    ->set_help_text( __( "The bot will randomly pick one of these when it doesn't know what to respond.", 'arniebot' ) )
                    ->add_fields( array(
                        Field::make( 'complex', self::$FIELDS['generics']['udc_response'], __( 'Responses', 'arniebot' ) )
                            ->add_fields( array(
                                Field::make( 'text', self::$FIELDS['generics']['udc_response_line'], __( 'Response line', 'arniebot' ) )
                            ) )
                            ->setup_labels( array( 'plural_name' => __( 'Responses', 'arniebot' ), 'singular_name' => __( 'Response', 'arniebot' ) ) ),
                    ) )
                    ->setup_labels( array( 'plural_name' => __( 'UDC', 'arniebot' ), 'singular_name' => __( 'UDC', 'arniebot' ) ) )
                    ->set_layout( 'tabbed-vertical' ),
            ) );

        Container::make( 'post_meta', __( 'Script Generics / Bot Idle', 'arniebot' ) )
            ->where( 'post_type', '=', self::POST_TYPE )
            ->add_fields( array(
                Field::make( 'complex', self::$FIELDS['generics']['idle_responses'], __( 'Bot Idle Responses', 'arniebot' ) )
                    ->set_help_text( __( 'The bot will randomly pick one of these when everyone is bored.', 'arniebot' ) )
                    ->add_fields( array(
                        Field::make( 'complex', self::$FIELDS['generics']['idle_response'], __( 'Responses', 'arniebot' ) )
                            ->add_fields( array(
                                Field::make( 'text', self::$FIELDS['generics']['idle_response_line'], __( 'Response line', 'arniebot' ) )
                            ) )
                            ->setup_labels( array( 'plural_name' => __( 'Responses', 'arniebot' ), 'singular_name' => __( 'Response', 'arniebot' ) ) ),
                    ) )
                    ->setup_labels( array( 'plural_name' => __( 'Idle Responses', 'arniebot' ), 'singular_name' => __( 'Idle Response', 'arniebot' ) ) )
                    ->set_layout( 'tabbed-vertical' ),
            ) );

        Container::make( 'post_meta', __( 'Script Generics / Bot Yes', 'arniebot' ) )
            ->where( 'post_type', '=', self::POST_TYPE )
            ->add_fields( array(
                Field::make( 'complex', self::$FIELDS['generics']['yes_patterns'], __( 'Bot Yes Patterns', 'arniebot' ) )
                    ->set_help_text( __( 'An affirmative answer to the question at hand.', 'arniebot' ) )
                    ->add_fields( array(
                        Field::make( 'text', self::$FIELDS['generics']['yes_pattern'], __( 'Patterns', 'arniebot' ) )
                    ) )
                    ->setup_labels( array( 'plural_name' => __( 'Patterns', 'arniebot' ), 'singular_name' => __( 'Pattern', 'arniebot' ) ) )
                    ->set_layout( 'tabbed-vertical' ),
            ) );

        Container::make( 'post_meta', __( 'Script Generics / Bot No', 'arniebot' ) )
            ->where( 'post_type', '=', self::POST_TYPE )
            ->add_fields( array(
                Field::make( 'complex', self::$FIELDS['generics']['no_patterns'], __( 'Bot No Patterns', 'arniebot' ) )
                    ->set_help_text( __( 'An affirmative answer to the question at hand.', 'arniebot' ) )
                    ->add_fields( array(
                        Field::make( 'text', self::$FIELDS['generics']['no_pattern'], __( 'Patterns', 'arniebot' ) )
                    ) )
                    ->setup_labels( array( 'plural_name' => __( 'Patterns', 'arniebot' ), 'singular_name' => __( 'Pattern', 'arniebot' ) ) )
                    ->set_layout( 'tabbed-vertical' ),
            ) );
	}

	/**
	 * A shortcode handler to output a chat window on the frontend.
	 *
	 * @param array $atts The attributes:
	 *                        int $id The Bot ID.
	 *
	 * @return string Markup, scripts, etc.
	 */
	public static function do_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => null,
		), $atts, 'arniebot' );

		if ( ! $bot = self::get( $atts['id'] ) ) {
			return __( 'A bot with this ID does not exist', 'arniebot' );
		}

		return 'Hello :)';
	}

	/**
	 * Retrieve a bot with the given ID.
	 *
	 * @param int $bot_id The bot ID.
	 *
	 * @return \ARNIE_Chat_Bot\Bot|null The bot or null if not found.
	 */
	public static function get( $bot_id ) {
		if ( ! $post = get_post( $bot_id ) ) {
			return null;
		}

		if ( $post->post_type != self::POST_TYPE ) {
			return null;
		}

		$bot = new self();
		$bot->ID = $post->ID;
		$bot->reset();

		return $bot;
	}

	/**
	 * Reset the state of this bot.
	 *
	 * @return \ARNIE_Chat_Bot The bot.
	 */
	public function reset() {
		$this->state['cuid'] = wp_generate_uuid4();
		return $this;
	}

	/**
	 * Retrive the conversation UUID.
	 *
	 * @return string
	 */
	public function get_CUID() {
		return $this->state['cuid'];
	}

	/**
	 * Dump the current state.
	 *
	 * Should be saved in a backend (session, localStorage, database, etc.)
	 * and resumed later with `load_state`.
	 *
	 * @return array The state.
	 */
	public function dump_state() {
		return array_merge( $this->state, array(
			'bid' => $this->ID,
		) );
	}

	/**
	 * Load the state of this bot.
	 *
	 * Conversation memory, current topic, etc.
	 *
	 * @param array The state.
	 *
	 * @return \ARNIE_Chat_Bot The bot.
	 */
	public function load_state( $state ) {
		$empty_bot = new self();
		$this->state = wp_parse_args( $state, $empty_bot->dump_state() );

		if ( $this->state['bid'] != $this->ID ) {
			throw new \Exception( __( 'Invalid state for bot.', 'arniebot' ) );
		}

		return $this;
	}

	/**
	 * Handle an incoming message.
	 *
	 * @param string $message The incoming message as text.
	 *
	 * @return string[] An array of responses.
	 */
	public function handle( $message ) {

		$response = array();

		/**
		 * A new conversation with an empty message.
		 * Greet.
		 */
		if ( ! $this->state['last'] ) {

			/** Pick a greeting. */
			$hello_responses = array();
			foreach ( $this->get_field( self::$FIELDS['generics']['hello_responses'] ) as $hello_response ) {
				$hello_responses[] = wp_list_pluck( $hello_response[ self::$FIELDS['generics']['hello_response'] ], self::$FIELDS['generics']['hello_response_line'] );
			}

			$this->state['last'] = time(); /** Rehandle the message if any. */
			if ( $hello_responses ) {
				$response = array_merge( $response, $hello_responses[ array_rand( $hello_responses ) ], $this->handle( $message ) );
			} else {
				$response = array_merge( $response, __( 'A hello response has not been defined for this bot.', 'arniebot' ) );
			}

		/**
		 * An empty message, a ping of sorts.
		 * Maybe idle. Otherwise return nothing.
		 */
		} elseif ( ! $message ) {
			/**
			 * No messages were exchanged in the last 60 seconds.
			 */
			if ( $this->state['last'] < ( time() - 60 ) && ! $this->state['idle'] ) {
				$this->state['idle'] = true;

				/** Pick an idle line. */
				$idle_responses = array();
				foreach ( $this->get_field( self::$FIELDS['generics']['idle_responses'] ) as $idle_response ) {
					$idle_responses[] = wp_list_pluck( $idle_response[ self::$FIELDS['generics']['idle_response'] ], self::$FIELDS['generics']['idle_response_line'] );
				}

				if ( $idle_responses ) {
					$response = array_merge( $response, $idle_responses[ array_rand( $idle_responses ) ] );
				} else {
					$response = array_merge( $response, __( 'An idle response has not been defined for this bot...', 'arniebot' ) );
				}
			}

		/**
		 * There's a message.
		 */
		} else {
			$this->state['last'] = time();
			$this->state['idle'] = false;

			/** Pick a default responce (UDC). */
			$idle_responses = array();
			foreach ( $this->get_field( self::$FIELDS['generics']['udc_responses'] ) as $udc_response ) {
				$udc_responses[] = wp_list_pluck( $udc_response[ self::$FIELDS['generics']['udc_response'] ], self::$FIELDS['generics']['udc_response_line'] );
			}

			if ( $udc_responses ) {
				$response = array_merge( $response, $udc_responses[ array_rand( $udc_responses ) ] );
			} else {
				$response = array_merge( $response, __( 'A UDC response has not been defined for this bot.', 'arniebot' ) );
			}
		}

		$response = array_filter( $response );

		if ( $message ) {
			$this->state['log'][] = '<' . json_encode( $message );
		}

		if ( count( $response ) ) {
			$this->state['log'][] = '>' . json_encode( $response );
		}

		return $response;
	}

	/**
	 * Retrieve a field.
	 *
	 * @param string $key     The key.
	 * @param mixed  $default A default value if none found.
	 *
	 * @return mixed
	 */
	public function get_field( $key, $default = array() ) {
		return carbon_get_post_meta( $this->ID, $key ) ? : array();
	}
}
