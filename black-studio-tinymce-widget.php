<?php
/*
Plugin Name: Black Studio TinyMCE Widget
Plugin URI: https://wordpress.org/plugins/black-studio-tinymce-widget/
Description: Adds a WYSIWYG widget based on the standard TinyMCE WordPress visual editor.
Version: 2.0.0
Author: Black Studio
Author URI: http://www.blackstudio.it
Requires at least: 3.1
Tested up to: 4.0
License: GPLv3
Text Domain: black-studio-tinymce-widget
Domain Path: /languages
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @package Black_Studio_TinyMCE_Widget
 * @since 2.0.0
 */

if ( ! class_exists( 'Black_Studio_TinyMCE_Plugin' ) ) {

	final class Black_Studio_TinyMCE_Plugin {

		/**
		 * Plugin version
		 *
		 * @var string
		 * @since 2.0.0
		 */
		public static $version = '2.0.0';

		/**
		 * The single instance of the plugin class
		 *
		 * @var object
		 * @since 2.0.0
		 */
		protected static $_instance = null;

		/**
		 * Instance of admin class
		 *
		 * @var object
		 * @since 2.0.0
		 */
		protected static $admin = null;

		/**
		 * Instance of compatibility class for 3rd party plugins
		 *
		 * @var object
		 * @since 2.0.0
		 */
		protected static $compat_plugins = null;

		/**
		 * Instance of compatibility class for WordPress old versions
		 *
		 * @var object
		 * @since 2.0.0
		 */
		protected static $compat_wordpress = null;

		/**
		 * Return the main plugin instance
		 *
		 * @return object
		 * @since 2.0.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Return the instance of the admin class
		 *
		 * @return object
		 * @since 2.0.0
		 */
		public static function admin() {
			return self::$admin;
		}

		/**
		 * Return the instance of the compatibility class for 3rd party plugins
		 *
		 * @return object
		 * @since 2.0.0
		 */
		public static function compat_plugins() {
			return self::$compat_plugins;
		}

		/**
		 * Return the instance of the compatibility class for WordPress old versions
		 *
		 * @return object
		 * @since 2.0.0
		 */
		public static function compat_wordpress() {
			return self::$compat_wordpress;
		}

		/**
		 * Get plugin version
		 *
		 * @return string
		 * @since 2.0.0
		 */
		public static function get_version() {
			return self::$version;
		}

		/**
		 * Class constructor
		 *
		 * @uses add_action()
		 * @uses add_filter()
		 * @uses get_option()
		 * @uses get_bloginfo()
		 *
		 * @global object $wp_embed
		 * @return void
		 * @since 2.0.0
		 */
		protected function __construct() {
			// Include required file(s)
			include_once( plugin_dir_path( __FILE__ ) . '/includes/class-wp-widget-black-studio-tinymce.php' );
			// Include and instantiate admin class on admin pages
			if ( is_admin() ) {
				include_once( plugin_dir_path( __FILE__ ) . '/includes/class-admin.php' );
				self::$admin = Black_Studio_TinyMCE_Admin::instance();
			}
			// Register action and filter hooks
			add_action( 'plugins_loaded', array( $this, 'compatibility' ), 20 );
			add_action( 'widgets_init', array( $this, 'widgets_init' ) );
			// Support for autoembed urls in widget text
			if ( get_option( 'embed_autourls' ) ) {
				add_filter( 'widget_text', array( $this, 'widget_text_autoembed' ), 10, 3 );
			}
			// Support for smilies in widget text
			if ( get_option( 'use_smilies' ) ) {
				add_filter( 'widget_text', array( $this, 'widget_text_convert_smilies' ), 20, 3 );
			}
			// Support for wpautop in widget text
			add_filter( 'widget_text', array( $this, 'widget_text_wpautop' ), 30, 3 );
			// Support for shortcodes in widget text
			add_filter( 'widget_text', array( $this, 'widget_text_do_shortcode' ), 40, 3 );
		}

		/**
		 * Prevent the class from being cloned
		 *
		 * @return void
		 * @since 2.0.0
		 */
		protected function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; uh?' ), '2.0' );
		}

		/**
		 * Include compatibility code
		 *
		 * @uses apply_filters()
		 * @uses get_bloginfo()
		 * @uses plugin_dir_path()
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function compatibility() {
			// Compatibility load flag (for both deprecated functions and code for compatibility with other plugins)
			$load_compatibility = apply_filters( 'black_studio_tinymce_load_compatibility', true );
			// Compatibility with previous BSTW versions
			$load_deprecated = apply_filters( 'black_studio_tinymce_load_deprecated', true );
			if ( $load_compatibility && $load_deprecated ) {
				include_once( plugin_dir_path( __FILE__ ) . '/includes/deprecated.php' );
			}
			// Compatibility with other plugins
			$compat_plugins = apply_filters( 'black_studio_tinymce_load_compatibility_plugins', array( 'siteorigin_panels', 'wpml', 'jetpack_after_the_deadline', 'wp_page_widget' ) );
			if ( $load_compatibility && ! empty( $compat_plugins ) ) {
				include_once( plugin_dir_path( __FILE__ ) . '/includes/class-compatibility-plugins.php' );
				self::$compat_plugins = Black_Studio_TinyMCE_Compatibility_Plugins::instance( $compat_plugins );
			}
			// Compatibility with previous WordPress versions
			if ( version_compare( get_bloginfo( 'version' ), '3.9', '<' ) ) {
				include_once( plugin_dir_path( __FILE__ ) . '/includes/class-compatibility-wordpress.php' );
				self::$compat_wordpress = Black_Studio_TinyMCE_Compatibility_Wordpress::instance();
			}
		}

		/**
		 * Widget initialization
		 *
		 * @uses is_blog_installed()
		 * @uses register_widget()
		 *
		 * @return null|void
		 * @since 2.0.0
		 */
		public function widgets_init() {
			if ( ! is_blog_installed() ) {
				return;
			}
			register_widget( 'WP_Widget_Black_Studio_TinyMCE' );
		}

		/**
		 * TinyMCE setup customization
		 * This method is deprecated but it is kept for compatibility reasons as it was present in 2.0.0 pre-release
		 *
		 * @param mixed[] $settings
		 * @return mixed[]
		 * @since 2.0.0
		 * @deprecated 2.0.0
		 */
		public function tiny_mce_before_init( $settings ) {
			_deprecated_function( __FUNCTION__, '2.0.0' );
			return $settings;
		}

		/**
		 * Apply auto_embed to widget text
		 *
		 * @param string $text
		 * @return string
		 * @since 2.0.0
		 */
		public function widget_text_autoembed( $text, $instance, $widget = null ) {
			if ( bstw()->check_widget( $widget ) && ! empty( $instance ) ) {
				global $wp_embed;
				$text = $wp_embed->run_shortcode( $text );
				$text = $wp_embed->autoembed( $text );
			}
			return $text;
		}

		/**
		 * Apply smilies conversion to widget text
		 *
		 * @param string $text
		 * @return string
		 * @since 2.0.0
		 */
		public function widget_text_convert_smilies( $text, $instance, $widget = null ) {
			if ( bstw()->check_widget( $widget ) && ! empty( $instance ) ) {
				$text = convert_smilies( $text );
			}
			return $text;
		}

		/**
		 * Apply automatic paragraphs in widget text
		 *
		 * @param string $text
		 * @return string
		 * @since 2.0.0
		 */
		public function widget_text_wpautop( $text, $instance, $widget = null ) {
			if ( bstw()->check_widget( $widget ) && ! empty( $instance ) ) {
				$text = wpautop( $text );
			}
			return $text;
		}

		/**
		 * Process shortcodes in widget text
		 *
		 * @param string $text
		 * @return string
		 * @since 2.0.0
		 */
		public function widget_text_do_shortcode( $text, $instance, $widget = null ) {
			if ( bstw()->check_widget( $widget ) && ! empty( $instance ) ) {
				$text = do_shortcode( $text );
			}
			return $text;
		}

		/**
		 * Check if a widget is a Black Studio Tinyme Widget instance
		 *
		 * @param object $widget
		 * @return boolean
		 * @since 2.0.0
		 */
		public function check_widget( $widget ) {
			return gettype( $widget ) == 'object' && get_class( $widget ) == 'WP_Widget_Black_Studio_TinyMCE';
		}
		
	} // END class Black_Studio_TinyMCE_Plugin

} // class_exists check

/**
 * Return the main instance to prevent the need to use globals
 *
 * @return object
 * @since 2.0.0
 */
function bstw() {
	return Black_Studio_TinyMCE_Plugin::instance();
}

/* Create the main instance */
bstw();
