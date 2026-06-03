<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Cf7_Pdf_Generation_Front' ) ) {

	/**
	 * Front bootstrap (hooks live in Cf7_Pdf_Generation_Front_Action).
	 */
	class Cf7_Pdf_Generation_Front {

		function __construct() {}
	}

	add_action(
		'plugins_loaded',
		function () {
			new Cf7_Pdf_Generation_Front();
		}
	);
}
