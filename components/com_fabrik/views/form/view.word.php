<?php
/**
 * MS Word/Open office .doc Fabrik Form view class
 * Very rough go at implementing .doc rendering based on the fact that they can read HTML
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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
	 * @param   string  $tpl  template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		if (parent::display($tpl) !== false)
		{
			$this->output();
			$app = JFactory::getApplication();
			if (!$app->isAdmin())
			{
				$this->state = $this->get('State');
				$this->document = JFactory::getDocument();
				$model = $this->getModel();
				$this->params = $this->state->get('params');
				$row = $model->getData();
				$w = new FabrikWorker;
				if ($this->params->get('menu-meta_description'))
				{
					$desc = $w->parseMessageForPlaceHolder($this->params->get('menu-meta_description'), $row);
					$this->document->setDescription($desc);
				}

				if ($this->params->get('menu-meta_keywords'))
				{
					$keywords = $w->parseMessageForPlaceHolder($this->params->get('menu-meta_keywords'), $row);
					$this->document->setMetadata('keywords', $keywords);
				}

				if ($this->params->get('robots'))
				{
					$this->document->setMetadata('robots', $this->params->get('robots'));
				}

				// Set the response to indicate a file download
				JResponse::setHeader('Content-Type', 'application/vnd.ms-word');
				$name = $this->getModel()->getTable()->label;
				$name = JStringNormalise::toDashSeparated($name);
				JResponse::setHeader('Content-Disposition', "attachment;filename=\"" . $name . ".doc\"");

				$this->document->setMimeEncoding('text/html; charset=Windows-1252', false);
			}
		}
	}
}
