<?php
/**
 * Google Map helper class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Google Map class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.0
 */

class FabGoogleMapHelper
{
	/**
	 * Set the google map style
	 *
	 * @param   object  $params  Element/vis parameters (contains gmap_styles property as json string)
	 *
	 * @since   3.0.7
	 *
	 * @return  array  Styles
	 */
	public static function styleJs($params)
	{
		$styles = $params->get('gmap_styles');
		$styles = is_string($styles) ? json_decode($styles) : $styles;

		if (!$styles)
		{
			return array();
		}

		// Map Feature type to style
		$features = $styles->style_feature;

		// What exactly to style in the feature type (road, fill, border etc)
		$elements = $styles->style_element;
		$styleKeys = $styles->style_styler_key;
		$styleValues = $styles->style_styler_value;

		// First merge any identical feature styles
		$stylers = array();

		for ($i = 0; $i < count($features); $i ++)
		{
			$feature = FArrayHelper::getValue($features, $i);
			$element = FArrayHelper::getValue($elements, $i);
			$key = $feature . '|' . $element;

			if (!array_key_exists($key, $stylers))
			{
				$stylers[$key] = array();
			}

			$aStyle = new stdClass;
			$styleKey = FArrayHelper::getValue($styleKeys, $i);
			$styleValue = FArrayHelper::getValue($styleValues, $i);

			if ($styleKey && $styleValue)
			{
				$aStyle->$styleKey = $styleValue;
				$stylers[$key][] = $aStyle;
			}
		}

		$return = array();

		foreach ($stylers as $styleKey => $styler)
		{
			$o = new stdClass;
			$bits = explode('|', $styleKey);

			if ( $bits[0] !== 'all')
			{
				$o->featureType = $bits[0];
				$o->elementType = $bits[1];
			}

			$o->stylers = $styler;
			$return[] = $o;
		}

		return $return;
	}
}
