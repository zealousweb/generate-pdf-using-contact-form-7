<?php
/**
 * Help & Support admin template.
 *
 **/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin_data           = get_plugin_data( WP_CF7_PDF_FILE );
$current_plugin_name   = isset( $plugin_data['Name'] ) ? $plugin_data['Name'] : '';

/*--------------------------------------------------------------
# Remote data (blogs + FAQs) with transient cache
--------------------------------------------------------------*/

$api_url    = 'https://api.zealousweb.com/wp-json/acf/v3/options/options/plugin_blogs/';
$cache_key  = 'cf7-pdf-generation_plugin_blogs_cache_v1';
$data       = get_transient( $cache_key );
$needs_refetch = (
	false === $data
	|| ! is_array( $data )
	|| empty( $data['plugin_blogs'] )
	|| ! is_array( $data['plugin_blogs'] )
);

if ( $needs_refetch ) {
	$data = array();
	$response = wp_remote_get(
		$api_url,
		array(
			'timeout'   => 20,
			'sslverify' => true,
		)
	);

	if ( ! is_wp_error( $response ) ) {
		$body = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );
		if ( is_array( $decoded ) ) {
			$data = $decoded;
		}
	}

	// Cache only valid payloads, so a temporary API failure does not hide blogs for 24h.
	if ( ! empty( $data['plugin_blogs'] ) && is_array( $data['plugin_blogs'] ) ) {
		set_transient( $cache_key, $data, DAY_IN_SECONDS );
	} else {
		delete_transient( $cache_key );
	}
}


$matched_blogs = array();

if ( ! empty( $data['plugin_blogs'] ) && is_array( $data['plugin_blogs'] ) ) {
	$current_name = strtolower( preg_replace( '/[^a-z0-9]/i', '', (string) $current_plugin_name ) );
	foreach ( $data['plugin_blogs'] as $plugin_item ) {
		if ( empty( $plugin_item['plugin_name'] ) || empty( $plugin_item['blogs'] ) || ! is_array( $plugin_item['blogs'] ) ) {
			continue;
		}

		$api_name = strtolower( preg_replace( '/[^a-z0-9]/i', '', (string) $plugin_item['plugin_name'] ) );

		// Keep matching flexible: exact match first, then "contains" for minor naming variants.
		if (
			$api_name === $current_name
			|| ( '' !== $api_name && '' !== $current_name && false !== strpos( $api_name, $current_name ) )
			|| ( '' !== $api_name && '' !== $current_name && false !== strpos( $current_name, $api_name ) )
		) {
			$matched_blogs = $plugin_item['blogs'];
			break;
		}
	}
}


$help_blog_posts = array();

foreach ( $matched_blogs as $blog ) {
	$blog_slug  = isset( $blog['post_name'] ) ? sanitize_title( $blog['post_name'] ) : '';
	$blog_title = isset( $blog['post_title'] ) ? $blog['post_title'] : '';

	if ( '' === $blog_slug || '' === $blog_title ) {
		continue;
	}

	$help_blog_posts[] = array(
		'url'   => trailingslashit( WP_CF7_PDF_FRONTEND_BLOG_URL ) . $blog_slug . '/',
		'title' => $blog_title,
	);
}

$faq_api_url   = 'https://store.zealousweb.com/productfaq/products/faqs?sku=cf7po';
$faq_cache_key = 'cf7-pdf-generation_faqs_cache_v1';
$faqs_raw      = get_transient( $faq_cache_key );

if ( false === $faqs_raw || ! is_array( $faqs_raw ) ) {
	$faqs_raw = array();
	$faq_response = wp_remote_get(
		$faq_api_url,
		array(
			'timeout'   => 20,
			'sslverify' => true,
		)
	);

	if ( ! is_wp_error( $faq_response ) ) {
		$faq_body = wp_remote_retrieve_body( $faq_response );
		$faq_data = json_decode( $faq_body, true );

		if ( ! empty( $faq_data['faqs'] ) && is_array( $faq_data['faqs'] ) ) {
			$faqs_raw = $faq_data['faqs'];
		}
	}

	set_transient( $faq_cache_key, $faqs_raw, DAY_IN_SECONDS );
}

