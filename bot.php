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
	 *                bid     The bot ID.
	 *                cuid    A universally unique identifier for this conversation.
	 *                topic   The current topic.
	 *                confirm Confirmation mode.
	 *                last    The last time anything was handled.
	 *                log     All that's been said.
	 *                idle    We've done the idle part.
	 */
	private $state = array();

	/**
	 * @var string The custom post type identifier.
	 */
	const POST_TYPE = 'arniebot';

	/**
	 * @var string[] Field IDs for post meta:
	 *                   description The internal bot description
	 *                   stopwords   Meaningless fillers
	 *                   humans      E-mail addresses for human notifications
	 *                   generics
	 *                       hello   Hello container
	 *                           responses The text responses
	 *                           response  A single text response
	 *                           line      A singe line
	 *                       yes     Yes container
	 *                           pattern   A single matching pattern
	 *                       no      No container
	 *                           pattern   A single matching pattern
	 *                       idle    Idle container
	 *                           responses The text responses
	 *                       udc     Undefined container
	 *                           responses The text responses
	 *                   topic       Topics/categories container
	 *                       id      An unique topic ID as a string
	 *                       sets    A set of related topics
	 *                           pattern      A pattern to match against
	 *                           responses    Responses
	 *                               response     A single response
	 *                           confirmation A confirmation (for goto and alerts)
	 *                           goto         Set topic for next message
	 *                           alert        Alert humans
	 */
	public static $FIELDS = array(
		'description'     => self::POST_TYPE . '_description',
		'stopwords'       => self::POST_TYPE . '_stopwords',
		'humans'          => self::POST_TYPE . '_humans',

		'generics'        => array(
			'hello'       => self::POST_TYPE . '_generics_hello',
				'hello_responses'       => self::POST_TYPE . '_generics_hello_responses',
				'hello_response'        => self::POST_TYPE . '_generics_hello_response',

			'bye'         => self::POST_TYPE . '_generics_bye',
				'bye_responses'         => self::POST_TYPE . '_generics_bye_responses',
				'bye_patterns'          => self::POST_TYPE . '_generics_bye_patterns',
				'bye_pattern'           => self::POST_TYPE . '_generics_bye_pattern',
				'bye_response'          => self::POST_TYPE . '_generics_bye_response',

			'yes'         => self::POST_TYPE . '_generics_yes',
				'yes_pattern'           => self::POST_TYPE . '_generics_yes_pattern',

			'no'          => self::POST_TYPE . '_generics_no',
				'no_pattern'            => self::POST_TYPE . '_generics_no_pattern',

			'idle'        => self::POST_TYPE . '_generics_idle',
				'idle_responses'        => self::POST_TYPE . '_generics_idle_responses',
				'idle_response'         => self::POST_TYPE . '_generics_idle_response',

			'udc'         => self::POST_TYPE . '_generics_udc',
				'udc_responses'         => self::POST_TYPE . '_generics_udc_responses',
				'udc_response'          => self::POST_TYPE . '_generics_udc_response',
		),

		'topics'          => self::POST_TYPE . '_topics',
			'topic_id'    => self::POST_TYPE . '_topic_id',
			'topic_sets'  => self::POST_TYPE . '_topic_sets',
				'topic_pattern'         => self::POST_TYPE . '_topic_pattern',
				'topic_confirmation'    => self::POST_TYPE . '_topic_confirmation',
				'topic_alert'           => self::POST_TYPE . '_topic_alert',
				'topic_responses'       => self::POST_TYPE . '_topic_responses',
					'topic_response'    => self::POST_TYPE . '_topic_response',
				'topic_goto'            => self::POST_TYPE . '_topic_goto',
				'topic_alert'           => self::POST_TYPE . '_topic_alert',
	);

	public function __construct() {
		$this->state = array(
			'bid'     => $this->ID,
			'cuid'    => '',
			'topic'   => '',
			'confirm' => false,
			'last'    => null,
			'idle'    => false,
			'log'     => array(),
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
				Field::make( 'textarea', self::$FIELDS['stopwords'], __( 'Stopwords', 'arniebot' ) )
					->set_help_text( __( 'A comma-separated list of words with no meaning.', 'arniebot' ) ),
				Field::make( 'text', self::$FIELDS['humans'], __( 'Humans', 'arniebot' ) )
					->set_help_text( __( 'Humans that should be alerted on alertable topics. A comma-separated e-mail list.', 'arniebot' ) ),
			) );

		Container::make( 'post_meta', __( 'Script Generics', 'arniebot' ) )
			->where( 'post_type', '=', self::POST_TYPE )
			->add_tab( __( 'Bot Introductions', 'arniebot' ), array(
				Field::make( 'complex', self::$FIELDS['generics']['hello_responses'], __( 'Bot Introductions', 'arniebot' ) )
					->set_help_text( __( 'The bot will randomly pick one of these when starting a conversation.', 'arniebot' ) )
					->add_fields( array(
						Field::make( 'rich_text', self::$FIELDS['generics']['hello_response'], __( 'Responses', 'arniebot' ) )
					) )
					->setup_labels( array( 'plural_name' => __( 'Introductions', 'arniebot' ), 'singular_name' => __( 'Introduction', 'arniebot' ) ) )
					->set_header_template( sprintf( '<%%- jQuery( %s ).text() %%>', self::$FIELDS['generics']['hello_response'] ) )
					->set_collapsed( true ),
			) )
			->add_tab(  __( 'Bot Idle Responses', 'arniebot' ), array(
				Field::make( 'complex', self::$FIELDS['generics']['idle_responses'], __( 'Bot Idle Responses', 'arniebot' ) )
					->set_help_text( __( 'The bot will randomly pick one of these when everyone is bored.', 'arniebot' ) )
					->add_fields( array(
						Field::make( 'rich_text', self::$FIELDS['generics']['idle_response'], __( 'Responses', 'arniebot' ) )
					) )
					->setup_labels( array( 'plural_name' => __( 'Idle Responses', 'arniebot' ), 'singular_name' => __( 'Idle Response', 'arniebot' ) ) )
					->set_header_template( sprintf( '<%%- jQuery( %s ).text() %%>', self::$FIELDS['generics']['idle_response'] ) )
					->set_collapsed( true ),
			) )
			->add_tab(  __( 'Bot UDC Responses', 'arniebot' ), array(
				Field::make( 'complex', self::$FIELDS['generics']['udc_responses'], __( 'Bot UDC Responses', 'arniebot' ) )
					->set_help_text( __( "The bot will randomly pick one of these when it doesn't know what to respond.", 'arniebot' ) )
					->add_fields( array(
						Field::make( 'rich_text', self::$FIELDS['generics']['udc_response'], __( 'Responses', 'arniebot' ) )
					) )
					->setup_labels( array( 'plural_name' => __( 'UDC', 'arniebot' ), 'singular_name' => __( 'UDC', 'arniebot' ) ) )
					->set_header_template( sprintf( '<%%- jQuery( %s ).text() %%>', self::$FIELDS['generics']['udc_response'] ) )
					->set_collapsed( true ),
			) )
			->add_tab(  __( 'Bot Yes Pattern', 'arniebot' ), array(
				Field::make( 'textarea', self::$FIELDS['generics']['yes_pattern'], __( 'Bot Yes Pattern', 'arniebot' ) )
					->set_help_text( __( 'An affirmative answer to the question at hand. Comma-separated, regular expressions supported.', 'arniebot' ) )
			) )
			->add_tab(  __( 'Bot No Pattern', 'arniebot' ), array(
				Field::make( 'textarea', self::$FIELDS['generics']['no_pattern'], __( 'Bot No Pattern', 'arniebot' ) )
					->set_help_text( __( 'A negative answer to a current confirmation. Comma-separated, regular expressions supported.', 'arniebot' ) )
			) );

		Container::make( 'post_meta', __( 'Script Topics', 'arniebot' ) )
			->where( 'post_type', '=', self::POST_TYPE )
			->add_fields( array(
				Field::make( 'complex', self::$FIELDS['topics'], __( 'Bot Topics', 'arniebot' ) )
					->set_help_text( __( 'The topics this bot can help with.', 'arniebot' ) )
					->add_fields( array(
						Field::make( 'text', self::$FIELDS['topic_id'], __( 'Topic ID', 'arniebot' ) )
							->set_help_text( __( 'A unique topic ID as a string.', 'arniebot' ) ),

						Field::make( 'complex', self::$FIELDS['topic_sets'], __( 'Topic Patterns and Responses', 'arniebot' ) )
							->add_fields( array(
								Field::make( 'text', self::$FIELDS['topic_pattern'], __( 'Topic Pattern', 'arniebot' ) )
									->set_help_text( __( 'Comma-separated keywords, unordered. Regular expressions allowed.', 'arniebot' ) ),
								Field::make( 'text', self::$FIELDS['topic_confirmation'], __( 'Topic Confirmation', 'arniebot' ) )
									->set_help_text( __( 'Confirm intent before moving to topic or sending alert.', 'arniebot' ) ),
								Field::make( 'complex', self::$FIELDS['topic_responses'], __( 'Topic Responses', 'arniebot' ) )
									->add_fields( array(
										Field::make( 'rich_text', self::$FIELDS['topic_response'], __( 'Topic Response', 'arniebot' ) ),
									) )
									->setup_labels( array( 'plural_name' => __( 'Topic Responses', 'arniebot' ), 'singular_name' => __( 'Topic Response', 'arniebot' ) ) )
									->set_header_template( sprintf( '<%%- jQuery( %s ).text() %%>', self::$FIELDS['topic_response'] ) )
									->set_layout( 'tabbed-vertical' )
									->set_collapsed( true ),
								Field::make( 'checkbox', self::$FIELDS['topic_alert'], __( 'Alert humans.', 'arniebot' ) )
									->set_help_text( __( 'A human will be alerted as this response is sent out via e-mail.', 'arniebot' ) ),
								Field::make( 'text', self::$FIELDS['topic_goto'], __( 'Goto Topic', 'arniebot' ) )
									->set_help_text( __( 'The conversation will be weighted towards this topic ID , if exists.', 'arniebot' ) ),
							) )
							->setup_labels( array( 'plural_name' => __( 'Topic Sets', 'arniebot' ), 'singular_name' => __( 'Topic Set', 'arniebot' ) ) )
							->set_header_template( sprintf( '<%%- %s %%>', self::$FIELDS['topic_pattern'] ) )
							->set_layout( 'tabbed-vertical' )
							->set_collapsed( true ),
					) )
					->setup_labels( array( 'plural_name' => __( 'Topics', 'arniebot' ), 'singular_name' => __( 'Topic', 'arniebot' ) ) )
					->set_header_template( sprintf( '<%%- %s %%>', self::$FIELDS['topic_id'] ) )
					->set_layout( 'tabbed-vertical' )
					->set_collapsed( true ),
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

        wp_enqueue_script( 'arniebot', plugins_url( '/js/arniebot.js', __FILE__ ) );
        wp_enqueue_style( 'arniebot', plugins_url( '/css/arniebot.css', __FILE__ ) );

        $html = '<div class="arniebot" data-bot-id="' . esc_attr( $atts[ 'id' ] ) . '">
                <div class="arniebot__chat"></div>
                <form class="arniebot__client-form">
                    <textarea class="client-board__message"></textarea><br>
                    <input class="client-board__message__send" type="submit" value="' . esc_attr__( 'Send', 'arniebot' ) .'">
                </form>
                </div>';
        return $html;
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
		$this->state['bid']      = $this->ID;
		$this->state['cuid']     = wp_generate_uuid4();
		$this->state['topic']    = '';
		$this->state['confirm']  = false;
		$this->state['last']     = false;
		$this->state['log']      = array();
		$this->state['idle']     = false;

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
	 * @todo HMAC it to prevent fiddling! Sakuriteeeee!
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
			$hello_responses = wp_list_pluck(
				$this->get_field( self::$FIELDS['generics']['hello_responses'] ),
				self::$FIELDS['generics']['hello_response']
			);

			$this->state['last'] = time(); /** Rehandle the message if any. */
			if ( $hello_responses ) {
				$response = array_merge( $response, array( $hello_responses[ array_rand( $hello_responses ) ] ), $this->handle( $message ) );
			} else {
				$response[] = __( 'A hello response has not been defined for this bot.', 'arniebot' );
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
				$idle_responses = wp_list_pluck(
					$this->get_field( self::$FIELDS['generics']['idle_responses'] ),
					self::$FIELDS['generics']['idle_response']
				);

				if ( $idle_responses ) {
					$response[] = $idle_responses[ array_rand( $idle_responses ) ];
				} else {
					$response[] = __( 'An idle response has not been defined for this bot...', 'arniebot' );
				}
			}

		/**
		 * There's a message.
		 */
		} else {
			$this->state['last'] = time();
			$this->state['idle'] = false;

			/**
			 * Parse a confirmation message.
			 * @todo
			 */
			if ( $this->state['confirm'] ) {
				// Yes or no
				// Say one of the responses stored in confirm.
			}

			/** Parse the message and try to figure it out. */

			/** Split words and lowercase them. */
			$words = array_map( 'strtolower', array_filter( preg_split( '#[?!., ]#', $message ) ) );

			/** Filter stop words. */
			$stopwords = array_map( 'trim', array_map( 'strtolower', explode( ',', $this->get_field( self::$FIELDS['stopwords'], '' ) ) ) );
			$words = array_diff( $words, $stopwords );

			/** Parse topics and patterns. */
			$matches = array();
			foreach ( $this->get_field( self::$FIELDS['topics'] ) as $topic ) {
				foreach ( $topic[ self::$FIELDS['topic_sets'] ] as $set ) {
					$keywords = array_map( 'trim', array_map( 'strtolower', explode( ',', $set[ self::$FIELDS['topic_pattern'] ] ) ) );
					$points   = 0;

					foreach ( $keywords as $keyword ) {
						foreach ( $words as $word ) {
							if ( preg_match( "#$keyword#", $word ) ) {
								$points++;
							}
							if ( $topic[ self::$FIELDS['topic_id'] ] == $word ) {
								$points++;
							}
							if ( $topic[ self::$FIELDS['topic_id'] ] == $this->state['topic']) {
								$points++;
							}
						}
					}

					if ( $points ) {
						$responses    = wp_list_pluck( $set[ self::$FIELDS['topic_responses'] ], self::$FIELDS['topic_response'] );
						$alert        = $set[ self::$FIELDS['topic_alert'] ];
						$goto         = $set[ self::$FIELDS['topic_goto'] ];
						$confirmation = $set[ self::$FIELDS['topic_confirmation'] ];

						$matches[] = compact( 'points', 'confirmation', 'responses', 'alert', 'goto' );
					}
				}
			}

			if ( $matches ) {
				/** Sort by score. */
				usort( $matches, function( $a, $b ) {
					if ( $a['points'] == $b['points'] ) {
						return 0;
					}
					return $a['points'] < $b['points'] ? 1 : -1;
				} );

				/** Grab the top 5% of matches and return them as responses. */
				$slice = max( 1, count( $matches ) * 0.05 );
				foreach ( array_slice( $matches, 0, $slice ) as $match ) {
					$response[] = $match['responses'][ array_rand( $match['responses'] ) ];
				}

				// @todo confirmation
				// @todo alert
				// @todo goto
			} else {
				/** Pick a UDC line. */
				$udc_responses = wp_list_pluck(
					$this->get_field( self::$FIELDS['generics']['udc_responses'] ),
					self::$FIELDS['generics']['udc_response']
				);

				if ( $udc_responses ) {
					$response[] = $udc_responses[ array_rand( $udc_responses ) ];
				} else {
					$response[] = __( 'A UDC response has not been defined for this bot.', 'arniebot' );
				}
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
		return carbon_get_post_meta( $this->ID, $key ) ? : $default;
	}
}
