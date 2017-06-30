/**
 * Module: TYPO3/CMS/Easydb/EasydbAdapter
 */
define(['jquery'], function($) {
	'use strict';

	var easydbAdapter = {

		/**
		 * Value map of events already handled
		 */
		eventsHandled: {},

		openPicker: function(event) {
			event.preventDefault();
			var $arguments = $(event.target).data('arguments');
			easydbAdapter.eventsHandled = {};
			window.top.EasydbData.filePicker = window.top.open(
				$arguments['targetUrl'],
				'easydb_picker',
				'width=' + $arguments['window']['width'] + ',height=' + $arguments['window']['height'] + ',status=0,menubar=0,resizable=1,location=0,directories=0,scrollbars=1,toolbar=0'
			);
		},

		/**
		 * Close the easydb file picker if we have a reference to it
		 */
		closePicker: function() {
			if (window.top.EasydbData.filePicker) {
				window.top.EasydbData.filePicker.close();
				window.top.EasydbData.filePicker = null;
			}
		},

		handleMessageEvent: function(event) {
			if (event.data['easydb']) {
				if (event.data['easydb']['action'] === 'reload' && !easydbAdapter.eventsHandled[event.data['easydb']['action']]) {
					window.location.reload();
					easydbAdapter.eventsHandled['reload'] = true;
				}
				if (event.data['easydb']['action'] === 'close' && !easydbAdapter.eventsHandled[event.data['easydb']['action']]) {
					easydbAdapter.closePicker();
					easydbAdapter.eventsHandled['close'] = true;
				}
			}
		},

		/**
		 * Add event listeners
		 */
		addEventListeners: function () {
			if (!window.top.EasydbData.messageListener) {
				window.top.window.addEventListener('message', this.handleMessageEvent);
				window.top.EasydbData.messageListener = true;
			}
			$(function() {
				$('.button__file-list-easydb').on('click', easydbAdapter.openPicker);
			});
		}
	};

	if (!window.top.EasydbData) {
		window.top.EasydbData = {
			messageListener: null,
			filePicker: null
		};
	}

	easydbAdapter.addEventListeners();

	return easydbAdapter;
});
