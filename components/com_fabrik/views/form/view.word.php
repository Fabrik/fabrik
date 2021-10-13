<?php
/**
 * MS Word/Open office .doc Fabrik Form view class
 * Very rough go at implementing .doc rendering based on the fact that they can read HTML
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');
require_once JPATH_SITE . '/components/com_fabrik/views/form/view.base.php';

/**
 * MS Word/Open office .doc Fabrik Form view class
 * Very rough go at implementing .doc rendering based on the fact that they can read HTML
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.7
 */
class FabrikViewForm extends FabrikViewFormBase
{
	/**
	 * Main setup routine for displaying the form/detail view
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		if (parent::display($tpl) !== false)
		{
			$this->output();

			if (!$this->app->isClient('administrator'))
			{
				$this->state  = $this->get('State');
				$model        = $this->getModel();
				$this->params = $this->state->get('params');
				$row          = $model->getData();
				$w            = new FabrikWorker;

				if ($this->params->get('menu-meta_description'))
				{
					$desc = $w->parseMessageForPlaceHolder($this->params->get('menu-meta_description'), $row);
					$this->doc->setDescription($desc);
				}

				if ($this->params->get('menu-meta_keywords'))
				{
					$keywords = $w->parseMessageForPlaceHolder($this->params->get('menu-meta_keywords'), $row);
					$this->doc->setMetadata('keywords', $keywords);
				}

				if ($this->params->get('robots'))
				{
					$this->doc->setMetadata('robots', $this->params->get('robots'));
				}

				// Set the response to indicate a file download
				$this->app->setHeader('Content-Type', 'application/vnd.ms-word');
				$name = $this->getModel()->getTable()->label;
				$name = JStringNormalise::toDashSeparated($name);
				$this->app->setHeader('Content-Disposition', "attachment;filename=\"" . $name . ".doc\"");
				$this->doc->setMimeEncoding('text/html; charset=Windows-1252', false);
			}
		}
	}
}
