CREATE TABLE IF NOT EXISTS `#__fabrik_connections` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`host` VARCHAR(255) NOT NULL,
	`user` VARCHAR(255) NOT NULL,
	`password` VARCHAR(255) NOT NULL,
	`database` VARCHAR(255) NOT NULL,
	`description` VARCHAR(255) NOT NULL,
	`published` INT(1) NOT NULL default '0',
	`checked_out` INT(4) NOT NULL default '0',
	`checked_out_time` DATETIME,
	`default` INT(1) NOT NULL DEFAULT '0',
	`params` TEXT NOT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_cron` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`label` VARCHAR(100) NOT NULL,
	`frequency` SMALLINT(6) NOT NULL,
	`unit` VARCHAR(15) NOT NULL,
	`created` DATETIME NOT NULL,
	`created_by` INT(6) NOT NULL,
	`created_by_alias` VARCHAR(30) NOT NULL,
	`modified` DATETIME NOT NULL,
	`modified_by` VARCHAR(30) NOT NULL,
	`checked_out` INT(6) NOT NULL,
	`checked_out_time` DATETIME NOT NULL,
	`published` TINYINT(1) NOT NULL,
	`plugin` VARCHAR(50) NOT NULL,
	`lastrun` DATETIME NOT NULL,
	`params` TEXT NOT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_elements` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(100) NOT NULL,
	`group_id` INT(4) NOT NULL,
	`plugin` VARCHAR(100) NOT NULL,
	`label` TEXT,
	`checked_out` INT(11) NOT NULL,
	`checked_out_time` datetime NOT NULL,
	`created` datetime NOT NULL,
	`created_by` INT(11) NOT NULL,
	`created_by_alias` varchar(100) NOT NULL,
	`modified` datetime NOT NULL,
	`modified_by` INT(11) NOT NULL,
	`width` INT(4) NOT NULL,
	`height` INT(4) NOT NULL default '0',
	`default` TEXT NOT NULL,
	`hidden` INT(1) NOT NULL,
	`eval` INT(1) NOT NULL,
	`ordering` INT(4) NOT NULL,
	`show_in_list_summary` INT(1), 
	`filter_type` VARCHAR (20),
	`filter_exact_match` INT(1),
	`published` INT(1) NOT NULL default '0',
	`link_to_detail` INT(1) NOT NULL default '0',
	`primary_key` INT(1) NOT NULL default '0',
	`auto_increment` INT(1) NOT NULL default '0',
	`access` INT(1) NOT NULL default '0',
	`use_in_page_title` INT(1) NOT NULL default '0',
	`parent_id` MEDIUMINT(6) NOT NULL,
	`params` MEDIUMTEXT NOT NULL,
	PRIMARY KEY (`id`),
	KEY `show_in_list_summary_INDEX` (`show_in_list_summary`),
	KEY `plugin_INDEX` (`plugin`(10)),
	KEY `checked_out_INDEX` (`checked_out`),
	KEY `group_id_INDEX` (`group_id`),
	KEY `parent_id_INDEX` (`parent_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_formgroup` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`form_id` INT(4) NOT NULL,
	`group_id` INT(4) NOT NULL,
	`ordering` INT(4) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `form_id_INDEX` (`form_id`),
	KEY `group_id_INDEX` (`group_id`),
	KEY `ordering_INDEX` (`ordering`)

) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_forms` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`label` VARCHAR(255) NOT NULL,
	`record_in_database` INT(4) NOT NULL,
	`error` VARCHAR(150) NOT NULL,
	`intro` TEXT NOT NULL,
	`created` datetime NOT NULL,
	`created_by` INT(11) NOT NULL,
	`created_by_alias` VARCHAR(100) NOT NULL,
	`modified` datetime NOT NULL,
	`modified_by` INT(11) NOT NULL,
	`checked_out` INT(11) NOT NULL,
	`checked_out_time` datetime NOT NULL,
	`publish_up` DATETIME, 
	`publish_down` DATETIME,
	`reset_button_label` VARCHAR (100) NOT NULL,
	`submit_button_label` VARCHAR (100) NOT NULL,
	`form_template` varchar(255), 
	`view_only_template` varchar(255),
	`published` INT(1) NOT NULL DEFAULT 0,
	`private` TINYINT(1) NOT NULL DEFAULT '0',
	`params` TEXT NOT NULL,
	PRIMARY KEY (`id`),
	KEY `published_INDEX` (`published`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_form_sessions` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`hash` VARCHAR(255) NOT NULL,
	`user_id` INT(6) NOT NULL,
	`form_id` INT(6) NOT NULL,
	`row_id` INT(10) NOT NULL,
	`last_page` INT(4) NOT NULL,
	`referring_url` VARCHAR(255) NOT NULL,
	`data` MEDIUMTEXT NOT NULL,
	`time_date` TIMESTAMP NOT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_groups` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(100) NOT NULL,
	`css` TEXT NOT NULL,
	`label` VARCHAR(100) NOT NULL,
	`published` INT(1) NOT NULL default '0',
	`created` datetime NOT NULL,
	`created_by` INT(11) NOT NULL,
	`created_by_alias` VARCHAR(100) NOT NULL,
	`modified` datetime NOT NULL,
	`modified_by` INT(11) NOT NULL,
	`checked_out` INT(11) NOT NULL,
	`checked_out_time` datetime NOT NULL,
	`is_join` INT(1) NOT NULL DEFAULT '0',
	`private` TINYINT(1) NOT NULL DEFAULT '0',
	`params` TEXT NOT NULL,
	PRIMARY KEY (`id`),
	KEY `published_INDEX` (`published`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_joins` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`list_id` INT(6) NOT NULL,
	`element_id` INT(6) NOT NULL,
	`join_from_table` VARCHAR(255) NOT NULL,
	`table_join` VARCHAR(255) NOT NULL,
	`table_key` VARCHAR(255) NOT NULL,
	`table_join_key` VARCHAR(255) NOT NULL, 
	`join_type` VARCHAR(255) NOT NULL,
	`group_id` INT(10) NOT NULL,
	`params` TEXT NOT NULL,
	PRIMARY KEY (`id`),
	KEY `list_id_INDEX` (`list_id`),
	KEY `element_id_INDEX` (`element_id`),
	KEY `group_id_INDEX` (`group_id`),
	KEY `table_join_INDEX` (`table_join`(10))
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_jsactions` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`element_id` INT(10) NOT NULL, 
	`action` VARCHAR(255) NOT NULL,
	`code` TEXT NOT NULL,
	`params` TEXT NOT NULL,
	PRIMARY KEY (`id`),
	KEY `element_id_INDEX` (`element_id`)
) DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__fabrik_lists` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`label` VARCHAR (255)  NOT NULL,
	`introduction` TEXT  NOT NULL,
	`form_id` INT(4) NOT NULL,
	`db_table_name` VARCHAR(255) NOT NULL,
	`db_primary_key` VARCHAR(255) NOT NULL,
	`auto_inc` INT(1) NOT NULL,
	`connection_id` INT(6)  NOT NULL,
	`created` DATETIME, 
	`created_by` INT(4) NOT NULL, 
	`created_by_alias` VARCHAR(255) NOT NULL, 
	`modified` DATETIME,
	`modified_by` INT(4) NOT NULL,
	`checked_out` INT(4) NOT NULL,
	`checked_out_time` DATETIME, 
	`published` INT(1) NOT NULL DEFAULT 0,
	`publish_up` DATETIME, 
	`publish_down` DATETIME, 
	`access` INT(4) NOT NULL, 
	`hits` INT(4) NOT NULL,
	`rows_per_page` INT(5) NOT NULL,
	`template` varchar (255) NOT NULL,
	`order_by` varchar (255) NOT NULL,
	`order_dir` varchar(255) NOT NULL default 'ASC',
	`filter_action` varchar(30) NOT NULL, 
	`group_by` VARCHAR(255) NOT NULL, 
	`private` TINYINT(1) NOT NULL DEFAULT '0',
	`params` TEXT NOT NULL,
	PRIMARY KEY (`id`),
	KEY `form_id_INDEX` (`form_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_log` (
	`id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`timedate_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`flag` SMALLINT(3) NOT NULL,
	`referring_url` VARCHAR(255) NOT NULL,
	`message_source` VARCHAR(255) NOT NULL,
	`message_type` CHAR(60) NOT NULL,
	`message` TEXT NOT NULL
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_packages` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`external_ref` VARCHAR(255) NOT NULL,
	`label` VARCHAR(255) NOT NULL,
	`component_name` VARCHAR(100) NOT NULL,
	`version` VARCHAR(10) NOT NULL,
	`published` TINYINT(1) NOT NULL,
	`checked_out` INT(4) NOT NULL,
	`checked_out_time` DATETIME,
	`created` DATETIME NOT NULL,
	`modified` DATETIME NOT NULL,
	`modified_by` INT(6) NOT NULL,
	`template` VARCHAR(255) NOT NULL,
	`params` TEXT NOT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_validations` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`element_id` INT(4) NOT NULL,
	`validation_plugin` VARCHAR (100)  NOT NULL,
	 `message` varchar(255) null,
	`client_side_validation` INT(1) NOT NULL default 0,
	`checked_out` INT(4) NOT NULL,
	`checked_out_time` DATETIME, 
	`params` TEXT NOT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_visualizations` (
	`id` INT(6) NOT NULL AUTO_INCREMENT,
	`plugin` VARCHAR(100) NOT NULL,
	`label` VARCHAR(255) NOT NULL,
	`intro_text` TEXT NOT NULL,
	`created` DATETIME NOT NULL,
	`created_by` INT(11) NOT NULL,
	`created_by_alias` VARCHAR(100) NOT NULL,
	`modified` DATETIME NOT NULL,
	`modified_by` INT(11) NOT NULL,
	`checked_out` INT(11) NOT NULL,
	`checked_out_time` DATETIME NOT NULL,
	`publish_up` DATETIME NOT NULL,
	`publish_down` DATETIME NOT NULL,
	`published` INT(1) NOT NULL,
	`access` INT(6) NOT NULL,
	`params` TEXT NOT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
				