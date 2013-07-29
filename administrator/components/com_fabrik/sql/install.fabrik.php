<?php

/**
 * DEPRECIATED - USE com_fabrik.mainfest.class.php INSTEAD
 *
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 * @deprecated
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// Try extending time, as unziping/ftping took already quite some... :
@set_time_limit(240);

$memMax = trim(@ini_get('memory_limit'));
if ($memMax)
{
	$last =	strtolower($memMax{JString::strlen($memMax) - 1});
	switch($last)
	{
		case 'g':
			$memMax	*= 1024;
		case 'm':
			$memMax	*= 1024;
		case 'k':
			$memMax	*= 1024;
	}
	if ($memMax < 16000000)
	{
		@ini_set('memory_limit', '16M');
	}
	if ($memMax < 32000000)
	{
		@ini_set('memory_limit', '32M');
	}
	if ($memMax < 48000000)
	{
		@ini_set('memory_limit', '48M');		// DOMIT XML parser can be very memory-hungry on PHP < 5.1.3
	}
}
@ini_set('memory_limit', '64M');
@ini_set('max_execution_time', 380);
ignore_user_abort(true);

/**
 * Installer
 *
 * @deprecated
 *
 * @retrun void
 */

function com_install() {
	//@TODO only run this when installing for the first time
	$app = JFactory::getApplication();
	$db = JFactory::getDbo();

	$db->setQuery("SELECT COUNT(*) FROM #__fabrik_connections");
	$c = $db->loadResult();

	if ($c == 0) {
		$sql = "insert into #__fabrik_connections (`host`,`user`,`password`,`database`,`description`,`published`, `default`) " ;

		$sql .= "VALUES ('" . $app->getCfg('host') . "', " .
							 "\n '" . $app->getCfg('user') ."', " .
							 "\n '" . $app->getCfg('password') ."', " .
							 "\n '" . $app->getCfg('db') ."', " .
							 "\n'site database','1', '1')";

		$db->setQuery($sql);
		$db->execute();

		//update the table's order_by col to allow for multiple order bys
		$db->setQuery("ALTER TABLE `#__fabrik_lists` CHANGE `order_dir` `order_dir` VARCHAR( 255 ) NOT NULL DEFAULT 'ASC'");
		$db->execute();

		//update db field names for fabrik3.0
		$db->setQuery("ALTER TABLE '#__fabrik_elements CHANGE `show_in_list_summary` `show_in_list_summary` INT( 1 ) NULL DEFAULT NULL");
		$db->execute();
		$tables = array('#__fabrik_elements', '#__fabrik_groups', '#_fabrik_jsactions', '#_fabrik_cron', '#__fabrik_forms',
	'#__fabrik_lists', '#__fabrik_connections', '#__fabrik_joins');
		foreach ($tables as $table) {
			$db->setQuery("ALTER TABLE `$table` CHANGE `attribs` `params` TEXT NOT NULL ");
			$db->execute();
		}

		$db->setQuery("ALTER TABLE `#__fabrik_packages` CHANGE `state` `published` TINYINT( 1 ) NOT NULL ");
		$db->execute();

		$db->setQuery("ALTER TABLE `#__fabrik_visualizations` CHANGE `state` `published` INT( 1 ) NOT NULL ");
		$db->execute();

		$db->setQuery("ALTER TABLE `#__fabrik_cron` CHANGE `state` `published` TINYINT( 1 ) NOT NULL ");
		$db->execute();

		$db->setQuery("ALTER TABLE `#__fabrik_lists` CHANGE `state` `published` TINYINT( 1 ) NOT NULL ");
		$db->execute();

		$db->setQuery("ALTER TABLE `#__fabrik_connections` CHANGE `state` `published` INT( 1 ) NOT NULL ");
		$db->execute();

		$db->setQuery("ALTER TABLE `#__fabrik_elements` CHANGE `state` `published` INT( 1 ) NOT NULL ");
		$db->execute();

		$db->setQuery("ALTER TABLE `#__fabrik_forms` CHANGE `state` `published` INT( 1 ) NOT NULL ");
		$db->execute();

		$db->setQuery("ALTER TABLE `#__fabrik_groups` CHANGE `state` `published` INT( 1 ) NOT NULL ");
		$db->execute();

		$db->setQuery("ALTER TABLE `#__fabrik_joins` CHANGE `table_id` `list_id` INT( 6 ) NOT NULL ");
		$db->execute();

		//for new acl in J1.6
		$db->setQuery("ALTER TABLE `#__fabrik_elements` ADD `asset_id` INT( 6 ) NOT NULL COMMENT 'fk to the #__assets table'");
		$db->execute();

		$db->setQuery("ALTER TABLE `#__fabrik_lists` ADD `asset_id` INT( 6 ) NOT NULL COMMENT 'fk to the #__assets table'");
		$db->execute();

		//ELEMENT filter_access parameter moved to JRule access object

		/*//test to ensure that the main component params have a default setup
		$db->setQuery("SELECT id, params FROM #__extension WHERE name = 'fabrik' and type = 'component'");
		$row = $db->loadObject();
		$opts = new stdClass;
		$opts->fbConf_wysiwyg_label = 0;
		$opts->fbConf_alter_existing_db_cols = 0;
		$opts->spoofcheck_on_formsubmission = 0;

		if ($row && $row->params == ''){
			$row->params = json_encode($opts);
			$ok = $db->updateObject('#__extension', $row, 'id', false);
		}*/
	}
}
