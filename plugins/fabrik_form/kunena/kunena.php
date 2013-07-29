<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.kunena
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Creates a thread in kunena forum
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.kunena
 * @since       3.0
 */

class PlgFabrik_FormKunena extends PlgFabrik_Form
{

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onAfterProcess($params, &$formModel)
	{
		jimport('joomla.filesystem.file');
		$files[] = COM_FABRIK_BASE . 'components/com_kunena/class.kunena.php';
		$define = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.defines.php';
		$files[] = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.defines.php';
		$files[] = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.link.class.php';
		$files[] = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.smile.class.php';
		if (!JFile::exists($define))
		{
			return JError::raiseError(500, 'could not find the Kunena component');
		}
		require_once $define;
		foreach ($files as $file)
		{
			require_once $file;
		}

		if (JFile::exists(KUNENA_PATH_FUNCS . '/post.php'))
		{
			$postfile = KUNENA_PATH_FUNCS . '/post.php';
		}
		else
		{
			$postfile = KUNENA_PATH_TEMPLATE_DEFAULT . '/post.php';
		}
		$w = new FabrikWorker;

		// $fbSession = CKunenaSession::getInstance();
		// Don't need this, session is loaded in CKunenaPost

		$catid = $params->get('kunena_category', 0);
		$parentid = 0;
		$action = 'post';

		// Added action in request
		JRequest::setVar('action', $action);
		$func = 'post';
		$contentURL = 'empty';
		JRequest::setVar('catid', $catid);
		$msg = $w->parseMessageForPlaceHolder($params->get('kunena_content'), $formModel->_fullFormData);
		$subject = $params->get('kunena_title');
		JRequest::SetVar('message', $msg);
		$subject = $w->parseMessageForPlaceHolder($subject, $formModel->_fullFormData);

		// Added subject in request
		JRequest::SetVar('subject', $subject);
		$origId = JRequest::getVar('id');
		JRequest::setVar('id', 0);
		/*
		ob_start();
		include ($postfile);
		ob_end_clean();
		 */
		ob_start();
		include $postfile;
		$mypost = new CKunenaPost;

		// Public CKunenaPost::display() will call protected method CKunenaPost::post() if JRequest action is 'post'
		$mypost->display();
		ob_end_clean();
		JRequest::setVar('id', $origId);
	}

}
