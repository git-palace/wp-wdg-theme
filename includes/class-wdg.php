<?php

/*!
 * NOTE: PLEASE DO NOT EDIT THIS FILE
 * EXTEND IT INSTEAD IN FUNCTIONS.PHP
 */

require_once 'theme-constants.php';
require_once 'class-wdg-walker-nav-menu.php';
require_once 'class-theme-string.php';

class WDG {
	public static $body_classes       = array();
	public static $enqueued_scripts   = array();
	public static $enqueued_styles    = array();
	public static $nav_menus          = array();
	public static $registered_scripts = array();
	public static $registered_styles  = array();
	public static $sidebars           = array();
	public static $admin_colors       = array();

	public static function init() {
		self::setup_filters();
		self::setup_actions();
	}

	public static function setup_actions() {
		add_action( 'after_setup_theme', array( __CLASS__, 'setup_theme' ) );

		// give this a high priority so any menus registered from the child class will get registered
		add_action( 'after_setup_theme', array( __CLASS__, 'register_nav_menus' ), 100 );

		add_action( 'after_setup_theme', array( __CLASS__, 'register_styles' ), 10 );
		add_action( 'init', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'init', array( __CLASS__, 'register_scripts' ) );
		add_action( 'init', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'init', array( __CLASS__, 'wp_head_cleanup' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_sidebars' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_widgets' ) );
	}

	public static function setup_filters() {
		add_filter( 'nav_menu_item_id', '__return_empty_string' );
		add_filter( 'nav_menu_link_attributes', array( __CLASS__, 'filter_nav_menu_link_attributes' ), null , 3 );
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'body_class', array( __CLASS__, 'filter_body_class' ) );

