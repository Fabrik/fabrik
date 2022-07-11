<?php
/**
 * Fabrik List CSV plugin example script
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.listcsv
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
 * 5) On your List's listcsv plugin settings for the List CSV plugin, select the renamed file from step 2 as
 * the "Import Row PHP File", no other settings or changes are needed.
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
 *
 * NOTE - if your import has separate fields for first and last name, you can set $csv_user->name element to
 * be blank, like ...
 *
 * $csv_user->name_element = 'changethis___name';
 *
 * ... and instead set first_name_element and last_name_element in the OPTIONAL settings further on.
 *
 * NOTE - only change the quoted part after the =, like 'changethis___username',
 * do not change the variable name part before the =.
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
 * first_name_element and last_name_element - as noted in the REQUIRED settings, you can optionally
 * use these if you import file uses separate first and last name fields, and we will concatenate first and
 * last names with a space to create the full name for the user.
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
 *
 * group_id_element - if specified, plugin will use the value of this element as the group ID
 * to assign the new user to.  No real sanity checking is done, so BE CAREFUL not to assign people to
 * things like Super Admins!  if not specified, the plugin will use 2 (Registered, in a default
 * Joomla install).
 */

$csv_user->first_name_element = '';
$csv_user->last_name_element = '';
$csv_user->password_element = '';
$csv_user->group_id_element = '';
$csv_user->first_password_element = '';
$csv_user->user_created_element = '';
$csv_user->user_created_value = '1';

$listModel = $this->getModel();
/**
 * If you want to use a group ID other than 2, but don't want to use a field in the import to specify the
 * group ID to use, you can change the default like this ....
 *
 * $csv_user->default_group_id = 123;
 */
$csv_user->createUser($listModel);

