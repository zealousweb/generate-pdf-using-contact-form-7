<?php
/**
 * PDF submission storage and admin list table.
 *
 * @package Cf7_Pdf_Generation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Cf7_Pdf_Submissions' ) ) {

	/**
	 * Handles cf7pdf_data posts and list columns.
	 */
	class Cf7_Pdf_Submissions {

		const META_FORM_ID         = '_cf7pdf_form_id';
		const META_PDF_URL         = '_cf7pdf_pdf_url';
		const META_PDF_PATH        = '_cf7pdf_pdf_path';
		const META_SUBMITTED_DATE  = '_cf7pdf_submitted_date';

		const LEGACY_FORM_ID       = '_form_id';
		const LEGACY_PDF_URL       = '_pdf_link';
		const LEGACY_PDF_PATH      = '_pdf_path';

		/** Minimum characters for PDF open password (mPDF). */
		const MIN_PDF_PASSWORD_LENGTH = 4;

		/**
		 * Register hooks (always — not gated on is_admin at load time).
		 */
		public static function init() {
			add_action( 'plugins_loaded', array( __CLASS__, 'register_admin_hooks' ) );
		}

		/**
		 * Admin-only list table and row actions.
		 */
		public static function register_admin_hooks() {
			if ( ! is_admin() ) {
				return;
			}

			add_filter( 'manage_' . Cf7_Pdf_Cpt::POST_TYPE . '_posts_columns', array( __CLASS__, 'filter_columns' ) );
			add_action( 'manage_' . Cf7_Pdf_Cpt::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
			add_filter( 'manage_edit-' . Cf7_Pdf_Cpt::POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
			add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
			add_filter( 'bulk_actions-edit-' . Cf7_Pdf_Cpt::POST_TYPE, array( __CLASS__, 'bulk_actions' ) );
			add_action( 'admin_init', array( __CLASS__, 'upgrade_private_submissions_to_publish' ) );
			add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
			add_action( 'add_meta_boxes', array( __CLASS__, 'remove_default_meta_boxes' ), 99 );
		}

		/**
		 * Submission details on the edit screen (CPT has no content editor).
		 */
		public static function register_meta_boxes() {
			add_meta_box(
				'cf7pdf-submission-details',
				__( 'PDF Submission Details', 'generate-pdf-using-contact-form-7' ),
				array( __CLASS__, 'render_details_meta_box' ),
				Cf7_Pdf_Cpt::POST_TYPE,
				'normal',
				'high'
			);
		}

		/**
		 * Remove meta boxes that do not apply to log entries.
		 */
		public static function remove_default_meta_boxes() {
			remove_meta_box( 'slugdiv', Cf7_Pdf_Cpt::POST_TYPE, 'normal' );
			remove_meta_box( 'commentstatusdiv', Cf7_Pdf_Cpt::POST_TYPE, 'normal' );
			remove_meta_box( 'commentsdiv', Cf7_Pdf_Cpt::POST_TYPE, 'normal' );
			remove_meta_box( 'authordiv', Cf7_Pdf_Cpt::POST_TYPE, 'normal' );
		}

		/**
		 * @param WP_Post $post Current post.
		 */
		public static function render_details_meta_box( $post ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$form_id      = absint( self::get_meta( $post->ID, self::META_FORM_ID ) );
			$form_name    = self::get_form_name( $form_id );
			$pdf_url      = self::get_meta( $post->ID, self::META_PDF_URL );
			$submitted    = self::get_meta( $post->ID, self::META_SUBMITTED_DATE );
			$submitted_ts = $submitted ? strtotime( $submitted ) : strtotime( $post->post_date );
			$submitted_display = $submitted_ts ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $submitted_ts ) : '—';

			?>
			<table class="form-table cf7pdf-submission-details" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Form Name', 'generate-pdf-using-contact-form-7' ); ?></th>
						<td>
							<?php
							if ( $form_name ) {
								echo esc_html( $form_name );
								if ( $form_id ) {
									printf(
										' <span class="description">(%s)</span>',
										esc_html(
											sprintf(
												/* translators: %d: Contact Form 7 form post ID. */
												__( 'ID: %d', 'generate-pdf-using-contact-form-7' ),
												$form_id
											)
										)
									);
								}
							} else {
								echo esc_html( '—', 'generate-pdf-using-contact-form-7' );
							}
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Submitted', 'generate-pdf-using-contact-form-7' ); ?></th>
						<td><?php echo esc_html( $submitted_display ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'PDF', 'generate-pdf-using-contact-form-7' ); ?></th>
						<td>
							<?php if ( ! empty( $pdf_url ) ) : ?>
								<p>
									<a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View PDF', 'generate-pdf-using-contact-form-7' ); ?></a>
									|
									<a href="<?php echo esc_url( $pdf_url ); ?>" download><?php esc_html_e( 'Download PDF', 'generate-pdf-using-contact-form-7' ); ?></a>
								</p>
								<p class="description"><?php echo esc_html( $pdf_url ); ?></p>
							<?php else : ?>
								<?php esc_html_e( 'No PDF file is stored for this submission.', 'generate-pdf-using-contact-form-7' ); ?>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="description">
				<?php esc_html_e( 'This entry is a PDF log only. Use the title field above to label the submission (for example, the submitter email).', 'generate-pdf-using-contact-form-7' ); ?>
			</p>
			<?php
		}

		/**
		 * One-time: publish existing private submission posts (removes "— Private" in list).
		 */
		public static function upgrade_private_submissions_to_publish() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( get_option( 'cf7pdf_submissions_public_status_upgraded' ) ) {
				return;
			}

			$post_ids = get_posts(
				array(
					'post_type'      => Cf7_Pdf_Cpt::POST_TYPE,
					'post_status'    => 'private',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			foreach ( $post_ids as $post_id ) {
				wp_update_post(
					array(
						'ID'          => (int) $post_id,
						'post_status' => 'publish',
					)
				);
			}

			update_option( 'cf7pdf_submissions_public_status_upgraded', '1', false );
		}

		/**
		 * Create a submission after PDF generation succeeds.
		 *
		 * @param int    $form_id   CF7 form ID.
		 * @param string $pdf_url   Public PDF URL.
		 * @param string $pdf_path  Filesystem path to PDF.
		 * @param string $title     Optional post title.
		 * @param array  $form_data Optional posted field data for preview/history.
		 * @return int|false Post ID or false.
		 */
		public static function create_submission( $form_id, $pdf_url, $pdf_path, $title = '', $form_data = array() ) {
			$form_id  = absint( $form_id );
			$pdf_url  = esc_url_raw( $pdf_url );
			$pdf_path = sanitize_text_field( $pdf_path );

			if ( ! $form_id || empty( $pdf_url ) ) {
				return false;
			}

			if ( class_exists( 'Cf7_Pdf_Pdf_Builder' ) && ! Cf7_Pdf_Pdf_Builder::is_pdf_operation_enabled_for_form( $form_id ) ) {
				return false;
			}

			if ( ! empty( $pdf_path ) && ! file_exists( $pdf_path ) ) {
				return false;
			}

			if ( '' === $title ) {
				$form_post = get_post( $form_id );
				$title     = $form_post ? $form_post->post_title : __( 'PDF Submission', 'generate-pdf-using-contact-form-7' );
				$title    .= ' — ' . current_time( 'mysql' );
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => Cf7_Pdf_Cpt::POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => sanitize_text_field( $title ),
					'post_date'   => current_time( 'mysql' ),
				),
				true
			);

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				return false;
			}

			$submitted = current_time( 'mysql' );

			update_post_meta( $post_id, self::META_FORM_ID, $form_id );
			update_post_meta( $post_id, self::META_PDF_URL, $pdf_url );
			update_post_meta( $post_id, self::META_PDF_PATH, $pdf_path );
			update_post_meta( $post_id, self::META_SUBMITTED_DATE, $submitted );

			update_post_meta( $post_id, self::LEGACY_FORM_ID, $form_id );
			update_post_meta( $post_id, self::LEGACY_PDF_URL, $pdf_url );
			if ( ! empty( $pdf_path ) ) {
				update_post_meta( $post_id, self::LEGACY_PDF_PATH, $pdf_path );
			}

			if ( is_array( $form_data ) && ! empty( $form_data ) ) {
				update_post_meta( $post_id, '_form_data', $form_data );
			}

			return (int) $post_id;
		}

		/**
		 * @param int    $post_id Post ID.
		 * @param string $key     Meta key constant.
		 * @return mixed
		 */
		public static function get_meta( $post_id, $key ) {
			$value = get_post_meta( $post_id, $key, true );

			if ( '' !== $value && false !== $value ) {
				return $value;
			}

			switch ( $key ) {
				case self::META_FORM_ID:
					return get_post_meta( $post_id, self::LEGACY_FORM_ID, true );
				case self::META_PDF_URL:
					return get_post_meta( $post_id, self::LEGACY_PDF_URL, true );
				case self::META_PDF_PATH:
					return get_post_meta( $post_id, self::LEGACY_PDF_PATH, true );
				default:
					return '';
			}
		}

		/**
		 * List table columns.
		 *
		 * @param array $columns Default columns.
		 * @return array
		 */
		public static function filter_columns( $columns ) {
			$new = array();

			if ( isset( $columns['cb'] ) ) {
				$new['cb'] = $columns['cb'];
			}

			$new['title']              = __( 'Title', 'generate-pdf-using-contact-form-7' );
			$new['cf7pdf_form_name']   = __( 'Form Name', 'generate-pdf-using-contact-form-7' );
			$new['cf7pdf_download']    = __( 'Download PDF', 'generate-pdf-using-contact-form-7' );
			$new['cf7pdf_view']        = __( 'View PDF', 'generate-pdf-using-contact-form-7' );

			return $new;
		}

		/**
		 * @param array $columns Sortable columns.
		 * @return array
		 */
		public static function sortable_columns( $columns ) {
			$columns['cf7pdf_form_name'] = self::META_FORM_ID;
			return $columns;
		}

		/**
		 * Resolve CF7 form title from stored form ID.
		 *
		 * @param int $form_id CF7 form post ID.
		 * @return string
		 */
		public static function get_form_name( $form_id ) {
			$form_id = absint( $form_id );

			if ( ! $form_id ) {
				return '';
			}

			if ( class_exists( 'WPCF7_ContactForm' ) ) {
				$form = WPCF7_ContactForm::get_instance( $form_id );
				if ( $form ) {
					return $form->title();
				}
			}

			$title = get_the_title( $form_id );

			return is_string( $title ) ? $title : '';
		}

		/**
		 * @param string $column  Column key.
		 * @param int    $post_id Post ID.
		 */
		public static function render_column( $column, $post_id ) {
			switch ( $column ) {
				case 'cf7pdf_form_name':
					$form_id   = self::get_meta( $post_id, self::META_FORM_ID );
					$form_name = self::get_form_name( $form_id );

					if ( $form_name ) {
						echo esc_html( $form_name );
					} else {
						echo esc_html( '—', 'generate-pdf-using-contact-form-7' );
					}
					break;

				case 'cf7pdf_download':
					self::render_pdf_link( $post_id, 'download' );
					break;

				case 'cf7pdf_view':
					self::render_pdf_link( $post_id, 'view' );
					break;
			}
		}

		/**
		 * @param int    $post_id Post ID.
		 * @param string $mode    download|view.
		 */
		private static function render_pdf_link( $post_id, $mode ) {
			$pdf_url = self::get_meta( $post_id, self::META_PDF_URL );

			if ( empty( $pdf_url ) ) {
				echo esc_html( '—', 'generate-pdf-using-contact-form-7' );
				return;
			}

			if ( 'view' === $mode ) {
				printf(
					'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
					esc_url( $pdf_url ),
					esc_html__( 'View PDF', 'generate-pdf-using-contact-form-7' )
				);
				return;
			}

			printf(
				'<a href="%1$s" download>%2$s</a>',
				esc_url( $pdf_url ),
				esc_html__( 'Download PDF', 'generate-pdf-using-contact-form-7' )
			);
		}

		/**
		 * @param array   $actions Row actions.
		 * @param WP_Post $post    Post object.
		 * @return array
		 */
		public static function row_actions( $actions, $post ) {
			if ( Cf7_Pdf_Cpt::POST_TYPE !== $post->post_type ) {
				return $actions;
			}

			unset( $actions['inline hide-if-no-js'] );
			return $actions;
		}

		/**
		 * @param array $actions Bulk actions.
		 * @return array
		 */
		public static function bulk_actions( $actions ) {
			unset( $actions['edit'] );
			return $actions;
		}

		/**
		 * Whether encrypted password is stored in form settings.
		 *
		 * @param array $settings Form PDF meta.
		 * @return bool
		 */
		public static function has_stored_password( $settings ) {
			return is_array( $settings ) && ! empty( $settings['cf7_opt_password_pdf'] );
		}

		/**
		 * Allowed HTML tags for the PDF message body (mPDF template).
		 *
		 * @return array
		 */
		public static function get_pdf_msg_body_allowed_html() {
			$cf7pdf_allowed = wp_kses_allowed_html( 'post' );

			$cf7pdf_layout_attrs = array(
				'style' => true,
				'class' => true,
				'id'    => true,
			);

			$cf7pdf_allowed['html']  = $cf7pdf_layout_attrs;
			$cf7pdf_allowed['head']  = array();
			$cf7pdf_allowed['body']  = $cf7pdf_layout_attrs;
			$cf7pdf_allowed['title'] = array();
			$cf7pdf_allowed['meta']  = array(
				'charset' => true,
				'name'    => true,
				'content' => true,
			);
			$cf7pdf_allowed['style'] = array(
				'type' => true,
			);
			$cf7pdf_allowed['link']  = array(
				'rel'  => true,
				'href' => true,
				'type' => true,
			);
			$cf7pdf_allowed['form']  = array_merge(
				isset( $cf7pdf_allowed['form'] ) ? $cf7pdf_allowed['form'] : array(),
				array(
					'action' => true,
					'method' => true,
				),
				$cf7pdf_layout_attrs
			);
			$cf7pdf_allowed['input'] = array_merge(
				isset( $cf7pdf_allowed['input'] ) ? $cf7pdf_allowed['input'] : array(),
				array(
					'type'        => true,
					'name'        => true,
					'value'       => true,
					'placeholder' => true,
					'required'    => true,
					'checked'     => true,
				),
				$cf7pdf_layout_attrs
			);
			$cf7pdf_allowed['label'] = array_merge(
				isset( $cf7pdf_allowed['label'] ) ? $cf7pdf_allowed['label'] : array(),
				array(
					'for' => true,
				),
				$cf7pdf_layout_attrs
			);

			foreach ( array( 'div', 'span', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'tr', 'td', 'th', 'thead', 'tbody', 'tfoot', 'ul', 'ol', 'li', 'a', 'img', 'hr', 'br', 'strong', 'em', 'b', 'i' ) as $cf7pdf_tag ) {
				if ( ! isset( $cf7pdf_allowed[ $cf7pdf_tag ] ) || ! is_array( $cf7pdf_allowed[ $cf7pdf_tag ] ) ) {
					$cf7pdf_allowed[ $cf7pdf_tag ] = array();
				}
				$cf7pdf_allowed[ $cf7pdf_tag ] = array_merge( $cf7pdf_allowed[ $cf7pdf_tag ], $cf7pdf_layout_attrs );
			}

			return $cf7pdf_allowed;
		}

		/**
		 * Strip dangerous CSS while preserving mPDF layout rules.
		 *
		 * @param string $style_block Full <style>...</style> block.
		 * @return string
		 */
		private static function sanitize_pdf_style_block( $style_block ) {
			if ( ! preg_match( '/<style\b[^>]*>(.*?)<\/style>/is', $style_block, $cf7pdf_matches ) ) {
				return '';
			}

			$cf7pdf_css = $cf7pdf_matches[1];
			$cf7pdf_css = preg_replace( '/expression\s*\(/i', '', $cf7pdf_css );
			$cf7pdf_css = preg_replace( '/javascript\s*:/i', '', $cf7pdf_css );
			$cf7pdf_css = preg_replace( '/@import\b/i', '', $cf7pdf_css );

			return '<style>' . $cf7pdf_css . '</style>';
		}

		/**
		 * Extract <style> blocks from HTML and return [ styles, remainder ].
		 *
		 * @param string $html HTML source.
		 * @return array{0:string,1:string}
		 */
		private static function extract_pdf_style_blocks( $html ) {
			$cf7pdf_styles = '';

			if ( preg_match_all( '/<style\b[^>]*>.*?<\/style>/is', $html, $cf7pdf_matches ) && ! empty( $cf7pdf_matches[0] ) ) {
				$cf7pdf_safe_styles = array();

				foreach ( $cf7pdf_matches[0] as $cf7pdf_style_block ) {
					$cf7pdf_safe_style = self::sanitize_pdf_style_block( $cf7pdf_style_block );
					if ( '' !== $cf7pdf_safe_style ) {
						$cf7pdf_safe_styles[] = $cf7pdf_safe_style;
					}
				}

				$cf7pdf_styles = implode( "\n", $cf7pdf_safe_styles );
				$html            = preg_replace( '/<style\b[^>]*>.*?<\/style>/is', '', $html );
			}

			return array( $cf7pdf_styles, $html );
		}

		/**
		 * Prepare stored PDF message HTML for mPDF (styles + body fragment).
		 *
		 * @param string $html Stored message body HTML.
		 * @return string
		 */
		public static function prepare_pdf_msg_body_for_mpdf( $html ) {
			$cf7pdf_html = trim( (string) $html );

			if ( '' === $cf7pdf_html ) {
				return '';
			}

			list( $cf7pdf_styles, $cf7pdf_html ) = self::extract_pdf_style_blocks( $cf7pdf_html );

			$cf7pdf_html = preg_replace( '/^\s*<!DOCTYPE[^>]*>\s*/i', '', $cf7pdf_html );

			$cf7pdf_body = '';
			if ( preg_match( '/<body[^>]*>(.*)<\/body>/is', $cf7pdf_html, $cf7pdf_matches ) ) {
				$cf7pdf_body = trim( $cf7pdf_matches[1] );
			} else {
				$cf7pdf_body = $cf7pdf_html;
				$cf7pdf_body = preg_replace( '/<\/?html[^>]*>/i', '', $cf7pdf_body );
				$cf7pdf_body = preg_replace( '/<head\b[^>]*>.*?<\/head>/is', '', $cf7pdf_body );
				$cf7pdf_body = preg_replace( '/<\/?body[^>]*>/i', '', $cf7pdf_body );
				$cf7pdf_body = trim( $cf7pdf_body );
			}

			if ( '' !== $cf7pdf_styles && '' !== $cf7pdf_body ) {
				return $cf7pdf_styles . "\n" . $cf7pdf_body;
			}

			if ( '' !== $cf7pdf_styles ) {
				return $cf7pdf_styles;
			}

			return $cf7pdf_body;
		}

		/**
		 * Sanitize PDF message body HTML while preserving mPDF-safe markup.
		 *
		 * @param string $value Raw message body.
		 * @return string
		 */
		public static function sanitize_pdf_msg_body( $value ) {
			$cf7pdf_value = wp_unslash( (string) $value );
			$cf7pdf_doctype = '';

			if ( preg_match( '/^\s*<!DOCTYPE[^>]*>\s*/i', $cf7pdf_value, $cf7pdf_matches ) ) {
				$cf7pdf_doctype = $cf7pdf_matches[0];
				$cf7pdf_value   = substr( $cf7pdf_value, strlen( $cf7pdf_matches[0] ) );
			}

			list( $cf7pdf_styles, $cf7pdf_markup ) = self::extract_pdf_style_blocks( $cf7pdf_value );
			$cf7pdf_sanitized = wp_kses( $cf7pdf_markup, self::get_pdf_msg_body_allowed_html() );

			if ( '' !== $cf7pdf_styles && '' !== $cf7pdf_sanitized ) {
				return $cf7pdf_doctype . $cf7pdf_styles . "\n" . $cf7pdf_sanitized;
			}

			if ( '' !== $cf7pdf_styles ) {
				return $cf7pdf_doctype . $cf7pdf_styles;
			}

			return $cf7pdf_doctype . $cf7pdf_sanitized;
		}

		/**
		 * Sanitize a single PDF settings field from admin POST data.
		 *
		 * @param string $key   Setting key.
		 * @param mixed  $value Raw POST value.
		 * @return string|array
		 */
		public static function sanitize_admin_setting_value( $key, $value ) {
			if ( 'cf7_pdf_msg_body' === $key ) {
				return self::sanitize_pdf_msg_body( $value );
			}

			if ( is_array( $value ) ) {
				$sanitized = array();

				foreach ( $value as $item_key => $item_value ) {
					$sanitized[ sanitize_key( $item_key ) ] = sanitize_text_field( wp_unslash( $item_value ) );
				}

				return $sanitized;
			}

			return sanitize_text_field( wp_unslash( $value ) );
		}

		/**
		 * Verify the admin PDF settings save nonce.
		 *
		 * @return bool
		 */
		public static function verify_settings_save_nonce() {
			return isset( $_POST['security-cf7-send-pdf'] ) && wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['security-cf7-send-pdf'] ) ),
				'cf7_send_form'
			);
		}

		/**
		 * Sanitize wp_cf7_pdf_settings POST array (password fields excluded).
		 *
		 * @param array $settings_post Unslashed settings POST array.
		 * @return array
		 */
		public static function sanitize_settings_post_array( array $settings_post ) {
			$cf7pdf_settings_post = array();
			$cf7pdf_skip_keys     = array( 'cf7_opt_password_pdf', 'cf7_opt_password_pdf_confirm' );

			foreach ( $settings_post as $cf7pdf_setting_key => $cf7pdf_setting_value ) {
				$cf7pdf_setting_key = sanitize_key( $cf7pdf_setting_key );

				if ( '' === $cf7pdf_setting_key || in_array( $cf7pdf_setting_key, $cf7pdf_skip_keys, true ) ) {
					continue;
				}

				$cf7pdf_settings_post[ $cf7pdf_setting_key ] = self::sanitize_admin_setting_value( $cf7pdf_setting_key, $cf7pdf_setting_value );
			}

			return self::apply_admin_settings_defaults( $cf7pdf_settings_post );
		}

		/**
		 * Collect sanitized PDF settings from a verified admin save request.
		 *
		 * @return array
		 */
		public static function collect_sanitized_settings_from_post() {
			if ( ! isset( $_POST['security-cf7-send-pdf'] ) || ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['security-cf7-send-pdf'] ) ),
				'cf7_send_form'
			) ) {
				return array();
			}

			$cf7pdf_settings_input = filter_input(
				INPUT_POST,
				'wp_cf7_pdf_settings',
				FILTER_DEFAULT,
				FILTER_REQUIRE_ARRAY
			);

			if ( ! is_array( $cf7pdf_settings_input ) ) {
				return array();
			}

			return self::sanitize_settings_post_array( $cf7pdf_settings_input );
		}

		/**
		 * Apply default PDF settings values when required fields are empty.
		 *
		 * @param array $settings Sanitized settings array.
		 * @return array
		 */
		public static function apply_admin_settings_defaults( array $settings ) {
			if ( empty( $settings['cf7_pdf_msg_body'] ) ) {
				$settings['cf7_pdf_msg_body'] = __( 'Your Name : [your-name]
Your Email : [your-email]
Subject : [your-subject]
Your Message : [your-message]', 'generate-pdf-using-contact-form-7' );
			}

			if ( empty( $settings['cf7_pdf_download_link_txt'] ) ) {
				$settings['cf7_pdf_download_link_txt'] = __( 'Click here to download PDF', 'generate-pdf-using-contact-form-7' );
			}

			return $settings;
		}

		/**
		 * Whether the admin settings form requested password removal.
		 *
		 * @param string $remove_password_sanitized Sanitized remove-password field value.
		 * @return bool
		 */
		public static function is_remove_password_requested( $remove_password_sanitized ) {
			return '1' === (string) $remove_password_sanitized;
		}

		/**
		 * Validate and resolve password fields when saving form settings.
		 *
		 * @param string $enabled         'true' or 'false'.
		 * @param string $new_pass        New plain password.
		 * @param string $confirm         Confirm plain password.
		 * @param array  $existing_meta   Existing cf7_pdf meta.
		 * @param bool   $remove_password User requested removal.
		 * @return array{ok:bool,error:string,encrypted:string,enabled:string}
		 */
		public static function process_password_save( $enabled, $new_pass, $confirm, $existing_meta, $remove_password = false ) {
			$existing_meta = is_array( $existing_meta ) ? $existing_meta : array();
			$has_stored    = self::has_stored_password( $existing_meta );

			if ( $remove_password ) {
				return array(
					'ok'        => true,
					'error'     => '',
					'encrypted' => '',
					'enabled'   => 'false',
				);
			}

			if ( 'true' !== $enabled ) {
				return array(
					'ok'        => true,
					'error'     => '',
					'encrypted' => '',
					'enabled'   => 'false',
				);
			}

			$new_pass = (string) $new_pass;
			$confirm  = (string) $confirm;

			if ( '' === $new_pass && '' === $confirm ) {
				if ( $has_stored ) {
					return array(
						'ok'        => true,
						'error'     => '',
						'encrypted' => $existing_meta['cf7_opt_password_pdf'],
						'enabled'   => 'true',
					);
				}

				return array(
					'ok'        => false,
					'error'     => 'missing',
					'encrypted' => '',
					'enabled'   => 'true',
				);
			}

			if ( $new_pass !== $confirm ) {
				return array(
					'ok'        => false,
					'error'     => 'mismatch',
					'encrypted' => '',
					'enabled'   => 'true',
				);
			}

			if ( strlen( $new_pass ) < self::MIN_PDF_PASSWORD_LENGTH ) {
				return array(
					'ok'        => false,
					'error'     => 'too_short',
					'encrypted' => '',
					'enabled'   => 'true',
				);
			}

			return array(
				'ok'        => true,
				'error'     => '',
				'encrypted' => self::encrypt_password( $new_pass ),
				'enabled'   => 'true',
			);
		}

		/**
		 * Admin notice text for password save errors.
		 *
		 * @param string $code Error code from process_password_save().
		 * @return string
		 */
		public static function get_password_error_message( $code ) {
			switch ( $code ) {
				case 'missing':
					return __( 'Enter a PDF password before enabling protection, or save a password first.', 'generate-pdf-using-contact-form-7' );
				case 'mismatch':
					return __( 'PDF Password and Confirm Password do not match.', 'generate-pdf-using-contact-form-7' );
				case 'too_short':
					return sprintf(
						/* translators: %d: minimum password length */
						__( 'PDF password must be at least %d characters.', 'generate-pdf-using-contact-form-7' ),
						self::MIN_PDF_PASSWORD_LENGTH
					);
				default:
					return __( 'Could not save the PDF password. Please try again.', 'generate-pdf-using-contact-form-7' );
			}
		}

		/**
		 * @param string $password Plain password.
		 * @return string
		 */
		public static function encrypt_password( $password ) {
			if ( '' === $password ) {
				return '';
			}

			if ( ! function_exists( 'openssl_encrypt' ) ) {
				return base64_encode( $password ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			}

			$key = substr( hash( 'sha256', wp_salt( 'auth' ) ), 0, 32 );
			$iv  = substr( hash( 'sha256', wp_salt( 'secure_auth' ) ), 0, 16 );

			$encrypted = openssl_encrypt( $password, 'AES-256-CBC', $key, 0, $iv );

			if ( false === $encrypted ) {
				return '';
			}

			return 'enc:' . base64_encode( $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		/**
		 * @param string $stored Stored value.
		 * @return string
		 */
		public static function decrypt_password( $stored ) {
			if ( '' === $stored || ! is_string( $stored ) ) {
				return '';
			}

			if ( 0 === strpos( $stored, 'enc:' ) ) {
				$payload = base64_decode( substr( $stored, 4 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

				if ( false === $payload || ! function_exists( 'openssl_decrypt' ) ) {
					return '';
				}

				$key = substr( hash( 'sha256', wp_salt( 'auth' ) ), 0, 32 );
				$iv  = substr( hash( 'sha256', wp_salt( 'secure_auth' ) ), 0, 16 );

				$plain = openssl_decrypt( $payload, 'AES-256-CBC', $key, 0, $iv );

				return is_string( $plain ) ? $plain : '';
			}

			return $stored;
		}
	}

	Cf7_Pdf_Submissions::init();
}
