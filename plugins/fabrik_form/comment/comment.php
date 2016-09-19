<?php
/**
 * Form Comment
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.comment
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Comment J Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikTableComment extends FabTable
{
	/**
	 * Object constructor to set table and key fields.
	 *
	 * @param   JDatabase  &$db  JDatabase connector object.
	 */

	public function __construct(&$db)
	{
		parent::__construct('#__{package}_comments', 'id', $db);
	}
}

/**
 * Insert a comment plugin into the bottom of the form
 * Various different plugin systems supported
 *  * Internal
 *  * disqus
 *  * Intensedebate
 *  * JComments
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.comment
 * @since       3.0
 */
class PlgFabrik_FormComment extends PlgFabrik_Form
{
	/**
	 * HTML comment form
	 *
	 * @var string
	 */
	protected $commentform = null;

	/**
	 * Comments locked
	 *
	 * @var bool
	 */
	protected $commentsLocked = null;

	protected $thumb = null;

	/**
	 * Get any html that needs to be written after the form close tag
	 *
	 * @return	string	html
	 */

	public function getEndContent_result()
	{
		return $this->data;
	}

	/**
	 * Determine if you can add new comments
	 *
	 * @return  bool
	 */

	protected function commentsLocked()
	{
		$params = $this->getParams();
		$formModel = $this->getModel();

		if (is_null($this->commentsLocked))
		{
			$this->commentsLocked = false;
			$lock = trim($params->get('comment_lock_element'));

			if ($lock !== '')
			{
				$lock = str_replace('.', '___', $lock) . '_raw';
				$lockVal = $formModel->data[$lock];

				if ($lockVal == 1)
				{
					$this->commentsLocked = true;
				}
			}
		}

		return $this->commentsLocked;
	}

	/**
	 * Sets up any end html (after form close tag)
	 *
	 * @return  void
	 */
	public function getEndContent()
	{
		$formModel = $this->getModel();
		$rowId = $formModel->getRowId();

		if ($rowId == '')
		{
			return;
		}

		$params = $this->getParams();
		$this->commentsLocked();
		$method = $params->get('comment_method', 'disqus');

		switch ($method)
		{
			default:
			case 'disqus':
				$this->_disqus();
				break;
			case 'intensedebate':
				$this->_intensedebate();
				break;
			case 'internal':
				$this->_internal();
				break;
			case 'jcomment':
				$this->_jcomment();
				break;
		}

		return true;
	}

	/**
	 * Get the js options for the thumb element
	 *
	 * @return  string  json option string
	 */
	protected function loadThumbJsOpts()
	{
		$input = $this->app->input;
		$opts = new stdClass;
		$thumb = $this->getThumb();
		$opts->livesite = COM_FABRIK_LIVESITE;
		$opts->row_id = $input->getString('rowid', '', 'string');
		$opts->voteType = 'comment';

		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/thumbs/images/', 'image', 'form', false);
		$opts->formid = $this->formModel->getId();
		$opts->j3 = FabrikWorker::j3();
		$opts->listid = $this->formModel->getListModel()->getTable()->id;
		$opts = json_encode($opts);

		return $opts;
	}

	/**
	 * Is the WYWIWYG option enabled for local commenting
	 *
	 * @return boolean
	 */
	protected function isWYSIWYG()
	{
		$params = $this->getParams();
		return $params->get('comment_internal_wysiwyg', '0') === '1';
	}

