<?php
/**
 * Required System plugin if using Fabrik
 * Enbles Fabrik to override some J classes
 *
 * @package     Joomla.Plugin
 * @subpackage  System
 * @copyright   Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');

/**
 * Joomla! Fabrik system
 *
 * @package     Joomla.Plugin
 * @subpackage  System
 * @since       3.0
 */

class PlgSystemFabrik extends JPlugin
{

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since	1.0
	 */

	public function plgSystemFabrik(&$subject, $config)
	{
		/**
		 * Moved these from defines.php to here, to fix an issue with Kunena.  Kunena imports the J!
		 * JForm class in their system plugin, in the class constructure.  So if we wait till onAfterInitialize
		 * to do this, we blow up.  So, import them here, and make sure the Fabrik plugin has a lower ordering
		 * than Kunena's.  We might want to set our default to -1.
		 */
		JLoader::import('components.com_fabrik.classes.field', JPATH_SITE . '/administrator', 'administrator.');
		JLoader::import('components.com_fabrik.classes.form', JPATH_SITE . '/administrator', 'administrator.');
		parent::__construct($subject, $config);
	}

	/**
	 * Need to call this here otherwise you get class exists error
	 *
	 * @since   3.0
	 *
	 * @return  void
	 */

	public function onAfterInitialise()
	{
		jimport('joomla.filesystem.file');
		$p = JPATH_SITE . '/plugins/system/fabrik/';
		$defines = JFile::exists($p . 'user_defines.php') ? $p . 'user_defines.php' : $p . 'defines.php';
		require_once $defines;
		$this->setBigSelects();
	}

	/**
	 * From Global configuration setting, set big select for main J database
	 *
	 * @since    3.0.7
	 *
	 * @return  void
	 */

	protected function setBigSelects()
	{
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$bigSelects = $fbConfig->get('enable_big_selects', 0);
		$db = JFactory::getDbo();
		if ($bigSelects)
		{
			$db->setQuery("SET OPTION SQL_BIG_SELECTS=1");
			$db->execute();
		}
	}

	/**
	 * Fabrik Search method
	 *
	 * The sql must return the following fields that are
	 * used in a common display routine: href, title, section, created, text,
	 * browsernav
	 *
	 * @param   string  $text      Target search string
	 * @param   string  $phrase    mathcing option, exact|any|all
	 * @param   string  $ordering  option, newest|oldest|popular|alpha|category
	 * @param   mixed   $areas     An array if restricted to areas, null if search all
	 *
	 * @return  array
	 */

	public function onDoContentSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		if (defined('COM_FABRIK_SEARCH_RUN'))
		{
			return;
		}
		define('COM_FABRIK_SEARCH_RUN', true);
		JModel::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');

		$user = JFactory::getUser();
		$db = FabrikWorker::getDbo(true);

		require_once JPATH_SITE . '/components/com_content/helpers/route.php';

		// Load plugin params info
		$limit = $this->params->def('search_limit', 50);
		$text = trim($text);
		if ($text == '')
		{
			return array();
		}

		switch ($ordering)
		{
			case 'oldest':
				$order = 'a.created ASC';
				break;

			case 'popular':
				$order = 'a.hits DESC';
				break;

			case 'alpha':
				$order = 'a.title ASC';
				break;

			case 'category':
				$order = 'b.title ASC, a.title ASC';
				$morder = 'a.title ASC';
				break;

			case 'newest':
			default:
				$order = 'a.created DESC';
				break;
		}
		// Get all tables with search on
		$query = $db->getQuery(true);
		$query->select('id')->from('#__{package}_lists')->where('published = 1');

		$db->setQuery($query);

		$list = array();
		$ids = $db->loadColumn();
		if ($db->getErrorNum() != 0)
		{
			jexit('search:' . $db->getErrorMsg());
		}
		$section = $this->params->get('search_section_heading');
		$urls = array();

