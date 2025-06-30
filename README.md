# TYPO3 [easydb](http://5.docs.easydb.de/docs/webfrontend/datamanagement/features/plugins/?node=4,4.1.3) integration

[![Build Status](https://github.com/programmfabrik/typo3-easydb-plugin/actions/workflows/Test.yml/badge.svg?branch=main)](https://github.com/programmfabrik/typo3-easydb-plugin/actions/workflows/Test.yml)

This TYPO3 extension provides an interface in the TYPO3 backend
to access and import files from easydb digital asset management.

## Installation

Either [download and install](https://extensions.typo3.org/extension/easydb) it from TER,
or install it with composer: `composer require easydb/typo3-integration`

## Configuration

You can configure the extension by using the Extension Manager backend module.
Just open the extension configuration of *easydb* extension and at least specify
a server URL to your easydb server.

You can limit the allowed file extensions of files that can be imported from easydb.

![Extension Manager Configuration](https://github.com/programmfabrik/typo3-easydb-plugin/raw/main/Documentation/Images/AdministratorManual/ExtensionConfiguration.png)

Just specify a comma separated list extensions.

If you don't need multi language support, you will be good to go. Otherwise read on.

### Multilanguage configuration

The only thing you need to do is set the easydb locale that should be mapped to the TYPO3 default
language in the extension manager configuration and set the according easydb locale in each TYPO3 language record.

![Extension Manager Configuration](https://github.com/programmfabrik/typo3-easydb-plugin/raw/main/Documentation/Images/AdministratorManual/LocaleForLanguage.png)

### Known problems with cross site cookies

If you are experiencing problems with the upload into the TYPO3 server,
and get errors like

* "No cookie present"
* "Are cross site cookies allowed in your browser?"

then this means that your browser is blocking cookies, which are needed to authenticate in the TYPO3 server.
This is most likely due to the security settings in your browser. 
Depending on the browser you are using, 
you need to update the settings to allow cross site cookies.

For example, in Firefox it is necessary to change the settings for blocking cookies 
from the most strict option "Cross-site tracking cookies, 
and isolate other cross-site cookies" to "Cross-site tracking cookies", 
so that the cross site cookie between easydb5/fylr and TYPO3 can be used. 
For other browsers, there are similar security options.
