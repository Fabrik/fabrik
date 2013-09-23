<?php
/**
 * Plugin element to render dropdown list to select user
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.user
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once JPATH_SITE . '/plugins/fabrik_element/databasejoin/databasejoin.php';

/**
 * Plugin element to render dropdown list to select user
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.user
 * @since       3.0
 */

class PlgFabrik_ElementUser extends PlgFabrik_ElementDatabasejoin
{

	/**
	 * Is a join element
	 *
	 * @var bool
	 */
	var $_isJoin = true;

	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'INT(11)';

	/**
	* Load element params
	* bit of a hack to set join_db_name in params
	*
	* @return  object  default element params
	*/

	public function getParams()
	{
		$params = parent::getParams();
		if (empty($params->join_db_name))
		{
			$params->set('join_db_name', '#__users');
		}
		// $$$ hugh - think we need to default key column as well, otherwise
		// when creating user element, we end up setting field to VARCHAR 255
		if (empty($params->join_key_column))
		{
			$params->set('join_key_column', 'id');
		}
		return $params;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to preopulate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$element = $this->getElement();
		$name = $this->getHTMLName($repeatCounter);
		$html_id = $this->getHTMLId($repeatCounter);
		$id = $html_id;
		$params = $this->getParams();

		/**
		 *  $$$ rob - if embedding a form inside a details view then rowid is true (for the detailed view) but we are still showing a new form
		 *  instead take a look at the element form's rowId;
		 *  $rowid = JRequest::getVar('rowid', false);
		 */
		$rowid = $this->getForm()->_rowId;
		/**
		 * @TODO when editing a form with joined repeat group the rowid will be set but
		 * the record is in fact new
		 */
		if ($params->get('update_on_edit') || !$rowid || ($this->_inRepeatGroup && $this->_inJoin && $this->_repeatGroupTotal == $repeatCounter))
		{
			// Set user to logged in user
			if ($this->isEditable())
			{
				$user = JFactory::getUser();
			}
			else
			{
				$userid = (int) $this->getValue($data, $repeatCounter);

				// On failed validtion value is 1 - user ids are always more than that so dont load userid=1 otherwise an error is generated
				$user = $userid <= 1 ? JFactory::getUser() : JFactory::getUser($userid);
			}
		}
		else
		{
			/**
			 *  $$$ hugh - this is blowing away the userid, as $element->default is empty at this point
			 *  so for now I changed it to the $data value
			 *  keep previous user
			 *  $user = JFactory::getUser((int) $element->default);
			 */
			// $$$ hugh ... what a mess ... of course if it's a new form, $data doesn't exist ...
			if (empty($data))
			{
				$user = JFactory::getUser();
			}
			else
			{
				if ($this->_inDetailedView)
				{
					$id = preg_replace('#_ro$#', '_raw', $id);
				}
				else
				{
					/**
					 *  $$$ rob 31/07/2011 not sure this is right - causes js error when field is hidden in form
					 *  $$$ hugh 10/31/2011 - but if we don't do it, $id is the label not the value (like 'username')
					 *  so wrong uid is written to form, and wipes out real ID when form is submitted.
					 *  OK, problem was we were using $id firther on as the html ID, so if we added _raw, element
					 *  on form had wrong ID.  Added $html_id above, to use as (duh) html ID instead of $id.
					 */
					if (!strstr($id, '_raw') && array_key_exists($id . '_raw', $data))
					{
						$id .= '_raw';
					}
				}
				$id = JArrayHelper::getValue($data, $id, '');
				if ($id === '')
				{
					$id = $this->getValue($data, $repeatCounter);
				}
				$id = is_array($id) ? $id[0] : $id;
				/* $$$ hugh - hmmm, might not necessarily be a new row.  So corner case check for
				 * editing a row, where user element is not set yet, and 'update on edit' is No.
				 */
				if ($rowid && empty($id) && !$params->get('update_on_edit'))
				{
					$user = JFactory::getUser(0);
				}
				else
				{
					$user = $id === '' ? JFactory::getUser() : JFactory::getUser((int) $id);
				}
			}
		}

		/**
		 *  If the table database is not the same as the joomla database then
		 *  we should simply return a hidden field with the user id in it.
		 */
		if (!$this->inJDb())
		{
			return $this->getHiddenField($name, $user->get('id'), $html_id);
		}
		$str = '';
		if ($this->isEditable())
		{
			$value = is_object($user) ? $user->get('id') : '';
			if ($element->hidden)
			{
				$str = $this->getHiddenField($name, $value, $html_id);
			}
			else
			{
				$str = parent::render($data, $repeatCounter);
			}
		}
		else
		{
			$displayParam = $this->getLabelOrConcatVal();
			if (is_a($user, 'JUser'))
			{
				$str = $user->get($displayParam);
			}
			else
			{
				JError::raiseWarning(E_NOTICE, "didnt load for $element->default");
			}
		}
		return $str;
	}

