<?php
/**
 * Plugin element to enable users to make notes on a give record
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.notes
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;

require_once JPATH_SITE . '/plugins/fabrik_element/databasejoin/databasejoin.php';

/**
 * Plugin element to enable users to make notes on a give record
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.notes
 * @since       3.0
 */
class PlgFabrik_ElementNotes extends PlgFabrik_ElementDatabasejoin
{
	/**
	 * Last row id to be inserted via ajax call
	 *
	 * @var int
	 */
	protected $loadRow = null;

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->rowid = (int) $this->getFormModel()->getRowId();
		$opts->joinPkVal = (int) $this->getJoinedGroupPkVal($repeatCounter);
		$opts->primaryKey = $this->getGroupModel()->isJoin() ? (int) $this->getJoinedGroupPkVal($repeatCounter) : $opts->rowid;
		$opts->id = $this->id;
		$opts->j3 = FabrikWorker::j3();

		return array('FbNotes', $id, $opts);
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$tmp = $this->_getOptions($data, $repeatCounter, true);
		$layout = $this->getLayout('form');
		$layoutData = new stdClass;
		$layoutData->id = $this->getHTMLId($repeatCounter);
		$layoutData->labels = array();
		$layoutData->name = $this->getHTMLName($repeatCounter);
		$layoutData->fieldType = $params->get('fieldType', 'textarea');
		$layoutData->editable = $this->isEditable();
		$layoutData->canUse = $this->canUse();
		$layoutData->rowid = $this->getFormModel()->getRowId();
		$layoutData->primaryKey = $this->getGroupModel()->isJoin() ? $this->getJoinedGroupPkVal($repeatCounter) : $layoutData->rowid;
		$layoutData->rows = $tmp;
		$layoutData->model = $this;
		$layoutData->labels = array();
		/*
		foreach ($tmp as $row)
		{
			$layoutData->labels[] = $this->getDisplayLabel($row);
		}
		*/

