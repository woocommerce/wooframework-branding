<?php
/**
 * Plugin Name: WooFramework Branding
 * Plugin URI: http://woothemes.com/products/wooframework-branding/
 * Description: Well, g'day there! Lets work together to rebrand your copy of the WooFramework using your logo, your icon and your brand name.
 * Version: 1.0.1
 * Author: Matty
 * Author URI: http://woothemes.com/
 * Requires at least: 3.9.1
 * Tested up to: 3.9.1
 *
 * Text Domain: wooframework-branding
 * Domain Path: /languages/
 *
 * @package WooFramework_Branding
 * @category Core
 * @author Matty
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Returns the main instance of WooFramework_Branding to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object WooFramework_Branding
 */
function WooFramework_Branding() {
	return WooFramework_Branding::instance();
} // End WooFramework_Branding()

WooFramework_Branding();

/**
 * Main WooFramework_Branding Class
 *
 * @class WooFramework_Branding
 * @version	1.0.0
 * @since 1.0.0
 * @package	WooFramework_Branding
 * @author Matty
 */
final class WooFramework_Branding {
	/**
	 * WooFramework_Branding The single instance of WooFramework_Branding.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * The admin page slug.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin_page;

	/**
	 * The admin parent page.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin_parent_page;

	/**
	 * The instance of WF_Fields.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	private $_field_obj;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct () {
		$this->token 			= 'wooframework-branding';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.0.1';

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// We need to run this only once the theme is setup and ready.
		add_action( 'after_setup_theme', array( $this, 'init' ) );
	} // End __construct()

	/**
	 * Initialise the plugin.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function init () {
		if ( is_admin() ) {
			// Register the admin screen.
			add_action( 'admin_menu', array( $this, 'register_admin_screen' ) );

			// Register the admin screen to be able to load the WooFramework's CSS and other assets.
			add_filter( 'wf_load_admin_css', array( $this, 'register_screen_id' ) );

			// If applicable, instantiate WF_Fields from the WooFramework.
			if ( defined( 'THEME_FRAMEWORK' ) && 'woothemes' == constant( 'THEME_FRAMEWORK' ) && class_exists( 'WF_Fields' ) ) {
				$this->_field_obj = new WF_Fields();
				$this->_field_obj->init( $this->get_settings_template() );
				$this->_field_obj->__set( 'token', 'framework_woo' );
			}
			// Maybe override the WooFramework settings screen logo.
			add_filter( 'wf_branding_logo', array( $this, 'maybe_override_logo_image_url' ) );

			// Maybe override the WooFramework administration menu icon.
			add_filter( 'wf_branding_icon', array( $this, 'maybe_override_icon_url' ) );

			// Maybe override the WooFramework administration menu label.
			add_action( 'admin_menu', array( $this, 'maybe_override_admin_menu_label' ) );
		}
	} // End init()

	/**
	 * Register the screen ID with the WooFramework's asset loader.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function register_screen_id ( $screens ) {
		if ( ! in_array( 'wf-branding', $screens ) ) {
			$screens[] = 'wf-branding';
		}
		return $screens;
	} // End register_screen_id()

	/**
	 * Register the admin screen within WordPress.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function register_admin_screen () {
		$this->admin_parent_page = 'themes.php';
		if ( defined( 'THEME_FRAMEWORK' ) && 'woothemes' == constant( 'THEME_FRAMEWORK' ) ) {
			$this->admin_parent_page = 'woothemes';
		}

		$this->admin_page = add_submenu_page( $this->admin_parent_page, __( 'Branding', 'wooframework-branding' ), __( 'Branding', 'wooframework-branding' ), 'manage_options', 'wf-branding', array( $this, 'admin_screen' ) );

		// Admin screen logic.
		add_action( 'load-' . $this->admin_page, array( $this, 'admin_screen_logic' ) );

		// Add contextual help tabs.
		add_action( 'load-' . $this->admin_page, array( $this, 'admin_screen_help' ) );

		// Make sure our data is added to the WooFramework settings exporter.
		add_filter( 'wooframework_export_query_inner', array( $this, 'add_exporter_data' ) );

		// Add admin notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	} // End register_admin_screen()

	/**
	 * Load the admin screen markup.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_screen () {
?>
	<div class="wrap wooframework-branding-wrap">
<?php
		// If a WooThemes theme isn't activated, display a notice.
		if ( ! defined( 'THEME_FRAMEWORK' ) || 'woothemes' != constant( 'THEME_FRAMEWORK' ) ) {
			echo '<div class="error fade"><p>' . __( 'It appears your theme does not contain the WooFramework. In order to use the WooFramework Branding, please use a theme which makes use of the WooFramework.', 'wooframework-branding' ) . '</p></div>' . "\n";
		} else {
			// If this is an old version of the WooFramework, display a notice.
			if ( ! class_exists( 'WF_Fields' ) ) {
				echo '<div class="error fade"><p>' . __( 'It appears you\'re using an older version of the WooFramework. WooFramework Branding requires WooFramework 6.0 or higher.', 'wooframework-branding' ) . '</p></div>' . "\n";
			} else {
				// Otherwise, we're good to go!
				$hidden_fields = array( 'page' => 'wf-branding' );
				do_action( 'wf_screen_get_header', 'wf-branding', 'themes' );
				$this->_field_obj->__set( 'has_tabs', false );
				$this->_field_obj->__set( 'extra_hidden_fields', $hidden_fields );
				$this->_field_obj->render();
				do_action( 'wf_screen_get_footer', 'wf-branding', 'themes' );
			}
		}
?>
	</div><!--/.wrap-->
<?php
		// This must be present if using fields that require Javascript or styling.
		add_action( 'admin_footer', array( $this->_field_obj, 'maybe_enqueue_field_assets' ) );
	} // End admin_screen()

	/**
	 * Display admin notices for this settings screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_notices () {
		$notices = array();

		if ( isset( $_GET['page'] ) && 'wf-branding' == $_GET['page'] && isset( $_GET['updated'] ) && 'true' == $_GET['updated'] ) {
			$notices['settings-updated'] = array( 'type' => 'updated', 'message' => __( 'Settings saved.', 'wooframework-branding' ) );
		}

		if ( 0 < count( $notices ) ) {
			$html = '';
			foreach ( $notices as $k => $v ) {
				$html .= '<div id="' . esc_attr( $k ) . '" class="fade ' . esc_attr( $v['type'] ) . '">' . wpautop( '<strong>' . esc_html( $v['message'] ) . '</strong>' ) . '</div>' . "\n";
			}
			echo $html;
		}
	} // End admin_notices()

	/**
	 * Load contextual help for the admin screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  string Modified contextual help string.
	 */
	public function admin_screen_help () {
		$screen = get_current_screen();
		if ( $screen->id != $this->admin_page ) return;

		$overview =
			  '<p>' . __( 'Configure the branding settings and hit the "Save Changes" button. It\'s as easy as that!', 'wooframework-branding' ) . '</p>' .
			  '<p><strong>' . __( 'For more information:', 'wooframework-branding' ) . '</strong></p>' .
			  '<p>' . sprintf( __( '<a href="%s" target="_blank">WooThemes Help Desk</a>', 'wooframework-branding' ), 'http://support.woothemes.com/' ) . '</p>';

		$screen->add_help_tab( array( 'id' => 'wooframework_branding_overview', 'title' => __( 'Overview', 'wooframework-branding' ), 'content' => $overview ) );
	} // End admin_screen_help()

