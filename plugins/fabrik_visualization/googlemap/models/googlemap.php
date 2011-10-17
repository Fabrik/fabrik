<?php

/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'visualization.php');

class fabrikModelGooglemap extends FabrikFEModelVisualization {

	var $txt = null;

	/** @param array of arrays (width, height) keyed on image icon*/
	var $markerSizes = array();

	var $recordCount = 0;

	function getText() {
		return $this->txt;
	}

	/**
	 * build js string to create the map js object
	 * @return string
	 */
	function getJs()
	{
		if (!$this->getRequiredFiltersFound()){
			return '';
		}
		$params = $this->getParams();
		$str = "head.ready(function() {";
		$viz = $this->getVisualization();

		$opts = new stdClass();
		$opts->icons = $this->getJSIcons();
		$opts->polyline = $this->getPolyline();
		$opts->id = $viz->id;
		$opts->zoomlevel = (int)$params->get('fb_gm_zoomlevel');
		$opts->scalecontrol = (bool)$params->get('fb_gm_scalecontrol');
		$opts->maptypecontrol = (bool)$params->get('fb_gm_maptypecontrol');
		$opts->overviewcontrol = (bool)$params->get('fb_gm_overviewcontrol');
		$opts->center = $params->get('fb_gm_center');
		$opts->ajax_refresh = (bool)$params->get('fb_gm_ajax_refresh', false);
		$opts->ajax_refresh_center = $params->get('fb_gm_ajax_refresh_center', 1);
		$opts->maptype = $params->get('fb_gm_maptype');
		$opts->clustering = (bool)$params->get('fb_gm_clustering', '0') == '1';
		$opts->cluster_splits = $params->get('fb_gm_cluster_splits');
		$opts->icon_increment = $params->get('fb_gm_cluster_icon_increment');
		$opts->refresh_rate = $params->get('fb_gm_ajax_refresh_rate');
		$opts->use_cookies = (bool)$params->get('fb_gm_use_cookies');
		$opts->container = $this->getContainerId();
		$opts->polylinewidth = $params->get('fb_gm_polyline_width', array(), '_default', 'array');
		$opts->polylinecolour = $params->get('fb_gm_polyline_colour', array(), '_default', 'array');
		$opts->overlay_urls = $params->get('fb_gm_overlay_urls', array(), '_default', 'array');
		$opts->overlay_labels = $params->get('fb_gm_overlay_labels', array(), '_default', 'array');
		$opts->use_overlays = (int)$params->get('fb_gm_use_overlays', '0');
		$opts->use_overlays_sidebar = $opts->use_overlays && (int)$params->get('fb_gm_use_overlays_sidebar', '0');
		$opts->groupTemplates = $this->getGroupTemplates();
		$opts->zoomStyle = (int)$params->get('fb_gm_zoom_control_style', 0);
		$opts->zoom = $params->get('fb_gm_zoom', 1);
		$opts = json_encode($opts);
		$str .= "fabrikMap{$viz->id} = new FbGoogleMapViz('table_map', $opts)";
		$str .= "\n" . "Fabrik.addBlock('vizualization_{$viz->id}', fabrikMap{$viz->id});";
		$str .= "});\n";
		return $str;
	}

	function setListIds()
	{
		if (!isset($this->listids)) {
			$params = $this->getParams();
			$this->listids = $params->get('googlemap_table', array(), '_default', 'array');
		}
	}

	/**
	 * build a polygon line to join up the markers
	 * @return array of lines each line being an array of points
	 */

	function getPolyline()
	{
		$params = $this->getParams();
		$lines = array();
		$polyelements = $params->get('fb_gm_polyline_element', array(), '_default', 'array');
		$listModels = $this->getlistModels();
		$c = 0;
		foreach ($listModels as $listModel) {
			$k = FabrikString::safeColName(FabrikString::rtrimword( $polyelements[$c], '[]'));
			if ($k == '``') {
				$c++;
				continue;
			}
			$mapsElements = $listModel->getElementsOfType('fabrikgooglemap');

			$coordColumn = $mapsElements[0]->getFullName(false, false, false);
			$table = $listModel->getTable();
			$where = $listModel->_buildQueryWhere();
			$join = $tableModel->_buildQueryJoin();
			$db = $listModel->getDb();
			$db->setQuery("SELECT $coordColumn AS coords FROM $table->db_table_name $join $where ORDER BY $k");
			$data = $db->loadObjectList();
			$points = array();
			if (is_null($data)) {
				JError::raiseNotice(500, $db->getErrorMsg());
			} else {
				foreach ($data as $d) {
					$d = $this->getCordsFromData($d->coords);
					if ($d == array(0,0)) {
						continue;//dont show icons with no data
					}
					$points[] = $d;
				}
			}
			$lines[] = $points;
			$c ++;
		}
		return $lines;
	}

