<?php
/**
 * Fabrik tag cloud module helper
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class modTagcloudHelper
{
	public static function getCloud(&$params)
	{
		$moduleclass_sfx = $params->get( 'moduleclass_sfx', '' );
		$table = $params->get('table', '');
		$column = $params->get('column', '');
		$filter = $params->get('filter', '');
		$url = $params->get('url', '');

		$splitter = $params->get('splitter', '');
		$alphabetically = $params->get('alphabetically', '');
		$min = (int) $params->get('min', 1);
		$max = (int) $params->get('max', 20);
		$seperator = $params->get('seperator', '');
		$document = JFactory::getDocument();
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($column)->from($table);
		if ($filter != '')
		{
			$query->where($filter);
		}
		$db->setQuery($query);
		$rows = $db->loadColumn();

		$oCloud = new tagCloud($rows, $url, $min, $max, $seperator, $splitter);
		return $oCloud->render($alphabetically);
	}
}


class tagCloud{

	var $rows = array();
	var $countedRows = array();
	var $url = '';
	var $cloud = '';
	var $splitter = ',';
	var $min = '1'; // min number of matches at which tag is shown
	var $maxRecords = 20;

	/**
	 * constructor
	 * @param array $rows
	 * "param string url
	 * @param int $min
	 * @return tagCloud
	 */

	function __construct($rows, $url,  $min = 1, $maxRecords = 20, $seperator = ' :: ', $splitter = ',' )
	{
		$this->rows = $rows;
		$this->url = $url;
		$this->min = $min;
		$this->maxRecords  = $maxRecords;
		$this->splitter = $splitter;
		$this->seperator = ' ' . $seperator . ' ';
		foreach ($this->rows as $row)
		{
			$bits = explode( $this->splitter, $row );
			foreach ($bits as $bit)
			{
				$bit = trim($bit);
				if ($bit != '')
				{
					if (array_key_exists($bit, $this->countedRows))
					{
						$this->countedRows[$bit] = $this->countedRows[$bit] + 1;
					}
					else
					{
						$this->countedRows[$bit] = 1;
					}
				}
			}
		}

	}

	/**
	 * Render
	 *
	 * @param   number  $order  Order type
	 *
	 * @return multitype:string
	 */

	public function render($order = 0)
	{
		arsort($this->countedRows);

		// Rremove any that are less than min
		foreach ($this->countedRows as $key => $val)
		{
			if ($val < $this->min)
			{
				unset($this->countedRows[$key]);
			}
		}
		// Trim to the top records
		$this->countedRows = array_slice( $this->countedRows, 0, $this->maxRecords);
		switch ($order)
		{
			case 0:
			default:
				// Order size - asc
				asort($this->countedRows);
				break;
			case 1:
				// Oder size dec
				arsort($this->countedRows);
				break;
			case 2:
				// Order alphabetically - asc
				ksort($this->countedRows);
				break;
			case 3:
				// Order alphabetically - desc
				krsort($this->countedRows);
				break;
		}

		$cloud = array();
		foreach ($this->countedRows as $bit => $count)
		{
			$url = strstr($this->url, '%s') ? str_replace('%s', $bit, $this->url) : $this->url . $bit;
			$cloud[] = '<span class="icon-tag"></span><a href="' . $url . '"><span class="cloud_' . $count . '">' . $bit . '</span></a>' . $this->seperator;
		}
		return $cloud;
	}
}
