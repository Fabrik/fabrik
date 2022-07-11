<?php
/**
 * Plugin element to render internal id
 * @package       fabrikar
 * @author        Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license       http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

class PlgFabrik_ElementLockrow extends PlgFabrik_Element
{

	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'VARCHAR(32)';

	private function getCustomMsg($lang, $data)
	{
		$params = $this->getParams();
		$param = strtolower(str_replace('PLG_ELEMENT_', '', $lang));
		$lockedMsg = $params->get($param, '');
		$lockedMsg = empty($lockedMsg) ? $lang : $lockedMsg;
		return Text::sprintf($lockedMsg, ...$data);
	}

	public function isSubmitLocked($value, $thisUserId = null)
	{
		// new records can't be locked
		if ($this->getFormModel()->isNewRecord())
		{
			return false;
		}

		if (!empty($value))
		{
			$params      = $this->getParams();
			$allowReedit = $params->get('lockrow_allow_user_reedit', '1');
			$origData    = $this->getFormModel()->getOrigData();
			$origData    = FArrayHelper::fromObject(FArrayHelper::getValue($origData, 0, new stdClass));
			$elName      = $this->getFullName(true, false);
			$origValue   = FArrayHelper::getValue(
				$origData,
				$elName . '_raw',
				FArrayHelper::getValue($origData, $elName, '')
			);

			$params      = $this->getParams();
			$allowReedit = $params->get('lockrow_allow_user_reedit', '1');
			$allowTimeout = $params->get('lockrow_allow_timeout_submit', '0') === '1';

			if (!isset($thisUserId))
			{
				$this_user  = JFactory::getUser();
				$thisUserId = (int) $this_user->get('id');
			}
			else
			{
				$thisUserId = (int) $thisUserId;
			}

			list($time, $lockingUserId) = explode(';', $value);
			list($origTime, $origLockingUserId) = explode(';', $origValue);

			$ttl            = (int) $params->get('lockrow_ttl', '60');
			$ttlTime       = (int) $time + ($ttl * 60);
			$timeNow       = time();
			$expireTime    = $ttlTime - $timeNow;
			$expireMinutes = round($expireTime / 60);

			$timedOut = $ttlTime > 0 && $timeNow > $ttlTime;

			// if user ids are different, it's locked, regardless
			if ((int) $lockingUserId !== (int) $origLockingUserId || (int)$lockingUserId !== (int)$thisUserId)
			{
				$origLockingUser = JFactory::getUser($origLockingUserId);
				$this->app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_SUBMIT_NOT_OWNER_MSG', [$origLockingUser->username, $origLockingUser->name]));

				return true;
			}

			if ($allowReedit === '0' || $allowReedit === '2')
			{
				// if allow reedit is 0 (no) or 2 (exclusive), locked if this != orig lock or timed out
				if ($value !== $origValue)
				{
					if ($expireMinutes < 0)
					{
						$this->app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_SUBMIT_WRONG_LOCK_EXPIRED_MSG', abs($expireMinutes)));
					}
					else
					{
						$this->app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_SUBMIT_WRONG_LOCK_MSG', [abs($expireMinutes)]));
					}

					return true;
				}

				if ($timedOut && !$allowTimeout)
				{
					$this->app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_SUBMIT_TIMEOUT_MSG', []));

					return true;
				}
			}
			else if ($allowReedit === '1')
			{
				// if allow reedit is 1 (yes), only locked if this lock is timed out
				if ($timedOut && !$allowTimeout)
				{
					$this->app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_SUBMIT_TIMEOUT_MSG', []));

					return true;
				}
			}
		}
		else
		{
			// if no lock data, barf
			$this->app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_SUBMIT_ERROR_MSG', []));

			return true;
		}

		// if we made it this far, the record isn't locked
		return false;
	}

	public function isLocked($value, $thisUserId = null)
	{
		if (!empty($value))
		{
			if ($this->app->input->get('unlock_hash', '') === md5($value))
			{
				return false;
			}

			$params      = $this->getParams();
			$allowReedit = $params->get('lockrow_allow_user_reedit', '1');

			if (!isset($thisUserId))
			{
				$thisUserId = (int) $this->user->get('id');
			}
			else
			{
				$thisUserId = (int) $thisUserId;
			}

			list($time, $lockingUserId) = explode(';', $value);

			$params  = $this->getParams();
			$ttl     = (int) $params->get('lockrow_ttl', '60');
			$ttlTime = (int) $time + ($ttl * 60);
			$timeNow = time();

			if ($ttl > 0 && $timeNow > $ttlTime)
			{
				return false;
			}

			if ((int) $thisUserId === (int) $lockingUserId)
			{
				if ($allowReedit === '0')
				{
					return true;
				}
			}
		}

		return false;
	}

	public function isLockOwner($value, $thisUserId = null)
	{
		if (!empty($value))
		{
			if (!isset($thisUserId))
			{
				$thisUserId = (int) $this->user->get('id');
			}
			else
			{
				$thisUserId = (int) $thisUserId;
			}

			list($time, $lockingUserId) = explode(';', $value);

			if ((int) $thisUserId === (int) $lockingUserId)
			{
				return true;
			}
		}

		return false;
	}

	public function showLocked($value, $thisUserId = null)
	{
		if (!empty($value))
		{
			list($time, $lockingUserId) = explode(';', $value);
			$params  = $this->getParams();
			$ttl     = (int) $params->get('lockrow_ttl', '24');
			$ttlTime = (int) $time + ($ttl * 60);
			$timeNow = time();
			if ($ttl === 0 || $timeNow < $ttlTime)
			{
				return true;
			}
		}

		return false;
	}

	private function canUnlock($value, $thisUserId = null)
	{
		$canUnlock = false;
		if (!empty($value))
		{
			$params = $this->getParams();

			if (!isset($thisUserId))
			{
				$thisUserId = (int) $this->user->get('id');
			}
			else
			{
				$thisUserId = (int) $thisUserId;
			}

			$access = $params->get('lockrow_lock_access', '');

			if (in_array($access, $this->user->getAuthorisedViewLevels()))
			{
				$canUnlock = true;
			}
			else
			{
				list($time, $lockingUserId) = explode(';', $value);
				$lockingUserId = (int) $lockingUserId;

				if ($thisUserId === $lockingUserId)
				{
					$canUnlock = true;
				}
			}
		}

		return $canUnlock;
	}

	private function canLock()
	{
		$params = $this->getParams();
		$access = $params->get('lockrow_lock_access', '');

		return in_array($access, $this->user->getAuthorisedViewLevels());
	}

	/**
	 * draws the form element
	 *
	 * @param int repeat group counter
	 *
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$app     = JFactory::getApplication();
		$name    = $this->getHTMLName($repeatCounter);
		$id      = $this->getHTMLId($repeatCounter);
		$params  = $this->getParams();
		$element = $this->getElement();
		$value   = $this->getValue($data, $repeatCounter);
		static $lockstr = null;

		$element->hidden = true;

		if ($this->app->input->get('view', '', 'string') === 'details')
		{
			return '';
		}

		$rowId = (int) $this->getFormModel()->getRowId();

		if (empty($rowId))
		{
			return "";
		}

		$ttl       = (int) $params->get('lockrow_ttl', '60');

		if ($this->app->input->get('unlock_hash', '') === md5($value))
		{
			$value = '';
		}

		if (!empty($value))
		{
			list($time, $lockingUserId) = explode(';', $value);
			$lockingUser = JFactory::getUser($lockingUserId);
			$ttlTime = (int) $time + ($ttl * 60);
			$timeNow = time();

			$thisUser = JFactory::getUser();
			// $$$ decide what to do about guests
			$thisUserId = $thisUser->get('id');

			if ((int) $thisUserId === (int) $lockingUserId)
			{
				if ($ttl === 0 || $timeNow < $ttlTime)
				{
					if ($params->get('lockrow_allow_user_reedit', '1') === '1')
					{
						$app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_RELOCKED_MSG', [$ttl]));
					}
					else if ($params->get('lockrow_allow_user_reedit', '1') === '2')
					{
						$app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_RELOCKED_EXCLUSIVE_MSG', [$ttl]));
					}
					else
					{
						if ($ttl === 0)
						{

							$app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_OWN_LOCKED_NO_EXPIRE_MSG', []));
						}
						else
						{
							$expireTime    = $ttlTime = $timeNow;
							$expireMinutes = round($expireTime / 60);
							$app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_OWN_LOCKED_MSG', [abs($expireMinutes)]));
						}

						return "";
					}
				}
				else
				{
					$app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_LOCK_EXPIRED_MSG', [$lockingUser->username, $lockingUser->name]));
				}
			}
			else
			{
				if ($timeNow < $ttlTime)
				{
					if ($this->canUnlock($value))
					{
						$url = 'index.php';
						$url .= '?option=' . $this->app->input->get('option');
						$url .= '&view=' . $this->app->input->get('view');
						$url .= '&formid=' . $this->app->input->get('formid');
						$url .= '&rowid=' . $this->app->input->get('rowid');
						$url .= '&Itemid=' . $this->app->input->get('Itemid');
						$url .= '&unlock_hash=' . md5($value);
						$url = Route::_($url, false);
						$app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_LOCKED_UNLOCK_MSG', [$ttl, $lockingUser->username, $lockingUser->name, $url]));
					}
					else
					{
						$app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_LOCKED_MSG', [$ttl, $lockingUser->username, $lockingUser->name]));
					}

					return "";
				}
				else
				{
					$app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_LOCK_EXPIRED_MSG', [$ttl]));
				}
			}
		}
		else
		{
			$app->enqueueMessage($this->getCustomMsg('PLG_ELEMENT_LOCKROW_LOCK_LOCKING_MSG', [$ttl]));
		}

		if (!isset($lockstr))
		{
			$dbTableName = $this->getTableName();
			$fieldName   = FabrikString::safeColName($this->getFullName(false, false));
			$listModel   = $this->getListModel();
			$pk          = $listModel->getTable()->db_primary_key;
			$db          = $listModel->getDb();
			$query       = $db->getQuery(true);

			$user    = JFactory::getUser();
			$userId  = $user->get('id');
			$lockstr = time() . ";" . $userId;

			$query->update($db->quoteName($dbTableName))
				->set($fieldName . ' = ' . $db->quote($lockstr))
				->where($pk . ' = ' . $db->quote($rowId));

			$db->setQuery($query);
			$db->execute();

			// $$$ @TODO - may need to clean com_content cache as well
			$cache = JFactory::getCache('com_fabrik');
			$cache->clean();
		}

		$layoutData        = new stdClass;
		$layoutData->id    = $id;
		$layoutData->name  = $name;
		$layoutData->value = $lockstr;
		$layoutData->type  = 'hidden';
		$layout            = $this->getLayout('form');

		return $layout->render($layoutData);
	}

	/**
	 * shows the data formatted for the table view
	 *
	 * @param string     $data
	 * @param stdClass  &$thisRow All the data in the lists current row
	 * @param array      $opts    Rendering options
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
		if (!isset($data))
		{
			$data = '';
		}

		$data = FabrikWorker::JSONtoData($data, true);

		for ($i = 0; $i < count($data); $i++)
		{
			$data[$i] = $this->_renderListData($data[$i], $thisRow, $opts);
		}

		$data = json_encode($data);

		return parent::renderListData($data, $thisRow, $opts);
	}

	function _renderListData($data, $thisRow, $opts)
	{
		$params   = $this->getParams();
		$showIcon = true;

		if ($params->get('lockrow_show_icon_read_only', '1') === '0')
		{
			$showIcon = $this->getListModel()->canEdit($thisRow);

			// show icon if we are the lock owner
			if (!$showIcon)
			{
				$showIcon = $this->isLocked($data, false) && $this->isLockOwner($data);
			}
		}

		if ($showIcon)
		{
			$layout           = $this->getLayout('list');
			$layoutData       = new StdClass();
			$layoutData->tmpl = isset($this->tmpl) ? $this->tmpl : '';

			if ($this->showLocked($data))
			{
				$layoutData->icon  = $params->get('lockrow_locked_icon', 'lock');
				$layoutData->alt   = 'Locked';
				$layoutData->class = 'fabrikElement_lockrow_locked';
				$layoutData->lockingUserId = explode(';',$data)[1];
			}
			else
			{
				$layoutData->icon  = $params->get('lockrow_unlocked_icon', 'unlock');
				$layoutData->alt   = 'Not Locked';
				$layoutData->class = 'fabrikElement_lockrow_unlocked';
				$layoutData->lockingUserId = '';
			}

			return $layout->render($layoutData);
		}

		return '';
	}

	function storeDatabaseFormat($val, $data)
	{
		return '0';
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */

	function getFieldDescription()
	{
		return "VARCHAR(32)";
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param int $repeatCounter Repeat group counter
	 *
	 * @return  array
	 */
	function elementJavascript($repeatCounter)
	{
		$id   = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		return array('FbLockrow', $id, $opts);
	}

	function isHidden()
	{
		return true;
	}

	public function elementListJavascript()
	{
		$user       = JFactory::getUser();
		$userId     = $user->get('id');
		$id         = $this->getHTMLId();
		$listModel  = $this->getListModel();
		$list       = $listModel->getTable();
		$data       = $listModel->getData();
		$gKeys      = array_keys($data);
		$elName     = $this->getFullName(true, false);
		$elNameRaw  = $elName . '_raw';
		$rowLocks   = array();
		$canUnlocks = array();
		$canLocks   = array();
		foreach ($gKeys as $gKey)
		{
			$groupedData = $data[$gKey];
			foreach ($groupedData as $rowkey)
			{
				$rowLocks[$rowkey->__pk_val]   = isset($rowkey->$elNameRaw) ? $this->showLocked($rowkey->$elNameRaw, $userId) : false;
				$canUnlocks[$rowkey->__pk_val] = isset($rowkey->$elNameRaw) ? $this->canUnlock($rowkey->$elNameRaw, $userId) : false;
				$canLocks[$rowkey->__pk_val]   = isset($rowkey->$elNameRaw) ? $this->canLock() : false;
			}
		}
		$opts = new stdClass();

		$crypt       = FabrikWorker::getCrypt('aes');
		$cryptUserId = $crypt->encrypt($userId);

		$opts->tableid     = $list->id;
		$opts->livesite    = COM_FABRIK_LIVESITE;
		$opts->imagepath   = COM_FABRIK_LIVESITE . '/plugins/fabrik_element/lockrow/images/';
		$opts->elid        = $this->getElement()->id;
		$opts->userid      = urlencode($cryptUserId);
		$opts->row_locks   = $rowLocks;
		$opts->can_unlocks = $canUnlocks;
		$opts->can_locks   = $canLocks;
		$opts->listRef     = $listModel->getRenderContext();
		$opts->formid      = $listModel->getFormModel()->getId();
		$opts->lockIcon    = FabrikHelperHTML::icon("icon-lock", '', '', true);
		$opts->unlockIcon  = FabrikHelperHTML::icon("icon-unlock", '', '', true);
		$opts->keyIcon     = FabrikHelperHTML::icon("icon-key", '', '', true);
		$opts              = json_encode($opts);

		return "new FbLockrowList('$id', $opts);\n";
	}

	function onAjax_unlock()
	{
		$input = $this->app->input;
		$this->setId($input->getInt('element_id'));
		$this->loadMeForAjax();
		$crypt = FabrikWorker::getCrypt('aes');
		$rowid     = $this->app->input->get('row_id', '', 'string');
		$userid    = $this->app->input->get('userid', '', 'string');

		$dbTableName = $this->getTableName();
		$field_name  = FabrikString::safeColName($this->getFullName(false, false));
		$listModel   = $this->getListModel();
		$pk          = $listModel->getTable()->db_primary_key;
		$db          = $listModel->getDb();
		$query       = $db->getQuery(true);
		$thisUserId  = $crypt->decrypt(urldecode($userid));

		$query->select($field_name)
			->from($db->quoteName($dbTableName))
			->where($pk . ' = ' . $db->quote($rowid));
		$db->setQuery($query);
		$value = $db->loadResult();

		$ret['status'] = 'unlocked';
		$ret['msg']    = 'Row unlocked';

		if (!empty($value))
		{
			if ($this->canUnlock($value, $thisUserId))
			{
				$query->clear()
					->update($db->quoteName($dbTableName))
					->set($field_name . ' = "0"')
					->where($pk . ' = ' . $db->quote($rowid));
				$db->setQuery($query);
				$db->execute();

				// $$$ @TODO - may need to clean com_content cache as well
				$cache = JFactory::getCache('com_fabrik');
				$cache->clean();
			}
			else
			{
				$ret['status'] = 'locked';
				$ret['msg']    = 'Row was not unlocked!';
			}
		}
		echo json_encode($ret);
	}

	function onAjax_lock()
	{
		$input = $this->app->input;
		$this->setId($input->getInt('element_id'));
		$this->loadMeForAjax();
		$rowid     = $this->app->input->get('row_id', '', 'string');

		$dbTableName = $this->getTableName();
		$fieldName   = FabrikString::safeColName($this->getFullName(false, false));
		$listModel   = $this->getListModel();
		$pk          = $listModel->getTable()->db_primary_key;
		$db          = $listModel->getDb();
		$query       = $db->getQuery(true);

		$ret['status'] = 'locked';
		$ret['msg']    = 'Row locked';

		if ($this->canLock())
		{
			$lockstr = time() . ";" . $this->user->get('id');

			$query->update($db->quoteName($dbTableName))
				->set($fieldName . ' = ' . $db->quote($lockstr))
				->where($pk . ' = ' . $db->quote($rowid));

			$db->setQuery($query);
			$db->execute();

			// $$$ @TODO - may need to clean com_content cache as well
			$cache = JFactory::getCache('com_fabrik');
			$cache->clean();
		}
		else
		{
			$ret['status'] = 'unlocked';
			$ret['msg']    = 'Row was not locked!';
		}

		echo json_encode($ret);
	}

}

?>
