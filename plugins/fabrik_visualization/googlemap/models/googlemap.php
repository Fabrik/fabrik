<?php
/**
 * Fabrik Google Map Viz Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Googlemap;
use Fabrik\Helpers\Lizt;
use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';

/**
 * Fabrik Google Map Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @since       3.0
 */
class FabrikModelGooglemap extends FabrikFEModelVisualization
{
	/**
	 * Out put text
	 *
	 * @var array
	 */
	protected $txt = null;

	/**
	 * Arrays (width, height) keyed on image icon
	 *
	 * @var array
	 */
	protected $markerSizes = array();

	/**
	 * Number of Fabrik records parsed
	 *
	 * @var int
	 */
	protected $recordCount = 0;

	/**
	 * Icon row data
	 */
	protected $iconRowData = null;

	/**
	 * Get HTML text
	 *
	 * @return  string
	 */
	public function getText()
	{
		return $this->txt;
	}

	/**
	 * Build js string to create the map js object
	 *
	 * @return string
	 */
	public function getJs()
	{
		$this->setPrefilters();
		$params = $this->getParams();

		if (!$this->getRequiredFiltersFound() && $params->get('fb_gm_always_show_map', '0') != '1')
		{
			return '';
		}

		$input  = $this->app->input;
		$params = $this->getParams();
		$viz    = $this->getVisualization();

		$opts            = new stdClass;
		$opts->lat       = (float) $params->get('fb_gm_default_lat', 0);
		$opts->lon       = (float) $params->get('fb_gm_default_lon', 0);
		$opts->ajaxDefer = (bool) $params->get('fb_gm_ajax_defer', '0');

		if ($params->get('fb_gm_ajax_defer', '0') !== '1')
		{
			$opts->icons = $this->getJSIcons();
		}
		else
		{
			$opts->icons = array();
		}

		$opts->polyline        = $this->getPolyline();
		$opts->id              = $viz->id;
		$opts->zoomlevel       = (int) $params->get('fb_gm_zoomlevel');
		$opts->fitbounds       = (bool) $params->get('fb_gm_fitbounds');
		$opts->scalecontrol    = (bool) $params->get('fb_gm_scalecontrol');
		$opts->scrollwheel     = (bool) $params->get('fb_gm_scrollwheelcontrol');
		$opts->maptypecontrol  = (bool) $params->get('fb_gm_maptypecontrol');
		$opts->traffic         = (bool) $params->get('fb_gm_trafficlayer', '0');
		$opts->overviewcontrol = (bool) $params->get('fb_gm_overviewcontrol');
		$opts->streetView      = (bool) $params->get('street_view');
		$opts->center          = $params->get('fb_gm_center');

		if ($opts->center == 'querystring')
		{
			$opts->lat       = $input->get('latitude', '') == '' ? $opts->lat : (float) $input->get('latitude');
			$opts->lon       = $input->get('longitude', '') == '' ? $opts->lon : (float) $input->get('longitude');
			$opts->zoomlevel = $input->get('zoom', '') == '' ? $opts->zoomlevel : $input->get('zoom');
		}

		$opts->ajax_refresh                = (bool) $params->get('fb_gm_ajax_refresh', false);
		$opts->ajax_refresh_center         = (bool) $params->get('fb_gm_ajax_refresh_center', true);
		$opts->maptype                     = $params->get('fb_gm_maptype');
		$opts->clustering                  = (bool) $params->get('fb_gm_clustering', '0') == '1';
		$opts->cluster_splits              = $params->get('fb_gm_cluster_splits');
		$opts->icon_increment              = $params->get('fb_gm_cluster_icon_increment');
		$opts->refresh_rate                = $params->get('fb_gm_ajax_refresh_rate');
		$opts->use_cookies                 = (bool) $params->get('fb_gm_use_cookies');
		$opts->container                   = $this->getContainerId();
		$opts->polylinewidth               = (array) $params->get('fb_gm_polyline_width');
		$opts->polylinecolour              = (array) $params->get('fb_gm_polyline_colour');
		$usePolygon                        = (array) $params->get('fb_gm_use_polygon');
		$opts->use_polygon                 = (bool) FArrayHelper::getValue($usePolygon, 0, true);
		$opts->polygonopacity              = $params->get('fb_gm_polygon_fillOpacity', 0.35);
		$opts->polygonfillcolour           = (array) $params->get('fb_gm_polygon_fillColor');
		$opts->overlay_urls                = (array) $params->get('fb_gm_overlay_urls');
		$opts->overlay_labels              = (array) $params->get('fb_gm_overlay_labels');
		$opts->overlay_preserveviewports   = (array) $params->get('fb_gm_overlay_preserveviewport');
		$opts->overlay_suppressinfowindows = (array) $params->get('fb_gm_overlay_suppressinfowindows');
		$opts->use_overlays                = (int) $params->get('fb_gm_use_overlays', '0');

		if ($opts->use_overlays)
		{
			$opts->overlay_urls                = (array) $params->get('fb_gm_overlay_urls');
			$opts->overlay_labels              = (array) $params->get('fb_gm_overlay_labels');
			$opts->overlay_preserveviewports   = (array) $params->get('fb_gm_overlay_preserveviewport');
			$opts->overlay_suppressinfowindows = (array) $params->get('fb_gm_overlay_suppressinfowindows');

			$overlayCode = trim($params->get('fb_gm_overlay_code', ''));

			if (!empty($overlayCode))
			{
				$overlayArray = eval($overlayCode);

				if (is_array($overlayArray))
				{
					if (array_key_exists('urls', $overlayArray) && is_array($overlayArray['urls']))
					{
						$opts->overlay_urls = array_merge($opts->overlay_urls, $overlayArray['urls']);
					}

					if (array_key_exists('labels', $overlayArray))
					{
						$opts->overlay_labels = array_merge($opts->overlay_labels, $overlayArray['labels']);
					}

					if (array_key_exists('preserveViewports', $overlayArray))
					{
						$opts->overlay_preserveviewports = array_merge($opts->overlay_preserveviewports, $overlayArray['preserveViewports']);
					}

					if (array_key_exists('suppressInfoWindows', $overlayArray))
					{
						$opts->overlay_suppressinfowindows = array_merge($opts->overlay_suppressinfowindows, $overlayArray['suppressInfoWindows']);
					}
				}
			}

			$this->overlayData = array(
				'urls'                => $opts->overlay_urls,
				'labels'              => $opts->overlay_labels,
				'preserveViewports'   => $opts->overlay_preserveviewports,
				'suppressInfoWindows' => $opts->overlay_suppressinfowindows
			);
		}
		else
		{
			$opts->overlay_urls                = array();
			$opts->overlay_labels              = array();
			$opts->overlay_preserveviewports   = array();
			$opts->overlay_suppressinfowindows = array();
			$this->overlayData                 = array(
				'urls'                => array(),
				'labels'              => array(),
				'preserveViewports'   => array(),
				'suppressInfoWindows' => array()
			);
		}

		$opts->use_overlays_sidebar = $opts->use_overlays && (int) $params->get('fb_gm_use_overlays_sidebar', '0');
		$opts->use_groups           = (bool) $params->get('fb_gm_group_sidebar', 0);
		$opts->groupTemplates       = $this->getGroupTemplates();
		$opts->zoomStyle            = (int) $params->get('fb_gm_zoom_control_style', 0);
		$opts->zoom                 = $params->get('fb_gm_zoom', 1);
		$opts->show_radius          = $params->get('fb_gm_use_radius', '1') == '1' ? true : false;
		$opts->heatmap              = $params->get('fb_gm_heatmap', '0') == '1' ? true : false;
		$opts->radius_defaults      = (array) $params->get('fb_gm_radius_default');
		$opts->radius_fill_colors   = (array) $params->get('fb_gm_radius_fill_color');
		$opts->styles               = Googlemap::styleJs($params);
		$config                     = JComponentHelper::getParams('com_fabrik');
		$apiKey                     = $config->get('google_api_key', '');
		$opts->key                  = empty($apiKey) ? false : $apiKey;
		$opts->showLocation         = $params->get('fb_gm_show_location', '0') === '1';
		$opts                       = json_encode($opts);
		$ref                        = $this->getJSRenderContext();
		$js                         = array();
		$js[]                       = "\t$ref = new FbGoogleMapViz('table_map', $opts)";
		$js[]                       = "\t" . "Fabrik.addBlock('$ref', $ref);";
		$js[]                       = "\n";

		return implode("\n", $js);
	}

