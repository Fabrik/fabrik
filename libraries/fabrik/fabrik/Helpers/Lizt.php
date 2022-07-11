<?php
/**
 * List Helper class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Helpers;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * List Helper class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.0.6
 */

class Lizt
{
	/**
	 * Get a list of elements which match a set of criteria
	 *
	 * @param   object  $listModel  list model to search
	 * @param   array   $filter     array of element properties to match on
	 *
	 * @throws \Exception
	 *
	 * @return  array
	 */

	public static function getElements($listModel, $filter = array())
	{
		$found = array();
		$groups = $listModel->getFormGroupElementData();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getMyElements();

			foreach ($elementModels as $elementModel)
			{
				$item = $elementModel->getElement();
				$ok = true;

				foreach ($filter as $key => $val)
				{
					if ($item->$key != $val)
					{
						$ok = false;
					}
				}

				if ($ok)
				{
					$found[] = $elementModel;
				}
			}
		}

		if (empty($found))
		{
			$filterNames = implode(', ', $filter);
			throw new \Exception(Text::sprintf('COM_FABRIK_ERR_NO_ELEMENTS_MATCHED_FILTER', $filterNames));
		}

		return $found;
	}
}
