<?php
/**
 * Element Helper class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

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
	 * Short cut for getting the element's filter value
	 *
	 * @param   int  $elementId  Element id
	 *
	 * @since   3.0.7
	 *
	 * @return  string
	 */

	public static function filterValue($elementId)
	{
		$app = JFactory::getApplication();
		$pluginManager = FabrikWorker::getPluginManager();
		$model = $pluginManager->getElementPlugin($elementId);
		$listModel = $model->getListModel();
		$listid = $listModel->getId();
		$key = 'com_fabrik.list' . $listid . '_com_fabrik_' . $listid . '.filter';
		$filters = JArrayHelper::fromObject($app->getUserState($key));
		$elementIds = (array) JArrayHelper::getValue($filters, 'elementid', array());
		$index = array_search($elementId, $elementIds);
		$value = $filters['value'][$index];
		return $value;
	}
}
