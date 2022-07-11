<?php
/**
 * MS Word/Open office .doc Fabrik List view class
 * Very rough go at implementing .doc rendering based on the fact that they can read HTML
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

/**
 * MS Word/Open office .doc Fabrik List view class
 * Very rough go at implementing .doc rendering based on the fact that they can read HTML
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.7
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
			if (!$this->app->isAdmin())
			{
				$state = $this->get('State');
				$this->params = $state->get('params');

				if ($this->params->get('menu-meta_description'))
				{
					$this->doc->setDescription($this->params->get('menu-meta_description'));
				}

				if ($this->params->get('menu-meta_keywords'))
				{
					$this->doc->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
				}

				if ($this->params->get('robots'))
				{
					$this->doc->setMetadata('robots', $this->params->get('robots'));
				}
			}

			// Set the response to indicate a file download
			$this->app->setHeader('Content-Type', 'application/vnd.ms-word');
			$name = $this->getModel()->getTable()->label;
			$name = JStringNormalise::toDashSeparated($name);
			$this->app->setHeader('Content-Disposition', "attachment;filename=\"" . $name . ".doc\"");
			$this->doc->setMimeEncoding('text/html; charset=Windows-1252', false);
			$this->output();
		}
	}

	public function layoutFilters()
	{
		return '';
	}
}
