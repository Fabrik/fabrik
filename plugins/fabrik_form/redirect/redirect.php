<?php
/**
 * Redirect the user when the form is submitted
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.redirect
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Redirect the user when the form is submitted
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.redirect
 * @since       3.0
 */
class PlgFabrik_FormRedirect extends PlgFabrik_Form
{
	/*
	 * Cache the navIds for "save and next" so we don't run the queries twice
	 *
	 * @var  object
	 */
	private $navIds = null;

	/**
	 * Process the plugin, called after form is submitted
	 *
	 * @return  bool
	 */
	public function onLastProcess()
	{
		$formModel = $this->getModel();
		$params = $this->getParams();
		$context = $formModel->getRedirectContext();

		// Get existing session params
		$surl = (array) $this->session->get($context . 'url', array());
		$stitle = (array) $this->session->get($context . 'title', array());
		$smsg = (array) $this->session->get($context . 'msg', array());
		$sshowsystemmsg = (array) $this->session->get($context . 'showsystemmsg', array());

		$this->formModel = $formModel;
		$w = new FabrikWorker;

		$form = $formModel->getForm();
		$this->data = $this->getProcessData();

		$this->data['append_jump_url'] = $params->get('append_jump_url');
		$this->data['save_and_next'] = $params->get('save_and_next', '0');
		$this->data['save_in_session'] = $params->get('save_insession');
		$this->data['jump_page'] = $w->parseMessageForPlaceHolder($params->get('jump_page'), $this->data);
		$this->data['thanks_message'] = $w->parseMessageForPlaceHolder($params->get('thanks_message'), $this->data);

		if (!$this->shouldRedirect($params))
		{
			// Clear any session redirects
			unset($surl[$this->renderOrder]);
			unset($stitle[$this->renderOrder]);
			unset($smsg[$this->renderOrder]);
			unset($sshowsystemmsg[$this->renderOrder]);

			$this->session->set($context . 'url', $surl);
			$this->session->set($context . 'title', $stitle);
			$this->session->set($context . 'msg', $smsg);
			$this->session->set($context . 'showsystemmsg', $sshowsystemmsg);

			return true;
		}

		$this->_storeInSession();
		$sshowsystemmsg[$this->renderOrder] = true;
		$this->session->set($context . 'showsystemmsg', $sshowsystemmsg);

		if ($this->data['save_and_next'] === '1')
		{
			$navIds = $this->getNavIds();
			$next_rowid = $navIds->next == $navIds->last ? '' : '&rowid=' . $navIds->next;
			$itemId = FabrikWorker::itemId();

			if ($this->app->isAdmin())
			{
				$url = 'index.php?option=com_' . $this->package . '&task=form.view&formid=' . $form->id . $keyIdentifier;
			}
			else
			{
				$url = 'index.php?option=com_' . $this->package . '&view=form&Itemid=' . $itemId . '&formid=' . $form->id  . '&listid=' . $formModel->getListModel()->getId();
			}

			$url .= $next_rowid;
			$this->data['jump_page'] = JRoute::_($url);
		}

		if ($this->data['jump_page'] != '')
		{
			$this->data['jump_page'] = $this->buildJumpPage();

			// 3.0 ajax/module redirect logic handled in form controller not in plugin
			$surl[$this->renderOrder] = $this->data['jump_page'];
			$this->session->set($context . 'url', $surl);
			$this->session->set($context . 'redirect_content_how', $params->get('redirect_content_how', 'popup'));
			$this->session->set($context . 'redirect_content_popup_width', $params->get('redirect_content_popup_width', '300'));
			$this->session->set($context . 'redirect_content_popup_height', $params->get('redirect_content_popup_height', '300'));
			$this->session->set($context . 'redirect_content_popup_x_offset', $params->get('redirect_content_popup_x_offset', '0'));
			$this->session->set($context . 'redirect_content_popup_y_offset', $params->get('redirect_content_popup_y_offset', '0'));
			$this->session->set($context . 'redirect_content_popup_title', $params->get('redirect_content_popup_title', ''));
			$this->session->set($context . 'redirect_content_popup_reset_form', $params->get('redirect_content_popup_reset_form', '1'));
		}
		else
		{
			$msg = $this->data['thanks_message'];

			// Redirect not working in admin.
			if (!$this->app->isAdmin())
			{
				$sshowsystemmsg[$this->renderOrder] = false;
				$this->session->set($context . 'showsystemmsg', $sshowsystemmsg);

				$stitle[$this->renderOrder] = $form->label;
				$this->session->set($context . 'title', $stitle);


				$surl[$this->renderOrder] = 'index.php?option=com_' . $this->package . '&view=plugin&g=form&plugin=redirect&method=displayThanks&task=pluginAjax';
				$this->session->set($context . 'url', $surl);
			}
		}

		$smsg[$this->renderOrder] = $this->data['thanks_message'];

		// Don't display system message if thanks is empty
		if (FArrayHelper::getValue($this->data, 'thanks_message', '') !== '')
		{
			$this->session->set($context . 'msg', $smsg[$this->renderOrder]);
		}

		return true;
	}

