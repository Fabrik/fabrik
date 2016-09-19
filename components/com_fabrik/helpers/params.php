<?php
/**
 * Extend J Params
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/*
 * DEPRECIATED?
could well be that we don't use this now???????????
 */
jimport('joomla.html.parameter');

/**
 * Extend J Params
 *
 * @package  Fabrik
 * @since    3.0
 */

class FabrikParams extends JForm
{
	/** @var bool duplicate-able param (if true add []" to end of element name)*/
	protected $duplicate = false;

	/** used by form plugins - to set id in name of radio buttons **/
	protected $counter_override = null;

	/**
	 * constructor
	 *
	 * @param   array   $data  data
	 * @param   string  $path  path
	 */

	public function __construct($data, $path = '')
	{
		$this->_identifier = str_replace("\\", "-", str_replace(".xml", "", str_replace(JPATH_SITE, '', $path)));
		$this->_identifier = str_replace('/', '-', $this->_identifier);
		parent::__construct($data, $path);
	}

	/**
	 * Get the names of all the parameters in the object
	 *
	 * @return array parameter names
	 */

	private function _getParamNames()
	{
		$p = array();
		$default = (object) $this->_xml['_default'];

		if (empty($default))
		{
			return $p;
		}

		foreach ($default->children() as $node)
		{
			$p[] = $node->attributes('name');
		}

		return $p;
	}

	/**
	 * overwrite core get function so we can force setting to array if needed
	 *
	 * @param   string  $key           key
	 * @param   string  $default       default
	 * @param   string  $group         group
	 * @param   string  $outputFormat  (string or array)
	 * @param   int     $counter       not used i think
	 *
	 * @return mixed - string or array
	 */

	public function get($key, $default = '', $group = '_default', $outputFormat = 'string', $counter = null)
	{
		$return = parent::get($key, $default);

		if ($outputFormat == 'array')
		{
			$return = $return == '' ? array() : (array) $return;
		}

		return $return;
	}

	/**
	 * get a groups parameters
	 *
	 * @param   string  $name          name
	 * @param   string  $group         name
	 * @param   string  $outputFormat  output format
	 * @param   int     $counter       repeat counter
	 *
	 * @return string|multitype:
	 */

	public function getParams($name = 'params', $group = '_default', $ouputformat = 'string', $counter = null)
	{
		if (!isset($this->_xml[$group]))
		{
			return false;
		}

		$results = array();

		foreach ($this->_xml[$group]->children() as $param)
		{
			$results[] = $this->getParam($param, $name, $group, $ouputformat, $counter);
		}

		return $results;
	}

	/**
	 * get a groups parameters names
	 *
	 * @param   string  $name   name
	 * @param   string  $group  name
	 *
	 * @return string|multitype:
	 */

	public function getParamsNames($name = 'params', $group = '_default')
	{
		if (!isset($this->_xml[$group]))
		{
			return false;
		}

		$results = array();

		foreach ($this->_xml[$group]->children() as $node)
		{
			$results[] = $node->attributes('name');
		}

		return $results;
	}

	/**
	 * Render a parameter type
	 *
	 * @param   object  &$node         A param tag node
	 * @param   string  $control_name  The control name
	 * @param   string  $group         parameter group
	 * @param   string  $outputFormat  output format
	 * @param   mixed   $counter       repeat group counter??? /how about repeating plugins is this the same??
	 *
	 * @return  array Any array of the label, the form element and the tooltip
	 */

	public function getParam(&$node, $control_name = 'params', $group = '_default', $outputFormat = 'string', $counter = null)
	{
		// Get the type of the parameter
		$type = $node->attributes('type');

		// Remove any occurrence of a mos_ prefix
		$type = str_replace('mos_', '', $type);
		$element = $this->loadElement($type);

		// Error happened
		if ($element === false)
		{
			$result = array();
			$result[0] = $node->attributes('name');
			$result[1] = FText::_('COM_FABRIK_ELEMENT_NOT_DEFINED_FOR_TYPE') . ' = ' . $type;
			$result[5] = $result[0];

			return $result;
		}

		// Get value
		if ($outputFormat == 'array' && !is_null($counter))
		{
			$nodeName = str_replace("[]", "", $node->attributes('name'));
		}
		else
		{
			$nodeName = $node->attributes('name');
		}

		$value = $this->get($nodeName, $node->attributes('default'), $group, $outputFormat, $counter);

		if ($outputFormat == 'array' && !is_null($counter))
		{
			$value = FArrayHelper::getValue($value, $counter, '');
		}

		// Value must be a string
		$element->array_counter = $counter;
		$result = $element->render($node, $value, $control_name);
		$reqParamName = $result[5];

		// Duplicate property set in view pages
		if ($this->duplicate)
		{
			if ($type == 'radio')
			{
				// Otherwise only a single entry is recorded no matter how many duplicates we make
				if ($counter == 0 && isset($this->counter_override))
				{
					$counter = $this->counter_override;
				}

				$replacewith = "[$reqParamName][$counter][]";
			}
			else
			{
				$replacewith = "[$reqParamName][]";
			}

			$result[1] = str_replace("[$reqParamName]", $replacewith, $result[1]);
		}

		return $result;
	}

