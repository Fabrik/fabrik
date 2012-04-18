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

require_once(JPATH_SITE . '/components/com_fabrik/models/visualization.php');

class fabrikModelTimeline extends FabrikFEModelVisualization { //JModel

	/**
	 * internally render the plugin, and add required script declarations
	 * to the document
	 */

	function render()
	{
		$app = JFactory::getApplication();
		$params = $this->getParams();
		$document = JFactory::getDocument();
		$w = new FabrikWorker();

		//$document->addScript( "http://simile.mit.edu/timeline/api/timeline-api.js");
		$document->addScript('http://static.simile.mit.edu/timeline/api-2.3.0/timeline-api.js?bundle=true');
		$c = 0;
		$templates = (array) $params->get('timeline_detailtemplate', array());
		$startdates = (array) $params->get('timeline_startdate', array());
		$enddates = (array) $params->get('timeline_enddate', array());
		$labels = (array) $params->get('timeline_label', array());
		$colours = (array) $params->get('timeline_colour', array());
		$textColours = (array) $params->get('timeline_text_color', array());
		$classNames = (array) $params->get('timeline_class', array());

		// $$$ rob - dates are already formatted with timezone offset i think
		$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));

		$lists = $params->get('timeline_table', array());
		$eventdata = array();
		foreach ($lists as $listid)
		{
			$template = JArrayHelper::getValue($templates, $c);
			$listModel = JModel::getInstance('List', 'FabrikFEModel');
			$listModel->setId($listid);
			$table = $listModel->getTable();
			$nav = $listModel->getPagination(0, 0, 0);

			$colour = JArrayHelper::getValue($colours, $c);
			$startdate =  JArrayHelper::getValue($startdates, $c);
			$enddate = JArrayHelper::getValue($enddates, $c);
			$title = JArrayHelper::getValue($labels, $c);
			$textColour = JArrayHelper::getValue($textColours, $c);
			$className = JArrayHelper::getValue($classNames, $c);

			$data = $listModel->getData();

			if ($listModel->canView() || $listModel->canEdit())
			{
				$elements = $listModel->getElements();
				$enddate2 = $enddate;
				$startdate2 = $startdate;
				$endKey = FabrikString::safeColName($enddate2);
				$startKey = FabrikString::safeColName($startdate2);
				if (!array_key_exists($endKey, $elements))
				{
					$endKey = $startKey;
					$enddate2 = $startdate2;
					//JError::raiseError(500, $enddate2 . " not found in the table, is it published?");
				}
				$endElement = $elements[$endKey];

				if (!array_key_exists($startKey, $elements))
				{
					JError::raiseError(500, $startdate2 . " not found in the list, is it published?");
				}
				$startElement = $elements[$startKey];
				$endParams = $endElement->getParams();
				$startParams = $startElement->getParams();

				// timeline always shows dates in GMT so lets take a guess if the table view should show the times or not
				// if they don't its a bit of a hack as the timeline will still say its GMT but at least its GMT for a time of 00:00:00
				$tblEndFormat = $endParams->get('date_table_format');
				$endTimeFormat = (strstr($tblEndFormat, '%H') || strstr($tblEndFormat, '%M')) ? true : false;
				$tblStartFormat = $startParams->get('date_table_format');
				$startTimeFormat = (strstr($tblStartFormat, '%H') || strstr($tblStartFormat, '%M')) ? true : false;

				$action = $app->isAdmin() ? "task" : "view";
				$nextview = $listModel->canEdit() ? "form" : "details";

				foreach ($data as $group)
				{
					if (is_array($group))
					{
						foreach ($group as $row)
						{
							$event = new stdClass();
							$html = $w->parseMessageForPlaceHolder($template, JArrayHelper::fromObject($row));
							$event->description = $html;
							$event->start = (array_key_exists($startdate. '_raw', $row) && $tblStartFormat) ? $row->{$startdate. '_raw'} : $row->$startdate;
							if (trim($enddate) !== '')
							{
								$end = (array_key_exists($enddate. '_raw', $row) && $endTimeFormat) ? $row->{$enddate. '_raw'} : @$row->$enddate;
								$event->end = ($end > $event->start) ? $end : '';
								//if we are showing the times we need to re-offset the date as its already been offset in the tbl model
								$endD = ($endTimeFormat) ? JFactory::getDate($event->end, $timeZone) : JFactory::getDate($event->end);
								$event->end = $endD->toISO8601($endTimeFormat);
							}

							$sDate = ($startTimeFormat) ? JFactory::getDate($event->start, $timeZone) : JFactory::getDate($event->start);
							$event->start = $sDate->toISO8601($startTimeFormat);
							$event->title = strip_tags(@$row->$title);
							$event->link = ($listModel->getOutPutFormat() == 'json') ? '#' : JRoute::_('index.php?option=com_fabrik&view=' . $nextview . '&formid=' . $table->form_id . '&rowid=' . $row->__pk_val . '&listid=' . $listModel->getId());
							$event->image = '';
							$event->color = $colour;
							$event->textColor = $textColour;
							$event->classname  =  isset($row->$className) ? $row->$className : '';
							if ($event->start !== '' && !is_null($event->start))
							{
								$eventdata[] = $event;
							}
						}
					}
				}
			}
			$c ++;
		}
		$json = new StdClass();
		$json->dateTimeFormat = 'ISO8601';
		$json->events = $eventdata;
		$json = json_encode($json);
		$str = "var timeline = new FbVisTimeline($json);";
		FabrikHelperHTML::script('plugins/fabrik_visualization/timeline/timeline.js', $str);
	}

	function setListIds()
	{
		if (!isset($this->listids))
		{
			$params = $this->getParams();
			$this->listids = (array) $params->get('timeline_table', array());
		}
	}
}
?>