	/**
	 * Called via ajax
	 * displays thanks message
	 *
	 * @return  void
	 */
	public function onDisplayThanks()
	{
		$this->displayThanks();
	}

	/**
	 * Once the form has been successfully completed, and if no jump page is
	 * specified then show the thanks message
	 *
	 * @param   string  $title    Thanks message title @deprecated - set in session in onLastProcess
	 * @param   string  $message  Thanks message string @deprecated - set in session in onLastProcess
	 *
	 * @return  void
	 */
	protected function displayThanks($title = '', $message = '')
	{
		$input = $this->app->input;
		$formdata = $this->session->get('com_' . $this->package . '.form.data');
		$context = 'com_' . $this->package . '.form.' . $formdata['formid'] . '.redirect.';
		$title = (array) $this->session->get($context . 'title', $title);
		$title = array_shift($title);
		$message = $this->session->get($context . 'msg', $message);

		if ($input->get('fabrik_ajax'))
		{
			// 3.0 - standardize on msg/title options.
			$opts = new stdClass;
			$opts->title = $title;
			$opts->msg = $message;
			echo json_encode($opts);
		}
		else
		{
			// $$$ hugh - it's an array, need to bust it up.
			if (is_array($message))
			{
				$message = implode('<br />', $message);
			}
?>
<div class="componentheading"><?php echo $title ?></div>
<p><?php echo $message ?></p>
			<?php
		}
	}

	/**
	 * Alter the returned plugin manager's result
	 *
	 * @param   string  $method  Plugin method
	 *
	 * @return bool
	 */
	public function customProcessResult($method)
	{
		$input = $this->app->input;
		$formModel = $this->getModel();

		// If we are applying the form don't run redirect
		if (is_array($formModel->formData) && array_key_exists('apply', $formModel->formData))
		{
			return true;
		}

		if ($method != 'onLastProcess')
		{
			return true;
		}

		if ($input->get('fabrik_ajax'))
		{
			// Return false to stop the default redirect occurring
			return false;
		}
		else
		{
			if (!empty($this->data['jump_page']))
			{
				// Ajax form submit load redirect page in mocha window
				if (strstr($this->data['jump_page'], "?"))
				{
					$this->data['jump_page'] .= "&tmpl=component";
				}
				else
				{
					$this->data['jump_page'] .= "?tmpl=component";
				}

				return false;
			}
			else
			{
				return true;
			}
		}
	}