	/**
	 * Set an array of list id's whose data is used inside the visualization
	 *
	 * @return  void
	 */
	protected function setListIds()
	{
		if (!isset($this->listids))
		{
			$params = $this->getParams();
			$this->listids = (array) $params->get('googlemap_table');
		}
	}

	/**
	 * Build a polygon line to join up the markers
	 *
	 * @return  array  of lines each line being an array of points
	 */
	protected function getPolyline()
	{
		$params = $this->getParams();
		$lines = array();
		$polyElements = (array) $params->get('fb_gm_polyline_element');
		$listModels = $this->getlistModels();
		$c = 0;

		foreach ($listModels as $listModel)
		{
			$k = FabrikString::safeColName(FabrikString::rtrimword($polyElements[$c], '[]'));

			if ($k == '``')
			{
				$c++;
				continue;
			}

			$mapsElements = Lizt::getElements($listModel, array('plugin' => 'googlemap', 'published' => 1));
			$coordColumn = $mapsElements[0]->getFullName(false, false);
			$table = $listModel->getTable();

			$db = $listModel->getDb();
			$query = $db->getQuery(true);
			$query->select($coordColumn . ' AS coords')->from($table->db_table_name)->order($k);
			$query = $listModel->buildQueryWhere(true, $query);
			$query = $listModel->buildQueryJoin($query);
			$db->setQuery($query);
			$data = $db->loadObjectList();
			$points = array();

			foreach ($data as $d)
			{
				$d = $this->getCordsFromData($d->coords);

				if ($d == array(0, 0))
				{
					// Don't show icons with no data
					continue;
				}

				$points[] = $d;
			}

			$lines[] = $points;
			$c++;
		}

		return $lines;
	}

