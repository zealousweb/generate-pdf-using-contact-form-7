(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

})( jQuery );

jQuery(document).ready(function() {
	jQuery(".mail_tag").on('click', function() {
		var sel, range;
		var el = jQuery(this)[0];

		if (window.getSelection && document.createRange) { //Browser compatibility
		  sel = window.getSelection();
		  if(sel.toString() == ''){ //no text selection
			 window.setTimeout(function(){
				range = document.createRange(); //range object
				range.selectNodeContents(el); //sets Range
				sel.removeAllRanges(); //remove all ranges from selection
				sel.addRange(range);//add Range to a Selection.
			},1);
		  }
		}else if (document.selection) { //older ie
			sel = document.selection.createRange();
			if(sel.text == ''){ //no text selection
				range = document.body.createTextRange();//Creates TextRange object
				range.moveToElementText(el);//sets Range
				range.select(); //make selection.
			}
		}
	});
});