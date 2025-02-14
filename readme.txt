=== Generate PDF using Contact Form 7 ===
Contributors: zealopensource
Donate link: http://www.zealousweb.com/payment/
Tags: contact form, contact, PDF, email
Requires at least: 3.0.1
Requires PHP: 5.6
Tested up to: 6.6
Stable tag: 4.1.3
Version: 4.1.3
License: GPLv3 or later
License URI: https://spdx.org/licenses/GPL-3.0-or-later.html

Generate PDF using Contact Form 7 Plugin makes it simple to create PDFs for downloads, viewing, or sending as attachments after form submissions.

== Description ==

Generate PDF using Contact Form 7 plugin provides an easier way to download PDF documents, open the PDF document file after the successful form submission.

Here, a user can set the document file (PDF) from the ‘Form Setting’ Page of each Contact form.

When the user fills the form and submits it, the same document will get open in a new tab. Once it gets opened, the user would be able to download it to the local system.

Also, in case an admin does not want any user to open it in-browser, admin can adjust settings and send that particular PDF as an email attachment. 

**Note**
For PDF we have used MPDF library so in admin side it's support Below HTML and CSS with editor to generate PDF.
https://mpdf.github.io/css-stylesheets/supported-css.html
https://mpdf.github.io/html-support/html-tags.html

== Features of Generate PDF using Contact Form 7 ==

* Attach PDF file to the Form Notifications Emails that are sent to the user and/or administrator, from the Admin side.
* In the message, the link of the attached PDF file is displayed, along with Thank You Message of the Form Submission
* Admin can add different PDFs with different Contact Forms and can create multiple forms.
* Admin can create their own PDF with submitted Data in the Form
* Admin can customize your PDF form by adding a logo on the Header and other relevant Form * Fields while sending a Thank You Message to the user.
* Ablity to Update PDF Header/Footer Text.
* Ability to add file option with our PDF attachement in mail.
* We can use Page Break and new content will be move on next pages in PDF.
* For Acceptance Field use particular tag in PDF editor then only it's showing proper value. Ex. [acceptance-] Start tag as example then you can use any word for tags like [acceptance-terms-condition] OR [acceptance-policy] etc.
* You can set file name from admin also use any form tag instead of file name.
* Add Page number options in admin so you can add Text with PDF page number.
* Ability to set font size of PDF content.
* Compatibility of WordPress VIP.

== Release Generate PDF using Contact Form 7 Pro ==

