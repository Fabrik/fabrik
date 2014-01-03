<?php
/**
 * PDF Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * PDF Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikViewList extends JViewLegacy
{
	/**
	 * Display the Feed
	 *
	 * @param   sting  $tpl  template
	 *
	 * @return void
	 */

	public function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$Itemid	= FabrikWorker::itemId();
		$user = JFactory::getUser();
		$model = $this->getModel();
		$model->setOutPutFormat('feed');
		$document = JFactory::getDocument();
		$document->_itemTags = array();

		// Get the active menu item
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$table = $model->getTable();
		$model->render();
		$params = $model->getParams();

		if ($params->get('rss') == '0')
		{
			return '';
		}

		$formModel = $model->getFormModel();
		$form = $formModel->getForm();

		$aJoinsToThisKey = $model->getJoinsToThisKey();

		// Get headings
		$aTableHeadings = array();
		$groupModels = $formModel->getGroupsHiarachy();

		foreach ($groupModels as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();
				$elParams = $elementModel->getParams();

				if ($elParams->get('show_in_rss_feed') == '1')
				{
					$heading = $element->label;

					if ($elParams->get('show_label_in_rss_feed') == '1')
					{
						$aTableHeadings[$heading]['label'] = $heading;
					}
					else
					{
						$aTableHeadings[$heading]['label'] = '';
					}

					$aTableHeadings[$heading]['colName'] = $elementModel->getFullName();
					$aTableHeadings[$heading]['dbField'] = $element->name;
					$aTableHeadings[$heading]['key'] = $elParams->get('use_as_fake_key');
				}
			}
		}

		foreach ($aJoinsToThisKey as $element)
		{
			$element = $elementModel->getElement();
			$elParams = new JRegistry($element->attribs);

			if ($elParams->get('show_in_rss_feed') == '1')
			{
				$heading = $element->label;

				if ($elParams->get('show_label_in_rss_feed') == '1')
				{
					$aTableHeadings[$heading]['label'] = $heading;
				}
				else
				{
					$aTableHeadings[$heading]['label'] = '';
				}

				$aTableHeadings[$heading]['colName'] = $element->db_table_name . "___" . $element->name;
				$aTableHeadings[$heading]['dbField'] = $element->name;
				$aTableHeadings[$heading]['key'] = $elParams->get('use_as_fake_key');
			}
		}

		$w = new FabrikWorker;
		$rows = $model->getData();
		$document->title = $w->parseMessageForPlaceHolder($table->label, $_REQUEST);
		$document->description = htmlspecialchars(trim(strip_tags($w->parseMessageForPlaceHolder($table->introduction, $_REQUEST))));
		$document->link = JRoute::_('index.php?option=com_' . $package . '&view=list&listid=' . $table->id . '&Itemid=' . $Itemid);

		// Check for a custom css file and include it if it exists
		$tmpl = $input->get('layout', $table->template);
		$csspath = COM_FABRIK_FRONTEND . '/views/list/tmpl/' . $tmpl . '/feed.css';

		if (file_exists($csspath))
		{
			$document->addStyleSheet(COM_FABRIK_LIVESITE . 'components/com_fabrik/views/list/tmpl/' . $tmpl . '/feed.css');
		}

		$titleEl = $params->get('feed_title');
		$dateEl = $params->get('feed_date');

		$dateColId = (int) $params->get('feed_date', 0);
		$dateColElement = $formModel->getElement($dateColId, true);
		$dateEl = $db->quoteName($dateColElement->getFullName(false, false, false));

		$view = $model->canEdit() ? 'form' : 'details';

		// List of tags to look for in the row data
		// If they are there don't put them in the desc but put them in as a separate item param
		$rsstags = array('<georss:point>' => 'xmlns:georss="http://www.georss.org/georss"');

		foreach ($rows as $group)
		{
			foreach ($group as $row)
			{
				// Get the content
				$str2 = '';
				$str = '<table style="margin-top:10px;padding-top:10px;">';
				$title = '';
				$item = new JFeedItem;

				foreach ($aTableHeadings as $heading => $dbcolname)
				{
					if ($title == '')
					{
						// Set a default title
						$title = $row->$dbcolname['colName'];
					}

					$rsscontent = $row->$dbcolname['colName'];

					$found = false;

					foreach ($rsstags as $rsstag => $namespace)
					{
						if (strstr($rsscontent, $rsstag))
						{
							$found = true;

							if (!strstr($document->_namespace, $namespace))
							{
								$rsstag = JString::substr($rsstag, 1, JString::strlen($rsstag) - 2);
								$document->_itemTags[] = $rsstag;
								$document->_namespace .= $namespace . "\n";
							}

							break;
						}
					}

					if ($found)
					{
						$item->{$rsstag} = $rsscontent;
					}
					else
					{
						if ($dbcolname['label'] == '')
						{
							$str2 .= $rsscontent . "<br />\n";
						}
						else
						{
							$str .= "<tr><td>" . $dbcolname['label'] . ":</td><td>" . $rsscontent . "</td></tr>\n";
						}
					}
				}

				if (isset($row->$titleEl))
				{
					$title = $row->$titleEl;
				}

				$str = $str2 . $str . "</table>";

				// Url link to article
				$link = JRoute::_('index.php?option=com_' . $package . '&view=' . $view . '&listid=' . $table->id . '&formid='
					. $form->id . '&rowid=' . $row->__pk_val
					);

				// Strip html from feed item description text
				$author	= @$row->created_by_alias ? @$row->created_by_alias : @$row->author;

				if ($dateEl != '')
				{
					$date = ($row->$dateEl ? date('r', strtotime(@$row->$dateEl)) : '');
				}
				else
				{
					$data = '';
				}
				// Load individual item creator class

				$item->title = $title;
				$item->link = $link;
				$item->guid = $link;
				$item->description = $str;
				$item->date = $date;
				$item->category = $row->category;

				// Loads item info into rss array
				$document->addItem($item);
			}
		}
	}
}