	/**
	 * Convert the Fabrik {lat,long:zoom} format into an array
	 *
	 * @param   string  $d  data
	 *
	 * @return  array
	 */
	private function getCordsFromData($d)
	{
		$v = str_replace(' ', '', $d);
		$v = trim($v);
		$v = FabrikString::ltrimword($v, "(");

		if (strstr($v, ","))
		{
			if (strstr($v, ":"))
			{
				$ar = explode(":", $v);
				array_pop($ar);
				$v = explode(",", $ar[0]);
			}
			else
			{
				$v = explode(",", $v);
			}

			$v[1] = FabrikString::rtrimword($v[1], ")");
		}
		else
		{
			$v = array(0, 0);
		}

		return $v;
	}

	/**
	 * Get the map icons
	 *
	 * @return  array
	 */
	public function getJSIcons()
	{
		$input = $this->app->input;
		$icons = array();
		$w = new FabrikWorker;
		$uri = JURI::getInstance();
		$params = $this->getParams();
		$templates = (array) $params->get('fb_gm_detailtemplate');
		$templates_nl2br = (array) $params->get('fb_gm_detailtemplate_nl2br');
		$listIds = (array) $params->get('googlemap_table');

		// Images for file system
		$aIconImgs = (array) $params->get('fb_gm_iconimage');

		// Image from marker data
		$markerImages = (array) $params->get('fb_gm_iconimage2');
		$markerImagesPath = (array) $params->get('fb_gm_iconimage2_path');

		// Specified letter
		$letters = (array) $params->get('fb_gm_icon_letter');
		$aFirstIcons = (array) $params->get('fb_gm_first_iconimage');
		$aLastIcons = (array) $params->get('fb_gm_last_iconimage');
		$titleElements = (array) $params->get('fb_gm_title_element');
		$radiusElements = (array) $params->get('fb_gm_radius_element');
		$radiusDefaults = (array) $params->get('fb_gm_radius_default');
		$radiusUnits = (array) $params->get('fb_gm_radius_unit');
		$groupClass = (array) $params->get('fb_gm_group_class');

		$c = 0;
		$this->recordCount = 0;

		$maxMarkers = $params->get('fb_gm_markermax', 0);
		$recLimit = count($listIds) == 1 ? $maxMarkers : 0;
		$limitMessageShown = false;
		$limitMessage = $params->get('fb_gm_markermax_message');
		$groupedIcons = array();
		$lc = 0;

		foreach ($listIds as $listId)
		{
			$listModel = $this->getlistModel($listId);

			$template = FArrayHelper::getValue($templates, $c, '');
			/**
			* One day we should get smarter about how we decide which elements to render
			* but for now all we can do is set formatAll(), in case they use an element
			* which isn't set for list display, which then wouldn't get rendered unless we do this.
			*/

			/*
			if (FabrikString::usesElementPlaceholders($template))
			{
				$listModel->formatAll(true);
			}
            */

			$template_nl2br = FArrayHelper::getValue($templates_nl2br, $c, '1') == '1';
			$mapsElements = Lizt::getElements($listModel, array('plugin' => 'googlemap', 'published' => 1));
			$coordColumn = $mapsElements[0]->getFullName(true, false) . "_raw";

			// Are we using random start location for icons?
			$listModel->_randomRecords = ($params->get('fb_gm_random_marker') == 1 && $recLimit != 0) ? true : false;

			// Used in list model setLimits
			$input->set('limit' . $listId, $recLimit);
			$listModel->setLimits(0, $recLimit);
			$listModel->getPagination(0, 0, $recLimit);
			$data = $listModel->getData(
			    array (
			        'rollover' => false,
                    'custom_layout' => false,
                    'add_box_and_links' => strstr($template, '{fabrik_')
                )
            );
			$this->txt = array();
			$k = 0;

			foreach ($data as $groupKey => $group)
			{
				foreach ($group as $row)
				{
					$customImageFound = false;
					$iconImg = FArrayHelper::getValue($aIconImgs, $c, '');

					if ($k == 0)
					{
						$firstIcon = FArrayHelper::getValue($aFirstIcons, $c, $iconImg);

						if ($firstIcon !== '')
						{
							$iconImg = $firstIcon;
						}
					}

					if (!empty($iconImg))
					{
						$iconImg = '/media/com_fabrik/images/' . $iconImg;
					}

					$v = $this->getCordsFromData($row->$coordColumn);

					if ($v == array(0, 0))
					{
						// Don't show icons with no data
						continue;
					}

					$rowData = ArrayHelper::fromObject($row);
					$rowData['rowid'] = $rowData['__pk_val'];

					$matches = array();

					// alllow {coords:X} to specify number of decimal points to show
					if (preg_match('/\{coords:(\d+)\}/', $template, $matches))
					{
						$rowData[trim($matches[0],'{}')] = sprintf('%.' . $matches[1] . 'f', $v[0]) . ',' . sprintf('%.' . $matches[1] . 'f', $v[1]);
					}

					$rowData['coords'] = $v[0] . ',' . $v[1];

					$rowData['nav_url'] = "http://maps.google.com/maps?q=loc:" . $rowData['coords'] . "&navigate=yes";
					$html = $w->parseMessageForPlaceHolder($template, $rowData);
					FabrikHelperHTML::runContentPlugins($html, true);

					$titleElement = FArrayHelper::getValue($titleElements, $c, '');
					$title = $titleElement == '' ? '' : html_entity_decode(strip_tags($row->$titleElement),ENT_COMPAT, 'UTF-8');
					/* $$$ hugh - if they provided a template, lets assume they will handle the link themselves.
					 * http://fabrikar.com/forums/showthread.php?p=41550#post41550
					 * $$$ hugh - at some point the fabrik_view / fabrik_edit links became optional
					 */

					if (empty($html) && (array_key_exists('fabrik_view', $rowData) || array_key_exists('fabrik_edit', $rowData)))
					{
						// Don't insert line break in empty bubble without links $html .= "<br />";

						// Use edit link by preference
						if (array_key_exists('fabrik_edit', $rowData))
						{
							if ($rowData['fabrik_edit'] != '')
							{
								$html .= "<br />";
							}

							$html .= $rowData['fabrik_edit'];
						}
						else
						{
							if ($rowData['fabrik_view'] != '')
							{
								$html .= "<br />";
							}

							$html .= $rowData['fabrik_view'];
						}
						$template_nl2br = false;
					}

					if ($template_nl2br)
					{
						/*
						 *  $$$ hugh - not sure why we were doing this rather than nl2br?
						 If there was a reason, this is still broken, as it ends up inserting
						 two breaks.  So if we can't use nl2br ... I need fix this before using it again!

						$html = str_replace(array("\n\r"), "<br />", $html);
						$html = str_replace(array("\r\n"), "<br />", $html);
						$html = str_replace(array("\n", "\r"), "<br />", $html);
						*/
						$html = FabrikString::safeNl2br($html);
					}

					$html = str_replace("'", '"', $html);
					$this->txt[] = $html;

					if ($iconImg == '')
					{
						$iconImg = FArrayHelper::getValue($markerImages, $c, '');

						if ($iconImg != '')
						{
							/**
							 * $$$ hugh - added 'path' choice for data icons, to make this option more flexible.  Up till
							 * now we have been forcing paths relative to /media/com_fabrik/images (which was added in the JS).
							 * New options for path root are:
							 *
							 * media - (default) existing behavior of /meadia/com_fabrik/images
							 * jroot - relative to J! root
							 * absolute - full server path
							 * url - url (surprise surprise)
							 * img - img tag (so we extract src=)
							 */
							$iconImgPath = FArrayHelper::getValue($markerImagesPath, $c, 'media');

							$iconImg = FArrayHelper::getValue($rowData, $iconImg, '');

							// Normalize the $iconimg so it is either a file path relative to J! root, or a non-local URL
							switch ($iconImgPath) {
								case 'media':
								default:
									$iconImg = 'media/com_fabrik/images/' . ltrim($iconImg, '/');
									break;
								case 'jroot':
									break;
								case 'absolute':
									$iconImg = str_replace(JPATH_BASE, '', $iconImg);
									break;
								case 'url':
									$iconImg = str_replace(COM_FABRIK_LIVESITE, '', $iconImg);
									break;
								case 'img':
									// Get the src
									preg_match('/src=["|\'](.*?)["|\']/', $iconImg, $matches);

									if (array_key_exists(1, $matches))
									{
										$iconImg = $matches[1];
									}

									$iconImg = str_replace(COM_FABRIK_LIVESITE, '', $iconImg);
									break;
							}

							if (strstr($iconImg, 'http://') || strstr($iconImg, 'https://') || JFile::exists(JPATH_BASE . $iconImg))
							{
								$customImageFound = true;
							}

						}

						if ($iconImg != '' && !(strstr($iconImg, 'http://') || strstr($iconImg, 'https://')))
						{
							list($width, $height) = $this->markerSize(JPATH_BASE . $iconImg);
						}
						else
						{
							// Standard google map icon size
							$width = 20;
							$height = 34;
						}
					}
					else
					{
						// Standard google map icon size
						list($width, $height) = $this->markerSize(JPATH_BASE . $iconImg);
					}

					$gClass = FArrayHelper::getValue($groupClass, 0, '');

					if (!empty($gClass))
					{
						$gClass .= '_raw';
						$gClass = (isset($row->$gClass)) ? $row->$gClass : '';
					}

					if (array_key_exists($v[0] . $v[1], $icons))
					{
						$existingIcon = $icons[$v[0] . $v[1]];

						if ($existingIcon['groupkey'] == $groupKey)
						{
							/* $$$ hugh - this inserts label between multiple record $html, but not at the top.
							 * If they want to insert label, they can do it themselves in the template.
							 * $icons[$v[0].$v[1]][2] = $icons[$v[0].$v[1]][2] . "<h6>$table->label</h6>" . $html;
							 * Don't insert linebreaks in empty bubble
							 */
							if ($html != '')
							{
								$html = "<br />" . $html;
							}

							$icons[$v[0] . $v[1]][2] = $icons[$v[0] . $v[1]][2] . $html;

							if ($customImageFound)
							{
								$icons[$v[0] . $v[1]][3] = $iconImg;
							}
						}
						else
						{
							$groupedIcons[] = array($v[0], $v[1], $html, $iconImg, $width, $height, 'groupkey' => $groupKey, 'listid' => $listId,
								'title' => $title, 'groupClass' => 'type' . $gClass);
						}
					}
					else
					{
						// Default icon - lets see if we need to use a letter icon instead
						if (FArrayHelper::getValue($letters, $c, '') != '')
						{
							$iconImg = $uri->getScheme() . '://www.google.com/mapfiles/marker' . JString::strtoupper($letters[$c]) . '.png';
						}

						$icons[$v[0] . $v[1]] = array($v[0], $v[1], $html, $iconImg, $width, $height, 'groupkey' => $groupKey, 'listid' => $listId,
							'title' => $title, 'groupClass' => 'type' . $gClass);
					}

					if ($params->get('fb_gm_use_radius', '0') == '1')
					{
						$radiusElement = FArrayHelper::getValue($radiusElements, $c, '');
						$radiusUnits = FArrayHelper::getValue($radiusUnits, $c, 'k');
						$radiusMeters = $radiusUnits == 'k' ? 1000 : 1609.34;

						if (!empty($radiusElement))
						{
							$radius = (float) $row->$radiusElement;
							$radius *= $radiusMeters;
							$icons[$v[0] . $v[1]]['radius'] = $radius;
						}
						else
						{
							$default = (float) ArrayHelper::getvalue($radiusDefaults, $c, 50);
							$default *= $radiusMeters;
							$icons[$v[0] . $v[1]]['radius'] = $default;
						}
					}

					$icons[$v[0] . $v[1]]['c'] = $c;
					$this->recordCount++;
					$k++;
				}
			}
			// Replace last icon?
			$iconImg = FArrayHelper::getValue($aLastIcons, $c, '');

			if ($iconImg != '' && !empty($icons))
			{
				list($width, $height) = $this->markerSize(JPATH_SITE . '/media/com_fabrik/images/' . $iconImg);
				$icons[$v[0] . $v[1]][3] = '/media/com_fabrik/images/' . $iconImg;
				$icons[$v[0] . $v[1]][4] = $width;
				$icons[$v[0] . $v[1]][5] = $height;
			}

			// $$$ hugh - not used?  And doesn't make sense
			/*
			if (!empty($icons))
			{
				$this->iconRowData[] = array(
					'icon' => $icons[$v[0] . $v[1]],
					'data' => $rowData
				);
			}
			*/

			$c++;
		}
		// Replace coord keys with numeric keys
		$icons = array_values($icons);
		$icons = array_merge($icons, $groupedIcons);

		if ($maxMarkers != 0 && $maxMarkers < count($icons))
		{
			$icons = array_slice($icons, -$maxMarkers);
		}

		$limitMessageShown = !($k >= $recLimit);

		if (!$limitMessageShown && $recLimit !== 0 && $limitMessage != '')
		{
			$this->app->enqueueMessage($limitMessage);
		}

		FabrikHelperHTML::debug($icons, 'map');

		return $icons;
	}

