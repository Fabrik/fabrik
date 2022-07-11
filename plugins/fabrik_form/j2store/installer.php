<?php
/**
 * J2Store Fabrik Form Installer Script
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.j2store
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class plgFabrik_formJ2StoreInstallerScript
{
	/**
	 * Run when the component is installed
	 *
	 * @param   object  $parent  installer object
	 *
	 * @return bool
	 */
	public function install($parent)
	{
		$src = JPATH_PLUGINS . '/fabrik_form/j2store/content_types/products.xml';
		$dest = JPATH_ADMINISTRATOR . '/components/com_fabrik/models/content_types/producst.xml';
		JFile::copy($src, $dest);
	}

	public function upgrade($parent)
	{
		$src = JPATH_PLUGINS . '/fabrik_form/j2store/content_types/products.xml';
		$dest = JPATH_ADMINISTRATOR . '/components/com_fabrik/models/content_types/producst.xml';
		JFile::copy($src, $dest);
	}
}
