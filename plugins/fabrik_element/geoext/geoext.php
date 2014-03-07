<?php
/**
 * Fabrik GoogleMap Element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.googlemap
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

//require_once JPATH_SITE . '/components/com_fabrik/models/element.php';


class plgFabrik_ElementGeoext extends plgFabrik_Element {

	protected static $geoJs = null;

	/**
	 * as different map instances may or may not load geo.js we shouldnt put it in
	 * formJavascriptClass() but call this code from elementJavascript() instead.
	 * The files are still only loaded when needed and only once
	 */
	protected function geoJs()
	{
		if (!isset(self::$geoJs)) {
			$document = JFactory::getDocument();
			$params = $this->getParams();
			if ($params->get('fb_gm_defaultloc')) {
				$uri = JURI::getInstance();
				$document->addScript($uri->getScheme() . '://code.google.com/apis/gears/gears_init.js');
				FabrikHelperHTML::script('components/com_fabrik/libs/geo-location/geo.js');
				self::$geoJs = true;
			}
		}
	}
        
	/**
	 * 
	 * Méthode assurant le chargement des lib javascripts nécessaires
	 * au bon fonctionnement de la carte en saisie et consultation.
	 */
	protected function loadGeoext()
	{
		$document = JFactory::getDocument();
		$params = $this->getParams();
		
		$uri = JURI::getInstance();
		
		$document->addScript('http://extjs.cachefly.net/ext-3.4.0/adapter/ext/ext-base.js');
		$document->addScript(COM_FABRIK_LIVESITE.'plugins/fabrik_element/geoext/script/ext-all-debug.js');
		$document->addScript(COM_FABRIK_LIVESITE.'plugins/fabrik_element/geoext/script/OpenLayers-2.11/OpenLayers.js');
                $document->addScript(COM_FABRIK_LIVESITE.'plugins/fabrik_element/geoext/script/OpenLayers-2.11/lib/OpenLayers/Lang/fr.js');
		JHTML::script('admincck.js', 'administrator/components/com_fabrik/views/', true);		
		$document->addScript('http://maps.google.com/maps/api/js?v=3.5&amp;sensor=false');
                $document->addScript(COM_FABRIK_LIVESITE.'plugins/fabrik_element/geoext/script/GeoExt.js');
		JHtml::stylesheet('http://extjs.cachefly.net/ext-3.4.0/resources/css/ext-all.css');
                JHtml::stylesheet('http://extjs.cachefly.net/ext-3.4.0/resources/css/xtheme-gray.css');
                JHtml::stylesheet('http://extjs.cachefly.net/ext-3.4.0/examples/shared/examples.css');
                JHtml::stylesheet('http:///www.openlayers.org/dev/theme/default/style.css');
                JHtml::stylesheet(COM_FABRIK_LIVESITE.'plugins/fabrik_element/geoext/script/tools.css');
	}

       
	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @param int repeat group counter
	 * @return string javascript to create instance. Instance name must be 'el'
	 */
	function elementJavascript($repeatCounter)
	{	
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
                $formModel = $this->getFormModel();
		$data = $formModel->data;
		$v = $this->getValue($data, $repeatCounter);
                
                $opts = $this->getElementJSOptions($repeatCounter);
		$this->geoJs();
		
		//chargement des librairies js utiles au bon fonctionnement de geoext.js
		$this->loadGeoext();
		$opts->rowid = (int)JArrayHelper::getValue($data, 'rowid');
		$opts->eval = $params->get('fb_ge_eval');
                $opts->drawablemap = $this->isEditable();
                
		// Get Default Location
		if ($opts->eval == "1") {
			//if the default value start with a ( the lat and lon must be fix by default
			$w = new FabrikWorker();
			$eval_value = $w->parseMessageForPlaceHolder($params->get('fb_ge_default'), $data);
			$eval_value = @eval(stripslashes($eval_value));
			FabrikWorker::logEval($eval_value, 'Caught exception on eval in '.$element->name.'::getDefaultValue() : %s');
			$opts->default_loc = $eval_value;
		}else{
			$opts->default_loc = $params->get('fb_ge_default');
		}
		//récupération du type de dessin pour paramétrage des options de dessin.
		$opts->drawtype = $params->get('fb_ge_typedraw','point');
                $opts->bg_layers = $params->get('fb_ge_bg_layers');
                $opts->layers = $params->get('fb_ge_layers');
                $opts->geom = $v;
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_NAVIGATE');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_DRAW');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_EDIT');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_DELETE');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_DISTANCE');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_SURFACE');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_NAVIGATE_DESC');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_DRAW_POLY_DESC');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_DRAW_LINE_DESC');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_DRAW_POINT_DESC');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_EDIT_DESC');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_DELETE_DESC');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_DISTANCE_DESC');
                JText::script('PLG_ELEMENT_GEOEXT_TOOL_SURFACE_DESC');
		
		
                return array('FbGeoext', $id, $opts);
	}

	/** fonction permettant de définir si l'élément est considéré comme vide pour la règle de gestion **/
	function dataConsideredEmpty($data, $repeatCounter)
	{
		return ($data == '') ? true : false;
	}
        
        /**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */
	public function getFieldDescription()
	{
		return "TEXT";
	}
        
        /**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */
	function render($data, $repeatCounter = 0)
	{
		require_once(COM_FABRIK_FRONTEND.'/helpers/string.php');
		$id         = $this->getHTMLId($repeatCounter);
		$name       = $this->getHTMLName($repeatCounter);
		$val        = $this->getValue($data, $repeatCounter);
		
		$str =  '<div class="fabrikSubElementContainer" id="' . $id . '">';
                $str .= '<div class="mapPanel"></div>';
		$str .= '<input type="hidden" class="fabrikinput" name="'.$name.'" value="'.htmlspecialchars($val, ENT_QUOTES).'" />';
               // $str .= '<input type="hidden" class="fabrikinput" name="'.$name.'_extent" value="" />';
		$str .= '</div>';

		return $str;
	}
        
        /** overwrite in plugin **/
	function validate($data, $repeatCounter = 0)
	{
		if (!($data ==''))
		{
			return true;
		}else{
			return false;
		}
	}
}
?>