		// Remove "Protected:" & "Private:" from the post titles
		add_filter( 'private_title_format', array( __CLASS__, 'filter_private_protected_title_format' ) );
		add_filter( 'protected_title_format', array( __CLASS__, 'filter_private_protected_title_format' ) );
	}

	/**
	 * Actions
	 */
	public static function setup_theme() {
		// See: http://codex.wordpress.org/Function_Reference/add_theme_support
		$features = apply_filters( 'WDG/theme_support', array(
			'post-thumbnails' => null,

			// This feature allows the use of HTML5 markup for the comment forms, search forms and comment lists.
			'html5' => array(
				'caption',
				'comment-form',
				'comment-list',
				'gallery',
				'search-form',
			),

			'title-tag' => null,
		) );

		foreach ( $features as $feature => $args ) {
			if ( $args ) {
				add_theme_support( $feature, $args );
			} else {
				add_theme_support( $feature );
			}
		}
	}

	public static function register_nav_menus() {
		$menus = array();

		foreach ( apply_filters( 'WDG/nav_menus', self::$nav_menus ) as $theme_location => $menu ) {
			$menus[ $theme_location ] = $menu['description'];
		}

		$menus = apply_filters( 'register_nav_menus', $menus );
		register_nav_menus( $menus );
	}

	public static function register_styles() {
		$styles = apply_filters( 'WDG/registered_styles', self::$registered_styles );

		add_action( 'wp_enqueue_scripts', function() use ( $styles ) {
			foreach ( $styles as $id => $style ) {
				$style = apply_filters( 'WDG/register_style', $style );
				$s = self::$registered_styles[ $style['handle'] ];
				wp_register_style( $s['handle'], $s['src'], $s['deps'], $s['ver'], $s['media'] );
			}
		} );

		return $styles;
	}

	public static function enqueue_styles() {
		add_action('wp_enqueue_scripts', function() {
			foreach ( self::$enqueued_styles as $style ) {
				wp_enqueue_style( $style['handle'] );
			}
		});

		return self::$enqueued_styles;
	}

	public static function register_scripts() {
		$scripts = apply_filters( 'WDG/registered_scripts', self::$registered_scripts );

		add_action( 'wp_enqueue_scripts', function() use ( $scripts ) {
			foreach ( $scripts as $id => $script ) {
				$script = apply_filters( 'WDG/register_script', $script );
				$s = self::$registered_scripts[ $script['handle'] ];
				wp_register_script( $s['handle'], $s['src'], $s['deps'], $s['ver'], $s['in_footer'] );

				if ( ! empty( $s['inline'] ) ) {
					foreach ( $s['inline'] as $inline ) {
						wp_add_inline_script( $s['handle'], $inline['data'], $inline['position'] );
					}
				}
			}
		} );

		return $scripts;
	}

	public static function enqueue_scripts() {
		add_action( 'wp_enqueue_scripts', function() {
			foreach ( self::$enqueued_scripts as $script ) {
				wp_enqueue_script( $script['handle'] );
			}
		} );

		return self::$enqueued_scripts;
	}

	public static function deregister_wordpress_jquery() {
		// deregister WordPress jQuery
		add_action( 'wp_enqueue_scripts', function() {
			wp_deregister_script( 'jquery' );
		} );
	}

	/**
	 * Cleans the `wp_head()` action
	 * Code from Bones
	 * See https://github.com/eddiemachado/bones/blob/master/library/bones.php#L32
	 */
	public static function wp_head_cleanup() {
		// category feeds
		remove_action( 'wp_head', 'feed_links_extra', 3 );

		// post and comment feeds
		remove_action( 'wp_head', 'feed_links', 2 );

		// EditURI link
		remove_action( 'wp_head', 'rsd_link' );

		// windows live writer
		remove_action( 'wp_head', 'wlwmanifest_link' );

		// index link
		remove_action( 'wp_head', 'index_rel_link' );

		// previous link
		remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );

		// start link
		remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );

		// links for adjacent posts
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );

		// WP version
		remove_action( 'wp_head', 'wp_generator' );

		// Turn off oEmbed auto discovery.
		remove_filter( 'oembed_dataparse' , 'wp_filter_oembed_result', 10 ); // Don't filter oEmbed results.
		remove_action( 'wp_head' , 'wp_oembed_add_discovery_links' ); // Remove oEmbed discovery links.
		remove_action( 'wp_head' , 'wp_oembed_add_host_js' ); // Remove oEmbed-specific JavaScript from the front-end and back-end.

		// Remove shortlink
		remove_action( 'wp_head' , 'wp_shortlink_wp_head' );

		// remove recentcomments style
		add_action( 'widgets_init', function() {
			global $wp_widget_factory;
			remove_action( 'wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ) );
		} );

		// disable emojis
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', function( $plugins ) {
			return ( is_array( $plugins ) ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
		});
	}

	public static function register_sidebars() {
		$sidebars = apply_filters( 'WDG/sidebars', self::$sidebars );

		foreach ( (array) $sidebars as $id => $sidebar ) {
			register_sidebar( $sidebar );
		}
	}

	public static function register_widgets() {
		self::include_directory( THEME_WIDGETS_PATH );
	}

	/**
	 * Filters
	 */

	public static function filter_nav_menu_link_attributes( $atts, $item, $args ) {
		$classes       = empty( $atts['class'] ) ? array() : explode( ' ', $atts['class'] );
		$classes[]     = 'nav-link';
		$atts['class'] = implode( ' ', $classes );
		return $atts;
	}

	public static function filter_body_class( $classes ) {
		return array_merge( $classes, self::$body_classes );
	}

	public static function filter_private_protected_title_format() {
		return '%s';
	}

	/**
	 * Public Functions
	 */

	/**
	 * Append CSS classes as strings to the `self::$body_class` array
	 * @param string|array $args,...
	 * @return array
	 */
	public static function add_body_class( $args ) {
		$body_classes = self::parse_args( func_get_args() );

		if ( is_wp_error( $body_classes ) ) {
			return $body_classes;
		}

		// merge passed argument with default set of body classes
		$body_classes = array_merge( self::$body_classes, $body_classes );

		// remove duplicates
		$body_classes = array_unique( $body_classes );

		// return value
		return self::$body_classes = apply_filters( 'WDG/add_body_class', $body_classes );
	}

	public static function nav( $handle, $override_args = array() ) {
		if ( ! is_string( $handle ) ) {
			return new WP_Error( 'invalid_argument_type', '`$handle` isn\'t a String', $handle );
		}

		if ( ! is_array( $override_args ) ) {
			return new WP_Error( 'invalid_argument_type', '`$override_args` isn\'t an Array', $override_args );
		}

		if ( isset( self::$nav_menus[ $handle ] ) ) {
			$args = self::$nav_menus[ $handle ];
		} else {
			$args = array( 'menu' => $handle );
			$args = self::defaults_nav_menu( $args );
		}

		$override_args = array_merge( $override_args, array(
			'echo'        => false,
			'fallback_cb' => null,
			'WDG_builder' => true,
		) );

		// Set container CSS class names
		if ( isset( $override_args['container_class'] ) && ! strpos( $override_args['container_class'], $args['container_class'] ) ) {
			$override_args['container_class'] = $args['container_class'] . ' ' . $override_args['container_class'];
		}

		// Set menu CSS class names
		if ( isset( $override_args['menu_class'] ) && ! strpos( $override_args['menu_class'], $args['menu_class'] ) ) {
			$override_args['menu_class'] = $args['menu_class'] . ' ' . $override_args['menu_class'];
		}

		$args = array_merge( $args, $override_args );

		// build menu markup
		$menu = wp_nav_menu( $args );
		// $menu = '<p>test</p>';

		// remove `id=""` attributes
		$menu = preg_replace( '/(id=["\'][^"\']+["\']\ ?)/i', '', $menu );

		// apply filters from theme
		$menu = apply_filters( 'WDG/nav', $menu );

		// return menu
		return $menu;
	}

	/**
	 * Append Navigation Menu items to the `self::$nav_menus` array
	 * @param string|array $args
	 * @return array
	 */
	public static function register_nav_menu( $args ) {
		if ( ! ( is_string( $args ) || is_array( $args ) ) ) {
			return new WP_Error( 'invalid_argument_type', 'Argument isn\'t a String or Array', $args );
		}

		// if $args is a string transform it into `theme_location`
		if ( is_string( $args ) ) {
			$args = strtolower( $args );
			$args = array( 'theme_location' => $args );
		}

		// throw error if `theme_location` isn't available
		if ( is_array( $args ) && ! isset( $args['theme_location'] ) ) {
			return new WP_Error( 'theme_location_missing', '`array("theme_location" => "")` is missing from the argument', $args );
		}

		// merge defaults
		$args = self::defaults_nav_menu( $args );

		// Append menu to `self::$nav_menus`
		self::$nav_menus[ $args['theme_location'] ] = apply_filters( 'WDG/register_nav_menu', $args );

		return $args;
	}

	/**
	 * Creates a sidebar
	 * @param string|array
	 */
	public static function register_sidebar( $args ) {
		if ( ! ( is_string( $args ) || is_array( $args ) ) ) {
			return new WP_Error( 'invalid_argument_type', 'Argument isn\'t a String or Array', $args );
		}

		$defaults = array(
			'name'          => '',
			'id'            => '',
			'description'   => '',
			'class'         => '',
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>',
		);

		// if $args is a string transform it into `id`
		if ( is_string( $args ) ) {
			$args = strtolower( $args );
			$args = array_merge( $defaults, array( 'id' => $args ) );
		}

		// throw error if `id` isn't available
		if ( is_array( $args ) && ! isset( $args['id'] ) ) {
			return new WP_Error( 'id_missing', '`array("id" => "")` is missing from the argument', $args );
		}

		// Merge default data
		$args = array_merge( $defaults, $args );

		// Create a sidebar name from id
		if ( empty( $args['name'] ) ) {
			// Append sidebar to the name
			$args['name'] = ( $args['id'] == 'sidebar' ) ? $args['id'] : $args['id'] . ' sidebar';
			$args['name'] = Theme_String::humanize( $args['name'] );
		}

		// Append menu to `self::$sidebars`
		self::$sidebars[ $args['id'] ] = apply_filters( 'WDG/register_sidebar', $args );

		return $args;
	}

	public static function register_style( $handle, $src, $deps = null, $ver = null, $media = 'screen' ) {
		$path = null;

		if ( ! is_string( $handle ) ) {
			return new WP_Error( 'invalid_argument_type', '`$handle` isn\'t a String', $handle );
		}

		if ( empty( $handle ) ) {
			return new WP_Error( 'invalid_handle', '`$handle` is empty', $handle );
		}

		if ( ! is_string( $src ) ) {
			return new WP_Error( 'invalid_argument_type', '`$src` isn\'t a String', $handle );
		}

		if ( empty( $src ) ) {
			return new WP_Error( 'invalid_src', '`$src` is empty', $handle );
		}

		if ( is_int( strpos( $src, THEME_DIST_URI ) ) ) {
			$path = str_replace( THEME_DIST_URI, THEME_DIST_PATH, $src );
			$path = realpath( $path );
		} elseif ( is_int( strpos( $src, THEME_CSS_URI ) ) ) {
			$path = str_replace( THEME_CSS_URI, THEME_CSS_PATH, $src );
			$path = realpath( $path );
		} elseif ( is_int( strpos( $src, THEME_VENDOR_URI ) ) ) {
			$path = str_replace( THEME_VENDOR_URI, THEME_VENDOR_PATH, $src );
			$path = realpath( $path );
		}

		if ( empty( $ver ) && ! empty ( $path ) ) {
			$ver = self::filemtime( $path );
		}

		$args = array(
			'deps'   => $deps,
			'handle' => $handle,
			'media'  => $media,
			'src'    => $src,
			'ver'    => $ver,
		);

		self::$registered_styles[ $handle ] = $args;

		return $args;
	}

	public static function register_script( $handle, $src, $deps = null, $ver = null, $in_footer = true ) {
		$path = null;

		if ( ! is_string( $handle ) ) {
			return new WP_Error( 'invalid_argument_type', '`$handle` isn\'t a String', $handle );
		}

		if ( empty( $handle ) ) {
			return new WP_Error( 'invalid_handle', '`$handle` is empty', $handle );
		}

		if ( ! is_string( $src ) ) {
			return new WP_Error( 'invalid_argument_type', '`$src` isn\'t a String', $handle );
		}

		if ( empty( $src ) ) {
			return new WP_Error( 'invalid_src', '`$src` is empty', $handle );
		}

		if ( is_int( strpos( $src, THEME_DIST_URI ) ) ) {
			$path = str_replace( THEME_DIST_URI, THEME_DIST_PATH, $src );
			$path = realpath( $path );
		} elseif ( is_int( strpos( $src, THEME_JS_URI ) ) ) {
			$path = str_replace( THEME_JS_URI, THEME_JS_PATH, $src );
			$path = realpath( $path );
		} elseif ( is_int( strpos( $src, THEME_VENDOR_URI ) ) ) {
			$path = str_replace( THEME_VENDOR_URI, THEME_VENDOR_PATH, $src );
			$path = realpath( $path );
		}

		if ( empty( $ver ) && ! empty( $path ) ) {
			$ver = self::filemtime( $path );
		}

		$args = array(
			'deps'      => $deps,
			'handle'    => $handle,
			'in_footer' => $in_footer,
			'src'       => $src,
			'ver'       => $ver,
		);

		self::$registered_scripts[ $handle ] = $args;

		return $args;
	}

	public static function register_script_inline( $handle, $data, $position = 'after' ) {
		if ( ! is_string( $handle ) ) {
			return new WP_Error( 'invalid_argument_type', '`$handle` isn\'t a String', $handle );
		}

		if ( empty( $handle ) ) {
			return new WP_Error( 'invalid_handle', '`$handle` is empty', $handle );
		}

		if ( ! isset( self::$registered_scripts[ $handle ] ) ) {
			return new WP_Error( 'unregistered_handle', '`$handle` isn\'t a registered script', $handle );
		}

		if ( ! isset( self::$registered_scripts[ $handle ]['inline'] ) ) {
			self::$registered_scripts[ $handle ]['inline'] = array();
		}

		self::$registered_scripts[ $handle ]['inline'][] = array(
			'position' => $position,
			'data'     => $data,
		);
	}

	public static function enqueue_style( $handle, $priority = 10 ) {
		if ( ! isset( self::$registered_styles[ $handle ] ) ) {
			return new WP_Error( 'enqueue_style_not_registered', 'Enqueued style is not registered', $handle, self::$registered_styles );
		}

		self::$enqueued_styles[ $handle ] = array(
			'handle'   => $handle,
			'priority' => $priority,
		);

		return self::$enqueued_styles[ $handle ];
	}

	public static function enqueue_script( $handle, $priority = 10 ) {
		if ( ! isset( self::$registered_scripts[ $handle ] ) ) {
			return new WP_Error( 'enqueue_script_not_registered', 'Enqueued script is not registered', $handle, self::$registered_scripts );
		}

		self::$enqueued_scripts[ $handle ] = array(
			'handle'   => $handle,
			'priority' => $priority,
		);

		return self::$enqueued_scripts[ $handle ];
	}


	/**
	 * Utility Functions
	 */

	/**
	 * Include all PHP files from a directory
	 * @param string $dir_path Directory path
	 * @return array List of all included files
	 */
	public static function include_directory( $dir_path ) {
		$path = realpath( $dir_path );
		if ( ! is_dir( $path ) ) {
			return new WP_Error( 'invalid_path', 'Invalid $dir_path, it\'s not a directory' );
		}

		$files = glob( $path . DIRECTORY_SEPARATOR . '*.php' );

		$files = apply_filters( 'WDG/include_directory', $files, $dir_path );

		if ( ! is_array( $files ) ) {
			return;
		}

		foreach ( $files as $file ) {
			if ( ! is_file( $file ) ) {
				continue;
			}

			include_once $file;
		}

		return $files;
	}

	/**
	 * Return printed content with Output Buffering
	 * @param function $fn The function that will write any content to Output Buffering
	 */
	public static function ob( $fn ) {
		// start ouput buffering
		ob_start();

		// echo all content here
		$fn();

		// grab content
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Get a scoped template part
	 * @param string $slug The slug name for the scoped template
	 * @param array $vars Variables to inject into the template
	 * @param bool $echo Output the rendered template or return it
	 * @return string Rendered template if $echo is false
	 */
	public static function get_template_part( $slug, $vars = array(), $echo = false ) {
		// Get a template name
		$template_name = apply_filters( 'WDG/template_part/template_name', $slug . '.php', $slug, $vars );

		if ( ! $template_name ) {
			return;
		}

		// Locate the template
		$templates = array(
			get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_name, // Child theme
			get_template_directory() . DIRECTORY_SEPARATOR . $template_name, // Parent theme
		);

		$templates = apply_filters( 'WDG/template_part/templates', $templates, $template_name, $vars );

		// Search through templates
		foreach ( $templates as $template_path ) {
			if ( file_exists( $template_path ) ) {
				break;
			}
		}

		if ( ! file_exists( $template_path ) ) {
			// Not found!
			return new WP_Error( 'template_not_found', 'Get template part not found: ' . $template_name );
		}

		$__vars = apply_filters( 'WDG/template_part/vars', $vars, $template_path );
		$__template_path = $template_path;

		$output = self::ob( function() use ( $__template_path, $__vars ) {
			extract( $__vars, EXTR_SKIP );
			require $__template_path;
		} );

		if ( $echo ) {
			echo $output;
		}

		return $output;
	}

	/**
	 * Creates HTML attributes from an array of key value pairs
	 * @param array $attributes Attributes to set
	 * @param array $defaults Defaults (optional)
	 * @return string
	 */
	public static function html_attributes( $attributes = array(), $defaults = array() ) {
		// Sanity check
		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		// Sanity check
		if ( ! is_array( $defaults ) ) {
			$defaults = array();
		}

		// Merge and eliminate false values
		$attributes = array_filter( array_merge( $defaults, $attributes ), function ( $value ) {
			return $value !== false;
		});

		// Smush attributes
		$html_attributes = '';
		foreach ( $attributes as $key => $value ) {
			$html_attributes .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}

		return $html_attributes;
	}

	/**
	 * Get Excerpt (how WordPress should)
	 * @uses Filters: the_excerpt, the_content, excerpt_length, excerpt_more, wp_trim_excerpt
	 * @param id|WP_Post $id
	 * @return string
	 */
	public static function get_excerpt( $id = null, $excerpt_length = 55 ) {
		$post = get_post( $id );

		if ( empty( $post ) ) {
			return '';
		}

		$excerpt        = '';
		$excerpt_length = apply_filters( 'excerpt_length', $excerpt_length );
		$excerpt_more   = apply_filters( 'excerpt_more', '&hellip;' );

		// Use the excerpt
		if ( strlen( $post->post_excerpt ) ) {
			$excerpt = $post->post_excerpt;

		// Make an excerpt
		} else {
			$excerpt = $post->post_content;
			$excerpt = strip_shortcodes( $excerpt );
			$excerpt = apply_filters( 'the_content', $excerpt );
			$excerpt = str_replace( ']]>', ']]&gt;', $excerpt );
		}

		$excerpt = apply_filters( 'the_excerpt', $excerpt );

		// let's use 0 as a "show all content" wildcard
		if ( $excerpt_length > 0 ) {
			$excerpt = wp_trim_words( $excerpt, $excerpt_length, $excerpt_more );
		}

		$excerpt = wpautop( $excerpt );

		return apply_filters( 'wp_trim_excerpt', $excerpt );
	}

	/**
	 * Show 404 template
	 * Use this instead of "die()""
	 */
	public static function show_404( ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		get_template_part( 404 );
		exit;
	}

	public static function defaults_nav_menu( $args = array() ) {
		$defaults = array(
			'container'       => 'nav',
			'container_class' => 'nav',
			'description'     => '',
			'menu_class'      => 'nav__menu nav__menu--depth0 menu',
			'theme_location'  => '',
			'walker'          => new WDG_Walker_Nav_Menu,
		);

		// Merge default arguments
		$defaults = apply_filters( 'WDG/register_nav_menu/defaults', $defaults, $args );
		$args     = array_merge( $defaults, $args );

		// Set container CSS class name
		if ( isset( $args['container_class'] ) ) {
			if ( isset( $args['theme_location'] ) && $args['theme_location'] ) {
				$args['container_class'] = $args['container_class'] ? $args['container_class'] . ' ' : '';
				$args['container_class'] .= 'nav--' . $args['theme_location'];
			}

			if ( isset( $args['menu'] ) && $args['menu'] ) {
				$args['container_class'] = $args['container_class'] ? $args['container_class'] . ' ' : '';
				$args['container_class'] .= 'nav--' . $args['menu'];
			}
		}

		// Set menu CSS class names
		if ( is_int( strpos( $args['menu_class'], $defaults['menu_class'] ) ) ) {
			$args['menu_class'] .= ' ' . $defaults['menu_class'] . ' ';
		}

		if ( empty( $args['description'] ) && isset( $args['theme_location'] ) ) {
			$args['description'] = Theme_String::humanize( $args['theme_location'] ) . ' menu';
		}

		return $args;
	}

	/**
	 * Protected Functions
	 */

	protected static function array_flatten( $array, $return = array() ) {
		if( count( $array ) > 1 ) {
			for ( $x = 0; $x < count( $array ); $x++ ) {
				if ( isset( $array[ $x ] ) && is_array( $array[ $x ] ) ) {
					// Y U NO RECURSIVE?!
					$return = self::array_flatten( $array[ $x ], $return );
				} else {
					if ( isset( $array[ $x ] ) ) {
						$return[] = $array[ $x ];
					}
				}
			}
		} else {
			$return = $array;
		}
		return $return;
	}

	protected static function parse_string_args( $arg ) {
		if ( is_string( $arg ) && is_int( strpos( $arg, ' ' ) ) ) {
			$arg = explode( ' ', $arg );
		}

		return $arg;
	}

	protected static function parse_array_args( $args ) {
		$result = array();

		foreach ( $args as $arg ) {
			if ( ! ( is_string( $arg ) || is_array( $arg ) ) ) {
				return new WP_Error( 'invalid_argument_type', 'Argument isn\'t a String or Array', $arg );
			}

			// if $args is a String, transform it to an Array
			$arg = self::parse_string_args( $arg );

			// if $arg is an Array, recursively execute this function to convert all strings into arrays
			if ( is_array( $arg ) && count( $arg ) ) {
				$arg = self::parse_array_args( $arg );
			}

			$result[] = $arg;
		}

		// return a multidimensional array
		return $result;
	}

	protected static function parse_args( $args ) {
		$args = self::parse_array_args( $args );

		if ( is_wp_error( $args ) || empty( $args ) ) {
			return $args;
		}

		// flatten multidimensional array
		$args = self::array_flatten( $args );

		// remove empty values
		$args = array_filter( $args );

		// remove duplicates
		$args = array_unique( $args );

		return $args;
	}

	protected static function filemtime( $path ) {
		if ( file_exists( $path ) ) {
			return filemtime( $path );
		}

		return false;
	}
}
