<?php
/**
 * @package     Fabrik\Helpers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Fabrik\Helpers;


use JFactory;
use JModelLegacy;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\User;
use Joomla\Utilities\ArrayHelper;
use GuzzleHttp\Client;
use JRoute;
use JTable;

/**
 * Helper for the log form plugin.  Used when needing to log changes to data outside of a
 * form submission.
 *
 * Call ...
 *
 * \Fabrik\Helpers\LogHelper::setOrigData($formId, $rowId);
 *
 * ... before changing the data for a row, and ...
 *
 * \Fabrik\Helpers\LogHelper::logRowChange($formId, $rowId);
 *
 * ... after changing data.  Will get settings from log plugin on that formid, and
 * log accordingly.
 */

class LogHelper
{
	private static $init = null;

	/**
	 * @var null JConfig
	 * @since version
	 */
	private static $config = null;

	/**
	 * @var User
	 * @since version
	 */
	private static $user = null;

	/**
	 * @var CMSApplication
	 * @since version
	 */
	private static $app = null;

	private static $lang = null;

	private static $date = null;

	/**
	 * @var Session
	 * @since version
	 */
	private static $session = null;

	private static $formModel = null;

	private static $origData = [];

	public static function __initStatic($config = array())
	{
		if (!isset(self::$init))
		{
			self::$config  = ArrayHelper::getValue($config, 'config', JFactory::getConfig());
			self::$user    = ArrayHelper::getValue($config, 'user', JFactory::getUser());
			self::$app     = ArrayHelper::getValue($config, 'app', JFactory::getApplication());
			self::$lang    = ArrayHelper::getValue($config, 'lang', JFactory::getLanguage());
			self::$date    = ArrayHelper::getValue($config, 'date', JFactory::getDate());
			self::$session = ArrayHelper::getValue($config, 'session', JFactory::getSession());
			self::$formModel = ArrayHelper::getValue($config, 'formModel', null);
			self::$init    = true;
		}
	}

	/**
	 * @param $formId
	 *
	 * @return \FabrikFEModelForm
	 *
	 * @since version
	 */
	private static function getFormModel($formId, $rowId)
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
		JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');
		/** @var \FabrikFEModelForm $formModel */
		$formModel = JModelLegacy::getInstance('Form', 'FabrikFEModel');
		$formModel->setId($formId);
		$formModel->setRowId($rowId);
		$formModel->origRowId = $rowId;
		$formModel->unsetData();
		$formModel->_origData = self::getOrigData($formId, $rowId);
		self::$app->input->set('rowid', $rowId);
		return $formModel;
	}

	/**
	 * @param $formModel \FabrikFEModelForm
	 *
	 *
	 * @since version
	 */
	private static function getLogPlugin($formModel)
	{
		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		$params = $formModel->getParams();
		$logPlugin = $pluginManager->getPlugin('log', 'form');
		$plugins = $params->get('plugins');

		foreach ($plugins as $c => $type)
		{
			if ($type === 'log')
			{
				$logPlugin->setModel($formModel);
				$logPlugin->setParams($params, $c);
			}
		}

		return $logPlugin;
	}

	private static function getOrigData($formId, $rowId)
	{
		if (array_key_exists($formId, self::$origData))
		{
			if (array_key_exists($rowId, self::$origData[$formId]))
			{
				return self::$origData[$formId][$rowId];
			}
		}

		return null;
	}

	public static function setOrigData($formId, $rowId)
	{
		if (!array_key_exists($formId, self::$origData))
		{
			self::$origData[$formId] = [];
		}

		if (!array_key_exists($rowId, self::$origData[$formId]))
		{
			$formModel = self::getFormModel($formId, $rowId);
			self::$origData[$formId][$rowId] = $formModel->getOrigData();
			//$logPlugin = self::getLogPlugin($formModel);
			//$logPlugin->onBeforeProcess();
		}
	}

	public static function logRowChange($formId, $rowId)
	{
		$formModel = self::getFormModel($formId, $rowId);
		$logPlugin = self::getLogPlugin($formModel);
		$logPlugin->setOrigData(self::getOrigData($formId, $rowId));
		$logPlugin->onAfterProcess();
	}

}