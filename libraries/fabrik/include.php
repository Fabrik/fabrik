<?php
/**
 * Fabrik Autoloader Class
 *
 * @package     Fabrik
 * @copyright   Copyright (C) 2014 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\String\Inflector;
use Joomla\String\Normalise;

/**'
 * Autoloader Class
 *
 * @package  Fabble
 * @since    1.0
 */
class FabrikAutoloader
{
	public function __construct()
	{
		spl_autoload_register(array($this, 'controller'));
		spl_autoload_register(array($this, 'helper'));
		spl_autoload_register(array($this, 'view'));

		// @TODO - at some point allow auto-loading of these as per Fabble
		/*
		spl_autoload_register(array($this, 'model'));
		spl_autoload_register(array($this, 'view'));
		spl_autoload_register(array($this, 'library'));
		spl_autoload_register(array($this, 'plugin'));*/
	}

	/**
	 * Load plugin class
	 *
	 * @param   string $class Class name
	 */
	private function plugin($class)
	{

		if (!strstr(strtolower($class), 'fabble\form\plugin\\') && !strstr(strtolower($class), 'fabble\lizt\plugin\\'))
		{
			return;
		}

		$class = str_replace('\\', '/', str_replace('Fabble\\', '', $class));
		$file  = explode('/', $class);
		$file  = array_pop($file);
		$path  = JPATH_SITE . '/libraries/fabble/' . $class . '/' . $file . '.php';

		require_once $path;
	}

	/**
	 * Load model class
	 *
	 * @param   string $class Class name
	 */
	private function model($class)
	{
		if (!strstr(strtolower($class), 'model'))
		{
			return;
		}

		$kls      = explode('\\', $class);
		$class    = array_pop($kls);
		$scope    = Factory::getApplication()->scope;
		$isFabble = strtolower(substr($class, 0, 11)) === 'fabblemodel';

		if ($this->appName($class) === $scope || $isFabble)
		{
			$path        = JPATH_SITE . '/libraries/fabble/';
			$defaultPath = JPATH_SITE . '/libraries/fabble/';
			$plural      = Inflector::getInstance();
			$parts       = Normalise::fromCamelCase($class, true);
			unset($parts[0]);
			$parts = array_values($parts);

			foreach ($parts as &$part)
			{
				$part = strtolower($part);

				if ($plural->isPlural($part))
				{
					$part = $plural->toSingular($part);
				}

				$part = JString::ucfirst(strtolower($part));
			}

			$path .= implode('/', $parts) . '.php';

			if (file_exists($path))
			{
				require_once $path;
				$type = array_pop($parts);

				if (!$isFabble)
				{
					class_alias('\\Fabble\\Model\\FabbleModel' . JString::ucfirst($type), $class);
				}

				return;
			}

			// IF no actual model name found try loading default model
			$parts[count($parts) - 1] = 'Default';
			$defaultPath .= implode('/', $parts) . '.php';

			if (file_exists($defaultPath))
			{
				require_once $defaultPath;
				$type = array_pop($parts);
				class_alias("\\Fabble\\Model\\FabbleModel" . JString::ucfirst($type), $class);

				return;
			}
		}
	}

	/**
	 * Load view class
	 *
	 * @param   string $class Class name
	 */
	private function view($class)
	{
		/*
		if (!strstr(strtolower($class), 'view'))
		{
			return;
		}

		$scope = \JFactory::getApplication()->scope;

		// Load component specific files
		if ($this->appName($class) === $scope)
		{
			$parts    = Normalise::fromCamelCase($class, true);
			$type     = array_pop($parts);
			$path     = JPATH_SITE . '/libraries/fabble/Views/' . JString::ucfirst($type) . '.php';
			$original = $type;

			if (file_exists($path))
			{
				require_once $path;
				class_alias('\\Fabble\\Views\\' . $original, $class);

				return;
			}
		}
		*/

		if ($class !== 'FabrikView')
		{
			return;
		}

		$path = JPATH_SITE . '/components/com_fabrik/views/FabrikView.php';

		if (file_exists($path))
		{
			require_once $path;
		}

	}

	private function appName($class)
	{
		$scope = \JFactory::getApplication()->scope;

		return 'com_' . strtolower(substr($class, 0, strlen($scope) - 4));
	}

	/**
	 * Load controller file
	 *
	 * @param   string $class Class name
	 */
	private function controller($class)
	{
		if (!strstr(strtolower($class), 'controller'))
		{
			return;
		}

		$class = str_replace('\\', '/', $class);
		$file  = explode('/', $class);
		$file  = strtolower(array_pop($file));
		$path  = JPATH_SITE . '/libraries/fabrik/fabrik/Controllers/' . \Joomla\String\StringHelper::ucfirst($file) . '.php';

		if (file_exists($path))
		{
			require_once $path;
		}

	}

	/**
	 * Load library files, and possible helpers
	 *
	 * @param   string $class Class Name
	 */
	private function library($class)
	{
		if (strstr($class, '\\'))
		{
			return;
		}

		if (strtolower(substr($class, 0, 3)) === 'fab')
		{
			$class = (substr($class, 3));

			// Change from camel cased (e.g. ViewHtml) into a lowercase array (e.g. 'view','html') taken from FOF
			$class = preg_replace('/(\s)+/', '_', $class);
			$class = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $class));
			$class = explode('_', $class);

			$file      = (count($class) === 1) ? $class[0] : array_pop($class);
			$path      = JPATH_SITE . '/libraries/fabble/' . implode('/', $class);
			$classFile = $path . '/' . $file . '.php';
			$helper    = $path . '/helper.php';

			if (file_exists($classFile))
			{
				include_once $classFile;
			}

			if (file_exists($helper))
			{
				include_once $helper;
			}
		}
	}

	/**
	 * Load helper file
	 **/
	private function helper($class)
	{
		if (!strstr($class, 'Fabrik\Helper'))
		{
			return;
		}

		$className = str_replace('Fabrik\\Helpers\\', '', $class);
		$path  = JPATH_SITE . '/libraries/fabrik/fabrik/Helpers/' . $className . '.php';
		$path = str_replace('\\', '/', $path);

		if (file_exists($path))
		{
			require_once $path;

			if (is_callable(array($class, '__initStatic')))
			{
				$class::__initStatic();
			}
		}
	}
}

/*
 * If the Fabrik library package has been installed, or we have full github code, we can use Composer autoload
 */
if (file_exists(JPATH_LIBRARIES . '/fabrik/vendor/autoload.php'))
{
	$loader = require JPATH_LIBRARIES . '/fabrik/vendor/autoload.php';
}

$autoLoader = new FabrikAutoloader();
