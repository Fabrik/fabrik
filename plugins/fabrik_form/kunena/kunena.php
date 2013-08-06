<?php
/**
 * Fabrik Form Kunena interface
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.kunena
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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
		$app = JFactory::getApplication();
		$input = $app->input;
		jimport('joomla.filesystem.file');
		$files[] = COM_FABRIK_BASE . 'components/com_kunena/class.kunena.php';
		$define = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.defines.php';
		$files[] = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.defines.php';
		$files[] = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.link.class.php';
		$files[] = COM_FABRIK_BASE . 'components/com_kunena/lib/kunena.smile.class.php';
		if (!JFile::exists($define))
		{
			throw new RuntimeException('could not find the Kunena component', 404);
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
		$input->set('action', $action);
		$func = 'post';
		$contentURL = 'empty';
		$input->set('catid', $catid);
		$msg = $w->parseMessageForPlaceHolder($params->get('kunena_content'), $formModel->_fullFormData);
		$subject = $params->get('kunena_title');
		$input->set('message', $msg);
		$subject = $w->parseMessageForPlaceHolder($subject, $formModel->_fullFormData);

		// Added subject in request
		$input->set('subject', $subject);
		$origId = $input->get('id');
		$input->set('id', 0);
		/*
		ob_start();
		include ($postfile);
		ob_end_clean();
		 */
		ob_start();
		include $postfile;
		$mypost = new CKunenaPost;

		// Public CKunenaPost::display() will call protected method CKunenaPost::post() if $app->input action is 'post'
		$mypost->display();
		ob_end_clean();
		$input->set('id', $origId);
	}

}
