<?php
/**
 * Content Component HTML Helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Content Component HTML Helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.0
 */

class FabrikHelperAdminHTML
{
	/**
	 * Get a list of directories
	 *
	 * @param   string  $path      path to read from
	 * @param   bool    $fullpath  return full paths or not
	 *
	 * @return  array
	 */

	public static function fabrikListDirs($path, $fullpath = false)
	{
		$arr = array();

		if (!@is_dir($path))
		{
			return $arr;
		}

		$handle = opendir($path);

		while ($file = readdir($handle))
		{
			$dir = JPath::clean($path . '/' . $file);
			$isDir = is_dir($dir);

			if (($file != ".") && ($file != "..") && ($file != ".svn"))
			{
				if ($isDir)
				{
					if ($fullpath)
					{
						$arr[] = trim(JPath::clean($path . '/' . $file));
					}
					else
					{
						$arr[] = trim($file);
					}
				}
			}
		}

		closedir($handle);
		asort($arr);

		return $arr;
	}
}
