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
	 *                bid  The bot ID.
	 *                cuid A universally unique identifier for this conversation.
	 */
	private $state = array();

	/**
	 * @var string The custom post type identifier.
	 */
	const POST_TYPE = 'arniebot';

	/**
	 * @var string[] Field IDs:
	 *                   description The internal bot description.
	 */
	public static $FIELDS = array(
		'description' => self::POST_TYPE . '_description',
	);

	public function __construct() {
		$this->state = array(
			'bid'    => $this->ID,
			'cuid'   => '',
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
		Carbon_Fields::boot();

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
			'description'       => __( 'A patient chat bot.' ),
			'public'            => false,
			'show_ui'           => true,
			'menu_icon'         => 'dashicons-smiley',
			'capability_type'   => array( 'arniebot', 'arniebots' ),
			'map_meta_cap'       => true,
			'supports'          => array( 'title' ),
			'can_export'        => false,
			'rewrite'           => false,
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
		Container::make( 'post_meta', __( 'Bot Properties', 'arniebot' ) )
			->where( 'post_type', '=', self::POST_TYPE );
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
}
