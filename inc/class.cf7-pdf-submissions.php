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
	}

	Cf7_Pdf_Submissions::init();
}
