CREATE TABLE IF NOT EXISTS  `#__fabrik_attending` (
	`user_id` INT( 6 ) NOT NULL ,
	`list_id` INT( 6 ) NOT NULL ,
	`form_id` INT( 6 ) NOT NULL ,
	`row_id` INT( 6 ) NOT NULL ,
	`element_id` int ( 6 ) NOT NULL,
	`type` VARCHAR( 255 ) NOT NULL,
	`data` TEXT NOT NULL,
	`date_created` DATETIME NOT NULL,
	 PRIMARY KEY ( `user_id` , `list_id` , `form_id` , `row_id`, `element_id` )
);