	/**
	 * Get the width and height for an icon image
	 *
	 * @param   string  $iconImg  icon image path
	 *
	 * @return  array  (width, height)
	 */
	private function markerSize($iconImg)
	{
		if (!array_key_exists($iconImg, $this->markerSizes))
		{
			@$size = getimagesize($iconImg);
			$width = is_array($size) ? $size[0] : 25;
			$height = is_array($size) ? $size[1] : 25;

			// Ensure icons aren't too big (25 is max)
			$scale = min(25 / $width, 25 / $height);

			// If the image is larger than the max shrink it
			if ($scale < 1)
			{
				$width = floor($scale * $width);
				$height = floor($scale * $height);
			}

			$this->markerSizes[$iconImg] = array($width, $height);
		}

		return $this->markerSizes[$iconImg];
	}

	/**
	 * Ajax call to get the json encoded string of map markers
	 *
	 * @return  string
	 */
	public function onAjax_getMarkers()
	{
		echo json_encode($this->getJSIcons());
	}

	/**
	 * Get a static map
	 *
	 * @return  string  html image
	 */
	public function getStaticMap()
	{
		$params = $this->getParams();
		$icons = $this->getJSIcons();
		$iconStr = '';
		$lat = 0;
		$lon = 0;

		if (!empty($icons))
		{
			$first = $icons[0];
			$bounds = array('lat' => array($first[0], $first[0]), 'lon' => array($first[1], $first[1]));
			$c = 1;

			foreach ($icons as $i)
			{
				if ($c >= 50)
				{
					break;
				}

				$iconStr .= "&markers=" . trim($i[0]) . ',' . trim($i[1]);

				if ($i[0] < $bounds['lat'][0])
				{
					$bounds['lat'][0] = $i[0];
				}

				if ($i[0] > $bounds['lat'][1])
				{
					$bounds['lat'][1] = $i[0];
				}

				if ($i[1] < $bounds['lon'][0])
				{
					$bounds['lon'][0] = $i[1];
				}

				if ($i[1] > $bounds['lon'][1])
				{
					$bounds['lon'][1] = $i[1];
				}

				$c++;
			}

			if ($params->get('fb_gm_center') != 'middle')
			{
				$i = array_pop($icons);
				$lat = $i[0];
				$lon = $i[1];
			}
			else
			{
				$lat = ($bounds['lat'][1] + $bounds['lat'][0]) / 2;
				$lon = ($bounds['lon'][1] + $bounds['lon'][0]) / 2;
			}
		}

		$w = $params->get('fb_gm_mapwidth');
		$h = $params->get('fb_gm_mapheight');
		$z = $params->get('fb_gm_zoomlevel');

		if ($w > 640)
		{
			$w = 640;
		}
		// Max allowed static map size
		if ($h > 640)
		{
			$h = 640;
		}

		$uri = JURI::getInstance();
		$src = $uri->getScheme() . "://maps.google.com/staticmap?center=$lat,$lon&zoom={$z}&size={$w}x{$h}&maptype=mobile$iconStr";

		$config = JComponentHelper::getParams('com_fabrik');
		$apiKey = $config->get('google_api_key', '');
		$client = $config->get('google_buisness_client_id', '');
		$signature = $config->get('google_buisness_signature', '');

		if ($client !== '')
		{
			if ($signature === '')
			{
				throw new Exception('You have entered a Google Maps Business Client id, but have not supplied a signature value');
			}

			$src .= '&client=' . $client;
			$src .= '&signature=' . $signature;
		}
		elseif ($apiKey !== '')
		{
			$src .= '&key=' . $apiKey;
		}

		$str = '<img src="' . $src . '" alt="static map" />';

		return $str;
	}

