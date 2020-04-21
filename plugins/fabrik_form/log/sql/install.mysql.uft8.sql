CREATE TABLE IF NOT EXISTS `#__{package}_change_log_fields` (
	`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`parent_id` INT( 11 ) NOT NULL,
	`user_id` INT( 11 ) NOT NULL ,
	`time_date` DATETIME NOT NULL ,
	`form_id` INT( 11 ) NOT NULL,
    `list_id` INT( 11 ) NOT NULL,
    `element_id` INT( 11 ) NOT NULL,
	`row_id` INT( 11 ) NOT NULL,
	`join_id` INT( 11 ),
	`parent_id` INT( 11 ),
    `pk_id` INT( 11 ) NOT NULL,
	`table_name` VARCHAR( 256 ) NOT NULL,
	`field_name` VARCHAR( 256 ) NOT NULL,
	`log_type_id` INT( 11 ) NOT NULL,
	`orig_value` TEXT,
	`new_value` TEXT
);

CREATE TABLE IF NOT EXISTS `#__{package}_change_log` (
     `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
     `user_id` INT( 11 ) NOT NULL ,
     `ip_address` CHAR( 14 ) NOT NULL ,
     `referrer` TEXT,
     `time_date` DATETIME NOT NULL ,
     `form_id` INT( 11 ) NOT NULL,
     `list_id` INT( 11 ) NOT NULL,
     `row_id` INT( 11 ) NOT NULL,
     `join_id` INT( 11 ),
     `log_type_id` INT( 11 ) NOT NULL
);

CREATE TABLE IF NOT EXISTS `#__{package}_change_log_types` (
     `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `type` VARCHAR( 32 ) NOT NULL
);

INSERT IGNORE INTO `#__{package}_change_log_types` (id, type)
VALUES
       (1, 'Add Row'),
       (2, 'Edit Row'),
       (3, 'Delete Row'),
       (4, 'Submit Form'),
       (5, 'Load Form'),
       (6, 'Delete Row'),
       (7, 'Add Joined Row'),
       (8, 'Delete Joined Row'),
       (9, 'Field Value Change'),
       (10, 'Edit Joined Row'),
       (11, 'Load Details')