$help_faqs = array();

foreach ( $faqs_raw as $faq_index => $faq ) {
	$question = isset( $faq['question'] ) ? $faq['question'] : '';
	$answer   = isset( $faq['answer'] ) ? $faq['answer'] : '';

	if ( '' === $question || '' === $answer ) {
		continue;
	}

	$help_faqs[] = array(
		'id'       => isset( $faq['id'] ) ? $faq['id'] : (string) ( $faq_index + 1 ),
		'question' => $question,
		'answer'   => $answer,
	);
}

/*--------------------------------------------------------------
# Newsletter embed (cached HTML string)
--------------------------------------------------------------*/

$newsletter_embed_html = get_transient( 'cf7-pdf-generation_help_newsletter_embed_html_v1' );

if ( false === $newsletter_embed_html || ! is_string( $newsletter_embed_html ) || '' === $newsletter_embed_html ) {
	$newsletter_embed_html  = '<iframe src="//api.zealousweb.com/gfembed/?f=55" width="100%" frameborder="0" scrolling="no" loading="lazy" class="gfiframe" style="display:block;border:0;overflow:hidden;min-height:170px;"></iframe>';
	$newsletter_embed_html .= '<script src="//api.zealousweb.com/wp-content/plugins/gravity-forms-iframe-develop/assets/scripts/gfembed.min.js" type="text/javascript"></script>';
	set_transient( 'cf7-pdf-generation_help_newsletter_embed_html_v1', $newsletter_embed_html, DAY_IN_SECONDS );
}

