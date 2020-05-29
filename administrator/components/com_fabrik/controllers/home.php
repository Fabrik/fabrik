<?php
/**
 * Fabrik Home Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controlleradmin');

/**
 * Fabrik Home Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminControllerHome extends JControllerAdmin
{
	/**
	 * Delete all data from fabrik
	 *
	 * @return null
	 */
	public function reset()
	{
		$model = $this->getModel('Home');
		$model->reset();
		$this->setRedirect('index.php?option=com_fabrik', FText::_('COM_FABRIK_HOME_FABRIK_RESET'));
	}

	/**
	 * Install sample form
	 *
	 * @return null
	 */
	public function installSampleData()
	{
		$model = $this->getModel('Home');
		$model->installSampleData();
		$this->setRedirect('index.php?option=com_fabrik', FText::_('COM_FABRIK_HOME_SAMPLE_DATA_INSTALLED'));
	}

	/**
	 * Get RSS News feed
	 *
	 * @return string
	 */
	public function getRSSFeed()
	{
		// Get RSS parsed object
		$rssDoc = JSimplepieFactory::getFeedParser('http://feeds.feedburner.com/fabrik', 86400);

		if ($rssDoc == false)
		{
			$output = FText::_('Error: Feed not retrieved');
		}
		else
		{
			// Channel header and link
			$title = $rssDoc->get_title();
			$link = $rssDoc->get_link();
			$output = '<table class="adminlist">';
			$output .= '<tr><th colspan="3"><a href="' . $link . '" target="_blank">' . FText::_($title) . '</th></tr>';
			$items = array_slice($rssDoc->get_items(), 0, 3);
			$numItems = count($items);

			if ($numItems == 0)
			{
				$output .= '<tr><th>' . FText::_('No news items found') . '</th></tr>';
			}
			else
			{
				$k = 0;

				for ($j = 0; $j < $numItems; $j++)
				{
					$item = $items[$j];
					$output .= '<tr><td class="row' . $k . '">';
					$output .= '<a href="' . $item->get_link() . '" target="_blank">' . $item->get_title() . '</a>';
					$output .= '<br />' . $item->get_date('Y-m-d');

					if ($item->get_description())
					{
						$description = $this->_truncateText($item->get_description(), 50);
						$output .= '<br />' . $description;
					}

					$output .= '</td></tr>';
					$k = 1 - $k;
				}
			}

			$output .= '</table>';
		}

		return $output;
	}
}
