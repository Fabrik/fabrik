<?php
/**
 * PDF Fabrik List view class, including closures
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\Registry\Registry;

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
		$input = $this->app->input;
		$itemId = FabrikWorker::itemId();
		$model = $this->getModel();
		$model->setOutPutFormat('feed');

		$this->app->allowCache(true);

		if (!parent::access($model))
		{
			exit;
		}

		$this->doc->_itemTags = array();

		// $$$ hugh - modified this so you can enable QS filters on RSS links
		// by setting &incfilters=1
		$input->set('incfilters', $input->getInt('incfilters', 0));
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
		$titleEl = $params->get('feed_title');
		$dateEl = (int) $params->get('feed_date');

		//$imageEl = $formModel->getElement($imageEl, true);
		$titleEl = $formModel->getElement($titleEl, true);
		$dateEl = $formModel->getElement($dateEl, true);
		$titleElName = $titleEl === false ? '' : $titleEl->getFullName(true, false);
		$dateElName = $dateEl === false ? '' : $dateEl->getFullName(true, false);
		$dateElNameRaw = $dateElName . '_raw';

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
			$elParams = new Registry($element->attribs);

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

		$this->doc->title = htmlspecialchars($w->parseMessageForPlaceHolder($table->label, $_REQUEST), ENT_COMPAT, 'UTF-8');
		$this->doc->description = htmlspecialchars(trim(strip_tags($w->parseMessageForPlaceHolder($table->introduction, $_REQUEST))));
		$this->doc->link = JRoute::_('index.php?option=com_' . $this->package . '&view=list&listid=' . $table->id . '&Itemid=' . $itemId);

		$this->addImage($params);

		// Check for a custom css file and include it if it exists
		$tmpl = $input->get('layout', $table->template);
		$cssPath = COM_FABRIK_FRONTEND . 'views/list/tmpl/' . $tmpl . '/feed.css';

		if (file_exists($cssPath))
		{
			$this->doc->addStyleSheet(COM_FABRIK_LIVESITE . 'components/com_fabrik/views/list/tmpl/' . $tmpl . '/feed.css');
		}

		$view = $model->canEdit() ? 'form' : 'details';

		// List of tags to look for in the row data
		// If they are there don't put them in the desc but put them in as a separate item param
		$rssTags = array(
				'<georss:point>' => 'xmlns:georss="http://www.georss.org/georss"'
		);

		foreach ($rows as $group)
		{
			foreach ($group as $row)
			{
				// Get the content
				$str2 = '';
				$str = '';
				$tStart = '<table style="margin-top:10px;padding-top:10px;">';
				$title = '';
				$item = new JFabrikFeedItem;
				$enclosures = array();

				foreach ($aTableHeadings as $heading => $dbColName)
				{
					if ($dbColName['enclosure'])
					{
						// $$$ hugh - diddling around trying to add enclosures
						$colName = $dbColName['colName'] . '_raw';
						$enclosureUrl = $row->$colName;

						if (!empty($enclosureUrl))
						{
							$remoteFile = false;

							// Element value should either be a full path, or relative to J! base
							if (strstr($enclosureUrl, 'http://') && !strstr($enclosureUrl, COM_FABRIK_LIVESITE))
							{
								$enclosureFile = $enclosureUrl;
								$remoteFile = true;
							}
							elseif (strstr($enclosureUrl, COM_FABRIK_LIVESITE))
							{
								$enclosureFile = str_replace(COM_FABRIK_LIVESITE, COM_FABRIK_BASE, $enclosureUrl);
							}
							elseif (preg_match('#^' . COM_FABRIK_BASE . "#", $enclosureUrl))
							{
								$enclosureFile = $enclosureUrl;
								$enclosureUrl = str_replace(COM_FABRIK_BASE, '', $enclosureUrl);
							}
							else
							{
								$enclosureUrl = ltrim($enclosureUrl, '/\\');
								$enclosureFile = COM_FABRIK_BASE . $enclosureUrl;
								$enclosureUrl = COM_FABRIK_LIVESITE . str_replace('\\', '/', $enclosureUrl);
							}

							if ($remoteFile || (file_exists($enclosureFile) && !is_dir($enclosureFile)))
							{
								$enclosureType = '';

								if ($enclosureType = FabrikWorker::getPodcastMimeType($enclosureFile))
								{
									$enclosure_size = $this->get_filesize($enclosureFile, $remoteFile);
									$enclosures[] = array(
											'url' => $enclosureUrl,
											'length' => $enclosure_size,
											'type' => $enclosureType
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
						$title = $row->{$dbColName['colName']};
					}

					// Rob - was stripping tags - but aren't they valid in the content?
					$rssContent = $row->{$dbColName['colName']};
					$found = false;

					foreach ($rssTags as $rssTag => $namespace)
					{
						if (strstr($rssContent, $rssTag))
						{
							$found = true;
							$rssTag = JString::substr($rssTag, 1, JString::strlen($rssTag) - 2);

							if (!strstr($this->doc->_namespace, $namespace))
							{
								$this->doc->_itemTags[] = $rssTag;
								$this->doc->_namespace .= $namespace . " ";
							}

							break;
						}
					}

					if ($found)
					{
						$item->{$rssTag} = $rssContent;
					}
					else
					{
						if ($dbColName['label'] == '')
						{
							$str2 .= $rssContent . "<br />\n";
						}
						else
						{
							$str .= "<tr><td>" . $dbColName['label'] . ":</td><td>" . $rssContent . "</td></tr>\n";
						}
					}
				}

				if (isset($row->$titleElName))
				{
					$title = $row->$titleElName;
				}


				if (FArrayHelper::getValue($dbColName, 'label') != '')
				{
					$str = $tStart . $str . "</table>";
				}
				else
				{
					$str = $str2;
				}

				// Url link to article
				$link = JRoute::_('index.php?option=com_' . $this->package . '&view=' . $view . '&listid=' . $table->id . '&formid=' . $form->id
					. '&rowid=' . $row->slug
					);
				$guid = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $this->package . '&view=' . $view . '&listid=' . $table->id . '&formid='
					. $form->id . '&rowid=' . $row->slug;

				// Strip html from feed item description text
				$author = @$row->created_by_alias ? @$row->created_by_alias : @$row->author;

				$item->date = isset($row->$dateElName) && $row->$dateElName ? date('r', strtotime(@$row->$dateElNameRaw)) : '';

				// Load individual item creator class

				$item->title = $title;
				$item->link = $link;
				$item->guid = $guid;
				$item->description = $str;

				// $$$ hugh - not quite sure where we were expecting $row->category to come from.  Comment out for now.
				// $item->category = $row->category;

				foreach ($enclosures as $enclosure)
				{
					$item->setEnclosure($enclosure);
				}

				// Loads item info into rss array
				$res = $this->doc->addItem($item);
			}
		}
	}

	/**
	 * Add <image> to document
	 *
	 * @param   object  $params    Registry list parameters
	 *
	 * @return  document
	 */
	private function addImage($params)
	{
		$imageSrc = $params->get('feed_image_src', '');

		if ($imageSrc !== '')
		{
			$image = new stdClass;
			$image->url = $imageSrc;
			$image->title = $this->doc->title;
			$image->link = $this->doc->link;
			$image->width = '';
			$image->height = '';
			$image->description = '';
			$this->doc->image = $image;
		}

		return $this->doc;
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
