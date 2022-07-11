<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fabrik\Helpers;

use Joomla\CMS\Layout\FileLayout;
use \JLoader;
use \JPath;

defined('JPATH_BASE') or die;

/**
 * Base class for rendering a display layout
 * loaded from from a layout file
 *
 * @package     Joomla.Libraries
 * @subpackage  Layout
 * @see         http://docs.joomla.org/Sharing_layouts_across_views_or_extensions_with_JLayout
 * @since       3.0
 */
class LayoutFile extends \JLayoutFile
{
	/**
	 * Method to finds the full real file path, checking possible overrides
	 *
	 * @return  string  The full path to the layout file
	 *
	 * @since   3.0
	 */
	protected function getPath()
	{
		\JLoader::import('joomla.filesystem.path');

		$layoutId     = $this->getLayoutId();
		$includePaths = $this->getIncludePaths();
		$suffixes     = $this->getSuffixes();

		$this->addDebugMessage('<strong>Layout:</strong> ' . $this->layoutId);

		if (!$layoutId)
		{
			$this->addDebugMessage('<strong>There is no active layout</strong>');

			return;
		}

		if (!$includePaths)
		{
			$this->addDebugMessage('<strong>There are no folders to search for layouts:</strong> ' . $layoutId);

			return;
		}

		$hash = md5(
			json_encode(
				array(
					'paths'    => $includePaths,
					'suffixes' => $suffixes,
				)
			)
		);

		if (isset(static::$cache[$layoutId][$hash]))
		{
			$this->addDebugMessage('<strong>Cached path:</strong> ' . static::$cache[$layoutId][$hash]);

			return static::$cache[$layoutId][$hash];
		}

		$this->addDebugMessage('<strong>Include Paths:</strong> ' . print_r($includePaths, true));

		// Search for suffixed versions. Example: tags.j31.php
		if ($suffixes)
		{
			$this->addDebugMessage('<strong>Suffixes:</strong> ' . print_r($suffixes, true));

			foreach ($suffixes as $suffix)
			{
				$rawPath  = str_replace('.', '/', $this->layoutId) . '.' . $suffix . '.php';
				$this->addDebugMessage('<strong>Searching layout for:</strong> ' . $rawPath);

				if ($foundLayout = \JPath::find($this->includePaths, $rawPath))
				{
					$this->addDebugMessage('<strong>Found layout:</strong> ' . $this->fullPath);

					static::$cache[$layoutId][$hash] = $foundLayout;

					return static::$cache[$layoutId][$hash];
				}
			}
		}

		// Standard version
		$rawPath  = str_replace('.', '/', $this->layoutId) . '.php';
		$this->addDebugMessage('<strong>Searching layout for:</strong> ' . $rawPath);

		$foundLayout = \JPath::find($this->includePaths, $rawPath);

		if (!$foundLayout)
		{
			$this->addDebugMessage('<strong>Unable to find layout: </strong> ' . $layoutId);

			static::$cache[$layoutId][$hash] = '';

			return;
		}

		$this->addDebugMessage('<strong>Found layout:</strong> ' . $foundLayout);

		static::$cache[$layoutId][$hash] = $foundLayout;

		return static::$cache[$layoutId][$hash];
	}

}