	/**
	 * Prepare local comment system
	 *
	 * @return  void
	 */
	protected function _internal()
	{
		$params = $this->getParams();
		$formModel = $this->getModel();
		$input = $this->app->input;
		$this->inJDb = $formModel->getListModel()->inJDb();
		$this->formModel = $formModel;
		$jsFiles = array();
		JHTML::stylesheet('plugins/fabrik_form/comment/comments.css');
		$jsFiles['Fabrik'] = 'media/com_fabrik/js/fabrik.js';
		$jsFiles['FabrikComment'] = 'plugins/fabrik_form/comment/comments.js';
		$jsFiles['InlineEdit'] = 'plugins/fabrik_form/comment/inlineedit.js';

		$thumbOpts = $this->doThumbs() ? $thumbOpts = $this->loadThumbJsOpts() : "{}";
		$rowId = $input->get('rowid', '', 'string');

		if (strstr($rowId, ':'))
		{
			// SLUG
			$rowId = array_shift(explode(':', $rowId));
		}

		$comments = $this->getComments($formModel->get('id'), $rowId);

		$layout = $this->getLayout('layout');
		$layoutData = new stdClass;
		$layoutData->commentCount = count($comments);
		$layoutData->showCountInTitle = $params->get('comment-show-count-in-title');
		$layoutData->commnents = $this->writeComments($params, $comments);
		$layoutData->commentsLocked = $this->commentsLocked;
		$layoutData->allowGuest = $params->get('comment-internal-allow-guest');
		$layoutData->userLoggedIn = $this->user->get('id') != 0;
		$layoutData->form = $this->getAddCommentForm(0, true);

		if ($this->doThumbs())
		{
			$thumb = $this->getThumb();
			$this->thumbCounts = $thumb->getListThumbsCount();
		}

		$opts = new stdClass;
		$opts->formid = $formModel->get('id');
		$opts->rowid = $rowId;
		$opts->admin = $this->user->authorise('core.delete', 'com_fabrik');
		$opts->label = '';
		$opts->wysiwyg = $this->isWYSIWYG();

		foreach ($formModel->data as $k => $v)
		{
			if (strstr($k, 'title'))
			{
				$opts->label = $v;
				break;
			}
		}

		$opts = json_encode($opts);
		JText::script('PLG_FORM_COMMENT_TYPE_A_COMMENT_HERE');
		JText::script('PLG_FORM_COMMENT_PLEASE_ENTER_A_COMMENT_BEFORE_POSTING');
		JText::script('PLG_FORM_COMMENT_PLEASE_ENTER_A_NAME_BEFORE_POSTING');
		JText::script('PLG_FORM_COMMENT_ENTER_EMAIL_BEFORE_POSTNG');
		$script = "var comments = new FabrikComment('fabrik-comments', $opts);";

		if ($this->doThumbs())
		{
			$jsFiles['FbThumbsList'] = 'plugins/fabrik_element/thumbs/list-thumbs.js';
			$script .= "\n comments.thumbs = new FbThumbsList(" . $this->formModel->getId() . ", $thumbOpts);";
		}

		FabrikHelperHTML::script($jsFiles, $script);

		$this->data = $layout->render($layoutData);
	}

	/**
	 * Can we add internal comments
	 *
	 * @return boolean
	 */
	private function canAddComment()
	{
		$params = $this->getParams();
		$anonymous = $params->get('comment-internal-allow-guest');

		return $this->user->get('id') == 0 && $anonymous == 0 ? false : true;
	}

	/**
	 * Build the html for the internal comment form
	 *
	 * @param   int   $reply_to  Comment id that we are replying to
	 * @param   bool  $master    Is it the master comment
	 *
	 * @return  string
	 */
	private function getAddCommentForm($reply_to = 0, $master = false)
	{
		$params = $this->getParams();
		$input = $this->app->input;

		if (!$this->canAddComment())
		{
			return;
		}

		$layout = $this->getLayout('form');
		$layoutData = new stdClass;
		$layoutData->formId = $master ? " id='master-comment-form' " : '';
		$layoutData->rating = $params->get('comment-internal-rating');
		$layoutData->anonymous = $params->get('comment-internal-anonymous');
		$layoutData->replyTo = $reply_to;
		$layoutData->notify = $params->get('comment_allow_user_subscriptions_to_notifications') == 1;
		$layoutData->name = trim($input->get('ide_people___voornaam', '', 'cookie') . ' ' . $input->get('ide_people___achternaam', '', 'cookie'));
		$layoutData->email = $input->get('ide_people___email', '', 'cookie');
		$layoutData->renderOrder = $this->renderOrder;
		$layoutData->wysiwyg = $this->isWYSIWYG();

		if ($layoutData->wysiwyg)
		{
			$cols = $params->get('comment_internal_wysiwyg_cols', '100');
			$rows = $params->get('comment_internal_wysiwyg_rows', '5');
			$layoutData->id = 'fabrik_form_comment_' . $layoutData->renderOrder . '_' . $reply_to;
			$editor = JEditor::getInstance($this->config->get('editor'));
			$buttons = (bool) $params->get('comment_internal_wysiwyg_extra_buttons', false);
			$layoutData->editor = $editor->display($layoutData->id, '', '100%', '100%', $cols, $rows, $buttons, $layoutData->id);
		}

		$layoutData->userLoggedIn = $this->user->get('id') != 0;

		return $layout->render($layoutData);
	}