?>
<div class="wrap cf7-pdf-generation-help-wrapper">
	<h1><?php esc_html_e( 'Help & Support', 'generate-pdf-using-contact-form-7' ); ?></h1>

	<div class="cf7-pdf-generation-help-page">
		<p class="cf7-pdf-generation-page-subtitle">
			<?php esc_html_e( 'If you’re experiencing issues or have questions about features, our support team is here to help. Submit a ticket, browse our knowledge base, or check the FAQs to find a fast solution.', 'generate-pdf-using-contact-form-7' ); ?>
		</p>

		<div class="cf7-pdf-generation-help-top-grid">
			<div class="cf7-pdf-generation-help-card">
				<div class="cf7-pdf-generation-help-card-icon" aria-hidden="true">
					<svg width="27" height="27" viewBox="0 0 27 27" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M13.3328 0C5.96995 0 0 5.96995 0 13.3328C0 20.6956 5.96995 26.6667 13.3328 26.6667C20.6956 26.6667 26.6667 20.6956 26.6667 13.3328C26.6667 5.96995 20.6956 0 13.3328 0ZM16.1084 20.664C15.4221 20.9349 14.8758 21.1403 14.466 21.2825C14.0574 21.4248 13.5822 21.4959 13.0416 21.4959C12.2108 21.4959 11.564 21.2927 11.1035 20.8875C10.643 20.4823 10.4138 19.9687 10.4138 19.3445C10.4138 19.1018 10.4308 18.8535 10.4646 18.6006C10.4996 18.3478 10.5549 18.0634 10.6305 17.7439L11.4895 14.7098C11.5652 14.4186 11.6306 14.1421 11.6825 13.8847C11.7345 13.6251 11.7593 13.3869 11.7593 13.1702C11.7593 12.7842 11.6792 12.5133 11.52 12.3609C11.3586 12.2085 11.055 12.134 10.6023 12.134C10.3811 12.134 10.1531 12.1668 9.91944 12.2356C9.68804 12.3067 9.48713 12.3711 9.32233 12.4343L9.54921 11.4997C10.1113 11.2705 10.6497 11.0741 11.1633 10.9116C11.6769 10.7468 12.1623 10.6655 12.6194 10.6655C13.4445 10.6655 14.0811 10.8665 14.5292 11.2638C14.9751 11.6622 15.1997 12.1803 15.1997 12.8169C15.1997 12.949 15.1839 13.1815 15.1534 13.5134C15.123 13.8463 15.0654 14.15 14.9819 14.4288L14.1274 17.4538C14.0574 17.6965 13.9953 17.9742 13.9389 18.2846C13.8836 18.595 13.8565 18.832 13.8565 18.9912C13.8565 19.393 13.9457 19.6673 14.1263 19.8129C14.3046 19.9585 14.6173 20.0319 15.0598 20.0319C15.2686 20.0319 15.5022 19.9946 15.7663 19.9224C16.0282 19.8502 16.2178 19.7858 16.3375 19.7305L16.1084 20.664ZM15.9571 8.38547C15.5587 8.7557 15.0789 8.94081 14.518 8.94081C13.9581 8.94081 13.475 8.7557 13.0732 8.38547C12.6736 8.01524 12.4715 7.56487 12.4715 7.03887C12.4715 6.514 12.6747 6.0625 13.0732 5.68889C13.475 5.31414 13.9581 5.1279 14.518 5.1279C15.0789 5.1279 15.5598 5.31414 15.9571 5.68889C16.3556 6.0625 16.5553 6.514 16.5553 7.03887C16.5553 7.566 16.3556 8.01524 15.9571 8.38547Z" fill="#0074A2" />
					</svg>
				</div>
				<h2 class="cf7-pdf-generation-help-card-title"><?php esc_html_e( 'Complete Plugin User Guide', 'generate-pdf-using-contact-form-7' ); ?></h2>
				<p class="cf7-pdf-generation-help-card-text">
					<?php esc_html_e( 'Browse helpful resources or reach out for support. Get quick solutions to keep things running smoothly.', 'generate-pdf-using-contact-form-7' ); ?>
				</p>
				<div class="cf7-pdf-generation-help-card-footer">
					<a class="cf7-pdf-generation-primary-btn" href="https://store.zealousweb.com/documentation/wordpress-plugins/generate-pdf-using-contact-form-7" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'View Plugin Guide', 'generate-pdf-using-contact-form-7' ); ?>
						<span class="cf7-pdf-generation-primary-btn-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M12.71 5.88542L0.322265 5.88542C0.143229 5.90251 -3.18009e-07 6.05794 -3.09827e-07 6.24512L-2.72974e-07 7.08822C-2.64294e-07 7.28678 0.161133 7.44954 0.356445 7.44954L10.5176 7.44954L5.90169 12.1191C5.861 12.1606 5.83171 12.2095 5.81543 12.2607C5.77311 12.3869 5.80241 12.5309 5.90169 12.631L6.49089 13.2275C6.63086 13.3683 6.8571 13.3683 6.99707 13.2275L13.2292 6.9222C13.3675 6.78141 13.3675 6.55192 13.2292 6.41113L12.71 5.88542ZM7.00297 0.105591L11.0166 4.16646L8.82579 4.16646L5.90759 1.21399C5.76762 1.07239 5.76762 0.84371 5.90759 0.702108L6.49678 0.105591C6.63676 -0.0351966 6.863 -0.0351966 7.00297 0.105591Z" fill="white" />
							</svg>
						</span>
					</a>
				</div>
			</div>

			<div class="cf7-pdf-generation-help-card">
				<div class="cf7-pdf-generation-help-card-icon" aria-hidden="true">
					<svg width="32" height="26" viewBox="0 0 32 26" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M15.9562 0.00679638C13.4543 -0.0765206 10.9266 0.606053 8.74434 2.06273C6.29334 3.70052 4.58045 6.11906 3.79148 8.82275C3.5736 8.79615 3.3127 8.80789 3.0029 8.89551C1.84624 9.22369 0.976303 10.1715 0.585143 11.0062C0.0778087 12.094 -0.136156 13.5327 0.0895435 15.0179C0.313678 16.4985 0.939142 17.7189 1.72146 18.4766C2.50613 19.2346 3.3921 19.4811 4.27221 19.2961C5.5826 19.0164 6.23232 18.8071 6.04886 17.5863L5.16054 11.6684C5.33969 8.46562 6.99781 5.45057 9.77114 3.59647C13.4832 1.11691 18.3501 1.27572 21.8908 3.99389C24.354 5.88241 25.784 8.71205 25.9491 11.6825L25.3279 15.8222C23.9425 19.6137 20.5077 22.2399 16.5331 22.6127H13.8865C13.2036 22.6127 12.6536 23.1627 12.6536 23.8449V24.495C12.6536 25.1775 13.2036 25.7275 13.8865 25.7275H17.2235C17.9061 25.7275 18.4537 25.1775 18.4537 24.495V24.155C21.4508 23.4236 24.0668 21.5816 25.7703 19.0129L26.8394 19.2964C27.7093 19.5221 28.6059 19.2346 29.3901 18.477C30.1725 17.7189 30.7975 16.4989 31.0221 15.0183C31.2485 13.5331 31.0283 12.0968 30.5269 11.0066C30.0234 9.91644 29.2736 9.22408 28.4079 8.9757C28.0453 8.87126 27.6518 8.83292 27.3143 8.82275C26.6008 6.378 25.132 4.15465 23.015 2.53134C20.9337 0.934236 18.458 0.0889399 15.9562 0.00679638Z" fill="#0074A2" />
						<path fill-rule="evenodd" clip-rule="evenodd" d="M19.8619 10.6534C20.7439 10.6534 21.459 11.3684 21.4609 12.2528C21.459 13.1349 20.7439 13.8519 19.8619 13.8519C18.9775 13.8519 18.2605 13.1349 18.2605 12.2528C18.2605 11.3688 18.9779 10.6534 19.8619 10.6534ZM15.5552 10.6534C16.4392 10.6534 17.1543 11.3684 17.1543 12.2528C17.1543 13.1349 16.4392 13.8519 15.5552 13.8519C14.6704 13.8519 13.9554 13.1349 13.9554 12.2528C13.9554 11.3688 14.6704 10.6534 15.5552 10.6534ZM11.2501 10.6534C12.1322 10.6534 12.8492 11.3684 12.8492 12.2528C12.8492 13.1349 12.1322 13.8519 11.2501 13.8519C10.3661 13.8519 9.65065 13.1349 9.65065 12.2528C9.65065 11.3688 10.3661 10.6534 11.2501 10.6534ZM15.5552 3.78384C10.8652 3.78384 7.08582 7.43884 7.08582 12.2528C7.08582 14.565 7.96006 16.6084 9.38427 18.1077L8.87889 20.3733C8.71226 21.1189 9.22937 21.6203 9.89943 21.2472L12.1118 20.0131C13.1633 20.4695 14.3262 20.7218 15.5552 20.7218C20.2468 20.7218 24.0238 17.0692 24.0238 12.2528C24.0238 7.43884 20.2468 3.78384 15.5552 3.78384Z" fill="#0074A2" />
					</svg>
				</div>
				<h2 class="cf7-pdf-generation-help-card-title"><?php esc_html_e( 'Experiencing an issue or have a suggestion?', 'generate-pdf-using-contact-form-7' ); ?></h2>
				<p class="cf7-pdf-generation-help-card-text">
					<?php esc_html_e( 'Submit a ticket to report problems, ask questions, or share feature ideas.', 'generate-pdf-using-contact-form-7' ); ?>
				</p>
				<div class="cf7-pdf-generation-help-card-footer">
					<a class="cf7-pdf-generation-primary-btn" href="https://support.zealousweb.com/portal/en/home" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Open Support Ticket', 'generate-pdf-using-contact-form-7' ); ?>
						<span class="cf7-pdf-generation-primary-btn-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M12.71 5.88542L0.322265 5.88542C0.143229 5.90251 -3.18009e-07 6.05794 -3.09827e-07 6.24512L-2.72974e-07 7.08822C-2.64294e-07 7.28678 0.161133 7.44954 0.356445 7.44954L10.5176 7.44954L5.90169 12.1191C5.861 12.1606 5.83171 12.2095 5.81543 12.2607C5.77311 12.3869 5.80241 12.5309 5.90169 12.631L6.49089 13.2275C6.63086 13.3683 6.8571 13.3683 6.99707 13.2275L13.2292 6.9222C13.3675 6.78141 13.3675 6.55192 13.2292 6.41113L12.71 5.88542ZM7.00297 0.105591L11.0166 4.16646L8.82579 4.16646L5.90759 1.21399C5.76762 1.07239 5.76762 0.84371 5.90759 0.702108L6.49678 0.105591C6.63676 -0.0351966 6.863 -0.0351966 7.00297 0.105591Z" fill="white" />
							</svg>
						</span>
					</a>
				</div>
			</div>

			<div class="cf7-pdf-generation-help-card">
				<div class="cf7-pdf-generation-help-card-icon" aria-hidden="true">
					<svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M3.99366 25.9048H21.8032C24.0699 25.9048 25.7969 24.0699 25.7969 21.9111V12.3048C25.7969 11.6572 25.3651 11.2254 24.7175 11.2254H19.9683V1.07937C19.9683 0.539683 19.4286 0 18.8889 0H1.07937C0.431747 0 0 0.539683 0 1.07937V21.9111C0 22.9905 0.431747 24.0699 1.1873 24.8254C1.94286 25.581 2.91429 25.9048 3.99366 25.9048ZM19.9683 13.3841H23.7461V21.9111C23.7461 22.9905 22.8826 23.7461 21.9111 23.7461C20.9397 23.7461 20.0762 22.8826 20.0762 21.9111V13.3841H19.9683ZM5.07302 4.31747H14.4635C15.1111 4.31747 15.5429 4.85715 15.5429 5.39683C15.5429 5.93652 15.1111 6.4762 14.4635 6.4762H5.07302C4.4254 6.4762 3.99366 5.93652 3.99366 5.39683C3.99366 4.85715 4.4254 4.31747 5.07302 4.31747ZM5.07302 9.28255H14.4635C15.1111 9.28255 15.5429 9.82224 15.5429 10.3619C15.5429 11.0095 15.1111 11.4413 14.4635 11.4413H5.07302C4.4254 11.4413 3.99366 11.0095 3.99366 10.3619C3.99366 9.82224 4.4254 9.28255 5.07302 9.28255ZM5.07302 14.3556H14.4635C15.1111 14.3556 15.5429 14.8953 15.5429 15.4349C15.5429 15.9746 15.1111 16.5143 14.4635 16.5143H5.07302C4.4254 16.5143 3.99366 15.9746 3.99366 15.4349C3.99366 14.8953 4.4254 14.3556 5.07302 14.3556ZM5.07302 19.4286H14.4635C15.1111 19.4286 15.5429 19.8603 15.5429 20.508C15.5429 21.0477 15.1111 21.5873 14.4635 21.5873H5.07302C4.4254 21.5873 3.99366 21.0477 3.99366 20.508C3.99366 19.9683 4.4254 19.4286 5.07302 19.4286Z" fill="#0074A2" />
					</svg>
				</div>
				<h2 class="cf7-pdf-generation-help-card-title"><?php esc_html_e( 'Subscribe to our newsletter', 'generate-pdf-using-contact-form-7' ); ?></h2>
				<p class="cf7-pdf-generation-help-card-text">
					<?php esc_html_e( 'Sign up for our newsletter and receive exclusive discounts and promotions.', 'generate-pdf-using-contact-form-7' ); ?>
				</p>
				<div class="cf7-pdf-generation-help-card-footer cf7-pdf-generation-help-newsletter-embed">
					<?php
					echo wp_kses(
						$newsletter_embed_html,
						array(
							'iframe' => array(
								'src'         => true,
								'width'       => true,
								'height'      => true,
								'frameborder' => true,
								'frameBorder' => true,
								'scrolling'   => true,
								'loading'     => true,
								'class'       => true,
								'style'       => true,
							),
							'script' => array(
								'src'  => true,
								'type' => true,
							),
						)
					);
					?>
				</div>
			</div>
		</div>

		<div class="cf7-pdf-generation-help-bottom-grid">
			<?php if ( ! empty( $help_blog_posts ) ) : ?>
                <div class="cf7-pdf-generation-help-card cf7-pdf-generation-help-card-wide">
                    <div class="cf7-pdf-generation-help-card-icon" aria-hidden="true">
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M23.0909 29H8.90909C8.59565 29 8.29505 28.8755 8.07342 28.6539C7.85179 28.4322 7.72727 28.1316 7.72727 27.8182C7.72727 27.5047 7.85179 27.2041 8.07342 26.9825C8.29505 26.7609 8.59565 26.6364 8.90909 26.6364H23.0909C23.4043 26.6364 23.7049 26.7609 23.9266 26.9825C24.1482 27.2041 24.2727 27.5047 24.2727 27.8182C24.2727 28.1316 24.1482 28.4322 23.9266 28.6539C23.7049 28.8755 23.4043 29 23.0909 29ZM29 8.90909V18.3636C29 19.1396 28.8472 19.908 28.5502 20.6249C28.2532 21.3419 27.818 21.9933 27.2693 22.542C26.7206 23.0907 26.0691 23.526 25.3522 23.8229C24.6353 24.1199 23.8669 24.2727 23.0909 24.2727H8.90909C8.1331 24.2727 7.3647 24.1199 6.64778 23.8229C5.93086 23.526 5.27944 23.0907 4.73073 22.542C3.62256 21.4338 3 19.9308 3 18.3636V8.90909C3 7.3419 3.62256 5.8389 4.73073 4.73073C5.8389 3.62256 7.3419 3 8.90909 3H23.0909C23.8669 3 24.6353 3.15284 25.3522 3.4498C26.0691 3.74676 26.7206 4.18202 27.2693 4.73073C27.818 5.27944 28.2532 5.93086 28.5502 6.64778C28.8472 7.3647 29 8.1331 29 8.90909ZM7.72727 11.2727C7.72727 11.5862 7.85179 11.8868 8.07342 12.1084C8.29505 12.33 8.59565 12.4545 8.90909 12.4545H16C16.3134 12.4545 16.614 12.33 16.8357 12.1084C17.0573 11.8868 17.1818 11.5862 17.1818 11.2727C17.1818 10.9593 17.0573 10.6587 16.8357 10.4371C16.614 10.2154 16.3134 10.0909 16 10.0909H8.90909C8.59565 10.0909 8.29505 10.2154 8.07342 10.4371C7.85179 10.6587 7.72727 10.9593 7.72727 11.2727ZM24.2727 16C24.2727 15.6866 24.1482 15.386 23.9266 15.1643C23.7049 14.9427 23.4043 14.8182 23.0909 14.8182H8.90909C8.59565 14.8182 8.29505 14.9427 8.07342 15.1643C7.85179 15.386 7.72727 15.6866 7.72727 16C7.72727 16.3134 7.85179 16.614 8.07342 16.8357C8.29505 17.0573 8.59565 17.1818 8.90909 17.1818H23.0909C23.4043 17.1818 23.7049 17.0573 23.9266 16.8357C24.1482 16.614 24.2727 16.3134 24.2727 16Z" fill="#0074A2" />
                        </svg>
                    </div>

                    <h2 class="cf7-pdf-generation-help-card-title">
                        <?php esc_html_e( 'Related Blog', 'generate-pdf-using-contact-form-7' ); ?>
                    </h2>

                    <p class="cf7-pdf-generation-help-card-text">
                        <?php esc_html_e( 'Read our latest blog posts for deeper insights.', 'generate-pdf-using-contact-form-7' ); ?>
                    </p>

                    <ul class="cf7-pdf-generation-help-list" aria-label="<?php esc_attr_e( 'Related blog posts', 'generate-pdf-using-contact-form-7' ); ?>">
                        <?php foreach ( $help_blog_posts as $help_blog_post ) : ?>
                            <li>
                                <a href="<?php echo esc_url( $help_blog_post['url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html( $help_blog_post['title'] ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

			<div class="cf7-pdf-generation-help-card cf7-pdf-generation-help-card-wide">
				<div class="cf7-pdf-generation-help-card-icon" aria-hidden="true">
					<svg width="28" height="26" viewBox="0 0 28 26" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M26 7.17999V15C26 15.6566 25.8707 16.3068 25.6194 16.9134C25.3681 17.52 24.9998 18.0712 24.5355 18.5355C24.0712 18.9998 23.52 19.3681 22.9134 19.6194C22.3068 19.8707 21.6566 20 21 20H8V21C8.00237 21.7949 8.31921 22.5566 8.8813 23.1187C9.4434 23.6808 10.2051 23.9976 11 24H22.53C22.6864 24.002 22.8403 24.0396 22.98 24.11L26.55 25.89C26.6897 25.9604 26.8436 25.998 27 26C27.1871 25.9999 27.3706 25.9479 27.53 25.85C27.6741 25.7599 27.7929 25.6346 27.8751 25.4859C27.9574 25.3372 28.0003 25.1699 28 25V9.99999C27.9983 9.38105 27.8051 8.77779 27.4471 8.27293C27.089 7.76806 26.5835 7.38631 26 7.17999Z" fill="#0074A2" />
						<path d="M24 15V3C24 2.20435 23.6839 1.44129 23.1213 0.87868C22.5587 0.316071 21.7956 0 21 0H3C2.20435 0 1.44129 0.316071 0.87868 0.87868C0.316071 1.44129 7.04546e-08 2.20435 7.04546e-08 3V21C-7.06492e-05 21.1884 0.0530992 21.373 0.153383 21.5326C0.253667 21.6921 0.396984 21.8201 0.566818 21.9017C0.736653 21.9833 0.926094 22.0153 1.11331 21.9939C1.30053 21.9726 1.47791 21.8988 1.625 21.781L6.077 18.219C6.25388 18.0776 6.47353 18.0003 6.7 18H21C21.7956 18 22.5587 17.6839 23.1213 17.1213C23.6839 16.5587 24 15.7956 24 15ZM12.92 13.38C12.8751 13.5041 12.8034 13.6168 12.7101 13.7101C12.6168 13.8034 12.5041 13.8751 12.38 13.92C12.2603 13.9727 12.1308 14 12 14C11.8692 14 11.7397 13.9727 11.62 13.92C11.4959 13.8751 11.3832 13.8034 11.2899 13.7101C11.1966 13.6168 11.1249 13.5041 11.08 13.38C11.0288 13.2598 11.0016 13.1307 11 13C11.0039 12.7353 11.1074 12.4817 11.29 12.29C11.3873 12.2017 11.4989 12.1307 11.62 12.08C11.7393 12.0256 11.8689 11.9975 12 11.9975C12.1311 11.9975 12.2607 12.0256 12.38 12.08C12.5011 12.1307 12.6127 12.2017 12.71 12.29C12.8942 12.4807 12.998 12.7349 13 13C12.9984 13.1307 12.9712 13.2598 12.92 13.38ZM13.111 9.88C13.0566 9.91263 13.0121 9.95949 12.9823 10.0155C12.9525 10.0716 12.9386 10.1346 12.942 10.198C12.942 10.4632 12.8366 10.7176 12.6491 10.9051C12.4616 11.0926 12.2072 11.198 11.942 11.198C11.6768 11.198 11.4224 11.0926 11.2349 10.9051C11.0474 10.7176 10.942 10.4632 10.942 10.198C10.9352 9.73915 11.0646 9.28858 11.3137 8.90323C11.5629 8.51788 11.9208 8.21505 12.342 8.033C12.5523 7.94578 12.7289 7.79311 12.8456 7.59763C12.9623 7.40215 13.0129 7.17427 12.9899 6.94777C12.9668 6.72128 12.8715 6.50823 12.7178 6.34021C12.5642 6.17219 12.3605 6.05815 12.137 6.015C11.8878 5.96957 11.6306 6.01384 11.4109 6.13996C11.1913 6.26608 11.0234 6.4659 10.937 6.704C10.8464 6.95164 10.6616 7.15346 10.4229 7.26552C10.1841 7.37758 9.91083 7.39081 9.66241 7.30234C9.41398 7.21386 9.21057 7.03084 9.09643 6.79311C8.9823 6.55537 8.96669 6.28218 9.053 6.033C9.22308 5.55226 9.51063 5.12166 9.88952 4.78037C10.2684 4.43908 10.7266 4.19792 11.2225 4.07883C11.7183 3.95974 12.236 3.96649 12.7286 4.09848C13.2212 4.23046 13.6729 4.48349 14.0428 4.83455C14.4126 5.18561 14.6889 5.62356 14.8463 6.10858C15.0038 6.59359 15.0375 7.11028 14.9445 7.61165C14.8514 8.11302 14.6344 8.58317 14.3133 8.97933C13.9923 9.37549 13.5772 9.6851 13.106 9.88H13.111Z" fill="#0074A2" />
					</svg>
				</div>
				<h2 class="cf7-pdf-generation-help-card-title"><?php esc_html_e( 'Frequently Asked Questions', 'generate-pdf-using-contact-form-7' ); ?></h2>

				<div class="cf7-pdf-generation-help-faq-list" role="list">
					<?php if ( ! empty( $help_faqs ) ) : ?>
						<?php foreach ( $help_faqs as $faq_index => $help_faq ) : ?>
							<?php
							$faq_id        = isset( $help_faq['id'] ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $help_faq['id'] ) : (string) ( $faq_index + 1 );
							$faq_suffix    = $faq_id . '-' . (int) $faq_index;
							$is_first_open = ( 0 === (int) $faq_index );
							$question_id   = 'cf7-pdf-generation-faq-question-' . $faq_suffix;
							$answer_id     = 'cf7-pdf-generation-faq-answer-' . $faq_suffix;
							?>
							<div class="cf7-pdf-generation-help-faq-item<?php echo $is_first_open ? ' is-open' : ''; ?>" role="listitem">
								<button type="button" class="cf7-pdf-generation-help-faq-question"
									aria-expanded="<?php echo $is_first_open ? 'true' : 'false'; ?>"
									aria-controls="<?php echo esc_attr( $answer_id ); ?>"
									id="<?php echo esc_attr( $question_id ); ?>">
									<?php echo esc_html( $help_faq['question'] ); ?>
									<span aria-hidden="true"><?php echo $is_first_open ? '&minus;' : '+'; ?></span>
								</button>
								<div class="cf7-pdf-generation-help-faq-answer" id="<?php echo esc_attr( $answer_id ); ?>" role="region"
									aria-labelledby="<?php echo esc_attr( $question_id ); ?>"
									aria-hidden="<?php echo $is_first_open ? 'false' : 'true'; ?>">
									<?php echo wp_kses_post( $help_faq['answer'] ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="cf7-pdf-generation-help-card-text cf7-pdf-generation-help-remote-empty">
							<?php esc_html_e( 'No FAQs are available at the moment. Please try again later or contact support.', 'generate-pdf-using-contact-form-7' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php if ( ! empty( $help_faqs ) ) : ?>
<script>
(function () {
	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.cf7-pdf-generation-help-faq-question').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var item = btn.closest('.cf7-pdf-generation-help-faq-item');
				if (!item) {
					return;
				}
				item.classList.toggle('is-open');
				var open = item.classList.contains('is-open');
				btn.setAttribute('aria-expanded', open ? 'true' : 'false');
				var answer = document.getElementById(btn.getAttribute('aria-controls'));
				if (answer) {
					answer.setAttribute('aria-hidden', open ? 'false' : 'true');
				}
				var sym = btn.querySelector('span[aria-hidden="true"]');
				if (sym) {
					sym.textContent = open ? '\u2212' : '+';
				}
			});
		});
	});
})();
</script>
<?php endif; ?>
