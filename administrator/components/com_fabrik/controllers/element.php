<?php
/**
 * Element controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die;

require_once 'fabcontrollerform.php';

/**
 * Element controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikControllerElement extends FabControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_ELEMENT';

	/**
	 * Set a URL for browser redirection.
	 *
	 * @param   string  $url   URL to redirect to.
	 * @param   string  $msg   Message to display on redirect. Optional, defaults to value set internally by controller, if any.
	 * @param   string  $type  Message type. Optional, defaults to 'message'.
	 *
	 * @return	JController	This object to support chaining.
	 */

	public function setRedirect($url, $msg = null, $type = null)
	{
		$app = JFactory::getApplication();
		$confirmUpdate = $app->getUserState('com_fabrik.confirmUpdate');

		// @TODO override the redirect url if confirm update is needed and task appropriate
		if ($confirmUpdate == true)
		{
			// Odd nes where redirect url was blank - caused blank pages when editing an element
			$testUrl = $app->getUserState('com_fabrik.redirect', '');
			if ($testUrl !== '')
			{
				$url = $testUrl;
			}
		}
		$this->redirect = $url;
		if ($msg !== null)
		{
			// Controller may have set this directly
			$this->message = $msg;
		}
		// Ensure the type is not overwritten by a previous call to setMessage.
		$this->messageType = ($type === null || empty($this->messageType)) ? 'message' : $type;
		return $this;
	}

	/**
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @param   int     $recordId  record id
	 * @param   string  $urlVar    url var
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   11.1
	 */

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		$gid = JRequest::getInt('filter_groupId');
		if ($gid !== 0)
		{
			$append .= '&filter_groupId=' . $gid;
		}
		return $append;
	}

	/**
	 * ask if the user really wants to update element field name/type
	 *
	 * @return  null
	 */

	public function updateStructure()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$pluginManager = JModel::getInstance('Pluginmanager', 'FabrikFEModel');
		$model = $pluginManager->getPlugIn('field', 'element');
		$id = JRequest::getInt('id');
		$model->setId($id);
		$db = $model->getListModel()->getDb();
		$oldName = str_replace('`', '', $app->getUserState('com_fabrik.oldname'));
		$newName = $app->getUserState('com_fabrik.newname');
		$model->updateJoinedPks($oldName, $newName);
		$db->setQuery($app->getUserState('com_fabrik.q'));

		if (!$db->execute())
		{
			JError::raiseWarning(E_WARNING, $db->stderr(true));
			$msg = '';
		}
		else
		{
			$msg = JText::_('COM_FABRIK_STRUCTURE_UPDATED');
		}
		if (JRequest::getCmd('origtask') == 'save')
		{
			$this->setRedirect('index.php?option=com_fabrik&view=elements', $msg);
		}
		else
		{
			$this->setRedirect('index.php?option=com_fabrik&task=element.edit&id=' . $id, $msg);
		}
	}

	/**
	 * user decided to cancel update
	 *
	 * @return  null
	 */

	public function cancelUpdateStructure()
	{
		JRequest::checkToken() or die('Invalid Token');
		$pluginManager = JModel::getInstance('Pluginmanager', 'FabrikFEModel');
		$model = $pluginManager->getPlugIn('field', 'element');
		$model->setId(JRequest::getInt('id'));
		$element = $model->getElement();
		$element->name = JRequest::getWord('oldname');
		$element->plugin = JRequest::getWord('origplugin');
		$element->store();
		if (JRequest::getVar('origtask') == 'save')
		{
			$this->setRedirect('index.php?option=com_fabrik&view=elements', $msg);
		}
		else
		{
			$this->setRedirect('index.php?option=com_fabrik&task=element.edit&id=' . $element->id, $msg);
		}
	}

	/**
	 * Method to save a record.
	 *
	 * @return	boolean
	 */

	public function save()
	{
		$ok = parent::save();
		$app = JFactory::getApplication();
		if (!is_null($app->getUserState('com_fabrik.redirect')))
		{
			$this->setRedirect($app->getUserState('com_fabrik.redirect'));
			$app->setUserState('com_fabrik.redirect', null);
		}
		return $ok;
	}

	/**
	 * When you go from a child to parent element, check in child before redirect
	 *
	 * @deprecated - dont think its used?
	 *
	 * @return  void
	 */

	function parentredirect()
	{
		JRequest::checkToken() or die('Invalid Token');
		$post = JRequest::get('post');
		$id = (int) JArrayHelper::getValue($post['jform'], 'id', 0);
		$pluginManager = JModel::getInstance('Pluginmanager', 'FabrikFEModel');
		$className = JRequest::getVar('plugin', 'field', 'post');
		$elementModel = $pluginManager->getPlugIn($className, 'element');
		$elementModel->setId($id);
		$row = $elementModel->getElement();
		$row->checkin();
		$to = JRequest::getInt('redirectto');
		$this->setRedirect('index.php?option=com_fabrik&task=element.edit&id=' . $to);
	}

}