	/**
	 * TODO replace parentid with left/right markers
	 * see http://dev.mysql.com/tech-resources/articles/hierarchical-data.html
	 * Get the comments from the db
	 *
	 * @param   int     $formId  Form id
	 * @param   string  $rowId   Row id
	 *
	 * @return  array	replies
	 */
	protected function getComments($formId, $rowId)
	{
		$formId = (int) $formId;
		$db = FabrikWorker::getDbo();
		$formModel = $this->setFormModel();
		$query = $db->getQuery(true);
		$query->select('c.*');
		$query->from('#__{package}_comments AS c');
		$this->inJDb = $formModel->getListModel()->inJDb();

		if ($this->inJDb)
		{
			$query->join('LEFT', '#__users AS u ON c.user_id = u.id');
		}

		$query->where('formid = ' . $formId . ' AND c.row_id = ' . $db->quote($rowId) . ' AND c.approved = 1')->order('c.time_date ASC');
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		$main = array();
		$replies = array();

		if (!is_array($rows))
		{
			return array();
		}

		foreach ($rows as $row)
		{
			if ($row->reply_to == 0)
			{
				$main[$row->id] = $row;
			}
			else
			{
				if (!array_key_exists($row->reply_to, $replies))
				{
					$replies[$row->reply_to] = array();
				}

				$replies[$row->reply_to][] = $row;
			}
		}

		$return = array();

		foreach ($main as $v)
		{
			$depth = 0;
			$v->depth = $depth;
			$return[$v->id] = $v;
			$this->getReplies($v, $replies, $return, $depth);
		}

		return $return;
	}

	/**
	 * Recursive method to append the replies to the comments
	 *
	 * @param   object  $v        current comment
	 * @param   array   $replies  replies
	 * @param   array   &$return  return data
	 * @param   int     $depth    current depth
	 *
	 * @return void
	 */
	private function getReplies($v, $replies, &$return, $depth)
	{
		$depth++;

		if (array_key_exists($v->id, $replies) && is_array($replies[$v->id]))
		{
			foreach ($replies[$v->id] as $row)
			{
				$row->depth = $depth;
				$return[$row->id] = $row;
				$this->getReplies($row, $replies, $return, $depth);
			}
		}
	}

	/**
	 * Generate the html for the comments
	 *
	 * @param   object  $params    plugin params
	 * @param   array   $comments  comments to write out
	 *
	 * @return  string
	 */
	private function writeComments($params, $comments)
	{
		$layout = $this->getLayout('comments');
		$layoutData = new stdClass;

		foreach ($comments as &$comment)
		{
			$comment->data = $this->writeComment($params, $comment);
		}

		$layoutData->comments = $comments;

		return $layout->render($layoutData);
	}

	/**
	 * Write a single comment
	 *
	 * @param   object  $params   plugin params
	 * @param   object  $comment  comment to write
	 *
	 * @return  string
	 */
	private function writeComment($params, $comment)
	{
		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_form/comment/images/', 'image', 'form', false);
		$input = $this->app->input;
		$layoutData = new stdClass;
		$layoutData->insrc = FabrikHelperHTML::image("star_in.png", 'form', @$this->tmpl, array(), true);
		$layoutData->name = (int) $comment->annonymous == 0 ? $comment->name : FText::_('PLG_FORM_COMMENT_ANONYMOUS_SHORT');
		$layoutData->comment = $comment;
		$layoutData->dateFormat = $params->get('comment-date-format');
		$layoutData->internalRating = $params->get('comment-internal-rating') == 1;
		$layoutData->canDelete = $this->user->authorise('core.delete', 'com_fabrik');
		$layoutData->canAdd = !$this->commentsLocked && $this->canAddComment();
		$layoutData->commentsLocked = $this->commentsLocked;
		$layoutData->form = $this->getAddCommentForm($comment->id);
		$layoutData->j3 = FabrikWorker::j3();

		if ($this->doThumbs())
		{
			$layoutData->useThumbsPlugin = true;

			$thumb = $this->getThumb();
			$input->set('commentId', $comment->id);
			$layoutData->thumbs = $thumb->render(array());
		}
		else
		{
			$layoutData->useThumbsPlugin = false;
		}

		$layout = $this->getLayout('comment');

		return $layout->render($layoutData);
	}

