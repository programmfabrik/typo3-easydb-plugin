.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual:

Administrator Manual
====================

Describes how to manage the extension from an administrator’s point of
view. That relates to Page/User TSconfig, permissions, configuration
etc., which administrator level users have access to.

Language should be non/semi-technical, explaining, using small
examples.

Target group: **Administrators**


Installation
------------

The extension can be installed using the TYPO3 extension manager (from TYPO3 extension repository) or included via composer.

Composer command for adding the extension to your project: `composer require easydb/typo3-integration`


Configuration
-------------

After installation, some mandatory settings need to be provided.
Select the extension configuration for easydb extension within you3 TYPO3 installation.

.. figure:: ../Images/AdministratorManual/ExtensionConfiguration.png
	:width: 500px
	:alt: Extension Configuration

	Extension Configuration

URL to your easydb server
~~~~~~~~~~~~~~~~~~~~~~~~~

You need to specify the URL to your easydb server.

The import of files can work in two modes.

If your TYPO3 server can reach the easydb server, the easydb extension will fetch the file
data by directly establishing a connection to the easydb server.

If your easydb server is not reachable from the TYPO3 server, file data can be sent
directly via the browser.

You can choose between the two modes in your esadb settings:

.. figure:: ../Images/AdministratorManual/DataTransfer.png
	:width: 500px
	:alt: easydb settings for data transfer

	easydb settings for data transfer


File extensions that shall be imported from easydb
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A comma separated list of file extensions that are allowed to be imported into TYPO3.

easydb locale for the TYPO3 default language
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In case you have a multi language TYPO3 project, you need to provide a mapping between TYPO3 languages
and easydb locales. In extension configuration you specify the easydb locale for the TYPO3 default language.

easydb Locales for each TYPO3 language records, can be specified within the TYPO3 language record as shown
in the following screen shot.

.. figure:: ../Images/AdministratorManual/LocaleForLanguage.png
	:width: 500px
	:alt: easydb locale in TYPO3 language record

	easydb locale in TYPO3 language record


Allow Session Transfer
~~~~~~~~~~~~~~~~~~~~~~

Only set this checkbox, if it really is required and you understood the impact!
Better options are: Setting `BE/cookieSameSite` to `none` in TYPO3 configuration,
or to have the easydb hostname on the same top level domain as your TYPO3 backend.

Detailed explanation
^^^^^^^^^^^^^^^^^^^^