		// $$$ rob remove previous search results?
		JRequest::setVar('resetfilters', 1);

		// Ensure search doesnt go over memory limits
		$memory = (int) FabrikString::rtrimword(ini_get('memory_limit'), 'M') * 1000000;
		$usage = array();
		$memSafety = 0;

		$listModel = JModel::getInstance('list', 'FabrikFEModel');
		$app = JFactory::getApplication();
		foreach ($ids as $id)
		{
			// Unset enough stuff in the table model to allow for correct query to be run
			$listModel->reset();

			// $$$ geros - http://fabrikar.com/forums/showthread.php?t=21134&page=2
			$key = 'com_' . $package . '.list' . $id . '.filter.searchall';
			$app->setUserState($key, null);

			unset($table);
			unset($elementModel);
			unset($params);
			unset($query);
			unset($allrows);
			$used = memory_get_usage();
			$usage[] = memory_get_usage();
			if (count($usage) > 2)
			{
				$diff = $usage[count($usage) - 1] - $usage[count($usage) - 2];
				if ($diff + $usage[count($usage) - 1] > $memory - $memSafety)
				{
					JError::raiseNotice(500, 'Some records were not searched due to memory limitations');
					break;
				}
			}
			// $$$rob set this to current table
			// Otherwise the fabrik_list_filter_all var is not used
			JRequest::setVar('listid', $id);

			$listModel->setId($id);
			$filterModel = $listModel->getFilterModel();
			$requestKey = $filterModel->getSearchAllRequestKey();

			// Set the request variable that fabrik uses to search all records
			JRequest::setVar($requestKey, $text, 'post');

			$table = $listModel->getTable(true);
			$fabrikDb = $listModel->getDb();
			$params = $listModel->getParams();

			// Test for swap too boolean mode
			$mode = JRequest::getVar('searchphraseall', 'all');

			// $params->set('search-mode-advanced', true);
			$params->set('search-mode-advanced', $mode);

			// The table shouldn't be included in the search results or we have reached the max number of records to show.
			if (!$params->get('search_use') || $limit <= 0)
			{
				continue;
			}

			// Set the table search mode to OR - this will search ALL fields with the search term
			$params->set('search-mode', 'OR');

			$allrows = $listModel->getData();
			$elementModel = $listModel->getFormModel()->getElement($params->get('search_description', $table->label), true);
			$descname = is_object($elementModel) ? $elementModel->getFullName(false, true) : '';

			$elementModel = $listModel->getFormModel()->getElement($params->get('search_title', 0), true);
			$title = is_object($elementModel) ? $elementModel->getFullName(false, true) : '';

			$aAllowedList = array();
			$pk = $table->db_primary_key;
			foreach ($allrows as $group)
			{
				foreach ($group as $oData)
				{
					$pkval = $oData->__pk_val;
					if ($app->isAdmin())
					{
						$href = $oData->fabrik_edit_url;
					}
					else
					{
						$href = $oData->fabrik_view_url;
					}
					if (!in_array($href, $urls))
					{
						$limit--;
						$urls[] = $href;
						$o = new stdClass;
						if (isset($oData->$title))
						{
							$o->title = $table->label . ' : ' . $oData->$title;
						}
						else
						{
							$o->title = $table->label;
						}
						$o->_pkey = $table->db_primary_key;
						$o->section = $section;

						$o->href = $href;
						$o->created = '';
						$o->browsernav = 2;
						if (isset($oData->$descname))
						{
							$o->text = $oData->$descname;
						}
						else
						{
							$o->text = '';
						}
						$o->title = strip_tags($o->title);
						$aAllowedList[] = $o;
					}
				}
				$list[] = $aAllowedList;
			}
		}
		$allList = array();
		foreach ($list as $li)
		{
			if (is_array($li) && !empty($li))
			{
				$allList = array_merge($allList, $li);
			}
		}
		return $allList;
	}

}
