<?php
/**
 * HTML Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

/**
 * HTML Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikViewList extends FabrikViewListBase
{

	/**
	 * Display the template
	 *
	 * @param   sting  $tpl  template
	 *
	 * @return void
	 */

	public function display($tpl = null)
	{
		$this->loadTabs();

		if (parent::display($tpl) !== false)
		{
			$app = JFactory::getApplication();
			if (!$app->isAdmin() && isset($this->params))
			{
				$this->state = $this->get('State');
				$this->document = JFactory::getDocument();
				if ($this->params->get('menu-meta_description'))
				{
					$this->document->setDescription($this->params->get('menu-meta_description'));
				}

				if ($this->params->get('menu-meta_keywords'))
				{
					$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
				}

				if ($this->params->get('robots'))
				{
					$this->document->setMetadata('robots', $this->params->get('robots'));
				}
			}
			$this->output();
		}
	}

	/**
	 * Set the List's tab HTML
	 *
	 * @return  null
	 */

	protected function loadTabs()
	{
		$this->tabs = array();
		$model = $this->getModel();
		$this->rows = $model->getData();
		$tabs = $model->tabs;
		if (!is_array($tabs) || empty($tabs))
		{
			return;
		}
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$listid = $model->getId();
		$tabsField = $model->tabsField;
		$uri = JURI::getInstance();
		$urlBase = $uri->toString(array('path'));
		$urlBase .= "?option=com_" . $package . "&";
		if ($app->isAdmin())
		{
			$urlBase .= "task=list.view&";
		}
		else
		{
			$urlBase .= "view=list&";
		}
		$urlBase .= "listid=" . $listid . "&resetfilters=1";
		$urlEquals = $urlBase . "&" . $tabsField . "=%s";
		$urlRange = $urlBase . "&" . $tabsField . "[value][]=%s&" . $tabsField . "[value][]=%s&" . $tabsField . "[condition]=BETWEEN";
		foreach ($tabs as $i => $tabArray)
		{
			list($label, $range) = $tabArray;
			if (empty($range))
			{
				$this->tabs[] = array($label, $urlBase);
			}
			elseif (!is_array($range))
			{
				$this->tabs[] = array($label, sprintf($urlEquals, $range));
			}
			else
			{
				list($low, $high) = $range;
				$this->tabs[] = array($label, sprintf($urlRange, $low, $high));
			}
		}
	}
}