	/**
	 * Is the element hidden or not - if not set then return false
	 * If the table db isnt the same as the joomla db the element
	 *
	 * @return  bool
	 */

	protected function isHidden()
	{
		if ($this->inJDb())
		{
			return parent::isHidden();
		}
		else
		{
			return true;
		}
	}

	/**
	 * run on formModel::setFormData()
	 *
	 * @param   int  $c  repeat group counter
	 *
	 * @return void
	 */

	public function preProcess($c)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$params = $this->getParams();

		/**
		 * $$$ hugh - special case for social plugins (like CB plugin).  If plugin sets
		 * fabrik.plugin.profile_id, and 'user_use_social_plugin_profile' param is set,
		 * and we are creating a new row, then use the session data as the user ID.
		 * This allows user B to view a table in a CB profile for user A, do an "Add",
		 * and have the user element set to user A's ID.
		 * TODO - make this table/form specific, but not so easy to do in CB plugin
		 */
		if ((int) $params->get('user_use_social_plugin_profile', 0))
		{
			if (JRequest::getInt('rowid') == 0 && JRequest::getCmd('task') !== 'doimport')
			{
				$context = 'fabrik.plugin.profile_id';
				if (JRequest::getVar('fabrik_social_profile_hash', '') != '')
				{
					$context = 'fabrik.plugin.' . JRequest::getVar('fabrik_social_profile_hash', '') . '.profile_id';
				}
				$session = JFactory::getSession();
				if ($session->has($context))
				{
					$profile_id = $session->get($context);
					$form = $this->getFormModel();
					$group = $this->getGroup();
					$joinid = $group->getGroup()->join_id;
					$key = $this->getFullName(true, true, false);
					$shortkey = $this->getFullName(false, true, false);
					$rawkey = $key . '_raw';
					if ($group->canRepeat())
					{
						if ($group->isJoin())
						{
							$key = str_replace("][", '.', $key);
							$key = str_replace(array('[', ']'), '.', $key) . "$c";
							$rawkey = str_replace($shortkey, $shortkey . '_raw', $key);
						}
						else
						{
							$key = $key . '.' . $c;
							$rawkey = $rawkey . '.' . $c;
						}
					}
					else
					{
						if ($group->isJoin())
						{
							$key = str_replace("][", ".", $key);
							$key = str_replace(array('[', ']'), '.', $key);
							$key = rtrim($key, '.');
							$rawkey = str_replace($shortkey, $shortkey . '_raw', $key);
						}
					}
					$form->updateFormData($key, $profile_id);
					$form->updateFormData($rawkey, $profile_id);
					JRequest::setVar($key, $profile_id, 'POST');
					JRequest::setVar($rawkey, $profile_id, 'POST');
				}
			}
		}
	}

	/**
	 * Trigger called when a row is stored.
	 * If we are creating a new record, and the element was set to readonly
	 * then insert the users data into the record to be stored
	 *
	 * @param   array  &$data  to store
	 *
	 * @return  void
	 */

	public function onStoreRow(&$data)
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		// $$$ hugh - special case, if we have just run the fabrikjuser plugin, we need to
		// use the 'newuserid' as set by the plugin.
		$newuserid = JRequest::getInt('newuserid', 0);
		if (!empty($newuserid))
		{
			$newuserid_element = JRequest::getVar('newuserid_element', '');
			$this_fullname = $this->getFullName(false, true, false);
			if ($newuserid_element == $this_fullname)
			{
				return;
			}
		}
		$element = $this->getElement();
		$params = $this->getParams();

		/**
		 *  $$$ hugh - special case for social plugins (like CB plugin).  If plugin sets
		 *  fabrik.plugin.profile_id, and 'user_use_social_plugin_profile' param is set,
		 *  and we are creating a new row, then use the session data as the user ID.
		 * This allows user B to view a table in a CB profile for user A, do an "Add",
		 * and have the user element set to user A's ID.
		 */
		// TODO - make this table/form specific, but not so easy to do in CB plugin
		if ((int) $params->get('user_use_social_plugin_profile', 0))
		{
			if (JRequest::getInt('rowid') == 0 && JRequest::getCmd('task') !== 'doimport')
			{
				$session = JFactory::getSession();
				if ($session->has('fabrik.plugin.profile_id'))
				{
					$data[$element->name] = $session->get('fabrik.plugin.profile_id');
					$data[$element->name . '_raw'] = $data[$element->name];

					// $session->clear('fabrik.plugin.profile_id');
					return;
				}
			}
		}

		// $$$ rob also check we aren't importing from CSV - if we are ingore
		if (JRequest::getInt('rowid') == 0 && JRequest::getCmd('task') !== 'doimport')
		{

			// $$$ rob if we cant use the element or its hidden force the use of current logged in user
			if (!$this->canUse() || $this->getElement()->hidden == 1)
			{
				$user = JFactory::getUser();
				$data[$element->name] = $user->get('id');
				$data[$element->name . '_raw'] = $data[$element->name];
			}
		}
		// $$$ hugh
		// If update-on-edit is set, we always want to store as current user??

		// $$$ rob NOOOOOO!!!!! - if its HIDDEN OR set to READ ONLY then yes
		// otherwise selected dropdown option is not taken into account

		// $$$ hugh - so how come we don't do the same thing on a new row?  Seems inconsistant to me?
		else
		{
			if ($this->updateOnEdit())
			{
				$user = JFactory::getUser();
				$data[$element->name] = $user->get('id');
				$data[$element->name . '_raw'] = $data[$element->name];

				// $$$ hugh - need to add to updatedByPlugin() in order to override write access settings.
				// This allows us to still 'update on edit' when element is write access controlled.
				if (!$this->canUse())
				{
					$this_fullname = $this->getFullName(false, true, false);
					$this->getFormModel()->updatedByPlugin($this_fullname, $user->get('id'));
				}
			}
		}
	}

	/**
	 * Should the element's value be replaced with the current user's id
	 *
	 * @return  bool
	 */
	protected function updateOnEdit()
	{
		$params = $this->getParams();
		$updaeOnEdit = $params->get('update_on_edit', 0);
		if ($updaeOnEdit == 1)
		{
			$updaeOnEdit = !$this->canUse() || $this->getElement()->hidden == 1;
		}
		if ($updaeOnEdit == 2)
		{
			$updaeOnEdit = true;
		}
		return $updaeOnEdit;
	}

	/**
	 * Check user can view the read only element OR view in list view
	 *
	 * When processing the form, we always want to store the current userid
	 * (subject to save-on-edit, but that's done elsewhere), regardless of
	 * element access settings, see:
	 *
	 * http://fabrikar.com/forums/showthread.php?p=70554#post70554
	 *
	 * So overriding the element model canView and returning true in that
	 * case allows addDefaultDataFromRO to do that, whilst still enforcing
	 * Read Access settings for detail/list view
	 *
	 * @param   string  $view  View list/form @since 3.0.7
	 *
	 * @return  bool  can view or not
	 */

	public function canView($view = 'form')
	{
		$app = JFactory::getApplication();
		if ($app->input->get('task', '') == 'processForm')
		{
			return true;
		}
		return parent::canView($view);
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$opts = parent::elementJavascriptOpts($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		return array('FbUser', $id, $opts);
	}

	/**
	 * Get the class to manage the form element
	 * if a plugin class requires to load another elements class (eg user for dbjoin then it should
	 * call FabrikModelElement::formJavascriptClass('plugins/fabrik_element/databasejoin/databasejoin.js', true);
	 * to ensure that the file is loaded only once
	 *
	 * @param   array   &$srcs   Scripts previously loaded (load order is important as we are loading via head.js
	 * and in ie these load async. So if you this class extends another you need to insert its location in $srcs above the
	 * current file
	 * @param   string  $script  Script to load once class has loaded
	 *
	 * @return void
	 */

	public function formJavascriptClass(&$srcs, $script = '')
	{
		plgFabrik_Element::formJavascriptClass($srcs, 'plugins/fabrik_element/databasejoin/databasejoin.js');
		parent::formJavascriptClass($srcs, $script);
	}

	/**
	 * Get select option label
	 *
	 * @return  string
	 */

	protected function _getSelectLabel()
	{
		return $this->getParams()->get('user_noselectionlabel', JText::_('COM_FABRIK_PLEASE_SELECT'));
	}

	/**
	 * Create the SQL select 'name AS alias' segment for list/form queries
	 *
	 * @param   array  &$aFields    array of element names
	 * @param   array  &$aAsFields  array of 'name AS alias' fields
	 * @param   array  $opts        options
	 *
	 * @return  void
	 */

	public function getAsField_html(&$aFields, &$aAsFields, $opts = array())
	{
		$table = $this->actualTableName();
		$element = $this->getElement();
		$params = $this->getParams();
		$db = FabrikWorker::getDbo();
		$fullElName = JArrayHelper::getValue($opts, 'alias', $table . '___' . $element->name);

		// Check if main database is the same as the elements database
		if ($this->inJDb())
		{
			/**
			 * it is so continue as if it were a database join
			 * make sure same connection as this table
			 */

			$join = $this->getJoin();

			// $$$ rob in csv import keytable not set
			$k = isset($join->keytable) ? $join->keytable : $join->join_from_table;
			$k = FabrikString::safeColName($k . '.' . $element->name);
			$k2 = FabrikString::safeColName($this->getJoinLabelColumn());

			if (JArrayHelper::getValue($opts, 'inc_raw', true))
			{
				$aFields[] = $k . ' AS ' . $db->quoteName($fullElName . '_raw');
				$aAsFields[] = $db->quoteName($fullElName . '_raw');
			}
			$aFields[] = $k2 . ' AS ' . $db->quoteName($fullElName);
			$aAsFields[] = $db->quoteName($fullElName);
		}
		else
		{
			$k = $db->quoteName($table) . '.' . $db->quoteName($element->name);

			// Its not so revert back to selecting the id
			$aFields[] = $k . ' AS ' . $db->quoteName($fullElName . '_raw');
			$aAsFields[] = $db->quoteName($fullElName . '_raw');
			$aFields[] = $k . ' AS ' . $db->quoteName($fullElName);
			$aAsFields[] = $db->quoteName($fullElName);
		}
	}

	/**
	 * Called when the element is saved
	 *
	 * @param   array  $data  posted element save data
	 *
	 * @return  bool  save ok or not
	 */

	public function onSave($data)
	{
		$params = json_decode($data['params']);
		if (!$this->canEncrypt() && !empty($params->encrypt))
		{
			JError::raiseNotice(500, 'The encryption option is only available for field and text area plugins');
			return false;
		}
		$label = (isset($params->my_table_data) && $params->my_table_data !== '') ? $params->my_table_data : 'username';
		$this->updateFabrikJoins($data, '#__users', 'id', $label);
		return true;
	}

	/**
	 * Get the join label name
	 *
	 * @return  string
	 */

	protected function getJoinLabel()
	{
		$label = parent::getJoinLabel();
		if ($label == 'gid')
		{
			$label = 'username';
		}
		return $label;
	}

	/**
	 * This really does get just the default value (as defined in the element's settings)
	 *
	 * @param   array  $data  Form data
	 *
	 * @return mixed
	 */

	public function getDefaultValue($data = array())
	{
		if (!isset($this->_default))
		{
			$user = JFactory::getUser();
			$this->_default = $user->get('id');
		}
		return $this->_default;
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  when repeating joinded groups we need to know what part of the array to access
	 * @param   array  $opts           options
	 *
	 * @return  string	value
	 */

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{

		// Cludge for 2 scenarios
		if (array_key_exists('rowid', $data))
		{
			// When validating the data on form submission
			$key = 'rowid';
		}
		else
		{
			// When rendering the element to the form
			$key = '__pk_val';
		}
		/*
		 * empty(data) when you are saving a new record and this element is in a joined group
		 * $$$ hugh - added !array_key_exists(), as ... well, rowid doesn't always exist in the query string
		 */

		if (empty($data) || !array_key_exists($key, $data))
		{
			// $$$ rob - added check on task to ensure that we are searching and not submitting a form
			// as otherwise not empty valdiation failed on user element
			if (!in_array(JRequest::getCmd('task'), array('processForm', 'view', '', 'form.process')))
			{
				return '';
			}
			return $this->getDefaultOnACL($data, $opts);
		}
		$res = parent::getValue($data, $repeatCounter, $opts);
		return $res;
	}

	/**
	 * Get the table filter for the element
	 *
	 * @param   int   $counter  filter order
	 * @param   bool  $normal   do we render as a normal filter or as an advanced search filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 *
	 * @return  string	filter html
	 */

	public function getFilter($counter = 0, $normal = true)
	{
		$listModel = $this->getlistModel();
		$formModel = $listModel->getFormModel();
		$elName2 = $this->getFullName(false, false, false);
		if (!$formModel->hasElement($elName2))
		{
			return '';
		}
		$table = $listModel->getTable();
		$element = $this->getElement();
		$params = $this->getParams();

		$elName = $this->getFullName(false, true, false);
		$htmlid = $this->getHTMLId() . 'value';
		$v = $this->filterName($counter, $normal);

		// Corect default got
		$default = $this->getDefaultFilterVal($normal, $counter);
		$return = array();
		$tabletype = $this->getLabelOrConcatVal();
		$join = $this->getJoin();
		$joinTableName = FabrikString::safeColName($join->table_join_alias);

		// If filter type isn't set was blowing up in switch below 'cos no $rows
		// so added '' to this test.  Should probably set $element->filter_type to a default somewhere.
		if (in_array($element->filter_type, array('range', 'dropdown', '', 'checkbox')))
		{
			$rows = $this->filterValueList($normal, '', $joinTableName . '.' . $tabletype, '', false);
			$rows = (array) $rows;
			if ($element->filter_type !== 'checkbox')
			{
				array_unshift($rows, JHTML::_('select.option', '', $this->filterSelectLabel()));
			}
		}

		switch ($element->filter_type)
		{
			case 'checkbox':
				$return[] = $this->checkboxFilter($rows, $default, $v);
				break;
			case "range":
				$attribs = 'class="inputbox fabrik_filter" size="1" ';
				$default1 = is_array($default) ? $default[0] : '';
				$return[] = JHTML::_('select.genericlist', $rows, $v . '[]', $attribs, 'value', 'text', $default1, $element->name . "_filter_range_0");
				$default1 = is_array($default) ? $default[1] : '';
				$return[] = JHTML::_('select.genericlist', $rows, $v . '[]', $attribs, 'value', 'text', $default1, $element->name . "_filter_range_1");
				break;
			case 'dropdown':
			case 'multiselect':
			default:
				$max = count($rows) < 7 ? count($rows) : 7;
				$size = $element->filter_type === 'multiselect' ? 'multiple="multiple" size="' . $max . '"' : 'size="1"';
				$v = $element->filter_type === 'multiselect' ? $v . '[]' : $v;
				$return[] = JHTML::_('select.genericlist', $rows, $v, 'class="inputbox fabrik_filter" ' . $size, 'value', 'text', $default, $htmlid);
				break;

			case 'field':
				if (get_magic_quotes_gpc())
				{
					$default = stripslashes($default);
				}
				$default = htmlspecialchars($default);
				$return[] = '<input type="text" name="' . $v . '" class="inputbox fabrik_filter" value="' . $default . '" id="' . $htmlid . '" />';
				break;

			case 'hidden':
				if (get_magic_quotes_gpc())
				{
					$default = stripslashes($default);
				}
				$default = htmlspecialchars($default);
				$return[] = '<input type="hidden" name="' . $v . '" class="inputbox fabrik_filter" value="' . $default . '" id="' . $htmlid . '" />';
				break;

			case 'auto-complete':
				$defaultLabel = $this->getLabelForValue($default);
				$autoComplete = $this->autoCompleteFilter($default, $v, $defaultLabel, $normal);
				$return = array_merge($return, $autoComplete);
				break;
		}
		if ($normal)
		{
			$return[] = $this->getFilterHiddenFields($counter, $elName);
		}
		else
		{
			$return[] = $this->getAdvancedFilterHiddenFields();
		}
		return implode("\n", $return);
	}

	/**
	 * If filterValueList_Exact incjoin value = false, then this method is called
	 * to ensure that the query produced in filterValueList_Exact contains at least the database join element's
	 * join
	 *
	 * @return  string  required join text to ensure exact filter list code produces a valid query.
	 */

	protected function _buildFilterJoin()
	{
		$params = $this->getParams();
		$joinTable = FabrikString::safeColName($params->get('join_db_name'));
		$join = $this->getJoin();
		$joinTableName = FabrikString::safeColName($join->table_join_alias);
		$joinKey = $this->getJoinValueColumn();
		$elName = FabrikString::safeColName($this->getFullName(false, true, false));
		return 'INNER JOIN ' . $joinTable . ' AS ' . $joinTableName . ' ON ' . $joinKey . ' = ' . $elName;
	}

	/**
	 * Build the filter query for the given element.
	 * Can be overwritten in plugin - e.g. see checkbox element which checks for partial matches
	 *
	 * @param   string  $key            element name in format `tablename`.`elementname`
	 * @param   string  $condition      =/like etc
	 * @param   string  $value          search string - already quoted if specified in filter array options
	 * @param   string  $originalValue  original filter value without quotes or %'s applied
	 * @param   string  $type           filter type advanced/normal/prefilter/search/querystring/searchall
	 *
	 * @return  string	sql query part e,g, "key = value"
	 */

	public function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal')
	{
		if (!$this->inJDb())
		{
			return $key . ' ' . $condition . ' ' . $value;
		}
		$element = $this->getElement();

		// $$$ hugh - we need to use the join alias, not hard code #__users
		$join = $this->getJoin();
		if (is_object($join))
		{
			$joinTableName = $join->table_join_alias;
		}
		if (empty($joinTableName))
		{
			$joinTableName = '#__users';
		}
		$db = JFactory::getDbo();
		if ($type == 'querystring' || $type == 'jpluginfilters')
		{
			$key = FabrikString::safeColNameToArrayKey($key);
			/* $$$ rob no matter whether you use elementname_raw or elementname in the querystring filter
			 * by the time it gets here we have normalized to elementname. So we check if the original qs filter was looking at the raw
			 * value if it was then we want to filter on the key and not the label
			 */
			if (!array_key_exists($key, JRequest::get('get')))
			{
				$key = $db->quoteName($joinTableName . '.id');
				$this->encryptFieldName($key);
				return $key . ' ' . $condition . ' ' . $value;
			}
		}
		if ($type == 'advanced')
		{
			$key = $db->quoteName($joinTableName . '.id');
			$this->encryptFieldName($key);
			return $key . ' ' . $condition . ' ' . $value;
		}
		$params = $this->getParams();
		if ($type != 'prefilter')
		{
			switch ($element->filter_type)
			{
				case 'range':
				case 'dropdown':
					$tabletype = 'id';
					break;
				case 'field':
				default:
					$tabletype = $this->getLabelOrConcatVal();
					break;
			}
			$k = $db->quoteName($joinTableName . '.' . $tabletype);
		}
		else
		{
			if ($this->_rawFilter)
			{
				$k = $db->quoteName($joinTableName . '.id');
			}
			else
			{
				$tabletype = $this->getLabelOrConcatVal();
				$k = $db->quoteName($joinTableName . '.' . $tabletype);
			}
		}
		$this->encryptFieldName($k);
		$str = $k . ' ' . $condition . ' ' . $value;
		return $str;
	}

	/**
	 * Get the database object
	 *
	 * @return  object	database
	 */

	function getDb()
	{
		return FabrikWorker::getDbo(true);
	}

	/**
	 * Used to format the data when shown in the form's email
	 *
	 * @param   mixed  $value          element's data
	 * @param   array  $data           form records data
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	formatted value
	 */

	public function getEmailValue($value, $data, $repeatCounter)
	{
		$key = $this->getFullName(false, true, false);
		$rawkey = $key . '_raw';
		$userid = $value;
		if (array_key_exists($rawkey, $data))
		{
			$userid = $data[$rawkey];
		}
		elseif (array_key_exists($key, $data))
		{
			$userid = $data[$key];
		}

		if (is_array($userid))
		{
			$userid = (int) array_shift($userid);
		}
		$user = $userid === 0 ? JFactory::getUser() : JFactory::getUser($userid);
		return $this->getUserDisplayProperty($user);
	}

	/**
	 * Get the user's property to show, if gid raise warning and revert to username (no gid in J1.7)
	 *
	 * @param   object  $user  Joomla user
	 *
	 * @since	3.0b
	 *
	 * @return  string
	 */

	protected function getUserDisplayProperty($user)
	{
		static $displayMessage;
		$params = $this->getParams();
		$displayParam = $this->getLabelOrConcatVal();
		return is_a($user, 'JUser') ? $user->get($displayParam) : false;
	}

	/**
	 * Get the column name used for the value part of the db join element
	 *
	 * @return  string
	 */

	protected function getJoinValueColumn()
	{
		$params = $this->getParams();
		$join = $this->getJoin();
		$db = FabrikWorker::getDbo();
		return $db->quoteName($join->table_join_alias) . '.id';
	}

	/**
	 * Used for the name of the filter fields
	 * Over written here as we need to get the label field for field searches
	 *
	 * @return string element filter name
	 */

	public function getFilterFullName()
	{
		$elName = $this->getFullName(false, true, false);
		return FabrikString::safeColName($elName);
	}

	/**
	 * Called when copy row list plugin called
	 *
	 * @param   mixed  $val  value to copy into new record
	 *
	 * @return mixed value to copy into new record
	 */

	public function onCopyRow($val)
	{
		$params = $this->getParams();
		if ($params->get('update_on_edit'))
		{
			$user = JFactory::getUser();
			$val = $user->get('id');
		}
		return $val;
	}

	/**
	 * Called when save as copy form button clicked
	 *
	 * @param   mixed  $val  value to copy into new record
	 *
	 * @return  mixed  value to copy into new record
	 */

	public function onSaveAsCopy($val)
	{
		$params = $this->getParams();
		if ($params->get('update_on_copy', false))
		{
			$user = JFactory::getUser();
			$val = $user->get('id');
		}
		return $val;
	}

	/**
	 * Get the element name or concat statement used to build the dropdown labels or
	 * table data field
	 *
	 * @return  string
	 */

	protected function getLabelOrConcatVal()
	{
		static $displayMessage;
		$params = $this->getParams();
		$displayParam = $params->get('my_table_data', 'username');
		if ($displayParam == 'gid')
		{
			$displayParam == 'username';
			if (!isset($displayMessage))
			{
				JError::raiseNotice(200, JText::sprintf('PLG_ELEMENT_USER_NOTICE_GID', $this->getElement()->id));
				$displayMessage = true;
			}
		}
		return $displayParam;
	}

	/**
	 * Get an array of element html ids and their corresponding
	 * js events which trigger a validation.
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  array  html ids to watch for validation
	 */

	public function getValidationWatchElements($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$ar = array('id' => $id, 'triggerEvent' => 'change');
		return array($ar);
	}
}
