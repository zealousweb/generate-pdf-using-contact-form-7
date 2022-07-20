<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that inc attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       #
 * @since      1.1
 *
 * @package    Cf7_Pdf_Generation
 * @subpackage Cf7_Pdf_Generation/inc
 */

class Cf7_Pdf_Generation {
	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.1
	 */
	
	private static $_instance = null;

	public static function instance() {

		if ( is_null( self::$_instance ) )
			self::$_instance = new self();

		return self::$_instance;
	}
		
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'action__plugins_loaded' ) );
		add_action( 'admin_init', array( $this, 'check_plugin_state' ) );

	}

	/**
	* Check plugin state (activate or deactivate).
	*/
	function check_plugin_state()
	{
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if (! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) )
		{
			add_action( 'admin_notices', array( $this, 'activate_notice_Cf7_Pdf_Generation' ) );
			deactivate_plugins(WP_CF7_PDF_PLUGIN_BASENAME );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
	}

	/**
	* Action function plugin loaded.
	*/
	function action__plugins_loaded() {
		global $wp_version;
		$cf7pdf_lang_dir = dirname( WP_CF7_PDF_PLUGIN_BASENAME ) . '/languages/';
		$cf7pdf_lang_dir = apply_filters( 'wp_cf7_pdf-operation_languages_directory', $cf7pdf_lang_dir );

		$get_locale = get_locale();

		if ( $wp_version >= 4.7 ) {
			$get_locale = get_user_locale();
		}

		$locale = apply_filters( 'plugin_locale',  $get_locale, 'cf7-pdf-generation' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'cf7-pdf-generation', $locale );

		$mofile_global = WP_LANG_DIR . '/plugins/' . basename( WP_CF7_PDF_DIR ) . '/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			load_textdomain( 'cf7-pdf-generation', $mofile_global );
		} else {
			load_plugin_textdomain( 'cf7-pdf-generation', false, $cf7pdf_lang_dir );
		}
	}

	/**
	* Admin notice of activate pugin.
	*/
	function activate_notice_Cf7_Pdf_Generation() {
	?>
		<div class="error">
			<p><?php esc_html(__( '<b>Generate PDF using Contact Form 7 :</b> Contact Form 7 is not active! Please install <a target="_blank" href="https://wordpress.org/plugins/contact-form-7/">Contact Form 7</a>.', 'cf7-pdf-generation' )); ?></p>
		</div>
	<?php
	}
}

function Cf7_Pdf_Generation() {
	return Cf7_Pdf_Generation::instance();
}
Cf7_Pdf_Generation();