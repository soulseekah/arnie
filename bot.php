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
 */
class Bot {
	/**
	 * @var string The custom post type identifier.
	 */
	const POST_TYPE = 'arniebot';

	/**
	 * @var string[] Field IDs:
	 *                   description The internal bot description.
	 */
	static $FIELDS = array(
		'description' => self::POST_TYPE . '_description'
	);

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
			'map_meta_cap' => true,
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

		return __( 'A bot with this ID does not exist', 'arniebot' );
	}
}
