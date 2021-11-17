<?php

/**
 * @package		Joomla
 * @subpackage	Fabik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html */

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once( JPATH_ROOT .'/components/com_community/libraries/core.php');

class plgCommunityFabrik extends CApplications
{
	var $name 		= "Fabrik for JomSocial";
	var $_name		= 'fabrik';
	var $_path		= '';
	var $_user		= '';
	var $_my		= '';


	function onProfileDisplay()
	{
		$config	= CFactory::getConfig();
		$this->loadUserParams();

		$uri		= JURI::base();
		//$user		= CFactory::getActiveProfile();
		$user		= CFactory::getRequestUser();
		$document	= JFactory::getDocument();
		$css		= $uri	.'plugins/community/groups/style.css';
		$document->addStyleSheet($css);

		$view = $this->params->get('fabrik_view');
		$id = $this->params->get('fabrik_view_id');
		$rowid = $this->params->get('fabrik_row_id');
		$usekey = $this->params->get('fabrik_usekey');
		$layout = $this->params->get('fabrik_layout');
		$additional = $this->params->get('fabrik_additional');
		$element = $this->params->get('fabrik_element');

		if( !empty($view) && !empty($id) ) {
			$cache = JFactory::getCache('plgCommunityFabrik');
			$cache->setCaching($this->params->get('cache', 1));
			$className = 'plgCommunityFabrik';
			$callback = array($className, '_getFabrikHTML');

			$content = $cache->call($callback, $view, $id, $rowid, $usekey, $layout, $element, $additional, $this->userparams, $user->id);
		}else{
			$content = "<div class=\"icon-nopost\"><img src='".JURI::base()."components/com_community/assets/error.gif' alt=\"\" /></div>";
			$content .= "<div class=\"content-nopost\">".JText::_('Fabrik view details not set.')."</div>";
		}

		return $content;
	}

	static function _getFabrikHTML($view, $id, $rowid, $usekey, $layout, $element, $additional, $params, $userId) {
		require_once( JPATH_BASE.'/plugins/community/fabrik/fabrik/api_class.php');
		$jsFabrik = new jsFabrik();
		$plugin_cmd = '';
		switch ($view) {
			case 'form':
			case 'details':
				if (empty($rowid) || $rowid == '-2') {
					$rowid = $userId;
				}
				$usekey = (empty($usekey)) ? '' : " usekey=$usekey";
				$plugin_cmd = "fabrik view=$view id=$id rowid=$rowid$usekey";
				break;
			case 'table':
			case 'list':
				$plugin_cmd = "fabrik view=$view id=$id profileid=$userId";
				break;
			case 'visualization':
				$plugin_cmd = "fabrik view=$view id=$id profileid=$userId";
				break;
			case 'element':
				if (empty($rowid) || $rowid == '-2') {
					$rowid = $userId;
				}
				$usekey = (empty($usekey)) ? '' : " usekey=$usekey";
				$plugin_cmd = "fabrik view=$view table=$id row=$rowid$usekey element=$element";
				break;
			default:
				return 'no such view!';
				break;
		}
		if (!empty($layout)) {
			$plugin_cmd .= " layout=$layout";
		}
		if (!empty($additional)) {
			$additionals = array();
			foreach(explode(' ',$additional) as $keyval) {
				list($key,$val) = explode('=',$keyval);
				if ($val == '-2') {
					$val = $userId;
				}
				$additionals[] = "$key=$val";
			}
			$plugin_cmd .= " " . implode(' ',$additionals);
		}
		//echo $plugin_cmd;
		return $jsFabrik->jsFabrikRender(array("{$plugin_cmd}"));
	}
}