	/**
	 * Render (NOTE when rendering admin settings I *think* the repeat group is set with $this->counter_override)
	 *
	 * @param   string  $name             The name of the control, or the default text area if a setup file is not found
	 * @param   string  $group            Group
	 * @param   bool    $write            Write out or return
	 * @param   int     $repeatSingleVal  If set and group is repeat only return int row from rendered params
	 *
	 * @return  string	HTML
	 *
	 * @since	1.5
	 */

	public function render($name = 'params', $group = '_default', $write = true, $repeatSingleVal = null)
	{
		$return = '';
		$this->_group = $group;

		// $$$rob experimental again
		/**
		 * Problem - when rendering plugin params - e.g. calendar vis - params like the table drop down
		 * are repeated n times. I think the best way to deal with this is to get the data recorded for
		 * the viz and update this objects _xml array duplicate the relevant JSimpleXMLElement Objects
		 * for the required number of table drop downs
		 */

		$repeat = false;
		$repeatControls = true;
		$repeatMin = 0;

		if (is_array($this->_xml))
		{
			if (array_key_exists($group, $this->_xml))
			{
				$repeat = $this->_xml[$group]->attributes('repeat');
				$repeatMin = (int) $this->_xml[$group]->attributes('repeatmin');
				$repeatControls = $this->_xml[$group]->attributes('repeatcontrols');
			}
		}

		if ($repeat)
		{
			// Get the name of the first element in the group
			$children = $this->_xml[$group]->children();

			if (empty($children))
			{
				$firstElName = '';
				$allParamData = '';
				$value = '';
			}
			else
			{
				$firstElName = str_replace("[]", "", $children[0]->attributes('name'));
				$allParamData = $this->_registry['_default']['data'];
				$value = $this->get($firstElName, array(), $group, 'array');
			}

			$c = 0;

			// Limit the number of groups of repeated params written out
			if (!is_null($repeatSingleVal) && is_int($repeatSingleVal))
			{
				$total = $repeatSingleVal + 1;
				$start = $repeatSingleVal;
			}
			else
			{
				$total = count($value);
				$start = 0;
			}

			$return .= '<div id="container' . $this->_identifier . '">';

			// Add in the 'add' button to duplicate the group
			// Only show for first added group
			if ($repeatControls && $repeatSingleVal == 0)
			{
				$return .= "<a href='#' class='addButton'>" . FText::_('COM_FABRIK_ADD') . "</a>";
			}

			for ($x = $start; $x < $total; $x++)
			{
				// Call render for the number of time the group is repeated

				$return .= '<div class="repeatGroup" id="' . $this->_identifier . 'group-' . $x . '">';
				$params = $this->getParams($name, $group, 'array', $x);
				$html = array();
				$html[] = '<table width="100%" class="paramlist admintable" cellspacing="1">';

				if ($description = $this->_xml[$group]->attributes('description'))
				{
					// Add the params description to the display
					$desc = FText::_($description);
					$html[] = '<tr><td class="paramlist_description" colspan="2">' . $desc . '</td></tr>';
				}

				foreach ($params as $param)
				{
					$html[] = '<tr>';

					if ($param[0])
					{
						$html[] = '<td width="40%" class="paramlist_key"><span class="editlinktip">' . $param[0] . '</span></td>';
						$html[] = '<td class="paramlist_value">' . $param[1] . '</td>';
					}
					else
					{
						$html[] = '<td class="paramlist_value" colspan="2">' . $param[1] . '</td>';
					}

					$html[] = '</tr>';
				}

				if (count($params) < 1)
				{
					$html[] = "<tr><td colspan=\"2\"><i>" . FText::_('COM_FABRIK_THERE_ARE_NO_PARAMETERS_FOR_THIS_ITEM') . "</i></td></tr>";
				}

				$html[] = '</table>';

				if ($repeatControls)
				{
					$html[] = "<a href='#' class=\"removeButton delete\">" . FText::_('COM_FABRIK_DELETE') . "</a>";
				}

				$return .= implode("\n", $html);
				$c++;
				$return .= "</div>";
			}

			$return .= "</div>";
		}
		else
		{
			$return .= parent::render($name, $group);
		}

		if ($repeat && $repeatControls && ($repeatSingleVal == null || $repeatSingleVal == 0))
		{
			FabrikHelperHTML::script('components/com_fabrik/libs/params.js');

			// Watch add and remove buttons
			$document = JFactory::getDocument();
			$script = "window.addEvent('fabrik.loaded', function() {
			 new RepeatParams('container{$this->_identifier}', {repeatMin:$repeatMin});
	});";
			FabrikHelperHTML::addScriptDeclaration($script);
		}

		if ($write)
		{
			echo $return;
		}
		else
		{
			return $return;
		}
	}

	/**
	 * get the child nodes
	 *
	 * @param   string  $namespace  namespace
	 *
	 * @return  xml nodes
	 *
	 * @since 3.0
	 */

	public function getChildren($namespace = '_default')
	{
		return $this->_xml[$namespace]->children();
	}
}