	private function getCordsFromData($d)
	{
		$v = trim($d);
		$v = FabrikString::ltrimword($v, "(");
		if (strstr($v, ",")) {
			if(strstr($v, ":")) {
				$ar = explode(":", $v);
				array_pop( $ar);
				$v = explode(",", $ar[0]);
			} else {
				$v = explode(",", $v);
			}
			$v[1] = FabrikString::rtrimword($v[1], ")");
		} else {

			$v = array(0,0);
		}
		return $v;
	}

	function getJSIcons()
	{
		$icons 			= array();
		$w 					= new FabrikWorker();
		$params 		=& $this->getParams();
		$templates 	= $params->get('fb_gm_detailtemplate', array(), '_default', 'array');
		$listids = $params->get('googlemap_table', array(), '_default', 'array');
		//images for file system
		$aIconImgs	= $params->get('fb_gm_iconimage', array(), '_default', 'array');
		//image from marker data
		$markerImages = $params->get('fb_gm_iconimage2', array(), '_default', 'array');
		//specifed letter
		$letters = $params->get('fb_gm_icon_letter', array(), '_default', 'array');

		$aFirstIcons = $params->get('fb_gm_first_iconimage', array(), '_default', 'array');
		$aLastIcons = $params->get('fb_gm_last_iconimage', array(), '_default', 'array');

		$titleElements = (array)$params->get('fb_gm_title_element', array(), '_default', 'arrray');
		$c = 0;
		$this->recordCount = 0;

		$maxMarkers = $params->get('fb_gm_markermax', 0);
		if (count($listids) == 1) {
			$recLimit = $maxMarkers;
		} else {
			$recLimit = 0;
		}
		$limitMessageShown = false;
		$limitMessage = $params->get('fb_gm_markermax_message');
		$groupedIcons = array();
		$k = 0;
		foreach ($listids as $listid) {
			$template = JArrayHelper::getValue($templates, $c, '');
			$listModel = $this->getlistModel($listid);
			$table = $listModel->getTable();
			$mapsElements = $listModel->getElementsOfType('googlemap');

			if (empty($mapsElements)) {
				JError::raiseError(500, JText::_('No google map element present in this table'));
				continue;
			}

			$coordColumn = $mapsElements[0]->getFullName(false, true, false) . "_raw";

			//are we using random start location for icons?
			$listModel->_randomRecords = ($params->get('fb_gm_random_marker') == 1 && $recLimit != 0) ? true : false;

			//used in table model setLimits
			JRequest::setVar('limit'.$listid, $recLimit);
			$listModel->setLimits();

			$nav	=& $listModel->getPagination(0, 0, $recLimit);
			$data = $listModel->getData();
			$this->txt = array();
			$k = 0;

			foreach ($data as $groupKey => $group) {
				foreach ($group as $row) {

					$customimagefound = false;
					if ($k == 0) {
						$iconImg = JArrayHelper::getValue($aFirstIcons, $c, '');
					} else {
						$iconImg = JArrayHelper::getValue($aIconImgs, $c, '');
					}
					$v = $this->getCordsFromData($row->$coordColumn);
					if ($v == array(0,0)) {
						continue;//dont show icons with no data
					}
					$rowdata = JArrayHelper::fromObject($row);
					$html = $w->parseMessageForPlaceHolder($template, $rowdata);

					$titleElement = JArrayHelper::getValue($titleElements, $c, '');
					$title = $titleElement == '' ? '' : strip_tags($row->$titleElement);
					// $$$ hugh - if they provided a template, lets assume they will handle the link themselves.
					// http://fabrikar.com/forums/showthread.php?p=41550#post41550
					// $$$ hugh - at some point the fabrik_view / fabrik_edit links became optional
					if (empty($html) && (array_key_exists('fabrik_view', $rowdata) || array_key_exists('fabrik_edit', $rowdata))) {
						$html .= "<br />";
						// use edit link by preference
						if (array_key_exists('fabrik_edit', $rowdata)) {
							$html .= $rowdata['fabrik_edit'];
						}
						else {
							$html .= $rowdata['fabrik_view'];
						}
					}
					$html = str_replace(array("\n\r" ), "<br />", $html);
					$html = str_replace(array("\n", "\r" ), "<br />", $html);
					$html = str_replace("'", '"', $html);
					$this->txt[] = $html;

					if ($iconImg == '') {
						$iconImg = JArrayHelper::getValue($markerImages, $c, '');

						if ($iconImg != '') {
							$iconImg = JArrayHelper::getValue($rowdata, $iconImg, '');

							//get the src
							preg_match('/src=["|\'](.*?)["|\']/', $iconImg, $matches);
							if (array_key_exists(1, $matches)) {
								$iconImg = $matches[1];
								//check file exists
								$path = str_replace(COM_FABRIK_LIVESITE, '', $iconImg);
								if (JFile::exists(JPATH_BASE.$path)) {
									$customimagefound = true;
								}
							}
						}

						if ($iconImg != '') {
							list($width, $height) = $this->markerSize($iconImg);

						} else {
							//standard google map icon size
							$width = 20;
							$height = 34;
						}
					} else {
						//standard google map icon size
						list($width, $height) = $this->markerSize(JPATH_SITE.DS.'images'.DS.$iconImg);
					}
					//just for moosehunt!
					$radomize = ($_SERVER['HTTP_HOST'] == 'moosehunt.mobi') ? true :false;
					$groupKey = strip_tags($groupKey);
					if (array_key_exists($v[0].$v[1], $icons)) {
						$existingIcon = $icons[$v[0].$v[1]];
						if ($existingIcon['groupkey'] == $groupKey) {
							// $$$ hugh - this inserts label between multiple record $html, but not at the top.
							// If they want to insert label, they can do it themselves in the template.
							// $icons[$v[0].$v[1]][2] = $icons[$v[0].$v[1]][2] . "<h6>$table->label</h6>" . $html;
							$icons[$v[0].$v[1]][2] = $icons[$v[0].$v[1]][2] . "<br />" . $html;
							if ($customimagefound) {
								//$icons[$v[0].$v[1]][3] =  "<br />" . $iconImg;
								$icons[$v[0].$v[1]][3] =  $iconImg;
							}
						} else {
								$groupedIcons[] = array($v[0], $v[1], $html, $iconImg, $width,
						$height, 'groupkey'=> $groupKey, 'listid' => $listid, 'title'=>$title);
						}
					} else {
						//default icon - lets see if we need to use a letterd icon instead
						if (JArrayHelper::getValue($letters, $c, '') != '') {
							$iconImg = 'http://www.google.com/mapfiles/marker'.strtoupper($letters[$c]).'.png';
						}
						$icons[$v[0].$v[1]] = array($v[0], $v[1], $html, $iconImg, $width,
						$height, 'groupkey'=> $groupKey, 'listid' => $listid, 'title'=>$title);
					}
					$this->recordCount++;
					$k++;
				}

			}
			//replace last icon?
			$iconImg = JArrayHelper::getValue($aLastIcons, $c, '');
			if ($iconImg != '') {
				list($width, $height) = $this->markerSize(JPATH_SITE.DS.'images'.DS.$iconImg);
				$icons[$v[0].$v[1]][3] = $iconImg;
				$icons[$v[0].$v[1]][4] = $width;
				$icons[$v[0].$v[1]][5] = $height;
			}
			$c ++;
		}
		$icons = array_values($icons); //replace coord keys with numeric keys
		$icons = array_merge($icons, $groupedIcons);
		if ($maxMarkers != 0 && $maxMarkers < count($icons)) {
			$icons = array_slice($icons, -$maxMarkers);
		}
		$limitMessageShown = !($k >= $recLimit);
		if (!$limitMessageShown && $recLimit !== 0 && $limitMessage != '') {
			$app->enqueueMessage($limitMessage);
		}
		FabrikHelperHTML::debug($icons, 'map');
		return $icons;
	}

