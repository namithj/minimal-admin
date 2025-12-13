<?php
/**
 * Plugin Name: Minimal Admin
 * Plugin URI: https://github.com/smartlogix/minimal-admin
 * Description: Adds minimal, clean styling overrides to WordPress admin with updated colors, borders, and focus states while preserving core layout.
 * Version: 1.0.0
 * Author: Smartlogix
 * Author URI: https://smartlogix.co.za
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: minimal-admin
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package    MinimalAdmin
 * @author     Smartlogix <info@smartlogix.co.za>
 * @license    GPL-2.0+ http://www.gnu.org/licenses/gpl-2.0.txt
 * @link       https://github.com/smartlogix/minimal-admin
 * @since      1.0.0
 * @category   WordPress
 * @version    1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin version.
 */
define( 'MINIMAL_ADMIN_VERSION', '1.0.0' );

/**
 * Plugin directory URL.
 */
define( 'MINIMAL_ADMIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Minimal Admin class.
 *
 * This plugin adds override styles on top of WordPress core admin CSS.
 * It does NOT replace core styles - it enhances them with minimal, clean
 * colors, improved focus states, and subtle visual refinements.
 *
 * @since 1.0.0
 */
class Minimal_Admin {

	/**
	 * Instance of this class.
	 *
	 * @var Minimal_Admin
	 */
	private static $instance = null;

	/**
	 * Get instance of this class.
	 *
	 * @return Minimal_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Enqueue override styles AFTER WordPress core styles (priority 999).
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ), 999 );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_styles' ), 999 );

		// Add custom body class for additional targeting if needed.
		add_filter( 'admin_body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * Enqueue admin override styles.
	 *
	 * These styles are loaded AFTER all WordPress core styles,
	 * allowing them to override colors, borders, shadows, etc.
	 */
	public function enqueue_admin_styles() {
		// Main override stylesheet - depends on WordPress core 'common' style.
		wp_enqueue_style(
			'minimal-admin',
			MINIMAL_ADMIN_URL . 'dist/css/minimal-admin.css',
			array( 'common', 'forms', 'admin-menu', 'dashboard', 'list-tables', 'edit', 'nav-menus' ),
			MINIMAL_ADMIN_VERSION
		);

		// RTL support.
		if ( is_rtl() ) {
			wp_enqueue_style(
				'minimal-admin-rtl',
				MINIMAL_ADMIN_URL . 'dist/css/minimal-admin-rtl.css',
				array( 'minimal-admin' ),
				MINIMAL_ADMIN_VERSION
			);
		}
	}

	/**
	 * Enqueue login page override styles.
	 */
	public function enqueue_login_styles() {
		wp_enqueue_style(
			'minimal-admin-login',
			MINIMAL_ADMIN_URL . 'dist/css/login.css',
			array( 'login' ),
			MINIMAL_ADMIN_VERSION
		);

		// RTL support for login.
		if ( is_rtl() ) {
			wp_enqueue_style(
				'minimal-admin-login-rtl',
				MINIMAL_ADMIN_URL . 'dist/css/login-rtl.css',
				array( 'minimal-admin-login' ),
				MINIMAL_ADMIN_VERSION
			);
		}
	}

	/**
	 * Add custom body class.
	 *
	 * @param string $classes Existing body classes.
	 * @return string Modified body classes.
	 */
	public function add_body_class( $classes ) {
		return $classes . ' minimal-admin';
	}
}

/**
 * Initialize the plugin.
 */
function minimal_admin_init() {
	Minimal_Admin::get_instance();
}
add_action( 'plugins_loaded', 'minimal_admin_init' );
