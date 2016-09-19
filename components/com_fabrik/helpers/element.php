<?php
/**
 * Element Helper class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

/**
 * Element Helper class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.0.6
 */

class FabrikHelperElement
{
	/**
	 * For processing repeat elements we need to make its
	 * ID element during the form process
	 *
	 * @param   plgFabrik_Element  $baseElement  repeat element (e.g. db join rendered as checkbox)
	 *
	 * @return  plgFabrik_ElementInternalid
	 */

	public static function makeIdElement($baseElement)
	{
		$pluginManager = FabrikWorker::getPluginManager();
		$groupModel = $baseElement->getGroupModel();
		$elementModel = $pluginManager->getPlugIn('internalid', 'element');
		$elementModel->getElement()->name = 'id';
		$elementModel->getParams()->set('repeat', $baseElement->isJoin());
		$elementModel->getElement()->group_id = $groupModel->getId();
		$elementModel->setGroupModel($baseElement->getGroupModel());
		$elementModel->_joinModel = $groupModel->getJoinModel();

		return $elementModel;
	}

	/**
	 * For processing repeat elements we need to make its
	 * parent id element during the form process
	 *
	 * @param   plgFabrik_Element  $baseElement  repeat element (e.g. db join rendered as checkbox)
	 *
	 * @return  plgFabrik_ElementField
	 */

	public static function makeParentElement($baseElement)
	{
		$pluginManager = FabrikWorker::getPluginManager();
		$groupModel = $baseElement->getGroupModel();
		$elementModel = $pluginManager->getPlugIn('field', 'element');
		$elementModel->getElement()->name = 'parent_id';
		$elementModel->getParams()->set('repeat', $baseElement->isJoin());
		$elementModel->getElement()->group_id = $groupModel->getId();
		$elementModel->setGroupModel($baseElement->getGroupModel());
		$elementModel->_joinModel = $groupModel->getJoinModel();

		return $elementModel;
	}

	/**
	 * Short cut for getting the element's filter value, or false if no value
	 *
	 * @param   int  $elementId  Element id
	 *
	 * @since   3.0.7
	 *
	 * @return  mixed
	 */

	public static function filterValue($elementId)
	{
		$app = JFactory::getApplication();
		$pluginManager = FabrikWorker::getPluginManager();
		$model = $pluginManager->getElementPlugin($elementId);
		$listModel = $model->getListModel();
		$listId = $listModel->getId();
		$key = 'com_fabrik.list' . $listId . '_com_fabrik_' . $listId . '.filter';
		$filters = ArrayHelper::fromObject($app->getUserState($key));
		$elementIds = (array) FArrayHelper::getValue($filters, 'elementid', array());
		$index = array_search($elementId, $elementIds);
		$value = $index === false ? false : FArrayHelper::getValue($filters['value'], $index, false);

		return $value;
	}

	/**
	 * Is the key part of an element join's data. Used in csv import/export
	 *
	 * @param   FabrikFEModelForm  $model  Form model
	 * @param   string             $key  Key - full element name or full element name with _id / ___params appended
	 *
	 * @return boolean
	 */
	public static function keyIsElementJoinInfo($model, $key)
	{
		$elementModel = self::findElementFromJoinKeys($model, $key);

		if ($elementModel && $elementModel->isJoin())
		{
			return true;
		}

		return false;
	}

	/**
	 * Find the element associated with a key.
	 * Loose lookup to find join element from any key related to the join (e.g. _id & __params).
	 * Used in csv import/export
	 *
	 * @param   FabrikFEModelForm  $model  Form model
	 * @param   string             $key    Key - full element name or full element name with _id / ___params appended
	 *
	 * @return  PlgFabrik_Element|boolean
	 */
	public static function findElementFromJoinKeys($model, $key)
	{
		// Search on fullname fullname_id and fullname___params
		$lookUps = array($key, substr($key, 0, JString::strlen($key) - 3), substr($key, 0, JString::strlen($key) - 9));

		foreach ($lookUps as $lookup)
		{
			$elementModel = $model->getElement($lookup);

			if ($elementModel)
			{
				return $elementModel;
			}
		}

		return false;
	}
}
