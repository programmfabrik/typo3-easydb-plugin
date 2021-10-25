CREATE TABLE sys_file (
	easydb_uid varchar(255) DEFAULT '' NOT NULL,
	easydb_asset_id int(11) unsigned DEFAULT '0' NOT NULL,
	easydb_asset_version varchar(255) DEFAULT '' NOT NULL,
	easydb_system_object_id int(11) unsigned DEFAULT '0' NOT NULL,
	easydb_objecttype varchar(255) DEFAULT '' NOT NULL,
	easydb_object_id int(11) unsigned DEFAULT '0' NOT NULL,
	easydb_object_version int(11) unsigned DEFAULT '0' NOT NULL,
	easydb_uuid varchar(255) DEFAULT '' NOT NULL
);
CREATE TABLE sys_language (
	easydb_locale varchar(255) DEFAULT '' NOT NULL
);
CREATE TABLE be_sessions (
	cookie_value varchar(32) DEFAULT '' NOT NULL,
	easydb_ses_id varchar(32) DEFAULT '' NOT NULL
);