	/**
	 * get the width and height for an icon image -
	 * @param string icon image path
	 * @return array(width, height)
	 */

	private function markerSize($iconImg)
	{
		if (!array_key_exists($iconImg, $this->markerSizes)) {
			@$size = getimagesize($iconImg);
			$width = is_array($size) ? $size[0] : 25;
			$height = is_array($size) ? $size[1] : 25;
			//ensure icons arent too big (25 is max)
			$scale = min(25 / $width, 25 / $height);
			/* If the image is larger than the max shrink it*/
			if ($scale < 1) {
				$width = floor($scale * $width);
				$height = floor($scale * $height);
			}
			$this->markerSizes[$iconImg] = array($width, $height);
		}
		return $this->markerSizes[$iconImg];
	}

	function ajax_getMarkers()
	{
		echo json_encode($this->getJSIcons());
	}

	function render()
	{
	}

	/**
	 * get a static map
	 * @return string html image
	 */

	function getStaticMap()
	{
		$params = $this->getParams();
		$icons = $this->getJSIcons();
		$iconstr = '';
		$lat = 0;
		$lon = 0;
		if (!empty($icons)) {
			$first = $icons[0];
			$bounds = array('lat'=>array($first[0], $first[0]), 'lon'=>array($first[1], $first[1]));
			$c = 1;

			foreach ($icons as $i) {
				if ($c >= 50) {
					break;
				}
				$iconstr .= "&markers=" .trim($i[0]).",".trim($i[1]);
				if ($i[0] < $bounds['lat'][0]) $bounds['lat'][0] = $i[0];
				if ($i[0] > $bounds['lat'][1]) $bounds['lat'][1] = $i[0];
				if ($i[1] < $bounds['lon'][0]) $bounds['lon'][0] = $i[1];
				if ($i[1] > $bounds['lon'][1]) $bounds['lon'][1] = $i[1];
				$c ++;
			}
			if ($params->get('fb_gm_center')  != 'middle') {
				$i = array_pop($icons);
				$lat = $i[0];
				$lon = $i[1];
			} else {
				$lat = ($bounds['lat'][1] + $bounds['lat'][0]) / 2;
				$lon = ($bounds['lon'][1] + $bounds['lon'][0]) / 2;
			}
		}
		$w = $params->get('fb_gm_mapwidth');
		$h = $params->get('fb_gm_mapheight');
		$z = $params->get('fb_gm_zoomlevel');

		if($w > 640) $w = 640;//max allowed static map size
		if($w > 640) $h = 640;
		$src = "http://maps.google.com/staticmap?center=$lat,$lon&zoom={$z}&size={$w}x{$h}&maptype=mobile$iconstr";
		$str = "<img src=\"$src\" alt=\"static map\" />";
		return $str;
	}

	function getSidebar() {
		$params = $this->getParams();
		if ((int)$params->get('fb_gm_use_overlays', 0) && (int)$params->get('fb_gm_use_overlays_sidebar')) {

		}
	}

	public function getShowSideBar()
	{
		$params = $this->getParams();
		// KLM layers side bar?
		if ((int)$params->get('fb_gm_use_overlays', 0) === 1 &&  (int)$params->get('fb_gm_use_overlays_sidebar', 0) > 0) {
			return true;
		}
		if ((int)$params->get('fb_gm_group_sidebar', 0) == 1){
			return true;
		}
		return false;
	}

	public function getGroupTemplates()
	{
		$models = $this->getListModels();
		$groupbyTemplates = array();
		foreach ($models as $model) {
			$id = $model->getTable()->id;
			$tmpls = $model->grouptemplates;
			foreach ($tmpls as $k => $v) {
				$k = preg_replace('#[^0-9a-zA-Z_]#', '', $k);
				$groupbyTemplates[$id][$k] = $v;
			}
		}
		return $groupbyTemplates;
	}
}

?>
