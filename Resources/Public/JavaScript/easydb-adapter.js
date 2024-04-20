
const ready = (fn) => {
    if (document.readyState !== "loading"){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}

let easydbArguments;

const easydbAdapter = {
    openPicker(event) {
        event.preventDefault();
        easydbAdapter.filePickerWindow = window.open(
            easydbArguments['targetUrl'],
            'easydb_picker',
            'width=' + easydbArguments['window']['width'] + ',height=' + easydbArguments['window']['height'] + ',status=0,menubar=0,resizable=1,location=0,directories=0,scrollbars=1,toolbar=0'
        );
    },

    /**
     * Close the easydb file picker if we have a reference to it
     */
    closePicker() {
        if (easydbAdapter.filePickerWindow) {
            easydbAdapter.filePickerWindow.close();
            easydbAdapter.filePickerWindow = null;
        }
    },

    handleMessageEvent(event) {
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
                                "config": easydbArguments['config']
                            }
                        },
                        '*'
                    );
                }
            }
        }
    },
};

window.addEventListener('message', easydbAdapter.handleMessageEvent);
ready(() => {
    const pickerButton = document.querySelector('.button__file-list-easydb');
    easydbArguments = JSON.parse(pickerButton.dataset.arguments);
    pickerButton.addEventListener('click', easydbAdapter.openPicker);
});
