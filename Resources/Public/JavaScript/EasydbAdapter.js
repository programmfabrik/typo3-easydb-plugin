/**
 * Module: TYPO3/CMS/Easydb/EasydbAdapter
 */
define(['jquery'], function ($) {
    'use strict';

    var pickerButton;
    var easydbAdapter = {
        openPicker: function (event) {
            event.preventDefault();
            var $arguments = pickerButton.data('arguments');
            easydbAdapter.filePickerWindow = window.open(
                    $arguments['targetUrl'],
                    'easydb_picker',
                    'width=' + $arguments['window']['width'] + ',height=' + $arguments['window']['height'] + ',status=0,menubar=0,resizable=1,location=0,directories=0,scrollbars=1,toolbar=0'
            );
        },

        /**
         * Close the easydb file picker if we have a reference to it
         */
        closePicker: function () {
            if (easydbAdapter.filePickerWindow) {
                easydbAdapter.filePickerWindow.close();
                easydbAdapter.filePickerWindow = null;
            }
        },

        handleMessageEvent: function (event) {
            if (event.data['easydb']) {
                if (event.data['easydb']['action'] === 'reload') {
                    window.location.reload();
                }
                if (event.data['easydb']['action'] === 'close') {
                    easydbAdapter.closePicker();
                    easydbAdapter.filePickerWindow = null;
                }
                if (event.data['easydb']['action'] === 'send_config') {
                    if (easydbAdapter.filePickerWindow) {
                        easydbAdapter.filePickerWindow.postMessage(
                            {
                                "typo3": {
                                    "config": pickerButton.data('arguments')['config']
                                }
                            },
                            '*'
                        );
                    }
                }
            }
        },

        /**
         * Add event listeners
         */
        addEventListeners: function () {
            window.addEventListener('message', this.handleMessageEvent);
            $(function () {
                pickerButton = $('.button__file-list-easydb');
                pickerButton.on('click', easydbAdapter.openPicker);
            });
        }
    };

    easydbAdapter.addEventListeners();

    return easydbAdapter;
});
