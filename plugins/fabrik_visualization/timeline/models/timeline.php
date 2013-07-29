<?php
/**
 * Fabrik Timeline Viz Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';

/**
 * Renders timeline visualization
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
 * @since       3.0
 */

class FabrikModelTimeline extends FabrikFEModelVisualization
{

	/**
	 * Number of ajax records to return each time
	 *
	 * @var int
	 */
	protected $step = 40;

	/**
	 * Get a slice of the total events.
	 *
	 * @return  string  json encoded event list
	 */

	public function onAjax_getEvents()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$params = $this->getParams();
		$lists = $params->get('timeline_table', array());
		$session = JFactory::getSession();

		$key = 'com_fabrik.timeline.total.' . $input->getInt('visualizationid');
		if (!$session->has($key))
		{
			$totals = $this->getTotal();
			$session->set($key, $totals);
		}
		else
		{
			$totals = $session->get($key);
		}
		$currentList = $input->getInt('currentList', 0);
		$start = $input->getInt('start', 0);

		$res = new stdClass;
		$fabrik = new stdClass;
		$json_data = array ();
		$res->events = array();
		$fabrik->total = array_sum($totals);
		$fabrik->done = 0;
		$c = 0;
		if ($start <= $totals[$currentList])
		{
			$fabrik->next = $start + $this->step;
			$fabrik->currentList = $currentList;

			$c = array_search($currentList, $lists);
			$res->events = $this->jsonEvents($currentList, $totals[$currentList], $start, $c);

			if ($start + $this->step > $totals[$currentList])
			{
				// Move onto next list?
				$nextListId = JArrayHelper::getValue($lists, $c + 1, null);
				$fabrik->nextListId = $nextListId;
				if (is_null($nextListId))
				{
					// No more lists to search
					$this->endAjax_getEvents($fabrik);
				}
				else
				{
					$c = array_search($nextListId, $lists);
					$res->events = array_merge($res->events, $this->jsonEvents($nextListId, $totals[$nextListId], 0, $c));
				}
			}
		}
		else
		{
			// Move onto next list?
			$nextListId = JArrayHelper::getValue($lists, $c + 1, null);
			$fabrik->nextListId = $nextListId;
			if (is_null($nextListId))
			{
				// No more lists to search
				$this->endAjax_getEvents($fabrik);
			}
			else
			{
				$fabrik->next = 0;
				$fabrik->currentList = $nextListId;
				$c = array_search($nextListId, $lists);
				$fabrik->nextC = $c;
				$res->events = array_merge($res->events, $this->jsonEvents($nextListId, $totals[$nextListId], 0, $c));
			}
		}

		$res->dateTimeFormat = 'ISO8601';

		$json_data = array (
				/*
				 * Timeline attributes
				 * 'wiki-url'=>'http://simile.mit.edu/shelf',
				 * 'wiki-section'=>'Simile Cubism Timeline',
				 * 'dateTimeFormat'=>'Gregorian', //JSON!
				 * Event attributes
				 */
				'events' => $res
		);
		$return = new stdClass;
		$return->timeline = $json_data;
		$return->fabrik = $fabrik;

