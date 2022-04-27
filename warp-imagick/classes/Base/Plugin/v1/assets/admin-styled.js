/**!
 * Copyright © 2017-2022 Dragan Đurić. All rights reserved.
 *
 * @package warp-imagick
 * @license GNU General Public License Version 2.
 * @copyright © 2017-2022. All rights reserved.
 * @author Dragan Đurić
 * @link https://warp-imagick.pagespeed.club/
 *
 * This copyright notice, source files, licenses and other included
 * materials are protected by U.S. and international copyright laws.
 * You are not allowed to remove or modify this or any other
 * copyright notice contained within this software package.
 */

(function ($) {
	if (typeof $ === 'function') {
		function fixChosenContainerWidth () {
			/*	Fix drop-down (div.chosen-container) width: 0px,
				when initialized under collapsed/hidden section/tab.*/

			var $sections   = $( '.accordion-container' );
			var $containers = $( 'div.chosen-container' );

			// Hook on tab switch.
			$( 'input[type=radio][name=nav-tab-state]' ).change(
				function() {
					$containers.each(
						function (ix, el) {
							if (el.style.width === '0px') { // Chosen width is 0px?
								var $chosen = $( el ).hide(); // Hide to prevent flickering.
								// Set Chosen width to width of previous sibling (select) element.
								$chosen.width( $chosen.prev().css( 'width' ) );
								$chosen.show();
							}
						}
					);
				}
			);

			// Hook on accordion event (as in /wp-admin/js/accordion.min.js).
			$sections.on(
				'click keydown',
				'.accordion-section-title',
				function (e) {
					if ( e.type === 'keydown' && 13 !== e.which ) {
						return; }
					var $found = $();
					var $chosen;
					$sections.find( 'div.chosen-container' ).each(
						function (ix, el) { // Get Chosen containers.
							if (el.style.width === '0px') { // Chosen width is 0px?
								$chosen = $( el );
								$chosen.hide(); // Hide to prevent flickering.
								$found = $found.add( $chosen );
							}
						}
					);
					if ($found.length !== 0) {
						setTimeout(
							function () { // Wait for the animations to finish (150)?
								$found.each(
									function (ix, el) { // Get Chosen containers.
										$chosen = $( el );
										// Set Chosen width to width of previous sibling (select) element.
										$chosen.width( $chosen.prev().css( 'width' ) );
										$chosen.show();
									}
								);
							},
							50
						);
					}
				}
			);
		}
		function adminStyle () {
			// Get admin-color from button-primary border-bottom-color (or lighter background-color).
			$color = $( ".button.button-primary" ).first().css( 'background-color' ); // .css ("border-bottom-color");.
			document.documentElement.style.setProperty( '--admin-color', $color ); // Set CSS variable.
		}
		$(
			function () {
				adminStyle();
				$settingsForm = $( 'div#post-body-content>form' );
				$settingsForm.find( 'input[type="submit"]' ).attr( 'disabled', 'disabled' );
				$settingsForm.find( 'select.chosen-select' ).chosen( {} );
				fixChosenContainerWidth();
				$settingsForm.areYouSure(
					{
						addRemoveFieldsMarksDirty: true,
						change: function() {
							// Enable save button only if the form is dirty (has something to save).
							if ($( this ).hasClass( 'dirty' )) {
								$( this ).find( 'input[type="submit"]' ).removeAttr( 'disabled' );
							} else {
								$( this ).find( 'input[type="submit"]' ).attr( 'disabled', 'disabled' );
							}
						}
					}
				);
			}
		);
	} else {
		console.log( 'jQuery function not available (' + typeof $ + ').' );
	}
}(jQuery));
