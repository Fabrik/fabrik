<?php
/**
 * Post content to a VBulletin forum
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.vbulletin
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Post content to a VBulletin forum
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.vbulletin
 * @since       3.0
 */

class PlgFabrik_FormVbForum extends PlgFabrik_Form
{
	/**
	 * Before the record is stored, this plugin will see if it should process
	 * and if so store the form data in the session.
	 *
	 * @return  bool  should the form model continue to save
	 */

	public function onBeforeStore()
	{
		global $vbulletin;
		$params = $this->getParams();
		define(VB_AREA, 'fabrik');
		define(THIS_SCRIPT, 'fabrik');

		// Initialize some variables
		$db = FabrikWorker::getDbo();
		$formModel = $this->getModel();
		$data = $formModel->formData;

		// Check for request forgeries
		JSession::checkToken() or jexit('Invalid Token');

		$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($params->get('vb_forum_field'));

		$element = $elementModel->getElement(true);
		$this->map_forum_field = $elementModel->getFullName();

		$this->vb_parent_forum = $params->get('vb_parent', '');

		$method = "POST";
		$url = JURI::base() . "forum/mkforum.php";
		$vars = array();
		$vars['forum_name'] = $data[$this->map_forum_field];
		$vars['forum_parent'] = $this->vb_parent_forum;
		$res = $this->doRequest($method, $url, $vars);
	}

	/**
	 * Perform curl post to forum
	 *
	 * @param   string  $method  post/get
	 * @param   url     $url     url to post to
	 * @param   array   $vars    variables to post
	 *
	 * @return curl result or curl error
	 */

	private function doRequest($method, $url, $vars)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');

		if ($method == 'POST')
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
		}

		$data = curl_exec($ch);
		curl_close($ch);

		if ($data)
		{
			return $data;
		}
		else
		{
			return curl_error($ch);
		}
	}
}
