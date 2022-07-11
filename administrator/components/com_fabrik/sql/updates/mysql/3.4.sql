ALTER TABLE `#__fabrik_elements` ADD INDEX (show_in_list_summary);
ALTER TABLE `#__fabrik_elements` ADD INDEX (plugin(10));
ALTER TABLE `#__fabrik_elements` ADD INDEX (checked_out);
ALTER TABLE `#__fabrik_elements` ADD INDEX (group_id);
ALTER TABLE `#__fabrik_elements` ADD INDEX (parent_id);
 
ALTER TABLE `#__fabrik_formgroup` ADD INDEX (group_id);
ALTER TABLE `#__fabrik_formgroup` ADD INDEX (form_id);
ALTER TABLE `#__fabrik_formgroup` ADD INDEX (ordering);

ALTER TABLE `#__fabrik_groups` ADD INDEX (published);

ALTER TABLE `#__fabrik_joins` ADD INDEX (list_id);
ALTER TABLE `#__fabrik_joins` ADD INDEX (element_id);
ALTER TABLE `#__fabrik_joins` ADD INDEX (group_id);
ALTER TABLE `#__fabrik_joins` ADD INDEX (table_join(100));

ALTER TABLE `#__fabrik_forms` ADD INDEX (published);

ALTER TABLE `#__fabrik_lists` ADD INDEX (form_id);

ALTER TABLE `#__fabrik_jsactions` ADD INDEX (element_id);
