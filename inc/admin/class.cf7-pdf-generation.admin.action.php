<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.1
 *
 * @package    Cf7_Pdf_Generation
 * @subpackage Cf7_Pdf_Generation/admin
 */
 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Cf7_Pdf_Generation_Admin_Action' ) ){
	
	/**
	*  The Cf7_Pdf_Generation_Admin_Action Class
	*/
	class Cf7_Pdf_Generation_Admin_Action {

		/**
		* Construction
		*/
		function __construct()  {
			add_action( 'admin_enqueue_scripts',array( $this, 'enqueue_styles' ));		
			add_action( 'admin_enqueue_scripts',array( $this, 'enqueue_scripts' ));		
			add_action( 'admin_menu',array( $this, 'wp_cf7_pdf_add_admin' ));		
			add_action( 'admin_print_styles',array( $this, 'wpcf7_pdf_admin_styles' ));		
			add_action( 'admin_print_scripts',array( $this, 'wpcf7_pdf_admin_scripts' ));
			add_action( 'admin_init', array( $this, 'wpcf7_pdf_review_notice' ) );
		}

		/**
		* WP Enqueue Styles
		*/
		function enqueue_styles() {
			wp_enqueue_style( 'main-admin-css', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-generation-admin-min.css', array(), 1.1, 'all' );
			wp_enqueue_style( 'codemirror-css', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-generation-codemirror-min.css', array(), 1.1, 'all' );
			wp_enqueue_style( 'codemirror-theme-3024-night', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-generation-3024-night-min.css', array(), 1.1, 'all' );
			wp_enqueue_style( 'jquery-ui-resize', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-jquery-ui-min.css', array(), 1.1, 'all' );
		}

		/**
		* WP Enqueue Scripts
		*/
		function enqueue_scripts() {
			wp_enqueue_script( 'codemirror', WP_CF7_PDF_URL . 'assets/js/cf7-pdf-generation-codemirror-min.js', array( 'jquery' ), 1.1, false );
			wp_enqueue_script( 'codemirror-javascript',WP_CF7_PDF_URL . 'assets/js/cf7-pdf-generation-codemirror-javascript-min.js', array( 'jquery' ), 1.1, false );
			wp_enqueue_script( 'admin-js', WP_CF7_PDF_URL . 'assets/js/cf7-pdf-generation-admin-min.js', array( 'jquery' ), 1.2, false );
		}

		/**
		* Function for add menu.
		*/
		function wp_cf7_pdf_add_admin() {
			$capability = apply_filters( 'wpcf7pdf_modify_capability', 'administrator' );

			$addPDF = add_submenu_page( 'wpcf7',
			esc_html(__('PDF with CF7', 'cf7-pdf-generation')),
			esc_html(__('PDF with CF7', 'cf7-pdf-generation')),
			$capability, 'wp-cf7-send-pdf',
			'wp_cf7_pdf_dashboard_html_page');
		}

		/**
		* WP enqueue script and style for media upload tool.
		*/
		function wpcf7_pdf_admin_scripts() {    
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_style( 'wp-pointer' );
	 
			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_register_script('my-upload', WP_CF7_PDF_URL .'assets/js/cf7-pdf-generation-admin-upload-script-min.js', array('jquery','media-upload','thickbox'),1.3);
			wp_enqueue_script('my-upload');
			wp_enqueue_script('jquery-ui-resizable');	
 
			/*
			jQuery('.CodeMirror').resizable({
			  resize: function() {
				editor.setSize(jQuery(this).width(), jQuery(this).height());
			  }
			});
			*/
		}

		/**
		* WP enqueue style thickbox.
		*/
		function wpcf7_pdf_admin_styles() {
			wp_enqueue_style('thickbox');
		}

		/**
		 *	Check and Dismiss review message.
		 *
		 *	@since 2.0
		 */
		private function wpcf7_pdf_review_dismissal() {

			
			//delete_site_option( 'wp_wpcf7_pdf_review_dismiss' );
			if ( ! is_admin() || ! current_user_can( 'manage_options' )  || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'wpcf7_pdf-review-nonce' ) || ! isset( $_GET['wp_wpcf7_pdf_review_dismiss'] )) {
				return;
			}
			//echo 'Hey There In wpcf7_pdf_review_dismissal Function';
			add_site_option( 'wp_wpcf7_pdf_review_dismiss_opt', 'yes' );
		}

		/**
		 * Ask users to review our plugin on .org
		 *
		 * @since 2.0
		 * @return boolean false
		 */
		public function wpcf7_pdf_review_notice() {
			
			$this->wpcf7_pdf_review_dismissal();
			$this->wpcf7_pdf_review_prending();

			$activation_time 	= get_site_option( 'wp_wpcf7_pdf_active_time' );
			$review_dismissal	= get_site_option( 'wp_wpcf7_pdf_review_dismiss_opt' );
			
			if ( 'yes' == $review_dismissal ) {
				return;
			}

			if ( ! $activation_time ) {

				$activation_time = time();
				add_site_option( 'wp_wpcf7_pdf_active_time', $activation_time );
			}
			// 1296000 = 15 Days in seconds.
			if ( time() - $activation_time > 648000 ) {
				add_action( 'admin_notices' , array( $this, 'wpcf7_pdf_review_notice_message' ) );
			}
		}
		/**
		 * Set time to current so review notice will popup after 14 days
		 *
		 * @since 2.0
		 */
		function wpcf7_pdf_review_prending() {

			// delete_site_option( 'wp_wpcf7_pdf_review_dismiss' );
			if ( ! is_admin() ||
				! current_user_can( 'manage_options' ) || ! isset( $_GET['_wpnonce'] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'wpcf7_pdf-review-nonce' ) ||
				! isset( $_GET['wp_wpcf7_pdf_review_later'] )  ) {

				return;
			}
			// Reset Time to current time.
			update_site_option( 'wp_wpcf7_pdf_active_time', time() );
		}

		/**
		 * Review notice message
		 *
		 * @since  1.3
		 */
		public function wpcf7_pdf_review_notice_message() {

			$scheme      = (parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY )) ? '&' : '?';
			$url         = $_SERVER['REQUEST_URI'] . $scheme . 'wp_wpcf7_pdf_review_dismiss=yes';
			$dismiss_url = wp_nonce_url( $url, 'wpcf7_pdf-review-nonce' );

			$_later_link = $_SERVER['REQUEST_URI'] . $scheme . 'wp_wpcf7_pdf_review_later=yes';
			$later_url   = wp_nonce_url( $_later_link, 'wpcf7_pdf-review-nonce' );
		?>
			<div class="wpcf7_pdf-review-notice">
				<div class="wpcf7_pdf-review-thumbnail">
					<img src="<?php echo WP_CF7_PDF_URL . 'assets/images/pdf-logo.png' ?>" alt="">
				</div>
				<div class="wpcf7_pdf-review-text">
					<h3><?php _e( 'Leave A Review?', 'wp-wpcf7_pdf' ) ?></h3>
					<p><?php _e( 'We hope you\'ve enjoyed <strong>Generate PDF using Contact Form 7 Plugin!</strong>! Would you consider leaving us a review on WordPress.org?', 'wp-wpcf7_pdf' ) ?></p>
					<ul class="wpcf7_pdf-review-ul"><li><a href="https://wordpress.org/support/plugin/generate-pdf-using-contact-form-7/reviews/" target="_blank"><span class="dashicons dashicons-external"></span><?php _e( 'Sure! I\'d love to!', 'wp-wpcf7_pdf' ) ?></a></li>
		             <li><a href="<?php echo $dismiss_url ?>"><span class="dashicons dashicons-smiley"></span><?php _e( 'I\'ve already left a review', 'wp-wpcf7_pdf' ) ?></a></li>
		             <li><a href="<?php echo $later_url ?>"><span class="dashicons dashicons-calendar-alt"></span><?php _e( 'Maybe Later', 'wp-wpcf7_pdf' ) ?></a></li>
		             <li><a href="<?php echo $dismiss_url ?>"><span class="dashicons dashicons-dismiss"></span><?php _e( 'Never show again', 'wp-wpcf7_pdf' ) ?></a></li></ul>
				</div>
			</div>
		<?php
		}
	}
	
	/**
	* Function for to require HTML of setting page
	*/
	function wp_cf7_pdf_dashboard_html_page() {
		require  WP_CF7_PDF_DIR . 'inc/templates/cf7-pdf-generation.admin.html.php';
	}

	/**
	* Function for plugin loaded
	*/
	add_action( 'plugins_loaded' , function() {
		Cf7_Pdf_Generation()->admin = new Cf7_Pdf_Generation_Admin_Action;
	} );
}