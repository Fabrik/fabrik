<?php
/**
 * Plugin element to render dropdown list to select user
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.user
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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
	 * @param   array $data          to pre-populate element with
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$element = $this->getElement();
		$name    = $this->getHTMLName($repeatCounter);
		$htmlId  = $this->getHTMLId($repeatCounter);
		$id      = $htmlId;
		$params  = $this->getParams();

		/**
		 *  $$$ rob - if embedding a form inside a details view then rowid is true (for the detailed view) but we are still showing a new form
		 *  so take a look at the element form's rowId and not app input
		 */
		$rowId = $this->getFormModel()->rowId;
		/**
		 * @TODO when editing a form with joined repeat group the rowid will be set but
		 * the record is in fact new
		 */
		//if ($params->get('update_on_edit') || !$rowId || ($this->inRepeatGroup && $this->_inJoin && $this->_repeatGroupTotal == $repeatCounter))
		if ($params->get('update_on_edit') || !$rowId)
		{
			// Set user to logged in user
			if ($this->isEditable())
			{
				$user = $this->user;
			}
			else
			{
				$userId = $this->getValue($data, $repeatCounter, array('raw' => 1));
				$userId = is_array($userId) ? (int) FArrayHelper::getValue($userId, 0) : (int) $userId;

				// On failed validation value is 1 - user ids are always more than that so don't load userid=1 otherwise an error is generated
				$user = $userId <= 1 ? false : JFactory::getUser($userId);
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
				$user = $this->user;
			}
			else
			{
				if ($this->inDetailedView)
				{
					$id = preg_replace('#_ro$#', '_raw', $id);
				}
				else
				{
					/**
					 *  $$$ rob 31/07/2011 not sure this is right - causes js error when field is hidden in form
					 *  $$$ hugh 10/31/2011 - but if we don't do it, $id is the label not the value (like 'username')
					 *  so wrong uid is written to form, and wipes out real ID when form is submitted.
					 *  OK, problem was we were using $id further on as the html ID, so if we added _raw, element
					 *  on form had wrong ID.  Added $htmlId above, to use as (duh) html ID instead of $id.
					 */
					if (!strstr($id, '_raw') && array_key_exists($id . '_raw', $data))
					{
						$id .= '_raw';
					}
				}

				$id = FArrayHelper::getValue($data, $id, '');

				if ($id === '')
				{
					$id = $this->getValue($data, $repeatCounter, array('raw' => 1));
				}

				/*
				 * After a failed validation, it may be JSON, and urlencoded, like [&quot;94&quot;]
				 * Or it may be an array with JSON and or urlencode and or ... yada yada ... who the f*ck knows
				 * So let's just cover all the bases, shall we?
				 */

				$id = is_array($id) ? $id[0] : $id;

				$id = html_entity_decode($id);
				if (FabrikWorker::isJSON($id))
				{
					$id = FabrikWorker::JSONtoData($id, true);
				}

				$id = is_array($id) ? $id[0] : $id;
				/* $$$ hugh - hmmm, might not necessarily be a new row.  So corner case check for
				 * editing a row, where user element is not set yet, and 'update on edit' is No.
				 */
				if ($rowId && empty($id) && !$params->get('update_on_edit'))
				{
					$user = JFactory::getUser(0);
				}
				else
				{
					$user = $id === '' ? $this->user : JFactory::getUser((int) $id);
				}
			}
		}

		$displayParam           = $this->getLabelOrConcatVal();
		$layout                 = $this->getLayout('form');
		$layoutData             = new stdClass;
		$layoutData->inJDb      = $this->inJDb();
		$layoutData->name       = $name;
		$layoutData->id         = $htmlId;
		$layoutData->isEditable = $this->isEditable();
		$layoutData->hidden     = $element->hidden;
		$layoutData->input      = parent::render($data, $repeatCounter);
		$layoutData->readOnly   = is_a($user, 'JUser') ? $user->get($displayParam) : '';
		$layoutData->value      = is_a($user, 'JUser') ? $user->get('id') : '';

		return $layout->render($layoutData);
	}

	/**
	 * Is the element hidden or not - if not set then return false
	 * If the table db isn't the same as the joomla db the element
	 *
	 * @return  bool
	 */
	public function isHidden()
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
	 * @param   int $c repeat group counter
	 *
	 * @return void
	 */
	public function preProcess($c)
	{
		$input  = $this->app->input;
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
			if ($input->getString('rowid', '', 'string') == '' && $input->get('task') !== 'doimport')
			{
				$context = 'fabrik.plugin.profile_id';

				if ($input->get('fabrik_social_profile_hash', '') != '')
				{
					$context = 'fabrik.plugin.' . $input->get('fabrik_social_profile_hash', '') . '.profile_id';
				}

				if ($this->session->has($context))
				{
					$profileId = $this->session->get($context);
					$form      = $this->getFormModel();
					$group     = $this->getGroup();
					$key       = $this->getFullName(true, false);
					$shortKey  = $this->getFullName(true, false);
					$rawKey    = $key . '_raw';

					if ($group->canRepeat())
					{
						if ($group->isJoin())
						{
							$key    = str_replace("][", '.', $key);
							$key    = str_replace(array('[', ']'), '.', $key) . "$c";
							$rawKey = str_replace($shortKey, $shortKey . '_raw', $key);
						}
						else
						{
							$key    = $key . '.' . $c;
							$rawKey = $rawKey . '.' . $c;
						}
					}
					else
					{
						if ($group->isJoin())
						{
							$key    = str_replace("][", ".", $key);
							$key    = str_replace(array('[', ']'), '.', $key);
							$key    = rtrim($key, '.');
							$rawKey = str_replace($shortKey, $shortKey . '_raw', $key);
						}
					}

					$form->updateFormData($key, $profileId);
					$form->updateFormData($rawKey, $profileId);
					$input->post->set($key, $profileId);
					$input->post->set($rawKey, $profileId);
				}
			}
		}
	}

	/**
	 * Trigger called when a row is stored.
	 * If we are creating a new record, and the element was set to readonly
	 * then insert the users data into the record to be stored
	 *
	 * @param   array &$data         Data to store
	 * @param   int   $repeatCounter Repeat group index
	 *
	 * @return  bool  If false, data should not be added.
	 */
	public function onStoreRow(&$data, $repeatCounter = 0)
	{
		if (!parent::onStoreRow($data, $repeatCounter))
		{
			return false;
		}

		// $$$ hugh - if importing a CSV, just use the data as is
		if ($this->getListModel()->importingCSV)
		{
			return true;
		}

		$input = $this->app->input;

		// $$$ hugh - special case, if we have just run the fabrikjuser plugin, we need to
		// use the 'newuserid' as set by the plugin.
		$newUserId = $input->getInt('newuserid', 0);

		if (!empty($newUserId))
		{
			$newUserIdElement = $input->get('newuserid_element', '');
			$thisFullName     = $this->getFullName(true, false);

			if ($newUserIdElement == $thisFullName)
			{
				return true;
			}
		}

		$element = $this->getElement();
		$params  = $this->getParams();

		/*
		 * After a failed validation, if readonly for ACL's, it may be JSON, and urlencoded, like [&quot;94&quot;]
		*/

		$data[$element->name] = is_array($data[$element->name]) ? $data[$element->name][0] : $data[$element->name];

		$data[$element->name] = html_entity_decode($data[$element->name]);

		if (FabrikWorker::isJSON($data[$element->name]))
		{
			$data[$element->name] = FabrikWorker::JSONtoData($data[$element->name], true);
		}

		$data[$element->name] = is_array($data[$element->name]) ? $data[$element->name][0] : $data[$element->name];

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
			//if ($input->getString('rowid', '', 'string') == '' && $input->get('task') !== 'doimport')
			if ($input->getString('rowid', '', 'string') == '' && !$this->getListModel()->importingCSV)
			{
				$session = JFactory::getSession();

				if ($session->has('fabrik.plugin.profile_id'))
				{
					$data[$element->name]          = $session->get('fabrik.plugin.profile_id');
					$data[$element->name . '_raw'] = $data[$element->name];

					// $session->clear('fabrik.plugin.profile_id');
					return true;
				}
			}
		}

		// $$$ rob also check we aren't importing from CSV - if we are ignore
		//if ($input->getString('rowid', '', 'string') == '' && $input->get('task') !== 'doimport')
		if ($input->getString('rowid', '', 'string') == '' && !$this->getListModel()->importingCSV)
		{
			// $$$ rob if we cant use the element or its hidden force the use of current logged in user
			if (!$this->canUse() || $this->getElement()->hidden == 1)
			{
				$data[$element->name]          = $this->user->get('id');
				$data[$element->name . '_raw'] = $data[$element->name];
			}
		}
		// $$$ hugh
		// If update-on-edit is set, we always want to store as current user??

		// $$$ rob NOOOOOO!!!!! - if its HIDDEN OR set to READ ONLY then yes
		// otherwise selected dropdown option is not taken into account

		// $$$ hugh - so how come we don't do the same thing on a new row?  Seems inconsistent to me?

		// $$$ paul - seems bonkers to me to use source code comments like an instant messaging system!

		/**
		 * $$$ hugh - it's not IM'ing, it's long running "frank and honest differences of opinion" over how things work
		 * and why we each make the assumptions / changes we do when working on "disputed" chunks of code
		 */

		else
		{
			if ($this->updateOnEdit())
			{
				$data[$element->name]          = $this->user->get('id');
				$data[$element->name . '_raw'] = $data[$element->name];

				// Set the formDataWithTableName so any plugins (like email) pick it up with getProcessData()
				$thisFullName = $this->getFullName(true, false);
				$formModel    = $this->getFormModel();
				$groupModel   = $this->getGroupModel();

				if ($groupModel->canRepeat())
				{
					$formModel->formDataWithTableName[$thisFullName][$repeatCounter] = array($data[$element->name]);
					$formModel->formDataWithTableName[$thisFullName . '_raw'][$repeatCounter] = array($data[$element->name]);
				}
				else{
					$formModel->formDataWithTableName[$thisFullName] = array($data[$element->name]);
					$formModel->formDataWithTableName[$thisFullName . '_raw'] = array($data[$element->name]);
				}

				// $$$ hugh - need to add to updatedByPlugin() in order to override write access settings.
				// This allows us to still 'update on edit' when element is write access controlled.
				if (!$this->canUse())
				{
					$this->getFormModel()->updatedByPlugin($thisFullName, $this->user->get('id'));
				}
			}

			/**
			 * If importing from CSV and not set to update on edit, let's check to see if they
			 * are trying to import a username rather than ID.
			 */

			else
			{
				if ($this->getListModel()->importingCSV)
				{
					$formData = $this->getFormModel()->formData;
					$userId   = FArrayHelper::getValue($formData, $element->name, '');
					if (!empty($userId) && !is_numeric($userId))
					{
						$user      = JFactory::getUser($userId);
						$newUserId = $user->get('id');

						if (empty($newUserId) && FabrikWorker::isEmail($userId))
						{
							$db    = $this->_db;
							$query = $db->getQuery(true)
								->select($db->qn('id'))
								->from($db->qn('#__users'))
								->where($db->qn('email') . ' = ' . $db-- > q($userId));
							$db->setQuery($query, 0, 1);

							$newUserId = (int) $db->loadResult();
						}
						$data[$element->name] = $newUserId;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Should the element's value be replaced with the current user's id
	 *
	 * @return  bool
	 */
	protected function updateOnEdit()
	{
		$params       = $this->getParams();
		$updateOnEdit = $params->get('update_on_edit', 0);

		if ($updateOnEdit == 1)
		{
			$updateOnEdit = !$this->canUse() || $this->getElement()->hidden == 1;
		}

		if ($updateOnEdit == 2)
		{
			$updateOnEdit = true;
		}

		return $updateOnEdit;
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
	 * @param   string $view View list/form @since 3.0.7
	 *
	 * @return  bool  can view or not
	 */
	public function canView($view = 'form')
	{
		if ($this->app->input->get('task', '') == 'processForm')
		{
			return true;
		}

		return parent::canView($view);
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$opts = parent::elementJavascriptOpts($repeatCounter);
		$id   = $this->getHTMLId($repeatCounter);

		return array('FbUser', $id, $opts);
	}



	/**
	 * Get select option label
	 *
	 * @param  bool $filter get alt label for filter, if present using :: splitter
	 *
	 * @return  string
	 */
	protected function _getSelectLabel($filter = false)
	{
		$label  = $this->getParams()->get('user_noselectionlabel');

		if (strstr($label, '::'))
		{
			$labels = explode('::', $label);
			$label  = $filter ? $labels[1] : $labels[0];
		}

		if (!$filter && $label == '')
		{
			$label = 'COM_FABRIK_PLEASE_SELECT';
		}

		return FText::_($label);
	}

	/**
	 * Create the SQL select 'name AS alias' segment for list/form queries
	 *
	 * @param   array &$aFields   array of element names
	 * @param   array &$aAsFields array of 'name AS alias' fields
	 * @param   array $opts       options
	 *
	 * @return  void
	 */
	public function getAsField_html(&$aFields, &$aAsFields, $opts = array())
	{
		$table      = $this->actualTableName();
		$element    = $this->getElement();
		$db         = FabrikWorker::getDbo();
		$fullElName = FArrayHelper::getValue($opts, 'alias', $table . '___' . $element->name);

		// Check if main database is the same as the elements database
		if ($this->inJDb())
		{
			/**
			 * it is so continue as if it were a database join
			 * make sure same connection as this table
			 */

			$join = $this->getJoin();

			// $$$ rob in csv import keytable not set
			$k  = isset($join->keytable) ? $join->keytable : $join->join_from_table;
			$k  = FabrikString::safeColName($k . '.' . $element->name);
			$k2 = FabrikString::safeColName($this->getJoinLabelColumn());

			if (FArrayHelper::getValue($opts, 'inc_raw', true))
			{
				$aFields[]   = $k . ' AS ' . $db->qn($fullElName . '_raw');
				$aAsFields[] = $db->qn($fullElName . '_raw');
			}

			$aFields[]   = $k2 . ' AS ' . $db->qn($fullElName);
			$aAsFields[] = $db->qn($fullElName);
		}
		else
		{
			$k = $db->qn($table) . '.' . $db->qn($element->name);

			// Its not so revert back to selecting the id
			$aFields[]   = $k . ' AS ' . $db->qn($fullElName . '_raw');
			$aAsFields[] = $db->qn($fullElName . '_raw');
			$aFields[]   = $k . ' AS ' . $db->qn($fullElName);
			$aAsFields[] = $db->qn($fullElName);
		}
	}

	/**
	 * Called when the element is saved
	 *
	 * @param   array $data posted element save data
	 *
	 * @return  bool  save ok or not
	 */
	public function onSave($data)
	{
		$params = json_decode($data['params']);

		if (!$this->canEncrypt() && !empty($params->encrypt))
		{
			throw new InvalidArgumentException('The encryption option is only available for field and text area plugins');
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
	 * @param   array $data Form data
	 *
	 * @return mixed
	 */
	public function getDefaultValue($data = array())
	{
		if (!isset($this->default))
		{
			$this->default = $this->user->get('id');
		}

		return $this->default;
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array $data          form data
	 * @param   int   $repeatCounter when repeating joined groups we need to know what part of the array to access
	 * @param   array $opts          options
	 *
	 * @return  string    value
	 */
	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		if ($this->getListModel()->importingCSV)
		{
			return parent::getValue($data, $repeatCounter, $opts);
		}

		$input = $this->app->input;

		// Kludge for 2 scenarios
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
			// as otherwise not empty validation failed on user element
			if (!in_array($input->get('task'), array('processForm', 'view', '', 'form.process', 'process')))
			{
				return '';
			}

			return $this->getDefaultOnACL($data, $opts);
		}

		return parent::getValue($data, $repeatCounter, $opts);
	}

	/**
	 * Get the table filter for the element
	 *
	 * @param   int  $counter filter order
	 * @param   bool $normal  do we render as a normal filter or as an advanced search filter
	 *                        if normal include the hidden fields as well (default true, use false for advanced filter
	 *                        rendering)
	 *
	 * @return  string    filter html
	 */
	public function getFilter($counter = 0, $normal = true)
	{
		$listModel = $this->getlistModel();
		$formModel = $listModel->getFormModel();
		$elName2   = $this->getFullName(false, false);

		if (!$formModel->hasElement($elName2))
		{
			return '';
		}

		$element = $this->getElement();

		$elName = $this->getFullName(true, false);
		$v      = $this->filterName($counter, $normal);

		// Correct default got
		$default                   = $this->getDefaultFilterVal($normal, $counter);
		$this->filterDisplayValues = array($default);
		$return                    = array();
		$tableType                 = $this->getLabelOrConcatVal();
		$join                      = $this->getJoin();
		$joinTableName             = FabrikString::safeColName($join->table_join_alias);

		// If filter type isn't set was blowing up in switch below 'cos no $rows
		// so added '' to this test.  Should probably set $element->filter_type to a default somewhere.
		if (in_array($element->filter_type, array('range', 'dropdown', '', 'checkbox')))
		{
			$rows = $this->filterValueList($normal, '', $joinTableName . '.' . $tableType, '', false);
			$rows = (array) $rows;
			$this->getFilterDisplayValues($default, $rows);

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
			case 'range':
				$this->rangedFilterFields($default, $return, $rows, $v, 'list');
				break;
			case 'dropdown':
			case 'multiselect':
			default:
				$return[] = $this->selectFilter($rows, $default, $v);
				break;

			case 'field':
				$return[] = $this->singleFilter($default, $v);
				break;

			case 'hidden':
				$return[] = $this->singleFilter($default, $v, 'hidden');
				break;

			case 'auto-complete':
				$defaultLabel = $this->getLabelForValue($default);
				$autoComplete = $this->autoCompleteFilter($default, $v, $defaultLabel, $normal);
				$return       = array_merge($return, $autoComplete);
				break;
		}

		if ($normal)
		{
			$return[] = $this->getFilterHiddenFields($counter, $elName, false, $normal);
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
	protected function buildFilterJoin()
	{
		$params        = $this->getParams();
		$joinTable     = FabrikString::safeColName($params->get('join_db_name'));
		$join          = $this->getJoin();
		$joinTableName = FabrikString::safeColName($join->table_join_alias);
		$joinKey       = $this->getJoinValueColumn();
		$elName        = FabrikString::safeColName($this->getFullName(true, false));

		return 'INNER JOIN ' . $joinTable . ' AS ' . $joinTableName . ' ON ' . $joinKey . ' = ' . $elName;
	}

	/**
	 * Build the filter query for the given element.
	 * Can be overwritten in plugin - e.g. see checkbox element which checks for partial matches
	 *
	 * @param   string $key           element name in format `tablename`.`elementname`
	 * @param   string $condition     =/like etc.
	 * @param   string $value         search string - already quoted if specified in filter array options
	 * @param   string $originalValue original filter value without quotes or %'s applied
	 * @param   string $type          filter type advanced/normal/prefilter/search/querystring/searchall
	 * @param   string  $evalFilter     evaled
	 *                                  
	 * @return  string    sql query part e,g, "key = value"
	 */
	public function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal', $evalFilter = '0')
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

		if ($type == 'querystring' || $type == 'jpluginfilters')
		{
			$key = FabrikString::safeColNameToArrayKey($key);
			/* $$$ rob no matter whether you use elementname_raw or elementname in the querystring filter
			 * by the time it gets here we have normalized to elementname. So we check if the original qs filter was looking at the raw
			 * value if it was then we want to filter on the key and not the label
			 */
			$filter = JFilterInput::getInstance();
			$get    = $filter->clean($_GET, 'array');

			if (!array_key_exists($key, $get))
			{
				$key = $this->_db->qn($joinTableName . '.id');
				$this->encryptFieldName($key);

				return $key . ' ' . $condition . ' ' . $value;
			}
		}

		if ($type == 'advanced')
		{
			$key = $this->_db->qn($joinTableName . '.id');
			$this->encryptFieldName($key);

			return $key . ' ' . $condition . ' ' . $value;
		}

		if ($type !== 'prefilter' && $type !== 'menuPrefilter')
		{
			switch ($element->filter_type)
			{
				case 'range':
				case 'dropdown':
					$tableType = 'id';
					break;
				case 'field':
				default:
					$tableType = $this->getLabelOrConcatVal();
					break;
			}

			$k = $this->_db->qn($joinTableName . '.' . $tableType);
		}
		else
		{
			if ($this->_rawFilter)
			{
				$k = $this->_db->qn($joinTableName . '.id');
			}
			else
			{
				$tableType = $this->getLabelOrConcatVal();
				$k         = $this->_db->qn($joinTableName . '.' . $tableType);
			}
		}

		$this->encryptFieldName($k);
		$str = $k . ' ' . $condition . ' ' . $value;

		return $str;
	}

	/**
	 * Get the database object
	 *
	 * @return  object    database
	 */
	public function getDb()
	{
		return FabrikWorker::getDbo(true);
	}

	/**
	 * Used to format the data when shown in the form's email
	 *
	 * @param   mixed $value         element's data
	 * @param   array $data          form records data
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    formatted value
	 */
	public function getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		$key    = $this->getFullName(true, false);
		$rawKey = $key . '_raw';
		$userId = $value;

		if (array_key_exists($rawKey, $data))
		{
			$userId = $data[$rawKey];
		}
		elseif (array_key_exists($key, $data))
		{
			$userId = $data[$key];
		}

		if ($this->getGroup()->canRepeat())
		{
			$userId = FArrayHelper::getValue($userId, $repeatCounter, 0);
		}

		if (is_array($userId))
		{
			$userId = (int) array_shift($userId);
		}
		else
		{
			// Test json string e.g. ["350"] - fixes JUser: :_load: User does not exist notices
			if (!is_int($userId))
			{
				$userId = FabrikWorker::JSONtoData($userId, true);
				$userId = (int) FArrayHelper::getValue($userId, 0, 0);
			}
		}

		if ($userId === 0)
		{
			return '';
		}

		$user = JFactory::getUser($userId);

		return $this->getUserDisplayProperty($user);
	}

	/**
	 * Get the user's property to show, if gid raise warning and revert to username (no gid in J1.7)
	 *
	 * @param   object $user Joomla user
	 *
	 * @since    3.0b
	 *
	 * @return  string
	 */
	protected function getUserDisplayProperty($user)
	{
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
		$join = $this->getJoin();
		$db   = FabrikWorker::getDbo();

		if ((string) $join->table_join_alias === '')
		{
			return '';
		}

		return $db->qn($join->table_join_alias . '.id');
	}

	/**
	 * Used for the name of the filter fields
	 * Over written here as we need to get the label field for field searches
	 *
	 * @return string element filter name
	 */
	public function getFilterFullName()
	{
		$elName = $this->getFullName(true, false);

		return FabrikString::safeColName($elName);
	}

	/**
	 * Called when copy row list plugin called
	 *
	 * @param   mixed $val value to copy into new record
	 *
	 * @return mixed value to copy into new record
	 */
	public function onCopyRow($val)
	{
		$params = $this->getParams();

		if ($params->get('update_on_edit'))
		{
			$val = $this->user->get('id');
		}

		return $val;
	}

	/**
	 * Called when save as copy form button clicked
	 *
	 * @param   mixed $val value to copy into new record
	 *
	 * @return  mixed  value to copy into new record
	 */
	public function onSaveAsCopy($val)
	{
		$params = $this->getParams();

		if ($params->get('update_on_copy', false))
		{
			$val = $this->user->get('id');
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
		$params       = $this->getParams();
		$displayParam = $params->get('my_table_data', 'username');

		if ($displayParam == 'gid')
		{
			$displayParam == 'username';

			if (!isset($displayMessage))
			{
				$this->app->enqueueMessage(JText::sprintf('PLG_ELEMENT_USER_NOTICE_GID', $this->getElement()->id), 'notice');
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
	 * @param   int $repeatCounter repeat group counter
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
