/**!
 * Copyright © 2017-2025 Dragan Đurić. All rights reserved.
 *
 * @package warp-imagick
 * @license GNU General Public License Version 2.
 * @copyright © 2017-2025. All rights reserved.
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
		var page_slug = false;
		function abstractSettingsSaveState () {
			var sections        = document.getElementsByClassName( 'accordion-section' );
			var sections_string = '';
			var sections_length = sections.length;
			var section_state;
			for (var i = 0; i < sections_length; i++) {
				section_state    = sections[i].classList.contains( 'open' );
				sections_string += section_state ? '1' : '0';
			}

			var tab_state = 0;
			var tabs      = document.getElementsByClassName( 'nav-tab-state' );
			var tabs_len  = tabs.length;
			for (var i = 0; i < tabs_len; i++) {
				if (tabs[i].checked) {
					tab_state = i;
					break;
				}
			}
			wpCookies.setHash(
				page_slug,
				{
					tabindex: tab_state,
					sections: sections_string,
				}
			);
		}
		function fixMozillaSlider () { // Mozilla/FF: fix slider thumb position on html-reload.
			$( 'input[type=range]' ).each(
				function (ix, el) {
					el.value = el.defaultValue;
				}
			);
		}
		$(
			function () {
				page_slug = document.getElementById( 'settings-page' ).dataset.page;
				if (typeof page_slug === 'string' && page_slug) {
					if ( typeof wpCookies === 'object' ) {
						window.onbeforeunload = function () {
							abstractSettingsSaveState();
						}
					} else {
						console.log( 'Object "wpCookies" not found.' );
					}
					if (typeof adminpage === 'string' && adminpage) {
						try {
							window.postboxes.add_postbox_toggles( adminpage ); // Initialize Meta Boxes.
						} catch (err) {
							console.log( 'Exception caught on "window.postboxes.add_postbox_toggles()".' );
							console.log( err.name );
							console.log( err.message );
						}
					} else {
						console.log( 'Var "adminpage" not found.' );
					}
					fixMozillaSlider();
				} else {
					console.log( 'Element #settings-page[data-page] not found.' );
				}

				if ( typeof sortable === "function" ) {
					$( '.multiple-input.ui-sortable' ).sortable(
						{
							update: function (event, ui) {
								/* TODO: AYS cannot recognize reordered fields nor can mark form dirty.
								note: Create/use hidden field and change its value?
								ui.helper.closest ('form').trigger ('checkform.areYouSure');
								*/
							}
						}
					);
					$( '.multiple-input.ui-sortable' ).disableSelection();
					$( '.multiple-input.ui-sortable' ).on(
						'dblclick',
						'.multiple-input.multiple-remove',
						function() {
							var $this = $( this );
							var $form = $this.closest( 'form' );
							$this.parent().remove();
							$form.trigger( 'checkform.areYouSure' );
						}
					);
					$( '.multiple-input.multiple-append' ).click(
						function() {
							var $this = $( this );
							var $form = $this.closest( 'form' );
							$this.siblings( '.multiple-input.ui-sortable' ).first().append( this.dataset.append );
							$form.trigger( 'checkform.areYouSure' );
						}
					);
				} else {
					console.log( 'Function "sortable" not found.' );
				}
			}
		);
	} else {
		console.log( 'jQuery function not available (' + typeof $ + ')' );
	}
}(jQuery));
