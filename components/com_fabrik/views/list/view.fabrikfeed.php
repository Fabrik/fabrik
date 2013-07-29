<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

/**
 * PDF Fabrik List view class, including closures
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikViewList extends FabrikViewListBase
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
		$Itemid = FabrikWorker::itemId();
		$config	= JFactory::getConfig();
		$user = JFactory::getUser();
		$model = $this->getModel();
		$model->setOutPutFormat('feed');
		if (!parent::access($model))
		{
			exit;
		}

		$document = JFactory::getDocument();
		$document->_itemTags = array();

		// Get the active menu item
		$usersConfig = JComponentHelper::getParams('com_fabrik');

		// $$$ hugh - modified this so you can enable QS filters on RSS links
		// by setting &incfilters=1
		JRequest::setVar('incfilters', JRequest::getInt('incfilters', 0));

		$table = $model->getTable();
		$model->render();
		$params	= $model->getParams();

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

		$titleEl = $params->get('feed_title');
		$dateEl = (int) $params->get('feed_date');
		foreach ($groupModels as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();
				if ($element->id == $titleEl)
				{
					$titleEl = $elementModel->getFullName(false, true, false);
				}
				if ($element->id == $dateEl)
				{
					$dateEl = $elementModel->getFullName(false, true, false);
					$rawdateEl = $dateEl . '_raw';
				}
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
					$aTableHeadings[$heading]['colName'] = $elementModel->getFullName(false, true);
					$aTableHeadings[$heading]['dbField'] = $element->name;
					$aTableHeadings[$heading]['key'] = $elParams->get('use_as_fake_key');

					// $$$ hugh - adding enclosure stuff for podcasting
					if ($element->plugin == 'fileupload' || $elParams->get('use_as_rss_enclosure', '0') == '1')
					{
						$aTableHeadings[$heading]['enclosure'] = true;
					}
					else
					{
						$aTableHeadings[$heading]['enclosure'] = false;
					}
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

				// $$$ hugh - adding enclosure stuff for podcasting
				if ($element->plugin == 'fileupload' || $elParams->get('use_as_rss_enclosure', '0') == '1')
				{
					$aTableHeadings[$heading]['enclosure'] = true;
				}
				else
				{
					$aTableHeadings[$heading]['enclosure'] = false;
				}
			}
		}
		$w = new FabrikWorker;
		$rows = $model->getData();

		$document->title = htmlentities($w->parseMessageForPlaceHolder($table->label, $_REQUEST), ENT_COMPAT, 'UTF-8');
		$document->description = htmlspecialchars(trim(strip_tags($w->parseMessageForPlaceHolder($table->introduction, $_REQUEST))));
		$document->link = JRoute::_('index.php?option=com_' . $package . '&view=list&listid=' . $table->id . '&Itemid=' . $Itemid);

		// Check for a custom css file and include it if it exists
		$tmpl = JRequest::getVar('layout', $table->template);
		$csspath = COM_FABRIK_FRONTEND . 'views/list/tmpl/' . $tmpl . '/feed.css';
		if (file_exists($csspath))
		{
			$document->addStyleSheet(COM_FABRIK_LIVESITE . 'components/com_fabrik/views/list/tmpl/' . $tmpl . '/feed.css');
		}

		$view = $model->canEdit() ? 'form' : 'details';

		// List of tags to look for in the row data
		// If they are there don't put them in the desc but put them in as a seperate item param
		$rsstags = array(
				'<georss:point>' => 'xmlns:georss="http://www.georss.org/georss"'
		);
		foreach ($rows as $group)
		{
			foreach ($group as $row)
			{
				// Get the content
				$str2 = '';
				$str = '';
				$tstart = '<table style="margin-top:10px;padding-top:10px;">';

				$title = '';
				$item = new JFabrikFeedItem;

				$enclosures = array();
				foreach ($aTableHeadings as $heading => $dbcolname)
				{
					if ($dbcolname['enclosure'])
					{

						// $$$ hugh - diddling aorund trying to add enclosures
						$colName = $dbcolname['colName'] . '_raw';
						$enclosure_url = $row->$colName;
						if (!empty($enclosure_url))
						{
							$remote_file = false;

							// Element value should either be a full path, or relative to J! base
							if (strstr($enclosure_url, 'http://') && !strstr($enclosure_url, COM_FABRIK_LIVESITE))
							{
								$enclosure_file = $enclosure_url;
								$remote_file = true;
							}
							elseif (strstr($enclosure_url, COM_FABRIK_LIVESITE))
							{
								$enclosure_file = str_replace(COM_FABRIK_LIVESITE, COM_FABRIK_BASE, $enclosure_url);
							}
							elseif (preg_match('#^' . COM_FABRIK_BASE . "#", $enclosure_url))
							{
								$enclosure_file = $enclosure_url;
								$enclosure_url = str_replace(COM_FABRIK_BASE, '', $enclosure_url);
							}
							else
							{
								$enclosure_file = COM_FABRIK_BASE . $enclosure_url;
								$enclosure_url = COM_FABRIK_LIVESITE . str_replace('\\', '/', $enclosure_url);
							}
							if ($remote_file || (file_exists($enclosure_file) && !is_dir($enclosure_file)))
							{
								$enclosure_type = '';
								if ($enclosure_type = FabrikWorker::getPodcastMimeType($enclosure_file))
								{
									$enclosure_size = $this->get_filesize($enclosure_file, $remote_file);
									$enclosures[] = array(
											'url' => $enclosure_url,
											'length' => $enclosure_size,
											'type' => $enclosure_type
									);
									/**
									 * No need to insert the URL in the description, as feed readers should
									 * automagically show 'media' when they see an 'enclosure', so just move on ..
									 */
									continue;
								}
							}
						}
					}
					if ($title == '')
					{
						// Set a default title
						$title = $row->$dbcolname['colName'];
					}
					// Rob - was stripping tags - but arent they valid in the content?
					$rsscontent = $row->$dbcolname['colName'];

					$found = false;
					foreach ($rsstags as $rsstag => $namespace)
					{
						if (strstr($rsscontent, $rsstag))
						{
							$found = true;
							$rsstag = JString::substr($rsstag, 1, JString::strlen($rsstag) - 2);
							if (!strstr($document->_namespace, $namespace))
							{
								$document->_itemTags[] = $rsstag;
								$document->_namespace .= $namespace . " ";
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
				if ($dbcolname['label'] != '')
				{
					$str = $tstart . $str . "</table>";
				}
				else
				{
					$str = $str2;
				}

				// Url link to article
				$link = JRoute::_('index.php?option=com_' . $package . '&view=' . $view . '&listid=' . $table->id . '&formid=' . $form->id . '&rowid=' . $row->slug);
				$guid = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&view=' . $view . '&listid=' . $table->id . '&formid=' . $form->id . '&rowid=' . $row->slug;

				// Strip html from feed item description text
				$author = @$row->created_by_alias ? @$row->created_by_alias : @$row->author;

				if ($dateEl != '')
				{
					$date = $row->$dateEl ? date('r', strtotime(@$row->$rawdateEl)) : '';
				}
				else
				{
					$date = '';
				}
				// Load individual item creator class

				$item->title = $title;
				$item->link = $link;
				$item->guid = $guid;
				$item->description = $str;
				$item->date = $date;

				// $$$ hugh - not quite sure where we were expecting $row->category to come from.  Comment out for now.
				// $item->category = $row->category;

				foreach ($enclosures as $enclosure)
				{
					$item->setEnclosure($enclosure);
				}

				// Loads item info into rss array
				$res = $document->addItem($item);
			}
		}
	}

	/**
	 * Get file size
	 *
	 * @param   string  $path    File path
	 * @param   bool    $remote  Remote file, if true attempt to load file via Curl
	 *
	 * @return mixed|number
	 */

	protected function get_filesize($path, $remote = false)
	{
		if ($remote)
		{
			$ch = curl_init($path);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_NOBODY, true);

			$data = curl_exec($ch);
			$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

			curl_close($ch);
			return $size;
		}
		else
		{
			return filesize($path);
		}
	}

}
