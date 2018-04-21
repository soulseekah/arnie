<?php
/**
 * Plugin Name: A.R.N.I.E
 * Description: A nice chat bot that helps do nice things for nice people.
 * Plugin URI:  https://github.com/soulseekah/arnie
 * Text Domain: arniebot
 * Domain Path: /languages/
 * License:     GPLv3
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

add_action( 'plugins_loaded', 'arniebot_load_textdomain' );
function arniebot_load_textdomain() {
	load_plugin_textdomain( 'arniebot', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

/**
 * Don't outright die with syntax errors on PHP 5.2, just don't load anything.
 */
if ( version_compare( PHP_VERSION, '5.3.0','<' ) ) {
	add_action( 'admin_notices', 'arniebot_output_admin_notice_php_version' );
	return;
}

function arniebot_output_admin_notice_php_version() {
	$message = __( 'A.R.N.I.E requires PHP Version 5.3.0 or higher. Please contact your web host and ask them to upgrade your server.', 'arniebot' );
	echo "<div class='error'>$message</div>";
}

/** Prevent double loading. */
if ( class_exists( '\ARNIE_Chat_Bot\Core' ) ) {
	return;
}

/** Prevent loading issues during activation and deactivation. */
if ( did_action( 'init' ) ) {
	return;
}

require __DIR__ . '/vendor/autoload.php';

require dirname( __FILE__ ) . '/core.php';
require dirname( __FILE__ ) . '/bot.php';

/**
 * PHP 5.2-compatible syntax.
 */
$loader = array( '\ARNIE_Chat_Bot\Core', 'bootstrap' );
if ( is_callable( $loader ) ) {
	call_user_func( $loader );
}
