<?php
/**
 * List Article update plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.article
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Add an action button to the list to enable update of content articles
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.article
 * @since       3.0
 */

class PlgFabrik_ListArticle extends PlgFabrik_List
{
	/**
	 * Button prefix
	 *
	 * @var string
	 */
	protected $buttonPrefix = 'file';

	/**
	 * Prep the button if needed
	 *
	 * @param   array   &$args   Arguments
	 *
	 * @return  bool;
	 */

	public function button(&$args)
	{
		parent::button($args);

		return true;
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */

	protected function getAclParam()
	{
		return 'access';
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 */

	public function canSelectRows()
	{
		return true;
	}

	/**
	 * Get the button label
	 *
	 * @return  string
	 */

	protected function buttonLabel()
	{
		$s = JString::strtoupper($this->buttonPrefix);

		return JText::_('PLG_LIST_ARTICLE_UPDATE_ARTICLE');
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   array   $opts    custom options
	 *
	 * @return  bool
	 */

	public function process($opts = array())
	{
		$model = $this->getModel();
		$params = $this->getParams();
		$input = JFactory::getApplication()->input;
		$ids = $input->get('ids', array(), 'array');
		$origRowId = $input->get('rowid');
		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');

		// Abstract version of the form article plugin
		$articlePlugin = $pluginManager->getPlugin('article', 'form');

		$formModel = $model->getFormModel();
		$formParams = $formModel->getParams();
		$plugins = $formParams->get('plugins');

		foreach ($plugins as $c => $type)
		{
			if ($type === 'article')
			{
				// Iterate over the records - load row & update articles
				foreach ($ids as $id)
				{
					$input->set('rowid', $id);
					$formModel->setRowId($id);
					$formModel->unsetData();
					$formModel->formData = $formModel->formDataWithTableName = $formModel->getData();
					$articlePlugin->setModel($formModel);
					$articlePlugin->setParams($formParams, $c);
					$articlePlugin->onAfterProcess();
				}
			}
		}

		$input->set('rowid', $origRowId);

		return true;
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int  $c  plugin render order
	 *
	 * @return  string
	 */

	public function process_result($c)
	{
		$input = JFactory::getApplication()->input;
		$ids = $input->get('ids', array(), 'array');

		return JText::sprintf('PLG_LIST_ARTICLES_UPDATED', count($ids));
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array   $args    array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */

	public function onLoadJavascriptInstance($args)
	{
		$model = $this->getModel();
		parent::onLoadJavascriptInstance($args);
		$opts = $this->getElementJSOptions($model);
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListArticle($opts)";

		return true;
	}
}
