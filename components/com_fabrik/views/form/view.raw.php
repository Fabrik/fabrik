<?php
/**
 * Fabrik Raw Form View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
use Fabrik\Helpers\ArrayHelper;

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');
require_once JPATH_SITE . '/components/com_fabrik/views/form/view.base.php';

/**
 * Fabrik Raw Form View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikViewForm extends FabrikViewFormBase
{
	/**
	 * Access value
	 *
	 * @var  int
	 */
	public $access = null;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 */
	public function display($tpl = null)
	{
		$input = $this->app->input;
		$model = $this->getModel('form');

		// Get the active menu item
		$model->render();

		$listModel = $model->getListModel();

		if (!$this->canAccess())
		{
			return false; 
		}

		if (is_object($listModel))
		{
			$joins = $listModel->getJoins();
			$model->getJoinGroupIds($joins);
		}

		$params = $model->getParams();
		$params->def('icons', $this->app->get('icons'));
		$pop = ($input->get('tmpl') == 'component') ? 1 : 0;
		$params->set('popup', $pop);
		$view = $input->get('view', 'form');

		if ($view == 'details')
		{
			$model->setEditable(false);
		}

		$groups    = $model->getGroupsHiarachy();
		$gkeys     = array_keys($groups);
		$JSONarray = array();
		$JSONHtml  = array();

		for ($i = 0; $i < count($gkeys); $i++)
		{
			$groupModel  = $groups[$gkeys[$i]];
			$groupTable  = $groupModel->getGroup();
			$group       = new stdClass;
			$groupParams = $groupModel->getParams();
			$aElements   = array();

			// Check if group is actually a table join
			$repeatGroup = 1;
			$foreignKey  = null;

			if ($groupModel->canRepeat())
			{
				if ($groupModel->isJoin())
				{
					$joinModel  = $groupModel->getJoinModel();
					$joinTable  = $joinModel->getJoin();
					$foreignKey = '';

					if (is_object($joinTable))
					{
						$foreignKey = $joinTable->table_join_key;

						// $$$ rob test!!!
						if (!$groupModel->canView('form'))
						{
							continue;
						}

						$elementModels = $groupModel->getPublishedElements();
						reset($elementModels);
						$tmpElement        = current($elementModels);
						$smallerElHTMLName = $tmpElement->getFullName(true, false);
						$repeatGroup       = count((array) ArrayHelper::getValue($model->data, $smallerElHTMLName, array()));
					}
				}
			}

			$groupModel->repeatTotal = $repeatGroup;
			$aSubGroups              = array();

			for ($c = 0; $c < $repeatGroup; $c++)
			{
				$aSubGroupElements = array();
				$elCount           = 0;
				$elementModels     = $groupModel->getPublishedElements();

				foreach ($elementModels as $elementModel)
				{
					if (!$model->isEditable())
					{
						/* $$$ rob 22/03/2011 changes element keys by appending "_id" to the end, means that
						 * db join add append data doesn't work if for example the popup form is set to allow adding,
						 * but not editing records
						 * $elementModel->inDetailedView = true;
						 */
						$elementModel->setEditable(false);
					}

					// Force reload?
					$elementModel->HTMLids = null;
					$elementHTMLId         = $elementModel->getHTMLId($c);

					if (!$model->isEditable())
					{
						$JSONarray[$elementHTMLId] = $elementModel->getROValue($model->data, $c);
					}
					else
					{
						$JSONarray[$elementHTMLId] = $elementModel->getValue($model->data, $c);
					}
					// Test for paginate plugin
					if (!$model->isEditable())
					{
						$elementModel->HTMLids        = null;
						$elementModel->inDetailedView = true;
					}

					$JSONHtml[$elementHTMLId] = htmlentities($elementModel->render($model->data, $c), ENT_QUOTES, 'UTF-8');
				}
			}
		}

		$data = array("id" => $model->getId(), 'model' => 'table', "errors" => $model->errors, "data" => $JSONarray, 'html' => $JSONHtml,
			'post' => $_REQUEST);
		echo json_encode($data);
	}
}
