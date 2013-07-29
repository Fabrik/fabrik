<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

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
		if (parent::display($tpl) !== false)
		{
			$app = JFactory::getApplication();
			if (!$app->isAdmin())
			{
				$this->state = $this->get('State');
				$this->params = $this->state->get('params');
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
}
