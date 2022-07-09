<?php
/**
 * @package		Joomla
 * @subpackage	Fabik
 * @copyright	Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license		GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die();
//$defines = JFile::exists(JPATH_SITE.'/components/com_fabrik/user_defines.php') ? JPATH_SITE.'/components/com_fabrik/user_defines.php' : JPATH_SITE.'/components/com_fabrik/defines.php';
//require_once( $defines );
require_once( JPATH_SITE.'/plugins/content/fabrik/fabrik.php' );

jimport( 'joomla.plugin.plugin' );

/**
 * Fabrik content plugin - renders forms and tables
 *
 * @package		Joomla
 * @subpackage	Content
 * @since 		1.5
 */

class jsFabrik extends plgContentFabrik
{
	function jsFabrik( )
	{
		// need to have this func so the default plgContentFabrik constructor doesn't run
	}

	function jsFabrikRender( $match )
	{
		$lang = JFactory::getLanguage();
		$extension = 'com_fabrik';
		$base_dir = JPATH_SITE . '/components/' . $extension . '/';
		$language_tag = null;
		$reload = true;
		$lang->load($extension, $base_dir, $language_tag, $reload);
		return $this->replace( $match );
	}
}
