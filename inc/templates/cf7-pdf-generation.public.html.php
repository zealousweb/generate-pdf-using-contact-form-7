<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
/**
* PDF HTML Body
*/

$headerContent = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
						<html lang="en">
							<head>
								<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
								<meta name="viewport" content="width=device-width, initial-scale=1">
								<meta http-equiv="X-UA-Compatible" content="IE=edge">
								<style="
									/* Outlines the grid, remove when sending */
									/* table td { border:1px solid cyan; } */
									/* CLIENT-SPECIFIC STYLES */
									body,
									table,
									td,
									a {
										-webkit-text-size-adjust: 100%;
										-ms-text-size-adjust: 100%;
										font-family: FreeSans,sans-serif;
									}

									table,
									td {
										mso-table-lspace: 0pt;
										mso-table-rspace: 0pt;
									}

									img {
										-ms-interpolation-mode: bicubic;
									}

									/* RESET STYLES */
									img {
										border: 0;
										outline: none;
										text-decoration: none;
									}

									table {
										border-collapse: collapse !important;
									}

									body {
										margin: 0 !important;
										padding: 0 !important;
										width: 100% !important;
									}

									/* iOS BLUE LINKS */
									a[x-apple-data-detectors] {
										color: inherit !important;
										text-decoration: none !important;
										font-size: inherit !important;
										font-family: inherit !important;
										font-weight: inherit !important;
										line-height: inherit !important;
									}

									/* GMAIL BLUE LINKS */
									u+#body a {
										color: inherit;
										text-decoration: none;
										font-size: inherit;
										font-family: inherit;
										font-weight: inherit;
										line-height: inherit;
									}

									/* Default Unordered list style*/
									ul {
										margin: 0px;
										padding-left: 30px;
									}

									/* Default Ordered list style*/
									ol {
										margin: 0px;
										padding-left: 25px;
									}

									li {
										font-size: 16px;
										font-family: FreeSans,sans-serif;
										color: #404040;
										margin-bottom: 20px;
										padding-left: 8px;
									}

									/* Default Paragraph style */
									p {
										font-size: 16px;
										font-family: FreeSans,sans-serif;
										color: #404040;
										margin: 0px 0px 20px;
									}
									">
								</head>

								<body style="background-color:#FAFAFA;font-family:FreeSans;">
								<!--[if mso]> <table role="presentation" width="640" cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="#FFFFFF"> <tr> <td> <![endif]-->

								<!-- Header -->
									<table width="100%" height="100%" border="0" align="center" valign="middle"  cellpadding="0" cellspacing="0" style="margin:0 auto; border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; border-bottom: 1px solid #000;display: block; position: absolute !important;">
										<tr>
										<td width="40%" align="left" style="padding:0px;height: 85px">';

										if($cf7_opt_header_pdf_image) {
											$headerContent .= '
												<img src="'.esc_url_raw($cf7_opt_header_pdf_image).'" style="max-width: '.$cf7_opt_max_width_logo.';height: auto;max-height: '.$cf7_opt_min_width_logo.';"/>';
										}
										else
										{
											$custom_logo_id = get_theme_mod( 'custom_logo' );
											$image = wp_get_attachment_image_src( $custom_logo_id , 'full' );
											if($image){
											
											$headerContent .= '
												<img src="'.esc_url_raw($image[0]).'" style="max-width: 160px;height: auto;max-height: 85px;"/>';
											}
										}
										if($cf7_opt_header_text){
											$headerContent .= '</td><td width="60%" align="right" style="padding:0px;font-weight: 600;"><strong>'.$cf7_opt_header_text.'</strong></td>';
										}
										else
										{
											$headerContent .= '</td><td width="60%" align="right" style="padding:0px;font-weight: 600;"><strong>'.get_bloginfo( 'name' ).'</strong></td>';
										}
										

										$headerContent .= '</tr>
										<tr>
											<td height="15" style="padding:0; line-height: 15px;"></td>
										</tr>
									</table>';

$footerContent = '
	<table width="100%" border="0" align="center" valign="middle"  cellpadding="0" cellspacing="0" style="margin:0 auto; border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; border-top: 1px solid #000;">
		<tr>
			<td height="15" style="padding:0; line-height: 15px;"></td>
		</tr>
		<tr>';
if($cf7_opt_footer_text)
{
	$footerContent .= '<td width="600" align="left" style="padding:0px;font-weight: 600;" width="40%">'.$cf7_opt_footer_text.'</td>';
}
else
{
	$footerContent .= '<td width="600" align="left" style="padding:0px;font-weight: 600;" width="40%">Copyright © '.gmdate('Y').' <a href="'.site_url().'" target="_blank">'.get_bloginfo( 'name' ).'</a></td>';	
}
if($cf7_pdf_download_fp_nbpgPrefix || $cf7_pdf_download_fp_nbpgSuffix )
{
	$footerContent .= '<td align="right" style="padding:0px;font-weight: 600;" width="60%">{PAGENO}{nbpg}</td>';
}
else
{
	$footerContent .= '<td align="right" style="padding:0px;font-weight: 600;" width="60%">{PAGENO}</td>';
}


$footerContent .= '</tr>
	</table>

</table>
</body>
</html>';