<strong>[Get more information of Pro version here](https://store.zealousweb.com/generate-pdf-using-contact-form-7-pro)</strong>

<strong>[Demo for Generate PDF using Contact Form 7 Pro](https://store.zealousweb.com/wordpress-plugins/generate-pdf-using-contact-form-7-pro)</strong>

== OUR OTHER PLUGINS ==

* <strong>[Accept PayPal Payments Using Contact Form 7 Pro](https://store.zealousweb.com/wordpress-plugins/accept-paypal-payments-using-contact-form-7-pro)</strong>
* <strong>[Accept Stripe Payments Using Contact Form 7 Pro](https://store.zealousweb.com/wordpress-plugins/accept-stripe-payments-using-contact-form-7-pro)</strong>
* <strong>[Accept Authorize.NET Payments Using Contact Form 7 Pro](https://store.zealousweb.com/accept-authorize-net-payments-using-contact-form-7-pro)</strong>
* <strong>[Accept Elavon Payments Using Contact Form 7 Pro](https://store.zealousweb.com/accept-elavon-payments-using-contact-form-7-pro)</strong>
* <strong>[Accept 2Checkout Payments Using Contact Form 7 Pro](https://store.zealousweb.com/wordpress-plugins/accept-2checkout-payments-using-contact-form-7-pro)</strong>
* <strong>[Accept Sage Pay Payments Using Contact Form 7 Pro](https://store.zealousweb.com/wordpress-plugins/accept-sage-pay-payments-using-contact-form-7-pro)</strong>
* <strong>[User Registration Using Contact Form 7 Pro](https://store.zealousweb.com/wordpress-plugins/user-registration-using-contact-form-7-pro)</strong>
* <strong>[Abandoned Contact Form 7 Pro](https://store.zealousweb.com/wordpress-plugins/abandoned-contact-form-7-pro)</strong>
* <strong>[Custom Product Options WooCommerce Pro](https://store.zealousweb.com/wordpress-plugins/custom-product-options-woocommerce-pro)</strong>

= Getting Help With Wordpress =

If you have any questions about this plugin, you can post a thread in our [WordPress.org forum](https://wordpress.org/support/plugin/generate-pdf-using-contact-form-7/). Please search existing threads before opening a new one or feel free to contact us at <a href="mailto:support@zealousweb.com">support@zealousweb.com</a>

We also offer custom Wordpress extension development and Wordpress theme design services to fulfill your e-commerce objectives.

Our professional impassioned Wordpress experts provide profound and customer oriented development of your project within short timeframes.

Thank you for choosing a Plugin developed by <strong>[ZealousWeb](https://www.zealousweb.com)</strong>!

== Installation ==

1. Download the plugin zip file from WordPress.org plugin site to your desktop / PC
2. If the file is downloaded as a zip archive, extract the plugin folder to your desktop.
3. With your FTP program, upload the plugin folder to the wp-content/plugins folder in your WordPress directory online
4. Go to the Plugin screen and find the newly uploaded Plugin in the list.
5. Click ‘Activate Plugin’ to activate it.

== Screenshots ==
1. Screenshot 'screenshot-1.png' Shows PDF settings of upload PDF in the Contact form.
2. Screenshot 'screenshot-2.png' PDF settings of Customize PDF in the Contact form.
3. Screenshot 'screenshot-3.png' New options of Page number and file name settings.


== Frequently Asked Questions ==

= How to upload mpdf TTf Fonts in library =

To prevent the plugin from becoming too large, we have opted to utilize common TTF fonts and have included them in the plugin.

However, if you encounter a fatal error that states “Cannot find TTF TrueType font file ‘XB Riyaz.ttf’ in configured font directories” 
because you are using a different font, you will need to upload the necessary font to the designated plugin path.

/wp-content/plugins/generate-pdf-using-contact-form-7/inc/lib/mpdf/vendor/mpdf/mpdf/ttfonts

1. You can obtain the required font by downloading it from the GitHub directory provided below.
Visit Github link https://github.com/mpdf/mpdf/tree/development/ttfonts
Download the fonts that’s missing and throwing error

How to Video: https://www.awesomescreenshot.com/video/17025362?key=99cbec8974ee85fdad75e8cea60a97d6

Review Screenshots :
https://prnt.sc/ZKde74y12q3N
https://prnt.sc/PsXaTarHTx4m

2: Upload this files to this plugin path
/wp-content/plugins/generate-pdf-using-contact-form-7/inc/lib/mpdf/vendor/mpdf/mpdf/ttfonts

S.S https://prnt.sc/-TLx40Qup76r

== Changelog ==

= 4.1.3 =
* Backed side issue fixed.
* Secure plugin 

= 4.1.2 =
* The issue with removing PDF attachments has been fixed.
* Secure plugin 

= 4.1.1 =
* Sanitize code
* Secure plugin 

= 4.1.0 =
* The backend warning issue has been resolved.
* Secure plugin 

= 4.0.9 =
* Sanitize and verify_nonce and file security issues resolved.
* Secure plugin 

= 4.0.8 =
* Bug and security issues resolved.

= 4.0.7 =
* Add - 'Post Title' or Current page title using the [_post_title] Shortcode in the PDF message body settings."

= 4.0.6 =
* Improved compatibility with WordPress VIP platform by refactoring code to adhere to VIP coding standards.

= 4.0.5 =
* The problem with the formatting of the Table tag has been resolved.

= 4.0.4 =
* The issue on the front end has been resolved.

= 4.0.3 =
* Comma separated Removed From Checkbox,Select Box, and radion button.

= 4.0.2 =
* Uplaod ttf fonts : XB Riyaz , DejaVuSansCondensed.ttf


= 4.0.1 =
* Fixed Bug

= 4.0 =
* Display option to add style to pdf uniquely in setting.

= 3.10 =
* Added New Feature to select: Attach pdf in mail or not.

= 3.9 =
* Fix - Solved issue of attaching pdf in mail.

= 3.8 =
* Fix - Solved issue of uploaded Path file Input.

= 3.7 =
* Fix - Solved issue and add filter for output content on PDF.

= 3.6 =
* Fix - Solved XSS script issue.

= 3.5 =
* Fix - Solved Success Message if multiple form on same page.

= 3.4 =
* Add - Update MPDF library support in PHP 8.0.x

= 3.3 =
* Add - Add New option of Font size if PDF Content.

= 3.2 =
* Fix - Remove Static Text from code and make it dynamic from Header option.

= 3.1 =
* Add - Add Background Image option in admin side for PDF.

= 3.0 =
* Fix - We have update PDF generate code and ignore blank Form tag from PDF.
* Add - Add Page number option with admin settings, so you can add Text with Number prefix and suffix in PDF.
* Add - Set file name options like if you want to use Form Tag instead PDF file name.

= 2.11 =
* Add - Add new Options for Page number settings in PDF Fotter.

= 2.10 =
* Add - Set Acceotance tag show proper values in PDF. For this Use Proper Tag in PDF editors.

= 2.9 =
* Add - Add Option of Remove PDF file from Media Library after mail sent. You can ON / OFF it from admin Options.

= 2.8 =
* Add - Set PDF message body editor resizable.

= 2.7 =
* Add - Option of Page number and Text.

= 2.6 =
* Add - Add Option for Changes Text for PDF link with Success Message.

= 2.5 =
* Fix - Solved Mail 2 attachement issue.

= 2.4 =
* Add - Add Option for PDF margin Left/Right side.

= 2.3 =
* Fix - Fix some minor bug and update.

= 2.2 =
* Addon - Add some more shortcode for send basic values of site into PDF.

= 2.1 =
* Addon - Add Notification for asking review from all customer from admin.

= 2.0 =
* Fix - Fix issue of Multiple Upload file attachement with Latest CF7 and Code Optimization.

= 1.9.9 =
* Fix - Fix issue of Upload file and make compitible with Latest Contact form 7 5.4.

= 1.9.8 =
* Add - Add option for PDF file name in admin side.

= 1.9.7 =
* Fix - Solved issue of Attachement conflict of Default CF7 and our PDF with emails.

= 1.9.6 =
* Add - Add Pages Break Feature for move content to the next Pages.

= 1.9.5 =
* Fix - Fixed Attachment issue with save attachment into Database.

= 1.9.4 =
* Fix - Fixed Attachment issue.

= 1.9.3 =
* Add - Add New option to Set Logo Size in Generated PDF.

= 1.9.2 =
* Fix - Fixed MPDF library Errro with update latest one.

= 1.9.1 =
* Add - Add New Feature of setting margin of Create PDF with Data.

= 1.9 =
* Add - Add Date format features and match with WP general settings. 

= 1.8 =
* Add - Fixed Issue. 

= 1.7 =
* Add - Now plugin support with Contact Form 7 file option with our PDF attachment. 

= 1.6 =
* Fix - Issue fixed regarding tooltip with latest version of WordPress 5.5.

= 1.5 =
* Fix - Now plugin support in version 5.2 and less then 5.2 of Contact Form 7.

= 1.4 =
* Add - Set Default Font to FreeSans for PDF file.

= 1.3 =
* Fix - We have fixed for support Dropdown and Radio button in PDF generate.

= 1.2 =
* Add - Add New Feature to edit PDF Header/Footer Text.

= 1.1 =
* Add support Link.

= 1.0 =
* Release version.

== Upgrade Notice ==

= 1.2 =
Add New Feature to edit PDF Header/Footer Text.

= 1.1 =
Add support Link.

= 1.0 =
Release version.