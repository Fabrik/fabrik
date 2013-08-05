<?php
/**
 * Content Component HTML Helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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

	/**
	 * Method to create a clickable icon to change the state of an item
	 *
	 * @param   array    $values    Array of values to toggle between
	 * @param   integer  $i         The index
	 * @param   mixed    $selected  The selected value
	 * @param   array    $tasks     Array of task to toggle over
	 * @param   array    $imgs      Array of images to match values
	 * @param   array    $alts      Array of alt text (will be parsed through JText)
	 * @param   string   $prefix    An optional prefix for the task
	 *
	 * @return  string
	 *
	 * @since   3.0.6
	 */

	public static function multistate($values, $i, $selected, $tasks = array('publish', 'unpublish', 'alert'),
		$imgs = array('publish_x.png', 'tick.png', 'alert.png'), $alts = array('JPUBLISHED', 'JUNPUBLISHED', 'NOTICE'), $prefix = '')
	{
		$index = array_search($selected, $values);
		if ($index === false)
		{
			$index = 0;
		}
		$task = JArrayHelper::getValue($tasks, $index);
		$alt = JText::_(JArrayHelper::getValue($alts, $index));

		$img = JArrayHelper::getValue($imgs, $index);
		$href = '
			<a href="#" onclick="return listItemTask(\'cb' . $i . '\',\'' . $prefix . $task . '\')" title="' . $alt . '">'
			. 'todo mulitstateimg' . '</a>';
		return $href;
	}

}