	/**
	 * Get list id
	 *
	 * @return  int  list id
	 */
	protected function getListId()
	{
		return $this->getModel()->getListModel()->getId();
	}

	/**
	 * Get thumb element
	 *
	 * @return  object	Thumb element
	 */

	protected function getThumb()
	{
		if (!isset($this->thumb))
		{
			$this->thumb = FabrikWorker::getPluginManager()->getPlugIn('thumbs', 'element');
			$this->thumb->setEditable(true);
			$this->thumb->commentThumb = true;
			$this->thumb->formid = $this->getModel()->getId();
			$this->thumb->listid = $this->getListId();
			$this->thumb->special = 'comments_' . $this->thumb->formid;
			$this->thumb->install();
		}

		return $this->thumb;
	}

	/**
	 * Delete a comment called from ajax request
	 *
	 * @return  void
	 */
	public function onDeleteComment()
	{
		$db = FabrikWorker::getDbo();
		$id = $this->app->input->getInt('comment_id');
		$query = $db->getQuery(true);
		$query->delete('#__{package}_comments')->where('id =' . $id);
		$db->setQuery($query);
		$db->execute();
		echo $id;
	}

	/**
	 * Update a comment called from ajax request by admin
	 *
	 * @return  void
	 */
	public function onUpdateComment()
	{
		$db = FabrikWorker::getDbo();
		$input = $this->app->input;
		$id = $input->getInt('comment_id');
		$comment = $db->q($input->get('comment', '', 'string'));
		$query = $db->getQuery(true);
		$query->update('#__{package}_comments')->set('comment = ' . $comment)->where('id = ' . $id);
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Set the form model
	 *
	 * @return  object form model
	 */
	private function setFormModel()
	{
		$input = $this->app->input;
		$formModel = JModelLegacy::getInstance('form', 'FabrikFEModel');
		$formModel->setId($input->getInt('formid'));
		$this->model = $formModel;

		return $this->model;
	}

	/**
	 * Add a comment called from ajax request
	 *
	 * @return  void
	 */
	public function onAddComment()
	{
		$input = $this->app->input;
		$row = FabTable::getInstance('comment', 'FabrikTable');
		$filter = JFilterInput::getInstance();
		$request = $filter->clean($_REQUEST, 'array');
		$row->bind($request);
		$row->ipaddress = FabrikString::filteredIp();
		$row->user_id = $this->user->get('id');
		$row->approved = 1;

		// @TODO this isn't set?
		$row->url = $input->server->get('HTTP_REFERER', '', 'string');
		$rowId = $input->get('rowid', '', 'string');
		$row->formid = $input->getInt('formid');
		$row->row_id = $rowId;

		if ($this->user->get('id') != 0)
		{
			$row->name = $this->user->get('name');
			$row->email = $this->user->get('email');
		}

		// Load up the correct params for the plugin -
		// First load all form params
		$formModel = $this->setFormModel();
		$params = $formModel->getParams();

		$this->renderOrder = (int) $input->get('renderOrder', 0);

		// Then map that data (for correct render order) onto this plugins params
		$params = $this->setParams($params, $this->renderOrder);
		$row->store();

		// $$$ rob 16/10/2012 db queries run when element/plugin selected in admin, so just return false if error now
		$obj = new stdClass;

		// Do this to get the depth of the comment
		$comments = $this->getComments($row->formid, $row->row_id);
		$row = $comments[$row->id];
		$obj->content = $this->writeComment($params, $row);
		$obj->depth = (int) $row->depth;
		$obj->id = $row->id;
		$notificationPlugin = $this->useNotificationPlugin();
		$this->fixTable();

		if ($notificationPlugin)
		{
			$this->addNotificationEvent($row);
		}

		// Do we notify everyone?
		if ($notificationPlugin)
		{
			$this->saveNotificationToPlugin($row, $comments);
		}
		else
		{
			$this->sentNotifications($row, $comments);
		}

		echo json_encode($obj);
	}

	/**
	 * Initial code missed out adding a notify field to the db.
	 * Manually add it in.
	 *
	 * @return void
	 */
	private function fixTable()
	{
		$table = FabTable::getInstance('Comment', 'FabrikTable');
		$columns = $table->getFields();

		if (!array_key_exists('notify', $columns))
		{
			$query = 'ALTER TABLE `#__fabrik_comments` ADD `notify` TINYINT(1) NOT NULL;';
			$this->_db->setQuery($query)
				->execute();

		}
	}

	/**
	 * Add notification event.
	 *
	 * @param   object  $row  Row?
	 *
	 * @return  void
	 */
	protected function addNotificationEvent($row)
	{
		$formModel = $this->getModel();
		$input = $this->app->input;
		$db = FabrikWorker::getDbo();
		$event = $db->q('COMMENT_ADDED');
		$userId = (int) $this->user->get('id');
		$rowId = $input->get('rowid', '', 'string');
		$ref = $db->q($formModel->getlistModel()->getTable()->id . '.' . $formModel->get('id') . '.' . $rowId);
		$date = $db->q($this->date->toSql());
		$query = $db->getQuery(true);
		$query->insert('#__{package}_notification_event')
			->set(array('event = ' . $event, 'user_id = ' . $userId, 'reference = ' . $ref, 'date_time = ' . $date));
		$db->setQuery($query);

		try
		{
			$db->execute();
		}
		catch (RuntimeException $e)
		{
			JLog::add('Couldn\'t save fabrik comment notification event: ' + $db->stderr(true), JLog::WARNING, 'fabrik');

			return false;
		}
	}

	/**
	 * Once we've ensured that the notification plugin is installed
	 * subscribe the user to the notification
	 * If comment-notify-admins is on then also subscribe admins to the notification
	 *
	 * @param   object  $row       Row (not used?)
	 * @param   array   $comments  Objects
	 *
	 * @return  void
	 */
	protected function saveNotificationToPlugin($row, $comments)
	{
		$params = $this->getParams();
		$formModel = $this->getModel();
		$input = $this->app->input;
		$db = FabrikWorker::getDbo();
		$userId = (int) $this->user->get('id');
		$rowId = $input->get('rowid', '', 'string');
		$label = $db->quote($input->get('label', '', 'string'));
		$ref = $db->quote($formModel->getlistModel()->getTable()->id . '.' . $formModel->get('id') . '.' . $rowId);
		$query = $db->getQuery(true);

		$onlySubscribed = (bool) $params->get('comment_allow_user_subscriptions_to_notifications');
		$shouldSubscribe = $onlySubscribed === false || ($onlySubscribed && (int) $row->notify === 1);

		if ((int) $params->get('comment-internal-notify') == 1 && $shouldSubscribe)
		{
			$query->insert('#__{package}_notification')
				->set(array('reason = ' . $db->q('commentor'), 'user_id = ' . $userId, 'reference = ' . $ref, 'label = ' . $label));
			$db->setQuery($query);

			try
			{
				$db->execute();
			}
			catch (RuntimeException $e)
			{
				JLog::add('Couldn\'t save fabrik comment notification: ' + $db->stderr(true), JLog::WARNING, 'fabrik');

				return false;
			}
		}

		// Notify original poster
		$owner = $params->get('comment_user_element', '');

		if (!empty($owner))
		{
			$owner = str_replace('.', '___', $owner) . '_raw';
			$listModel = $formModel->getlistModel();
			$rowData   = $listModel->getRow($row->row_id);

			if (isset($rowData->$owner) && !empty($rowData->$owner))
			{
				$userId = $rowData->$owner;

				if (is_numeric($userId))
				{
					$query->clear->insert('#__{package}_notification')
						->set(array('reason = ' . $db->q('owner'), 'user_id = ' . $userId, 'reference = ' . $ref, 'label = ' . $label));
					$db->setQuery($query);

					try
					{
						$db->execute();
					}
					catch (RuntimeException $e)
					{
						JLog::add('Couldn\'t save fabrik comment notification: ' + $db->stderr(true), JLog::WARNING, 'fabrik');

						return false;
					}
				}
			}
		}

		if ($params->get('comment-notify-admins') == 1)
		{
			$rows = $this->getAdminInfo();

			foreach ($rows as $row)
			{
				if ($row->id != $userId)
				{
					$query->clear();
					$fields = array('reason = ' . $db->quote('admin observing a comment'), 'user_id = ' . $row->id, 'reference = ' . $ref,
						'label = ' . $label);
					$query->insert('#__{package}_notification')->set($fields);
					$db->setQuery($query);

					try
					{
						$db->execute();
					}
					catch (RuntimeException $e)
					{
						JLog::add('Couldn\'t save fabrik comment notification for admin: ' + $db->stderr(true), JLog::WARNING, 'fabrik');
					}
				}
			}
		}
	}

	/**
	 * Test if the notification plugin is installed
	 *
	 * @return  unknown_type
	 */
	protected function notificationPluginInstalled()
	{
		return FabrikWorker::getPluginManager()->pluginExists('cron', 'notification');
	}

	/**
	 * Is the notification plugin installed and have we set the comment plugin option 'Use notification cron plugin'
	 * to something other than 'no'
	 *
	 * @return bool
	 */
	protected function useNotificationPlugin()
	{
		$params = $this->getparams();

		return $this->notificationPluginInstalled() && (int) $params->get('comment-plugin-notify') !== 0;
	}

	/**
	 * Thumb the comment
	 *
	 * @return boolean
	 */
	private function doThumbs()
	{
		$params = $this->getParams();

		return $params->get('comment-thumb') && FabrikWorker::getPluginManager()->pluginExists('element', 'thumbs');
	}

	/**
	 * Default send notifications code (sends to all people who have commented PLUS all admins)
	 *
	 * @param   object  $row       Notification
	 * @param   array   $comments  Objects
	 *
	 * @return  void
	 */
	protected function sentNotifications($row, $comments)
	{
		$formModel = $this->getModel();
		$params = $this->getParams();
		$sentTo = array();
		$title = FText::_('PLG_FORM_COMMENT_NEW_COMMENT_ADDED_TITLE');

		$layoutData = new stdClass;
		$layoutData->row = $row;
		$layoutData->comments = $comments;
		$layout = $this->getLayout('emailnotification');
		$message = $layout->render($layoutData);

		$mail = JFactory::getMailer();

		if ((int) $params->get('comment-internal-notify') == 1)
		{
			$onlySubscribed = (bool) $params->get('comment_allow_user_subscriptions_to_notifications');

			foreach ($comments as $comment)
			{
				if ($comment->id == $row->id)
				{
					// Don't sent notification to user who just posted
					continue;
				}

				$shouldSend = $onlySubscribed === false || ($onlySubscribed && (int) $comment->notify === 1);

				if ($shouldSend && !in_array($comment->email, $sentTo))
				{
					FabrikWorker::sendMail($this->app->get('mailfrom'), $this->app->get('fromname'), $comment->email, $title, $message, true);
					$sentTo[] = $comment->email;
				}
			}
		}

		// Notify original poster
		$owner = $params->get('comment_user_element', '');

		if (!empty($owner))
		{
			$owner = str_replace('.', '___', $owner) . '_raw';
			$listModel = $formModel->getlistModel();
			$rowData   = $listModel->getRow($row->row_id);

			if (isset($rowData->$owner) && !empty($rowData->$owner))
			{
				$ownerEmail = $rowData->$owner;

				if (is_numeric($ownerEmail))
				{
					$ownerUser = JFactory::getUser((int)$rowData->$owner);
					$ownerEmail = $ownerUser->get('email');
				}

				if (FabrikWorker::isEmail($ownerEmail))
				{
					FabrikWorker::sendMail($this->app->get('mailfrom'), $this->app->get('fromname'), $ownerEmail, $title, $message, true);
					$sentTo[] = $rowData->ide_idea___email_raw;
				}
			}
		}

		if ($params->get('comment-notify-admins') == 1)
		{
			// Notify admins
			// Get all super administrator
			$rows = $this->getAdminInfo();

			foreach ($rows as $row)
			{
				if (!in_array($row->email, $sentTo))
				{
					FabrikWorker::sendMail($this->app->get('mailfrom'), $this->app->get('fromname'), $row->email, $title, $message, true);
					$sentTo[] = $row->email;
				}
			}
		}
	}

	/**
	 * Get email
	 *
	 * @return  void
	 */
	public function onGetEmail()
	{
		$commentId = $this->app->input->getInt('commentid');

		// TableComment
		$c = FabTable::getInstance('Comment', 'FabrikTable');
		$c->load($commentId);
		echo "<a href=\"mailto:$c->email\">$c->email</a>";
	}

	/**
	 * Prepare intense debate comment system
	 *
	 * @return  void
	 */
	protected function _intensedebate()
	{
		$params = $this->getParams();
		FabrikHelperHTML::addScriptDeclaration(
			"
				var idcomments_acct = '" . $params->get('comment-intesedebate-code') . "';
						var idcomments_post_id;
						var idcomments_post_url;");
		$this->data = '
				<span id="IDCommentsPostTitle" style="display:none"></span>
				<script type=\'text/javascript\' src=\'http://www.intensedebate.com/js/genericCommentWrapperV2.js\'></script>';
	}

	/**
	 * Prepare disqus comment system
	 *
	 * @return  void
	 */
	protected function _disqus()
	{
		$params = $this->getParams();
		$input = $this->app->input;

		if ($input->get('ajax') == 1)
		{
			$this->data = '';

			return;
		}

		FabrikHelperHTML::addScriptDeclaration(
			"
				(function() {
				var links = document.getElementsByTagName('a');
				var query = '?';
				for (var i = 0; i < links.length; i++) {
				if(links[i].href.indexOf('#disqus_thread') >= 0) {
				query += 'url' + i + '=' + encodeURIComponent(links[i].href) + '&';
	}
	}
				document.write('<script type=\"text/javascript\" src=\"http://disqus.com/forums/rotterdamvooruit/get_num_replies.js' + query + '\">
			</' + 'script>');
	})();
				");
		$this->data = '<div id="disqus_thread"></div><script type="text/javascript" src="http://disqus.com/forums/'
			. $params->get('comment-disqus-subdomain') . '/embed.js"></script><noscript>'
			. '<a href="http://rotterdamvooruit.disqus.com/?url=ref">View the discussion thread.</a>'
			. '</noscript><a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>';
	}

	/**
	 * Prepare JComment system
	 *
	 * @return  void
	 */
	protected function _jcomment()
	{
		$formModel = $this->getModel();
		$input = $this->app->input;

		/*
		 * 'Fix' for jcomments not loading languages? Means you have to copy:
		 * components/com_jcomments/languages/yourfile.ini to
		 * components/com_jcomments/language/xx-XX/yourfile.ini
		 */
		$lang = JFactory::getLanguage();
		$lang->load('com_jcomments', JPATH_BASE . '/components/com_jcomments');
		$jComments = JPATH_SITE . '/components/com_jcomments/jcomments.php';

		if (JFile::exists($jComments))
		{
			require_once $jComments;

			if ($this->commentsLocked)
			{
				$jc_config = JCommentsFactory::getConfig();
				$jc_config->set('comments_locked', 1);
			}

			$this->data = '<div id="jcomments" style="clear: both;">
					' . JComments::show($input->get('rowid', '', 'string'), "com_fabrik_{$formModel->getId()}") . '
							</div>';
		}
		else
		{
			throw new RuntimeException('JComment is not installed on your system');
		}
	}

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @return    bool
	 */
	public function onAfterProcess()
	{
		$params = $this->getParams();
		$method = $params->get('comment_method', 'disqus');
		$notification = (bool) $params->get('comment_jcomment_notify', false);

		if ($method !== 'jcomment' || $notification === false)
		{
			return;
		}

		require_once JPATH_PLUGINS . '/fabrik_form/comment/helpers/jcomments.php';
		FabrikJCommentHelper::subscribe($this);
	}
}