		return $layout->render($layoutData);
	}

	/**
	 * Get display label
	 *
	 * @param   object  $row  Row
	 *
	 * @return string
	 */
	public function getDisplayLabel($row)
	{
		$params = $this->getParams();
		$layout = $this->getLayout('form-note');
		$layoutData = new stdClass;
		$layoutData->headers = 0;

		if ($params->get('showuser', true))
		{
			$layoutData->showUser = true;
			$layoutData->user = $this->getUserNameLinked($row);
			$layoutData->headers++;
		}

		if ($params->get('notes_date', '') !== '')
		{
			$layoutData->showDate = true;
			$layoutData->date = $this->getFormattedDate($row);
			$layoutData->headers++;
		}

		$layoutData->note = $row;

		return $layout->render($layoutData);
	}

	/**
	 * Get linked user name (only for com_uddeim apparently!?)
	 *
	 * @param   object  $row  Row
	 *
	 * @return string
	 */
	public function getUserNameLinked($row)
	{
		$ret = '';

		if (isset($row->username))
		{
			$params = $this->getParams();
			$userid_url = $params->get('userid_url', '');

			if (!empty($userid_url))
			{
				$userid_url = sprintf($userid_url, $row->userid);
				$ret = '<a href="' . $userid_url . '">' . $row->username . '</a>';
			}
			else
			{
				$ret = $row->username;
			}
		}

		return $ret;
	}

	/**
	 * Get formatted date
	 *
	 * @param   object  $row  Row
	 *
	 * @return string
	 */
	public function getFormattedDate($row)
	{
		$ret = '';

		if (isset($row->date_time) && !empty($row->date_time))
		{
			$params = $this->getParams();
			$notes_date_format = $params->get('notes_date_format', '');

			if (!empty($notes_date_format))
			{
				$ret = date($notes_date_format, strtotime($row->date_time));
			}
			else
			{
				$ret = $row->date_time;
			}
		}

		return $ret;
	}

	/**
	 * Has component. [Really shouldn't be here but in a helper].
	 *
	 * @param   string  $c  Component name (com_foo)
	 *
	 * @return  bool
	 */
	protected function hasComponent($c)
	{
		if (!isset($this->components))
		{
			$this->components = array();
		}

		if (!array_key_exists($c, $this->components))
		{
			$query = $this->_db->getQuery(true);
			$query->select('COUNT(id)')->from('#__extensions')->where('name = ' . $this->_db->q($c));
			$this->_db->seQuery($query);
			$found = $this->db->loadResult();
			$this->components[$c] = $found;
		}

		return $this->components[$c];
	}

	/**
	 * Create the where part for the query that selects the list options
	 *
	 * @param   array                $data            Current row data to use in placeholder replacements
	 * @param   bool                 $incWhere        Should the additional user defined WHERE statement be included
	 * @param   string               $thisTableAlias  Db table alias
	 * @param   array                $opts            Options
	 * @param   JDatabaseQuery|bool  $query           Append where to JDatabaseQuery object or return string (false)
	 *
	 * @return string|JDatabaseQuery
	 */
	protected function buildQueryWhere($data = array(), $incWhere = true, $thisTableAlias = null, $opts = array(), $query = false)
	{
		$params = $this->getParams();
		$db = $this->getDb();
		$field = $params->get('notes_where_element');
		$value = $params->get('notes_where_value');
		$fk = $params->get('join_fk_column', '');
		$rowId = $this->getFormModel()->getRowId();
		$repeatCounter = isset($opts['repeatCounter']) ? $opts['repeatCounter'] : 0;
		$primaryKey = $this->getGroupModel()->isJoin() ? $this->getJoinedGroupPkVal($repeatCounter) : $rowId;
		$where = array();

		// Jaanus: here we can choose whether WHERE has to have single or (if field is the same as FK then only) custom (single or multiple) criteria,
		if ($value != '')
		{
			if ($field != '' && $field !== $fk)
			{
				$where[] = $db->qn($field) . ' = ' . $db->q($value);
			}
			else
			{
				$where[] = $value;
			}
		}
		// Jaanus: when we choose WHERE field to be the same as FK then WHERE criteria is automatically FK = rowid, custom criteria(s) above may be added
		if ($fk !== '' && $field === $fk && $primaryKey != '')
		{
			$where[] = $db->qn($fk) . ' = ' . $primaryKey;
		}

		if ($this->loadRow != '')
		{
			$pk = $db->qn($this->getJoin()->table_join_alias . '.' . $params->get('join_key_column'));
			$where[] = $pk . ' = ' . $this->loadRow;
		}

		/**
		 * $$$ hugh if where is still empty (most likely if new form) set it to "1 = -1", otherwise
		 * we'll wind up selecting everything in the table.
		 */

		if ($query)
		{
			if (!empty($where))
			{
				$query->where(implode(' OR ', $where));
			}
			else
			{
				$query->where('1 = -1');
			}

			return $query;
		}
		else
		{
			return empty($where) ? '1 = -1' : 'WHERE ' . implode(' OR ', $where);
		}
	}

	/**
	 * Get options order by
	 *
	 * @param   string               $view   View mode '' or 'filter'
	 * @param   JDatabaseQuery|bool  $query  Set to false to return a string
	 *
	 * @return  string  order by statement
	 */
	protected function getOrderBy($view = '', $query = false)
	{
		$params = $this->getParams();
		$orderBy = $params->get('notes_order_element');

		if ($orderBy == '')
		{
			return $query ? $query : '';
		}
		else
		{
			$order = FabrikString::safeQuoteName($params->get('join_db_name') . '.' . $orderBy) . ' ' . $params->get('notes_order_dir', 'ASC');

			if ($query)
			{
				$query->order($order);

				return $query;
			}

			return " ORDER BY " . $order;
		}
	}

	/**
	 * If buildQuery needs additional fields then set them here, used in notes plugin
	 *
	 * @since 3.0rc1
	 *
	 * @return string fields to add e.g return ',name, username AS other'
	 */
	protected function getAdditionalQueryFields()
	{
		$fields = array();
		$db = $this->getDb();
		$params = $this->getParams();

		if ($params->get('showuser', true))
		{
			$user = $params->get('userid', '');

			if ($user !== '')
			{
				$tbl = $db->qn($this->getJoin()->table_join_alias);
				$fields[] = $tbl . '.' . $db->qn($user) . 'AS userid';
				$fields[] = 'u.name AS username';
			}
		}

		$date = $params->get('notes_date', '');

		if ($date !== '')
		{
			$tbl = $db->qn($this->getJoin()->table_join_alias);
			$fields[] = $tbl . '.' . $db->qn($date) . 'AS date_time';
		}

		return implode(', ', $fields);
	}

	/**
	 * If buildQuery needs additional joins then set them here, used in notes plugin
	 *
	 * @param   mixed  $query  false to return string, or JQueryBuilder object
	 *
	 * @since 3.0rc1
	 *
	 * @return string|JDatabaseQuery join statement to add
	 */
	protected function buildQueryJoin($query = false)
	{
		$join = '';
		$db = $this->getDb();
		$params = $this->getParams();

		if ($params->get('showuser', true))
		{
			$user = $params->get('userid', '');

			if ($user !== '')
			{
				$tbl = $db->qn($this->getJoin()->table_join_alias);

				if (!$query)
				{
					$join .= ' LEFT JOIN #__users AS u ON u.id = ' . $tbl . '.' . $db->qn($user);
				}
				else
				{
					$query->join('LEFT', '#__users AS u ON u.id = ' . $tbl . '.' . $db->qn($user));
				}
			}
		}

		return $query ? $query : $join;
	}

	/**
	 * Do you add a please select option to the cdd list
	 *
	 * @since 3.0b
	 *
	 * @return boolean
	 */
	protected function showPleaseSelect()
	{
		return false;
	}

	/**
	 * Ajax add note
	 *
	 * @return  void
	 */
	public function onAjax_addNote()
	{
		$input = $this->app->input;
		$this->loadMeForAjax();
		$return = new stdClass;
		$db = $this->getDb();
		$query = $db->getQuery(true);
		$params = $this->getParams();
		$table = $db->qn($params->get('join_db_name'));
		$col = $params->get('join_val_column');
		$v = $input->get('v', '', '', 'string');
		$rowId = $this->getFormModel()->getRowId();
		//$joinPkVal = $this->getJoinedGroupPkVal($repeatCounter);

		// Jaanus - avoid inserting data when the form is 'new' not submitted ($rowId == '')
		if ($rowId !== '')
		{
			$query->insert($table)->set($col . ' = ' . $db->q($v));
			$user = $params->get('userid', '');

			if ($user !== '')
			{
				$query->set($db->qn($user) . ' = ' . (int) $this->user->get('id'));
			}

			$fk = $params->get('join_fk_column', '');

			if ($fk !== '')
			{
				if ($this->getGroupModel()->isJoin())
				{
					$query->set($db->qn($fk) . ' = ' . $db->q($input->get('joinPkVal')));
				}
				else
				{
					$query->set($db->qn($fk) . ' = ' . $db->q($input->get('rowid')));
				}
			}

			$date = $params->get('notes_date', '');

			if ($date !== '')
			{
				$query->set($db->qn($date) . ' = NOW()');
			}

			$db->setQuery($query);
			$db->execute();
			$return->label = $v;
			echo json_encode($return);
		}
	}
}
