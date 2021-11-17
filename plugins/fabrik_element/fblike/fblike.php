<?php
/**
 * Plugin element to render facebook open graph like button
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.facebooklike
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render facebook open graph like button
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.facebooklike
 * @since       3.0
 */

class PlgFabrik_ElementFblike extends PlgFabrik_Element
{
	/**
	 * Does the element have a label
	 *
	 * @var bool
	 */
	protected $hasLabel = false;

	/**
	 * Db table field type
	 *
	 * @var  string
	 */
	protected $fieldDesc = 'INT(%s)';

	/**
	 * Db table field size
	 *
	 * @var  string
	 */
	protected $fieldLength = '1';

	/**
	 * If the list view cant see details records we can't render the plugin
	 * use this var to set single notice
	 *
	 * @var  bool
	 */
	protected static $warned = false;

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      Elements data
	 * @param   stdClass  &$thisRow  All the data in the lists current row
	 * @param   array     $opts      Rendering options
	 *
	 * @return  string	formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
        $profiler = JProfiler::getInstance('Application');
        JDEBUG ? $profiler->mark("renderListData: {$this->element->plugin}: start: {$this->element->name}") : null;

        if ($this->app->input->get('format') === 'raw')
		{
			return $data;
		}

		$input = $this->app->input;
		$params = $this->getParams();
		$meta = array();
		$ex = $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://';

		// $$$ rob no need to get other meta data as we are linking to the details which contains full meta info on what it is
		// you are liking
		$meta['og:url'] = $ex . $input->server->getString('SERVER_NAME') . $input->server->getString('REQUEST_URI');
		$meta['og:site_name'] = $this->config->get('sitename');
		$meta['fb:admins'] = $params->get('fblike_opengraph_applicationid');
		$str = FabrikHelperHTML::facebookGraphAPI($params->get('opengraph_applicationid'), $params->get('fblike_locale', 'en_US'), $meta);

		// In list view we link to the detailed record not the list view itself
		// means form or details view must be viewable by the user
		$url = $this->getListModel()->linkHref($this, $thisRow);

		if ($url === '')
		{
			if (!self::$warned)
			{
				$this->app->enqueueMessage('Your list needs to have viewable details records for the FB Like button to work');
				self::$warned = true;
			}

			return '';
		}

		return $str . $this->_render($url);
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$input = $this->app->input;
		$meta = array();
		$formModel = $this->getFormModel();
		$ex = $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://';
		$map = array(
			'og:title' => 'fblike_title',
			'og:type' => 'fblike_type',
			'og:image' => 'fblike_image',
			'og:description' => 'fblike_description',
			'og:street-address' => 'fblike_street_address',
			'og:locality' => 'fblike_locality',
			'og:region' => 'fblike_region',
			'og:postal-code' => 'fblike_postal_code',
			'og:country-name' => 'fblike_country',
			'og:email' => 'fblike_email',
			'og:phone_number' => 'fblike_phone_number',
			'og:fax_number' => 'fblike_fax_number'
		);

		foreach ($map as $k => $v)
		{
			$elid = $params->get($v);

			if ($elid != '')
			{
				$el = $formModel->getElement($elid, true);

				if (is_object($el))
				{
					$name = $el->getFullName(true, false);
					$v = FArrayHelper::getValue($data, $name);

					if ($k == 'og:image')
					{
						$v = $ex . $input->server->getString('SERVER_NAME') . $v;
					}

					if ($v !== '')
					{
						$meta[$k] = $v;
					}
				}
			}
		}

		$locEl = $formModel->getElement($params->get('fblike_location'), true);

		if ($locEl != '')
		{
			$loc = FArrayHelper::getValue($data, $locEl->getFullName(true, false));
			$loc = array_shift(explode(':', $loc));
			$loc = explode(",", $loc);

			if (count($loc) == 2)
			{
				$meta['og:latitude'] = $loc[0];
				$meta['og:longitude'] = $loc[1];
			}
		}

		$meta['og:url'] = $ex . $input->server->getString('SERVER_NAME') . $input->server->getString('REQUEST_URI');
		$meta['og:site_name'] = $this->config->get('sitename');
		$meta['fb:app_id'] = $params->get('fblike_opengraph_applicationid');
		$str = FabrikHelperHTML::facebookGraphAPI($params->get('fblike_opengraph_applicationid'), $params->get('fblike_locale', 'en_US'), $meta);
		$url = $params->get('fblike_url');
		$w = new FabrikWorker;
		$url = $w->parseMessageForPlaceHolder($url, $data);
		$this->getElement()->hidden = true;

		return $str . $this->_render($url);
	}

	/**
	 * Render the button
	 *
	 * @param   string  $url  button url
	 *
	 * @return string
	 */
	protected function _render($url)
	{
		$params = $this->getParams();
		$input = $this->app->input;

		if ($url !== '')
		{
			if (!strstr($url, COM_FABRIK_LIVESITE))
			{
				// $$$ rob doesn't work with sef urls as $url already contains site folder.
				// $url = COM_FABRIK_LIVESITE.$url;
				$base = JURI::base();
				$ex = $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://';
				$url = $ex . $input->server->getString('SERVER_NAME') . $url;
			}
		}
		else
		{
			$href = '';
		}

		$data = new stdClass;
		$data->url = $url;
		$data->layout = $params->get('fblike_layout', 'standard');
		$data->showfaces = $params->get('fblike_showfaces', 0) == 1 ? 'true' : 'false';
		$data->includeShare = $params->get('fblike_include_share', 0) == 1 ? 'true' : 'false';
		$data->width = $params->get('fblike_width', 300);
		$data->action = $params->get('fblike_action', 'like');
		$data->font = $params->get('fblike_font', 'arial');
		$data->colorscheme = $params->get('fblike_colorscheme', 'light');
		$jLayout = $this->getLayout('form');

		return $jLayout->render($data);
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->listid = $this->getListModel()->getId();
		$opts->elid = $this->getElement()->id;
		$opts->row_id = $this->getFormModel()->getRowId();

		return array('FbLike', $id, $opts);
	}

	/**
	 * Called via Facebook event subscription (useful for ordering)
	 *
	 * @return  null
	 */
	public function onAjax_rate()
	{
		$input = $this->app->input;
		$this->loadMeForAjax();
		$listId = $input->getInt('listid');
		$list = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$list->setId($listId);
		$rowId = $input->get('row_id');
		$direction = $input->get('direction', '+');
		$field = $this->getFullName(false, false, false);
		$update = $field . ' = ' . $field . ' ' . $direction . ' 1';
		$list->updateRows(array($rowId), $field, null, $update);
	}
}