		echo json_encode($return);
	}

	/**
	 * End the ajax get events
	 *
	 * @param   object  &$res  return object
	 *
	 * @return  void
	 */

	protected function endAjax_getEvents(&$res)
	{
		$this->clearSession();
		$res->done = 1;
	}

	/**
	 * Get JSON events
	 *
	 * @param   int  $listId  list id
	 * @param   int  $total   total list record count
	 * @param   int  $start   where to start from
	 * @param   int  $c       list order in timeline params
	 *
	 * @return  array of events
	 */

	protected function jsonEvents($listId, $total, $start, $c)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$params = $this->getParams();
		$document = JFactory::getDocument();
		$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));
		$w = new FabrikWorker;
		jimport('string.normalise');
		$templates = (array) $params->get('timeline_detailtemplate', array());
		$startdates = (array) $params->get('timeline_startdate', array());
		$enddates = (array) $params->get('timeline_enddate', array());
		$labels = (array) $params->get('timeline_label', array());
		$colours = (array) $params->get('timeline_colour', array());
		$textColours = (array) $params->get('timeline_text_color', array());
		$classNames = (array) $params->get('timeline_class', array());
		$evals = (array) $params->get('eval_template', array());

		$template = JArrayHelper::getValue($templates, $c);
		$colour = JArrayHelper::getValue($colours, $c);
		$startdate = JArrayHelper::getValue($startdates, $c);
		$enddate = JArrayHelper::getValue($enddates, $c);
		$title = JArrayHelper::getValue($labels, $c);
		$textColour = JArrayHelper::getValue($textColours, $c);
		$className = JArrayHelper::getValue($classNames, $c);
		$eval = JArrayHelper::getValue($evals, $c);

		$listModel = JModel::getInstance('List', 'FabrikFEModel');
		$listModel->setId($listId);

		$eventdata = array();
		$input->set('limit' . $listId, $this->step);
		$input->set('limitstart' . $listId, $start);
		$listModel->setLimits($start, $this->step);

		if ($listModel->canView() || $listModel->canEdit())
		{
			$data = $listModel->getData();
			$elements = $listModel->getElements();
			$enddate2 = $enddate;
			$startdate2 = $startdate;
			$endKey = FabrikString::safeColName($enddate2);
			$startKey = FabrikString::safeColName($startdate2);
			if (!array_key_exists($endKey, $elements))
			{
				$endKey = $startKey;
				$enddate2 = $startdate2;
			}
			$endElement = $elements[$endKey];

			if (!array_key_exists($startKey, $elements))
			{
				JError::raiseError(500, $startdate2 . " not found in the list, is it published?");
			}
			$startElement = $elements[$startKey];
			$endParams = $endElement->getParams();
			$startParams = $startElement->getParams();

			foreach ($data as $group)
			{
				if (is_array($group))
				{
					foreach ($group as $row)
					{
						$event = new stdClass;
						$html = $w->parseMessageForPlaceHolder($template, JArrayHelper::fromObject($row), false, true);
						if ($eval)
						{
							$html = eval($html);
						}
						$event->description = $html;
						$event->start = array_key_exists($startdate . '_raw', $row) ? $row->{$startdate . '_raw'} : $row->$startdate;
						$event->end = $event->start;
						if (trim($enddate) !== '')
						{
							$end = array_key_exists($enddate . '_raw', $row) ? $row->{$enddate . '_raw'} : @$row->$enddate;
							$event->end = ($end >= $event->start) ? $end : '';

							$sDate = JFactory::getDate($event->end);
							$sDate->setTimezone($timeZone);
							$event->end = $sDate->toISO8601(true);
							$bits = explode('+', $event->end);
							$event->end = $bits[0] . '+00:00';
						}
						$sDate = JFactory::getDate($event->start);
						$sDate->setTimezone($timeZone);
						$event->start = $sDate->toISO8601(true);
						$bits = explode('+', $event->start);
						$event->start = $bits[0] . '+00:00';

						if (isset($row->$title))
						{
							$event->title = @$row->$title;
						}
						else
						{
							$event->title = $w->parseMessageForPlaceHolder($title, $row);
						}
						$event->title = strip_tags($event->title);
						$url = $this->getLinkURL($listModel, $row, $c);
						$event->link = ($listModel->getOutPutFormat() == 'json') ? '#' : $url;
						$event->image = '';
						$event->color = $colour;
						$event->textColor = $textColour;
						$event->classname = isset($row->$className) ? $row->$className : '';
						$event->classname = strip_tags($event->classname);
						$event->classname = $this->toVariable($event->classname);
						if ($event->start !== '' && !is_null($event->start))
						{
							if ($event->end == $event->start)
							{
								$event->end = '';
							}
							$eventdata[] = $event;
						}
					}
				}
			}
			// $eventdata['query'] = $listModel->mainQuery;
		}
		else
		{
			throw new RuntimeException('Timeline: no access to list', 500);
		}
		return $eventdata;
	}

	/**
	 * Get total number of Events
	 *
	 * @return  array of ints keyed on list id
	 */

	protected function getTotal()
	{
		$params = $this->getParams();
		$lists = $params->get('timeline_table', array());
		$totals = array();
		foreach ($lists as $listid)
		{
			$listModel = JModel::getInstance('List', 'FabrikFEModel');
			$listModel->setId($listid);
			$totals[$listid] = $listModel->getTotalRecords();
		}
		return $totals;
	}

	/**
	 * Clear the session, ensures event loading starts from the beginning
	 *
	 * @return  void
	 */

	protected function clearSession()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$session = JFactory::getSession();
		$key = 'com_fabrik.timeline.total.' . $input->getInt('visualizationid');
		$session->clear($key);
	}

	/**
	 * Internally render the plugin, and add required script declarations
	 * to the document
	 *
	 * @return  string  js ini
	 */

	public function render()
	{
		$app = JFactory::getApplication();
		$params = $this->getParams();
		$document = JFactory::getDocument();
		$this->clearSession();
		$w = new FabrikWorker;
		jimport('string.normalise');

		// The similie jQuery autodetect and load code is broken as it tests for $ (for which mootools gives a false positive) so include
		$document->addScript('http://code.jquery.com/jquery-1.9.1.min.js');
		$document->addScript('http://api.simile-widgets.org/timeline/2.3.1/timeline-api.js?bundle=true');
		$c = 0;
		$templates = (array) $params->get('timeline_detailtemplate', array());
		$startdates = (array) $params->get('timeline_startdate', array());
		$enddates = (array) $params->get('timeline_enddate', array());
		$labels = (array) $params->get('timeline_label', array());
		$colours = (array) $params->get('timeline_colour', array());
		$textColours = (array) $params->get('timeline_text_color', array());
		$classNames = (array) $params->get('timeline_class', array());

		$timeZone = new DateTimeZone(JFactory::getConfig()->get('offset'));

		$lists = $params->get('timeline_table', array());
		$eventdata = array();
		$json = new StdClass;
		$json->dateTimeFormat = 'ISO8601';
		$json->events = array();
		$json->bands = $this->getBandInfo();
		$json = json_encode($json);
		$options = new stdClass;
		$options->id = $this->getId();
		$options->listRef = 'list' . $lists[0] . '_' . $app->scope . '_' . $lists[0];
		$options->step = $this->step;
		$options->admin = (bool) $app->isAdmin();
		$options->dateFormat = $params->get('timeline_date_format', '%c');
		$options->orientation = $params->get('timeline_orientation', 'horizontal');
		$options->currentList = $lists[0];
		$options = json_encode($options);
		$ref = $this->getJSRenderContext();
		$str = "var " . $ref . " = new FbVisTimeline($json, $options);";
		$str .= "\n" . "Fabrik.addBlock('" . $ref . "', " . $ref . ");";
		return $str;
		$srcs[] = 'plugins/fabrik_visualization/timeline/timeline.js';
		FabrikHelperHTML::script($srcs, $str);
	}

	/**
	 * Convert string into css class name
	 *
	 * @param   string  $input  string
	 *
	 * @return  string
	 */

	protected function toVariable($input)
	{
		// Should simply be (except theres a bug in J)
		// JStringNormalise::toVariable($event->className);

		$input = trim($input);

		// Remove dashes and underscores, then convert to camel case.
		$input = JStringNormalise::toSpaceSeparated($input);
		$input = JStringNormalise::toCamelCase($input);

		// Remove leading digits.
		$input = preg_replace('#^[\d\.]*#', '', $input);

		// Lowercase the first character.
		$first = JString::substr($input, 0, 1);
		$first = JString::strtolower($first);

		// Replace the first character with the lowercase character.
		$input = JString::substr_replace($input, $first, 0, 1);
		return $input;
	}

	/**
	 * Build the item link
	 *
	 * @param   object  $listModel  list model
	 * @param   object  $row        current row
	 * @param   int     $c          which data set are we in (needed for getting correct params data)
	 *
	 *  @return  string  url
	 */

	protected function getLinkURL($listModel, $row, $c)
	{
		$w = new FabrikWorker;
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$params = $this->getParams();
		$customLink = (array) $params->get('timeline_customlink');
		$customLink = JArrayHelper::getValue($customLink, $c, '');
		if ($customLink !== '')
		{
			$url = @ $w->parseMessageForPlaceHolder($customLink, JArrayHelper::fromObject($row), false, true);
			$url = str_replace('{rowid}', $row->__pk_val, $url);
		}
		else
		{
			$nextview = $listModel->canEdit() ? "form" : "details";
			$table = $listModel->getTable();
			if ($app->isAdmin())
			{
				$url = 'index.php?option=com_fabrik&task=' . $nextview . '.view&formid=' . $table->form_id . '&rowid=' . $row->__pk_val;
			}
			else
			{
				$url = 'index.php?option=com_' . $package . '&view=' . $nextview . '&formid=' . $table->form_id . '&rowid=' . $row->__pk_val
				. '&listid=' . $listModel->getId();
			}
		}
		return $url;
	}

	/**
	 * Get band info
	 *
	 * @return  array  band info
	 */

	protected function getBandInfo()
	{
		$params = $this->getParams();
		$bands = $params->get('timeline_bands');
		$bands = FabrikWorker::JSONtoData($bands, true);
		$intervals = JArrayHelper::getValue($bands, 'timelne_band_interval_unit', array());
		$widths = JArrayHelper::getValue($bands, 'timeline_band_width', array());
		$overviews = JArrayHelper::getValue($bands, 'timeline_band_as_overview', array());
		$bgs = JArrayHelper::getValue($bands, 'timeline_band_background_colour', array());
		$data = array();
		$length = count($intervals);
		$css = array();

		// When $i is 0 this is the top band
		for ($i = 0; $i < $length; $i++)
		{
			$o = new stdClass;
			$o->width = strstr($widths[$i], '%') ? $widths[$i] : $widths[$i] . '%';
			$o->intervalUnit = (int) $intervals[$i];
			$defaultOverview = $i === $length - 1 ? true : false;
			$o->overview = (bool) JArrayHelper::getValue($overviews, $i, $defaultOverview);
			$bg = JArrayHelper::getValue($bgs, $i, '');
			if ($bg !== '')
			{
				$css[] = '.timeline-band-' . $i . ' .timeline-ether-bg {
				background: ' . $bg . ' !important; }';
			}
			$data[] = $o;
		}

		$document = JFactory::getDocument();
		$css = implode("\n", $css);
		$document->addStyleDeclaration($css);
		return $data;
	}

	/**
	 * Set list ids
	 *
	 * @return  void
	 */

	public function setListIds()
	{
		if (!isset($this->listids))
		{
			$params = $this->getParams();
			$this->listids = (array) $params->get('timeline_table', array());
		}
	}
}
