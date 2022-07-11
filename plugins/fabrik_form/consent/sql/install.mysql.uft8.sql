CREATE TABLE IF NOT EXISTS `#__fabrik_privacy` (
	`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`date_time` DATETIME DEFAULT NULL,
	`list_id` INT( 6 ) NOT NULL,
	`form_id` INT( 6 ) NOT NULL,
	`row_id` INT( 6 ) NOT NULL ,
	`user_id` INT( 6 ) NOT NULL ,
	`consent_message` TEXT NOT NULL,
	`update_record` TINYINT( 1 ) NOT NULL ,
	`ip` VARCHAR( 100 ) NOT NULL,
	`newsletter_engine` VARCHAR(50) NULL,
	`sublist_id` INT(6) NOT NULL,
	`subid` INT(6) NOT NULL
);
 