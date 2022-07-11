CREATE TABLE IF NOT EXISTS  `#__fabrik_sequences` (
	`table_name` VARCHAR( 64 ) NOT NULL,
	`affix` VARCHAR( 32 ) NOT NULL,
	`sequence` INT( 6 ) NOT NULL,
	`date_created` DATETIME NOT NULL,
	`element_id` INT( 6 ) NOT NULL,
	 PRIMARY KEY ( `table_name` , `affix`, `sequence`, `element_id` )
);