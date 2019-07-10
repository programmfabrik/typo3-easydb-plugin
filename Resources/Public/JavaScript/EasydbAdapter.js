/**
 * Module: TYPO3/CMS/Easydb/EasydbAdapter
 */
define(['jquery'], function($) {
	'use strict';

	var easydbAdapter = {

		openPicker: function(event) {
			event.preventDefault();
			var $arguments = $(event.target).data('arguments');
			easydbAdapter.filePickerWindow = window.open(
				$arguments['targetUrl'],
				'easydb_picker',
				'width=' + $arguments['window']['width'] + ',height=' + $arguments['window']['height'] + ',status=0,menubar=0,resizable=1,location=0,directories=0,scrollbars=1,toolbar=0'
			);
		},

		/**
		 * Close the easydb file picker if we have a reference to it
		 */
		closePicker: function() {
			if (easydbAdapter.filePickerWindow) {
				easydbAdapter.filePickerWindow.close();
				easydbAdapter.filePickerWindow = null;
			}
		},

		handleMessageEvent: function(event) {
			if (event.data['easydb']) {
				if (event.data['easydb']['action'] === 'reload') {
					window.location.reload();
				}
				if (event.data['easydb']['action'] === 'close') {
					easydbAdapter.closePicker();
				}
			}
		},

		/**
		 * Add event listeners
		 */
		addEventListeners: function () {
			window.addEventListener('message', this.handleMessageEvent);
			$(function() {
				$('.button__file-list-easydb').on('click', easydbAdapter.openPicker);
			});
		}
	};

	easydbAdapter.addEventListeners();

	return easydbAdapter;
});