	/**
	 * Get the map side bar which shows the list of overlays
	 * (not returning anything at the moment)
	 *
	 * @return  string
	 */
	public function getSidebar()
	{
		$params = $this->getParams();

		if ((int) $params->get('fb_gm_use_overlays', 0) && (int) $params->get('fb_gm_use_overlays_sidebar'))
		{
		}
	}

	/**
	 * Get whether the map side bar should be shown
	 *
	 * @return  bool
	 */
	public function getShowSideBar()
	{
		$params = $this->getParams();

		// KLM layers side bar?
		if ((int) $params->get('fb_gm_use_overlays', 0) === 1 && (int) $params->get('fb_gm_use_overlays_sidebar', 0) > 0)
		{
			return true;
		}

		if ((int) $params->get('fb_gm_group_sidebar', 0) === 1)
		{
			return true;
		}

		return false;
	}

	/**
	 * Get all the list models group templates
	 *
	 * @return  array
	 */
	public function getGroupTemplates()
	{
		$models = $this->getListModels();
		$groupByTemplates = array();

		foreach ($models as $model)
		{
			$id = $model->getTable()->id;
			$templates = $model->groupTemplates;

			foreach ($templates as $k => $v)
			{
				$k = preg_replace('#[^0-9a-zA-Z_]#', '', $k);
				$groupByTemplates[$id][$k] = $v;
			}
		}

		return $groupByTemplates;
	}
}
