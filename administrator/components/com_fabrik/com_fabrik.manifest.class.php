<?php
/**
 * Fabrik: Installer Manifest Class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @author     Rob Clayburn
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

/**
 * Installer manifest class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class Com_FabrikInstallerScript
{
	/**
	 * Drivers
	 *
	 * @var array
	 */
	protected $drivers = array('mysql_fab.php', 'mysqli_fab.php', 'pdomysql_fab.php');

	/**
	 * Documents >3.7
	 *
	 * @var array
	 */
	protected $documents38 = array('Partial', 'Pdf');

	/**
	 * Documents <= 3.7
	 *
	 * @var array
	 */
	protected $documents37 = array('fabrikfeed', 'partial', 'pdf');

	/**
	 * Run when the component is installed
	 *
	 * @param   object $parent installer object
	 *
	 * @return bool
	 */
	public function install($parent)
	{
		$parent->getParent()->setRedirectURL('index.php?option=com_fabrik');

		return true;
	}

	/**
	 * Check if there is a connection already installed if not create one
	 * by copying over the site's default connection
	 *
	 * @return  bool
	 */
	protected function setConnection()
	{
		$db               = JFactory::getDbo();
		$app              = JFactory::getApplication();
		$row              = new stdClass;
		$row->host        = $app->get('host');
		$row->user        = $app->get('user');
		$row->password    = $app->get('password');
		$row->database    = $app->get('db');
		$row->description = 'site database';
		$row->params      = '';
		$row->checked_out = 0;
		$row->published   = 1;
		$row->default     = 1;
		$res              = $db->insertObject('#__fabrik_connections', $row, 'id');

		return $res;
	}

	/**
	 * Test to ensure that the main component params have a default setup
	 *
	 * @return  bool
	 */
	protected function setDefaultProperties()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('extension_id, params')->from('#__extensions')
			->where('name = ' . $db->q('fabrik'))
			->where('type = ' . $db->q('component'));
		$db->setQuery($query);
		$row                                 = $db->loadObject();
		$opts                                = new stdClass;
		$opts->fbConf_wysiwyg_label          = 0;
		$opts->fbConf_alter_existing_db_cols = 0;
		$opts->spoofcheck_on_formsubmission  = 0;

		if ($row && ($row->params == '{}' || $row->params == ''))
		{
			$json  = $row->params;
			$query = $db->getQuery(true);
			$query->update('#__extensions')->set('params = ' . $db->quote($json))
				->where('extension_id = ' . (int) $row->extension_id);
			$db->setQuery($query);

			if (!$db->execute())
			{
				return false;
			}
		}

		return true;
	}

	protected function getVersion()
    {
        $version = new JVersion;
        return $version->RELEASE;
    }

	/**
	 * Move over files into Joomla libraries folder
	 *
	 * @param   object &$installer installer
	 * @param   bool   $upgrade    upgrade
	 *
	 * @return  bool
	 */
	protected function moveFiles(&$installer, $upgrade = false)
	{
		jimport('joomla.filesystem.file');
		$componentFrontend = 'components/com_fabrik';

		if (version_compare($this->getVersion(), '3.8', '<')) {
            $docTypes = array('fabrikfeed', 'pdf', 'partial');

            foreach ($docTypes as $docType) {
                $dest = 'libraries/joomla/document/' . $docType;

                if (!JFolder::exists(JPATH_ROOT . '/' . $dest)) {
                    JFolder::create(JPATH_ROOT . '/' . $dest);
                }
                // $$$ hugh - have to use false as last arg (use_streams) on JFolder::copy(), otherwise
                // it bypasses FTP layer, and will fail if web server does not have write access to J! folders
                $moveRes = JFolder::copy($componentFrontend . '/' . $docType, $dest, JPATH_SITE, true, false);

                if ($moveRes !== true) {
                    echo "<p style=\"color:red\">failed to moved " . $componentFrontend . '/fabrikfeed to ' . $dest . '</p>';

                    return false;
                }
            }
        }
        else
        {
            $dest = 'libraries/src/Document';

            if (!JFolder::exists(JPATH_ROOT . '/' . $dest)) {
                JFolder::create(JPATH_ROOT . '/' . $dest);
            }
            // $$$ hugh - have to use false as last arg (use_streams) on JFolder::copy(), otherwise
            // it bypasses FTP layer, and will fail if web server does not have write access to J! folders
            $moveRes = JFolder::copy($componentFrontend . '/Document', $dest, JPATH_SITE, true, false);

            if ($moveRes !== true) {
                echo "<p style=\"color:red\">failed to copy " . $componentFrontend . '/Document to ' . $dest . '</p>';

                return false;
            }
        }

		$dest             = 'libraries/joomla/database/database';
		$driverInstallLoc = $componentFrontend . '/dbdriver/';
		$moveRes          = JFolder::copy($driverInstallLoc, $dest, JPATH_SITE, true, false);

		if ($moveRes !== true)
		{
			echo "<p style=\"color:red\">failed to moved " . $driverInstallLoc . ' to ' . $dest . '</p>';

			return false;
		}

		// Joomla 3.0 db drivers and queries
		$dest             = 'libraries/joomla/database/driver';
		$driverInstallLoc = $componentFrontend . '/driver/';

		$moveRes = JFolder::copy($driverInstallLoc, $dest, JPATH_SITE, true, false);

		if ($moveRes !== true)
		{
			echo "<p style=\"color:red\">failed to moved " . $driverInstallLoc . ' to ' . $dest . '</p>';

			return false;
		}

		$dest             = 'libraries/joomla/database/query';
		$driverInstallLoc = $componentFrontend . '/query/';
		$moveRes          = JFolder::copy($driverInstallLoc, $dest, JPATH_SITE, true, false);

		if ($moveRes !== true)
		{
			echo "<p style=\"color:red\">failed to moved " . $driverInstallLoc . ' to ' . $dest . '</p>';

			return false;
		}

		$dest = 'libraries/fabrik';

		if (!JFolder::exists(JPATH_ROOT . '/' . $dest))
		{
			JFolder::create(JPATH_ROOT . '/' . $dest);
		}

		$moveRes = JFolder::copy($componentFrontend . '/fabrik', $dest, JPATH_SITE, true, false);

		if ($moveRes !== true)
		{
			echo "<p style=\"color:red\">failed to moved " . $componentFrontend . '/fabrik to ' . $dest . '</p>';

			return false;
		}

		return true;
	}

	/**
	 * Run when the component is uninstalled.
	 *
	 * @param   object $parent installer object
	 *
	 * @return  void
	 */
	public function uninstall($parent)
	{
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		// <= 3.7 documents formats
		$dest = JPATH_ROOT . '/libraries/joomla/document';

		foreach ($this->documents37 as $document)
		{
			if (!empty($document) && JFolder::exists($dest . '/' . $document))
			{
				JFolder::delete($dest . '/' . $document);
			}
		}

		// database drivers, all versions
		$dest = JPATH_ROOT . '/libraries/joomla/database/database';

		foreach ($this->drivers as $driver)
		{
			if (!empty($driver) && JFile::exists($dest . '/' . $driver))
			{
				JFile::delete($dest . '/' . $driver);
			}
		}

		// >= 3.8 documents and renderers
		$dest = JPATH_ROOT . '/libraries/src/Document';

		foreach ($this->documents38 as $document)
		{
			if (!empty($document) && JFile::exists($dest . '/' . $document . 'Document.php'))
			{
				JFile::delete($dest . '/' . $document . 'Document.php');
			}
		}

		$dest = JPATH_ROOT . '/libraries/src/Document/Renderer';

		foreach ($this->documents38 as $document)
		{
			if (!empty($document) && JFolder::exists($dest . '/' . $document))
			{
				JFolder::delete($dest . '/' . $document);
			}
		}

		$this->disableFabrikPlugins();
	}

	/**
	 * Disable Fabrik Plugins
	 *
	 * @return bool
	 */
	protected function disableFabrikPlugins()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->update('#__extensions')
			->set('enabled = 0')
			->where('folder LIKE "%fabrik%" OR element LIKE ' . $db->q('%fabrik%'));

		return $db->setQuery($query)->execute();
	}

	/**
	 * God knows why but install component, uninstall component and install
	 * again and component_id is set to 0 for the menu items
	 *
	 * @return  bool
	 */
	protected function fixMenuComponentId()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('extension_id')->from('#__extensions')->where('element = ' . $db->q('com_fabrik'));
		$db->setQuery($query);
		$id = (int) $db->loadResult();
		$query->clear();
		$query->update('#__menu')->set('component_id = ' . $id)->where('path LIKE ' . $db->q('fabrik%'));

		return $db->setQuery($query)->execute();
	}

	/**
	 * Run when the component is updated
	 *
	 * @param   object $parent installer object
	 *
	 * @return  bool
	 */
	public function update($parent)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$app   = JFactory::getApplication();
		$msg = array();

		// Fabrik 3.5 Uninstalled plugins.
		$plugins = array(
			'fabrik_element' => array('fbactivityfeed', 'fblikebox', 'fbrecommendations'),
			'fabrik_form' => array('vbforum')
		);

		// Deprecated - 'timestamp', 'exif'
		$query->select('*')->from('#__extensions');

		foreach ($plugins as $folder => $plugs)
		{
			$query->where('(folder = ' . $db->q($folder) . ' AND element IN (' . implode(', ', $db->q($plugs)) . '))', 'OR');

			foreach ($plugs as $plug)
			{
				$path = JPATH_PLUGINS . '/' . $folder . '/' . $plug;

				if (JFolder::exists($path))
				{
					JFolder::delete($path);
				}
			}
		}

		$deprecatedPlugins = $db->setQuery($query)->loadObjectList();

		if (!empty($deprecatedPlugins))
		{
			$ids = ArrayHelper::getColumn($deprecatedPlugins, 'extension_id');
			$ids = ArrayHelper::toInteger($ids);

			$query->clear()->delete('#__extensions')->where('extension_id IN ( ' . implode(',', $ids) . ')');
			$db->setQuery($query)->execute();

			// Un-publish elements
			$query->clear()->select('id, name, label')->from('#__fabrik_elements')
				->where('plugin IN (' . implode(', ', $db->q($plugins['fabrik_element'])) . ')')
				->where('published = 1');
			$db->setQuery($query);
			$unpublishedElements = $db->loadObjectList();
			$unpublishedIds      = ArrayHelper::getColumn($unpublishedElements, 'id');

			if (!empty($unpublishedIds))
			{
				$msg[] = 'The following elements have been unpublished as their plug-ins have been uninstalled. : ' . implode(', ', $unpublishedIds);
				$query->clear()
					->update('#__fabrik_elements')->set('published = 0')->where('id IN (' . implode(',', $db->q($unpublishedIds)) . ')');
				$db->setQuery($query)->execute();
			}
		}

		// Un-publish form plug-ins
		$query->clear()->select('id, params')->from('#__fabrik_forms');
		$forms = $db->setQuery($query)->loadObjectList();
		foreach ($forms as $form)
		{
			$params = json_decode($form->params);
			$found = false;

			if (isset($params->plugins))
			{
				for ($i = 0; $i < count($params->plugins); $i++)
				{
					if (in_array($params->plugins[$i], $plugins['fabrik_form']))
					{
						$msg[]                    = 'Form ' . $form->id . '\'s plugin \'' . $params->plugins[$i] .
							'\' has been unpublished';
						$params->plugin_state[$i] = 0;
						$found = true;
					}
				}

				if ($found)
				{
					$query->clear()->update('#__fabrik_forms')->set('params = ' . $db->q(json_encode($params)))
						->where('id = ' . (int) $form->id);

					$db->setQuery($query)->execute();
				}
			}
		}

		if (!empty($msg))
		{
			$app->enqueueMessage(implode('<br>', $msg), 'warning');
		}

		return true;
	}

	/**
	 * Run before installation or upgrade run
	 *
	 * @param   string $type   discover_install (Install unregistered extensions that have been discovered.)
	 *                         or install (standard install)
	 *                         or update (update)
	 * @param   object $parent installer object
	 *
	 * @return  void
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * Run after installation or upgrade run
	 *
	 * @param   string $type   discover_install (Install unregistered extensions that have been discovered.)
	 *                         or install (standard install)
	 *                         or update (update)
	 * @param   object $parent installer object
	 *
	 * @return  bool
	 */
	public function postflight($type, $parent)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Remove old update site & Fabrik 3.0.x update site
		$where = "location LIKE '%update/component/com_fabrik%' OR location = 'http://fabrikar.com/update/fabrik/package_list.xml'";
		$query->delete('#__update_sites')->where($where);
		$db->setQuery($query);

		if (!$db->execute())
		{
			echo "<p>didnt remove old update site</p>";
		}
		else
		{
			echo "<p style=\"color:green\">removed old update site</p>";
		}

		$query->clear();
		$query->update('#__extensions')->set('enabled = 1')
			->where('type = ' . $db->q('plugin') . ' AND (folder LIKE ' . $db->q('fabrik_%'), 'OR')
			->where('(folder=' . $db->q('system') . ' AND element = ' . $db->q('fabrik') . ')', 'OR')
			->where('(folder=' . $db->q('content') . ' AND element = ' . $db->q('fabrik') . '))', 'OR');
		$db->setQuery($query)->execute();
		$this->fixMenuComponentId();

		if ($type !== 'update')
		{
			if (!$this->setConnection())
			{
				echo "<p style=\"color:red\">Didn't set connection. Aborting installation</p>";
				exit;

				return false;
			}
		}

		echo "<p style=\"color:green\">Default connection created</p>";

		if (!$this->moveFiles($parent))
		{
			echo "<p style=\"color:red\">Unable to move library files. Aborting installation</p>";
			exit;

			return false;
		}
		else
		{
			echo "<p style=\"color:green\">Libray files moved</p>";
		}

		if ($type !== 'update')
		{
			if (!$this->setDefaultProperties())
			{
				echo "<p>couldnt set default properties</p>";
				exit;

				return false;
			}
		}

		echo "<p>Installation finished</p>";
		echo "<p>Note that this extension places a small number of additional files in the Joomla core directories,
providing extended functionality such as PDF document types.  These files will be removed if you uninstall Fabrik.</p>";
		echo '<p><a target="_top" href="index.php?option=com_fabrik&amp;task=home.installSampleData">Click
here to install sample data</a></p>
	  ';

		// An example of setting a redirect to a new location after the install is completed
		// $parent->getParent()->set('redirect_url', 'http://www.google.com');

		// $upgrade = JModelLegacy::getInstance('Upgrade', 'FabrikModel');
	}
}