	/**
	 * Takes the forms data and merges it with the jump page
	 *
	 * @return new jump page
	 */
	protected function buildJumpPage()
	{
		/* $$$rob - I've tested the issue reported in rev 1268
		 * where Hugh added a force call to getTable() in elementModel->getFullName() to stop the wrong table name
		 * being appended to the element name. But I can't reproduce the issue (Testing locally php 5.2.6 on my Gigs table)
		 *  if there is still an issue it would make a lot more sense to manually set the element's table model rather than calling
		 * force in the getFullName() code - as doing so increases the table query count by a magnitude of 2
		 */
		$formModel = $this->getModel();
		$jumpPage = $this->data['jump_page'];
		$reserved = array('format', 'view', 'layout', 'task');
		$queryvars = array();

		if ($this->data['append_jump_url'] == '1')
		{
			$groups = $formModel->getGroupsHiarachy();

			foreach ($groups as $group)
			{
				$elements = $group->getPublishedElements();
				$tmpData = !is_null($formModel->fullFormData) ? $formModel->fullFormData : $formModel->formDataWithTableName;

				foreach ($elements as $elementModel)
				{
					$name = $elementModel->getFullName(true, false);

					if (array_key_exists($name, $tmpData))
					{
						$this->_appendQS($queryvars, $name, $tmpData[$name]);
					}
					else
					{
						$element = $elementModel->getElement();

						if (array_key_exists($element->name, $tmpData))
						{
							$this->_appendQS($queryvars, $element->name, $tmpData[$element->name]);
						}
					}
				}
			}
		}

		$isMabmot = $this->app->input->get('isMambot', false);

		if ($isMabmot)
		{
			$queryvars['isMambot'] = 'isMambot=1';
		}

		if (empty($queryvars))
		{
			return $jumpPage;
		}

		$jumpPage .= (!strstr($jumpPage, "?")) ? "?" : "&";
		$jumpPage .= implode('&', $queryvars);

		return $jumpPage;
	}

	/**
	 * Append data to query string array
	 *
	 * @param   array   &$queryvars  Previously added querystring variables
	 * @param   string  $key         Key
	 * @param   mixed   $val         Value string or array
	 * @param	bool	$appendEmpty	Append even if value is empty, default true
	 *
	 * @return  void
	 */
	protected function _appendQS(&$queryvars, $key, $val, $appendEmpty = true)
	{
		if (is_array($val))
		{
			if (count($val) === 1)
			{
				$this->_appendQS($queryvars, $key, $val[0], $appendEmpty);
			}
			else
			{
				foreach ($val as $v)
				{
					$this->_appendQS($queryvars, "{$key}[value][]", $v, $appendEmpty);
				}
			}
		}
		else
		{
			if ($appendEmpty || ($appendEmpty && !empty($val)))
			{
				$val = urlencode(stripslashes($val));
				$queryvars[] = $key . '=' . $val;
			}
		}
	}

	/**
	 * Data is stored in session com_fabrik.searchform.form'.$formModel->get('id').'.filters
	 * listfilters looks up the com_fabrik.searchform.fromForm session var to then be able to pick up
	 * the search form data.
	 * Once its got it it unsets com_fabrik.searchform.fromForm so that the search values are not reused
	 * (they are however stored in the session so behave like normal filters afterwards)
	 * If the listfilter does find the com_fabrik.searchform.fromForm var it won't use any session filters
	 *
	 * @return void
	 */
	protected function _storeInSession()
	{
		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$listModel = $formModel->getlistModel();
		$input = $this->app->input;
		$store = array();

		$pk = $listModel->getPrimaryKey(true);

		if ($this->data['save_in_session'] == '1')
		{
			/*
			 * Was using simply formData but, for a form set to record in db its keys were
			 * in the short format whilst we compare the full name in the code below
			 */
			$tmpData = $formModel->formDataWithTableName;
			$groups = $formModel->getGroupsHiarachy();

			foreach ($groups as $group)
			{
				$elements = $group->getPublishedElements();

				foreach ($elements as $element)
				{
					if ($element->getElement()->name == 'fabrik_list_filter_all')
					{
						continue;
					}

					$name = $element->getFullName();

					if ($name == $pk)
					{
						continue;
					}

					if (array_key_exists($name, $tmpData))
					{
						$value = $tmpData[$name];
						$match = $element->getElement()->filter_exact_match;

						if (!is_array($value))
						{
							$value = array($value);
						}

						$c = 0;

						foreach ($value as $v)
						{
							if (count($value) == 1 || $c == 0)
							{
								$join = 'AND';
								$grouped = false;
							}
							else
							{
								$join = 'OR';
								$grouped = true;
							}

							if ($v != '')
							{
								$store['join'][] = $join;
								$store['key'][] = FabrikString::safeColName($name);
								$store['condition'][] = '=';
								$store['search_type'][] = 'search';
								$store['access'][] = 0;
								$store['grouped_to_previous'][] = $grouped;
								$store['eval'][] = FABRIKFILTER_TEXT;
								$store['required'][] = false;
								$store['value'][] = $v;
								$store['full_words_only'][] = false;
								$store['match'][] = $match;
								$store['hidden'][] = 0;
								$store['elementid'][] = $element->getElement()->id;
							}

							$c++;
						}
					}
				}
			}

			// Set registry search form entries
			$key = 'com_' . $this->package . '.searchform';
			$id = $formModel->get('id');

			// Check for special fabrik_list_filter_all element!
			$searchAll = $input->get($listModel->getTable()->db_table_name . '___fabrik_list_filter_all');

			$this->app->setUserState($key . '.form' . $id . '.searchall', $searchAll);
			$this->app->setUserState($key . '.form' . $id . '.filters', $store);

			$this->app->setUserState($key. '.fromForm', $id);
		}
	}

