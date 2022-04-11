<?php
/**
 * HTML Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\StringHelper;

jimport('joomla.application.component.view');
require_once JPATH_SITE . '/components/com_fabrik/views/form/view.base.php';

/**
 * HTML Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.6
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
			$this->setCanonicalLink();
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

				$formModel = $this->getModel();
				$listModel = $formModel->getListModel();
				$listParams = $listModel->getParams();
				$sefSlug = $listParams->get('sef-slug', '');

				if (!empty($sefSlug))
				{
					$elementName = StringHelper::safeColNameToArrayKey($sefSlug);
					$slug = ArrayHelper::getValue($formModel->data, $elementName, '');

					if (!empty($slug))
					{
						$pathway = $this->app->getPathway();
						$pathway->addItem($slug);
					}
				}
			}
		}
	}

	/**
	 * Set the canonical link - this is the definitive URL that Google et all, will use
	 * to determine if duplicate URLs are the same content
	 *
	 * @return  string
	 */
	public function getCanonicalLink()
	{
		$url = '';

		if (!$this->app->isClient('administrator') && !$this->isMambot)
		{
			/** @var FabrikFEModelForm $model */
			$model  = $this->getModel();
			$data   = $model->getData();
			$formId = $model->getId();
			$slug   = $model->getListModel()->getSlug(ArrayHelper::toObject($data));
			$rowId  = $slug === '' ? $model->getRowId() : $slug;
			$view   = $model->isEditable() ? 'form' : 'details';
			$url    = JRoute::_('index.php?option=com_' . $this->package . '&view=' . $view . '&formid=' . $formId . '&rowid=' . $rowId);
		}

		return $url;
	}

	/**
	 * Set the canonical link - this is the definitive URL that Google et all, will use
	 * to determine if duplicate URLs are the same content
	 *
	 * @throws Exception
	 */
	public function setCanonicalLink()
	{
		if (!$this->app->isClient('administrator') && !$this->isMambot)
		{
			$url = $this->getCanonicalLink();

			// Set a flag so that the system plugin can clear out any other canonical links.
			$this->session->set('fabrik.clearCanonical', true);
			try
			{
				$this->doc->addCustomTag('<link rel="canonical" href="' . htmlspecialchars($url) . '" />');
			} catch (Exception $err)
			{

			}

		}
	}
}
