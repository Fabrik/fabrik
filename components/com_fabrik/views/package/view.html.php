<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Package HTML view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.6
 */
class FabrikViewPackage extends FabrikView
{
	/**
	 * Display
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		FabrikHelperHTML::framework();
		$input = $this->app->input;
		$item  = $this->get('Item');
		$srcs  = array('media/com_fabrik/js/icons.js', 'media/com_fabrik/js/icongen.js', 'media/com_fabrik/js/canvas.js',
			'media/com_fabrik/js/history.js', 'media/com_fabrik/js/keynav.js', 'media/com_fabrik/js/tabs.js',
			'media/com_fabrik/js/pages.js', 'media/com_fabrik/js/frontpackage.js');

		FabrikHelperHTML::script($srcs);

		FabrikHelperHTML::stylesheet('media/com_fabrik/css/package.css');
		$canvas = $item->params->get('canvas');

		// $$$ rob 08/11/2011 test if component name set but still rendering
		// in option=com_fabrik then we should use fabrik as the package
		if ($input->get('option') === 'com_fabrik')
		{
			$item->component_name = 'fabrik';
		}

		$tabs = FArrayHelper::getValue($canvas, 'tabs', array('Page 1'));
		$tabs = json_encode($tabs);
		$d    = new stdClass;

		$layout = json_encode(FArrayHelper::getValue($canvas, 'layout', $d));
		$id     = $this->get('State')->get('package.id');
		$script = "window.addEvent('fabrik.loaded', function() {
			new FrontPackage({
		tabs : $tabs,
		tabelement : 'packagemenu',
		pagecontainer : 'packagepages',
		layout: $layout,
		'packageId': $id,
		'package':'$item->component_name'
	});
		});";
		FabrikHelperHTML::addScriptDeclaration($script);

		// Force front end templates
		$this->_basePath = COM_FABRIK_FRONTEND . '/views';
		$tmpl            = !isset($item->template) ? 'default' : $item->template;
		$this->addTemplatePath($this->_basePath . '/' . $this->_name . '/tmpl/' . $tmpl);
		$text = $this->loadTemplate();
		FabrikHelperHTML::runContentPlugins($text);
		echo $text;
	}
}
