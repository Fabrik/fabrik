<?php
/**
 * Fabrik List CSV plugin example script
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.listcsv
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Some example code for creating J! users when importing CSV file.
 *
 * Instructions:
 *
 * 1) Install the listcsv plugin, and add it to the List you are importing to.
 *
 * 2) Copy and rename this file, to whatever name you want, in the same directory,
 * to avoid having your changes overwritten next time you update Fabrik.
 *
 * 3) Modify the REQUIRED 'changethis' options below to match your full element names.
 *
 * 4) Set any of the OPTIONAL options below.
 *
 * 5) On your List plugin settings for the List CSV plugin, select the renamed file from step 2
 *
 * 6) Run your CSV import.  This plugin will run once for each row being imported, and
 * attempt to either create or modify a J! user accordingly. Modification occurs if username already exists
 *
 */
defined('_JEXEC') or die();

require_once JPATH_SITE . '/plugins/fabrik_list/listcsv/scripts/csv_import_user_class.php';

$csv_user = new ImportCSVCreateUser;

/*
 * REQUIRED
 *
 * The full Fabrik element names for the username, email, name and J! userid.
 * The plugin will write the newly created J! userid to the userid element.
 * These four are REQUIRED and the code will fail if they are missing or wrong.
 */

$csv_user->username_element = 'changethis___username';
$csv_user->email_element = 'changethis___email';
$csv_user->name_element = 'changethis___name';
$csv_user->userid_element = 'changethis_userid';

/*
 * OPTIONAL
 *
 * The following are optional:
 *
 * password_element - if specified, plugin we will use this as the clear text password
 * for creating a new user.  This value will be cleared and not saved in the table.
 * If not specified, plugin will generate a random password when creating new users.
 *
 * first_password_element - if specified, the clear text password used to create the
 * user will be stored in this field, whether it came from a specified password_element
 * or was randomly generated.  Can be same as password_element if you want.
 *
 * user_created_element - if specified, this element will be set to a configurable value
 * if a user is created.
 *
 * user_created_value - value to use when setting user_created_element above.
 */

$csv_user->password_element = '';
$csv_user->first_password_element = '';
$csv_user->user_created_element = '';
$csv_user->user_created_value = '1';

$listModel = $this->getModel();
$csv_user->createUser($listModel);