	/**
	 * Determines if a condition has been set and decides if condition is matched
	 *
	 * @param   object  $params  Plugin params
	 *
	 * @return bool true if you should redirect, false ignores redirect
	 */
	protected function shouldRedirect($params)
	{
		// If we are applying the form don't run redirect
		if (array_key_exists('apply', $this->formModel->formData))
		{
			return false;
		}

		/**
		 * If noredirect QS present and non 0, don't redirect.
		 * Used by things like frontend add option on joins to squash any redirection on the join's form.
		 */
		if (FArrayHelper::getValue($this->formModel->formData, 'noredirect', 0) !== 0)
		{
			return false;
		}

		$params = $this->getParams();
		return $this->shouldProcess('redirect_conditon', null, $params);
	}

	/**
	 * Get the first last, prev and next record ids
	 *
	 * @return  object
	 */
	protected function getNavIds()
	{
		if (isset($this->navIds))
		{
			return $this->navIds;
		}

		$formModel = $this->getModel();
		$listModel = $formModel->getListModel();
		$listModel->filters = null;
		$filterModel = $listModel->getFilterModel();
		$filterModel->destroyRequest();
		$this->app->input->set('view', 'list');
		$listref = $listModel->getId() . '_com_' . $this->package . '_' . $listModel->getId();
		$this->app->input->set('listref', $listref);
		$table = $listModel->getTable();
		$db = $listModel->getDb();
		$query = $db->getQuery(true);

		// As we are selecting on primary key we can select all rows - 3000 records load in 0.014 seconds
		$query->select($table->db_primary_key)->from($table->db_table_name);
		$query = $listModel->buildQueryJoin($query);
		$query = $listModel->buildQueryWhere(true, $query);
		$query = $listModel->buildQueryOrder($query);

		foreach ($listModel->orderEls as $orderName)
		{
			$orderName = FabrikString::safeColNameToArrayKey($orderName);
			$query->select(FabrikString::safeColName($orderName) . ' AS ' . $orderName);
		}

		$db->setQuery($query);
		$rows = $db->loadColumn();
		$keys = array_flip($rows);
		$o = new stdClass;
		$o->index = FArrayHelper::getValue($keys, $formModel->getRowId(), 0);
		$o->first = $rows[0];
		$o->lastKey = count($rows) - 1;
		$o->last = $rows[$o->lastKey];
		$o->next = $o->index + 1 > $o->lastKey ? $o->lastKey : $rows[$o->index + 1];
		$o->prev = $o->index - 1 < 0 ? 0 : $rows[$o->index - 1];
		$this->navIds = $o;

		$this->app->input->set('view','form');

		return $this->navIds;
	}
}
