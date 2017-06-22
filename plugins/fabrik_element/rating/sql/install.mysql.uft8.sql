CREATE TABLE IF NOT EXISTS  `#__fabrik_ratings` (
	`user_id` VARCHAR( 40 ) NOT NULL ,
	`listid` INT( 6 ) NOT NULL ,
	`formid` INT( 6 ) NOT NULL ,
	`row_id` INT( 6 ) NOT NULL ,
	`rating` INT( 6 ) NOT NULL,
	`date_created` DATETIME NOT NULL,
	`element_id` INT( 6 ) NOT NULL,
	 PRIMARY KEY ( `user_id` , `listid` , `formid` , `row_id`, `element_id` )
);