	/**
	 * Logic to run on the admin screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_screen_logic () {
		if ( ! empty( $_POST ) && check_admin_referer( $this->_field_obj->__get( 'token' ) . '_nonce', $this->_field_obj->__get( 'token' ) . '_nonce' ) ) {
			$data = $_POST;

			$page = 'wf-branding';
			if ( isset( $data['page'] ) ) {
				$page = $data['page'];
				unset( $data['page'] );
			}

			$data = $this->_field_obj->validate_fields( $data );

			if ( 0 < count( $data ) ) {
				foreach ( $data as $k => $v ) {
					update_option( $k, $v );
				}
			}

			// Keep track of the last username to edit the branding screen, so as least one user is never locked out. :)
			$user_id = get_current_user_id();
			update_option( 'framework_woo_last_branding_editor', intval( $user_id ) );

			// Redirect on settings save, and exit.
			$url = add_query_arg( 'page', $page );
			$url = add_query_arg( 'updated', 'true', $url );

			wp_safe_redirect( $url );
			exit;
		}
	} // End admin_screen_logic()

	/**
	 * Maybe override the logo image URL.
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function maybe_override_logo_image_url ( $url ) {
		$image_url = get_option( 'framework_woo_backend_header_image', '' );
		if ( '' != $image_url ) {
			$url = esc_url( $image_url );
		}
		return $url;
	} // End maybe_override_logo_image_url()

	/**
	 * Maybe override the icon URL.
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function maybe_override_icon_url ( $url ) {
		$image_url = get_option( 'framework_woo_backend_icon', '' );
		if ( '' != $image_url ) {
			$url = esc_url( $image_url );
		}
		return $url;
	} // End maybe_override_icon_url()

	/**
	 * Maybe override the menu label.
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function maybe_override_admin_menu_label () {
		global $menu;
		$label = get_option( 'framework_woo_menu_label', '' );
		if ( '' != $label && 0 < count( (array)$menu ) ) {
			foreach ( $menu as $k => $v ) {
				if ( isset( $v[0] ) && isset( $v[2] ) && 'woothemes' == $v[2] ) {
					$menu[$k][0] = esc_html( $label );
				}
			}
		}
	} // End maybe_override_admin_menu_label()

	/**
	 * Return an array of the settings scafolding. The field types, names, etc.
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function get_settings_template () {
		return array(
				// We must have a heading, so the fields can be assigned a section, and display correctly. :)
				'woo_branding_heading' => array(
										'name' => __( 'Branding', 'wooframework-branding' ),
										'std' => '',
										'id' => 'woo_branding_heading',
										'type' => 'heading'
										),
				'framework_woo_backend_header_image' => array(
										'name' => __( 'Your Logo Image', 'wooframework-branding' ),
										'desc' => __( 'Your logo image, for use on all WooFramework screens.', 'wooframework-branding' ),
										'std' => '',
										'id' => 'framework_woo_backend_header_image',
										'type' => 'upload'
										),
				'framework_woo_backend_icon' => array(
										'name' => __( 'Your Logo Icon', 'wooframework-branding' ),
										'desc' => __( 'Your logo icon, for the WordPress administration menu.', 'wooframework-branding' ),
										'std' => '',
										'id' => 'framework_woo_backend_icon',
										'type' => 'upload'
										),
				'framework_woo_menu_label' => array(
										'name' => __( 'Admin Menu Label', 'wooframework-branding' ),
										'desc' => sprintf( __( 'The label of the %1$s administration menu. Leave empty for the default menu label.', 'wooframework-branding' ), wp_get_theme()->__get( 'Name' ) ),
										'std' => '',
										'id' => 'framework_woo_menu_label',
										'type' => 'text'
										)
				);
	} // End get_settings_template()

	/**
	 * Main WooFramework_Branding Instance
	 *
	 * Ensures only one instance of WooFramework_Branding is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WooFramework_Branding()
	 * @return Main WooFramework_Branding instance
	 */
	public static function instance () {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'wooframework-branding', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // End load_plugin_textdomain()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __wakeup()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install()

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	} // End _log_version_number()

	/**
 	 * Add our saved data to the WooFramework data exporter.
 	 * @access  public
	 * @since   1.0.1
 	 * @param   string $data SQL query.
 	 * @return  string SQL query.
 	 */
	public function add_exporter_data ( $data ) {
		$option_keys = array(
								'framework_woo_last_branding_editor',
								'framework_woo_backend_header_image',
								'framework_woo_backend_icon',
								'framework_woo_menu_label'
								);
		foreach ( $option_keys as $key ) {
			$data .= " OR option_name = '" . $key . "'";
		} // End For Loop
		return $data;
	} // End add_exporter_data()

} // End Class
?>