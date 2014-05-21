<?php
/**
 * Form Comment
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.comment
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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

	/**
	 * Data
	 *
	 * @var array
	 */
	protected $data = array();

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
				$lockval = $formModel->data[$lock];

				if ($lockval == 1)
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
		$rowid = $formModel->getRowId();

		if ($rowid == '')
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
		$app = JFactory::getApplication();
		$input = $app->input;
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
	 * Prepare local comment system
	 *
	 * @return  void
	 */

	protected function _internal()
	{
		$params = $this->getParams();
		$formModel = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$data = array();
		$document = JFactory::getDocument();
		$this->inJDb = $formModel->getTableModel()->inJDb();
		$this->formModel = $formModel;
		$jsfiles = array();
		JHTML::stylesheet('/plugins/fabrik_form/comment/comments.css');
		$jsfiles[] = 'media/com_fabrik/js/fabrik.js';
		$jsfiles[] = 'plugins/fabrik_form/comment/comments.js';
		$jsfiles[] = 'plugins/fabrik_form/comment/inlineedit.js';

		$thumbopts = $this->doThumbs() ? $thumbopts = $this->loadThumbJsOpts() : "{}";
		$db = FabrikWorker::getDbo();
		$user = JFactory::getUser();
		$data[] = '<div id="fabrik-comments">';
		$rowid = $input->get('rowid', '', 'string');

		if (strstr($rowid, ':'))
		{
			// SLUG
			$rowid = array_shift(explode(':', $rowid));
		}

		$comments = $this->getComments($formModel->get('id'), $rowid);
		$data[] = '<h3><a href="#" name="comments">';

		if (empty($comments))
		{
			$data[] = FText::_('PLG_FORM_COMMENT_NO_COMMENTS');
		}
		else
		{
			if ($params->get('comment-show-count-in-title'))
			{
				$data[] = count($comments) . ' ';
			}

			$data[] = FText::_('PLG_FORM_COMMENT_COMMENTS');
		}

		$data[] = '</a></h3>';

		if ($this->doThumbs())
		{
			$thumb = $this->getThumb();
			$this->thumbCounts = $thumb->getListThumbsCount();
		}

		$data[] = $this->writeComments($params, $comments);
		$anonymous = $params->get('comment-internal-anonymous');

		if (!$this->commentsLocked)
		{
			if ($user->get('id') == 0 && $anonymous == 0)
			{
				$data[] = '<h3>' . FText::_('PLG_FORM_COMMENT_PLEASE_SIGN_IN_TO_LEAVE_A_COMMENT') . '</h3>';
			}
			else
			{
				$data[] = '<h3>' . FText::_('PLG_FORM_COMMENT_ADD_COMMENT') . '</h3>';
			}

			$data[] = $this->getAddCommentForm(0, true);
		}

		// Form
		$data[] = '</div>';
		$opts = new stdClass;
		$opts->formid = $formModel->get('id');
		$opts->rowid = $rowid;
		$opts->admin = $user->authorise('core.delete', 'com_fabrik');
		$opts->label = '';

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
			$jsfiles[] = 'plugins/fabrik_element/thumbs/list-thumbs.js';
			$script .= "\n comments.thumbs = new FbThumbsList(" . $this->formModel->getId() . ", $thumbopts);";
		}

		FabrikHelperHTML::script($jsfiles, $script);
		$this->data = implode("\n", $data);
	}

	/**
	 * Can we add internal comments
	 *
	 * @return boolean
	 */
	private function canAddComment()
	{
		$user = JFactory::getUser();
		$params = $this->getParams();
		$anonymous = $params->get('comment-internal-anonymous');

		return $user->get('id') == 0 && $anonymous == 0 ? false : true;
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
		$data = array();
		$app = JFactory::getApplication();
		$input = $app->input;
		$user = JFactory::getUser();
		$anonymous = $params->get('comment-internal-anonymous');

		if (!$this->canAddComment())
		{
			return;
		}

		$m = $master ? " id='master-comment-form' " : '';
		$data[] = '<form action="index.php" ' . $m . ' class="replyform">';
		$data[] = '<p><textarea style="width:95%" rows="6" cols="3" placeholder="' . FText::_('PLG_FORM_COMMENT_TYPE_A_COMMENT_HERE') . '">';
		$data[] = '</textarea></p>';
		$data[] = '<table class="adminForm" style="width:350px" summary="comments">';

		if ($user->get('id') == 0)
		{
			$data[] = '<tr>';
			$name = trim($input->get('ide_people___voornaam', '', 'cookie') . ' ' . $input->get('ide_people___achternaam', '', 'cookie'));
			$email = $input->get('ide_people___email', '', 'cookie');
			$data[] = '<td>';
			$data[] = '<label for="add-comment-name-' . $reply_to . '">' . FText::_('PLG_FORM_COMMENT_NAME') . '</label>';
			$data[] = '<br />';
			$data[] = '<input class="inputbox" type="text" size="20" id="add-comment-name-' . $reply_to . '" name="name" value="' . $name
			. '" /></td>';
			$data[] = '<td>';
			$data[] = '<label for="add-comment-email-' . $reply_to . '">' . FText::_('PLG_FORM_COMMENT_EMAIL') . '</label>';
			$data[] = '<br />';
			$data[] = '<input class="inputbox" type="text" size="20" id="add-comment-email-' . $reply_to . '" name="email" value="' . $email
			. '" /></td>';
			$data[] = '</tr>';
		}

		if ($this->notificationPluginInstalled())
		{
			if ($params->get('comment-plugin-notify') == 1)
			{
				$data[] = '<tr>';
				$data[] = '<td>';
				$data[] = FText::_('PLG_FORM_COMMENT_NOTIFY_ME');
				$data[] = '<label><input type="radio" name="comment-plugin-notify[]" checked="checked" class="inputbox" value="1">' . FText::_('JNO')
				. '</label>';
				$data[] = '</td>';
				$data[] = '<td>';
				$data[] = '<label><input type="radio" name="comment-plugin-notify[]" class="inputbox" value="0">' . FText::_('JYES') . '</label>';
				$data[] = '</td>';
				$data[] = '</tr>';
			}
		}

		$rating = $params->get('comment-internal-rating');

		if ($rating == 1 || $anonymous == 1)
		{
			$data[] = '<tr>';
			$data[] = '<td>';

			if ($rating)
			{
				$data[] = '<label for="add-comment-rating-' . $reply_to . '">' . FText::_('PLG_FORM_COMMENT_RATING') . '</label><br />';
				$data[] = '<select id="add-comment-rating-' . $reply_to . '" class="inputbox" name="rating">';
				$data[] = '<option value="0">' . FText::_('PLG_FORM_COMMENT_NONE') . '</option>';
				$data[] = '<option value="1">' . FText::_('PLG_FORM_COMMENT_ONE') . '</option>';
				$data[] = '<option value="2">' . FText::_('PLG_FORM_COMMENT_TWO') . '</option>';
				$data[] = '<option value="3">' . FText::_('PLG_FORM_COMMENT_THREE') . '</option>';
				$data[] = '<option value="4">' . FText::_('PLG_FORM_COMMENT_FOUR') . '</option>';
				$data[] = '<option value="5">' . FText::_('PLG_FORM_COMMENT_FIVE') . '</option>\n</select>';
			}

			$data[] = '</td>';
			$data[] = '<td>';

			if ($anonymous)
			{
				$data[] = FText::_('Anonymous') . '<br />';
				$data[] = '<label for="add-comment-anonymous-no-' . $reply_to . '">' . FText::_('JNO') . '</label>';
				$data[] = '<input type="radio" id="add-comment-anonymous-no-' . $reply_to
				. '" name="anonymous[]" checked="checked" class="inputbox" value="0" />';
				$data[] = '<label for="add-comment-anonymous-yes-' . $reply_to . '">' . FText::_('JYES') . '</label>';
				$data[] = '<input type="radio" id="add-comment-anonymous-yes-' . $reply_to . '" name="anonymous[]" class="inputbox" value="1" />';
			}

			$data[] = '</td>';
			$data[] = '</tr>';
		}

		$data[] = '<tr>';
		$data[] = '<td colspan="2">';
		$data[] = '<button class="button btn btn-success submit" style="margin-left:0">';
		$data[] = '<i class="icon-comments-2"></i> ';
		$data[] = FText::_('PLG_FORM_COMMENT_POST_COMMENT');
		$data[] = '</button>';
		$data[] = '<input type="hidden" name="reply_to" value="' . $reply_to . '" />';
		$data[] = '<input type="hidden" name="renderOrder" value="' . $this->renderOrder . '" />';
		$data[] = '</td>';
		$data[] = '</tr>';
		$data[] = '</table>';
		$data[] = '</form>';

		return implode("\n", $data);
	}

	/**
	 * TODO replace parentid with left/right markers
	 * see http://dev.mysql.com/tech-resources/articles/hierarchical-data.html
	 * Get the comments from the db
	 *
	 * @param   int     $formid  Form id
	 * @param   string  $rowid   Row id
	 *
	 * @return  array	replies
	 */

	protected function getComments($formid, $rowid)
	{
		$formid = (int) $formid;
		$db = FabrikWorker::getDbo();
		$formModel = $this->setFormModel();
		$query = $db->getQuery(true);
		$query->select('c.*');
		$query->from('#__{package}_comments AS c');
		$this->inJDb = $formModel->getTableModel()->inJDb();

		if ($this->inJDb)
		{
			$query->join('LEFT', '#__users AS u ON c.user_id = u.id');
		}

		$query->where('formid = ' . $formid . ' AND c.row_id = ' . $db->quote($rowid) . ' AND c.approved = 1')->order('c.time_date ASC');
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
		$data = array();
		$data[] = '<ul id="fabrik-comment-list">';

		if (empty($comments))
		{
			$data[] = '<li class="empty-comment">&nbsp;</li>';
		}
		else
		{
			foreach ($comments as $comment)
			{
				$depth = (int) $comment->depth * 20;

				// @TODO need to add class per user group
				$data[] = '<li class="usergroup-x" id="comment_' . $comment->id . '" style="margin-left:' . $depth . 'px">';
				$data[] = $this->writeComment($params, $comment);
				$data[] = '</li>';
			}
		}

		$data[] = '</ul>';

		return implode("\n", $data);
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
		$app = JFactory::getApplication();
		$input = $app->input;
		$user = JFactory::getUser();
		$j3 = FabrikWorker::j3();
		$name = (int) $comment->anonymous == 0 ? $comment->name : FText::_('PLG_FORM_COMMENT_ANONYMOUS_SHORT');
		$data = array();
		$data[] = '<div class="metadata muted">';
		$data[] = '<small><i class="icon-user"></i> ';
		$data[] = $name . ', ' . FText::_('PLG_FORM_COMMENT_WROTE_ON') . ' </small>';
		$data[] = '<i class="icon-calendar"></i> ';
		$data[] = ' <small>' . JHTML::date($comment->time_date) . '</small>';

		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_form/comment/images/', 'image', 'form', false);
		$insrc = FabrikHelperHTML::image("star_in.png", 'form', @$this->tmpl, array(), true);

		if ($params->get('comment-internal-rating') == 1)
		{
			$data[] = '<div class="rating">';
			$r = (int) $comment->rating;

			for ($i = 0; $i < $r; $i++)
			{
				$data[] = $j3 ? '<i class="icon-star"></i> ' : '<img src="' . $insrc . '" alt="star" />';
			}

			$data[] = '</div>';
		}

		$data[] = '</div>';
		$data[] = '<div class="comment" id="comment-' . $comment->id . '">';
		$data[] = '<div class="comment-content">' . $comment->comment . '</div>';
		$this->commentActions($data, $comment);
		$data[] = '</div>';

		if (!$this->commentsLocked)
		{
			$data[] = $this->getAddCommentForm($comment->id);
		}

		return implode("\n", $data);
	}

	/**
	 * Add reply/delete links to the comment form
	 *
	 * @param   array   &$data    HTML
	 * @param   object  $comment  Comment object
	 *
	 * @return  void
	 */

	protected function commentActions(&$data, $comment)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$user = JFactory::getUser();
		$data[] = '<div class="reply">';

		if (!$this->commentsLocked && $this->canAddComment())
		{
			$data[] = '<a href="#" class="replybutton btn btn-small btn-link">' . FText::_('PLG_FORM_COMMENT_REPLY') . '</a>';
		}

		if ($user->authorise('core.delete', 'com_fabrik'))
		{
			$data[] = '<a href="#" class="del-comment btn btn-danger btn-small">' . FText::_('PLG_FORM_COMMENT_DELETE') . '</a>';
		}

		if ($this->doThumbs())
		{
			$thumb = $this->getThumb();
			$input->set('commentId', $comment->id);
			$data[] = $thumb->render(array());
		}

		$data[] = '</div>';
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
		$app = JFactory::getApplication();
		$id = $app->input->getInt('comment_id');
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
		$app = JFactory::getApplication();
		$input = $app->input;
		$id = $input->getInt('comment_id');
		$comment = $db->quote($input->get('comment', '', 'string'));
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
		$app = JFactory::getApplication();
		$input = $app->input;
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
		$app = JFactory::getApplication();
		$input = $app->input;
		$db = FabrikWorker::getDbo();
		$user = JFactory::getUser();
		$row = FabTable::getInstance('comment', 'FabrikTable');
		$filter = JFilterInput::getInstance();
		$request = $filter->clean($_REQUEST, 'array');
		$row->bind($request);
		$row->ipaddress = $_SERVER['REMOTE_ADDR'];
		$row->user_id = $user->get('id');
		$row->approved = 1;

		// @TODO this isn't set?
		$row->url = $input->server->get('HTTP_REFERER', '', 'string');
		$rowid = $input->get('rowid', '', 'string');
		$row->formid = $input->getInt('formid');
		$row->row_id = $rowid;

		if ($user->get('id') != 0)
		{
			$row->name = $user->get('name');
			$row->email = $user->get('email');
		}

		// Load up the correct params for the plugin -
		// First load all form params
		$formModel = $this->setFormModel();
		$params = $formModel->getParams();
		$tmp = array();
		$this->renderOrder = $input->get('renderOrder', 2);

		// Then map that data (for correct render order) onto this plugins params
		$params = $this->setParams($params, $this->renderOrder);
		$res = $row->store();

		// $$$ rob 16/10/2012 db queries run when element/plugin selected in admin, so just return false if error now
		$obj = new stdClass;

		// Do this to get the depth of the comment
		$comments = $this->getComments($row->formid, $row->row_id);
		$row = $comments[$row->id];
		$obj->content = $this->writeComment($params, $row);
		$obj->depth = (int) $row->depth;
		$obj->id = $row->id;
		$notificationPlugin = $this->notificationPluginInstalled();

		if ($notificationPlugin)
		{
			$this->addNotificationEvent($row);
		}

		$comment_plugin_notify = $input->get('comment-plugin-notify');

		// Do we notify everyone?
		if ($params->get('comment-internal-notify') == 1)
		{
			if ($notificationPlugin)
			{
				$this->saveNotificationToPlugin($row, $comments);
			}
			else
			{
				$this->sentNotifications($row, $comments);
			}
		}

		echo json_encode($obj);
	}

	/**
	 * Add notification event
	 *
	 * @param   object  $row  Row?
	 *
	 * @return  void
	 */

	protected function addNotificationEvent($row)
	{
		$formModel = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$db = FabrikWorker::getDbo();
		$event = $db->quote('COMMENT_ADDED');
		$user = JFactory::getUser();
		$user_id = (int) $user->get('id');
		$rowid = $input->get('rowid', '', 'string');
		$ref = $db->quote($formModel->getlistModel()->getTable()->id . '.' . $formModel->get('id') . '.' . $rowid);
		$date = $db->quote(JFactory::getDate()->toSql());
		$query = $db->getQuery(true);
		$query->insert('#__{package}_notification_event')
		->set(array('event = ' . $event, 'user_id = ' . $user_id, 'reference = ' . $ref, 'date_time = ' . $date));
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
		$formModel = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$db = FabrikWorker::getDbo();
		$user = JFactory::getUser();
		$user_id = (int) $user->get('id');
		$rowid = $input->get('rowid', '', 'string');
		$label = $db->quote($input->get('label', '', 'string'));
		$ref = $db->quote($formModel->getlistModel()->getTable()->id . '.' . $formModel->get('id') . '.' . $rowid);
		$query = $db->getQuery(true);
		$query->insert('#__{package}_notification')
		->set(array('reason = ' . $db->quote('commentor'), 'user_id = ' . $user_id, 'reference = ' . $ref, 'label = ' . $label));
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

		$params = $formModel->getParams();

		if ($params->get('comment-notify-admins') == 1)
		{
			$rows = $this->getAdminInfo();

			foreach ($rows as $row)
			{
				if ($row->id != $user_id)
				{
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
		$db = FabrikWorker::getDbo();
		$user = JFactory::getUser();
		$app = JFactory::getApplication();
		$sentto = array();
		$title = FText::_('PLG_FORM_COMMENT_NEW_COMMENT_ADDED_TITLE');
		$message = FText::_('PLG_FORM_COMMENT_NEW_COMMENT_ADDED');
		$message .= "<br /><a href=\"{$row->url}\">" . FText::_('PLG_FORM_COMMENT_VIEW_COMMENT') . "</a>";
		$mail = JFactory::getMailer();

		foreach ($comments as $comment)
		{
			if ($comment->id == $row->id)
			{
				// Don't sent notification to user who just posted
				continue;
			}

			if (!in_array($comment->email, $sentto))
			{
				$mail->sendMail($app->getCfg('mailfrom'), $app->getCfg('fromname'), $comment->email, $title, $message, true);
				$sentto[] = $comment->email;
			}
		}

		// Notify original poster (hack for ideenbus)
		$listModel = $formModel->getlistModel();
		$rowdata = $listModel->getRow($row->row_id);

		if (!in_array($rowdata->ide_idea___email_raw, $sentto))
		{
			$mail->sendMail($app->getCfg('mailfrom'), $app->getCfg('fromname'), $rowdata->ide_idea___email_raw, $title, $message, true);
			$sentto[] = $rowdata->ide_idea___email_raw;
		}

		if ($params->get('comment-notify-admins') == 1)
		{
			// Notify admins
			// Get all super administrator
			$rows = $this->getAdminInfo();

			foreach ($rows as $row)
			{
				$mail->sendMail($mailfrom, $fromname, $row->email, $subject2, $message2);

				if (!in_array($row->email, $sentto))
				{
					$mail->sendMail($app->getCfg('mailfrom'), $app->getCfg('fromname'), $row->email, $title, $message, true);
					$sentto[] = $row->email;
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
		$app = JFactory::getApplication();
		$commentid = $app->input->getInt('commentid');

		// TableComment
		$c = FabTable::getInstance('Comment', 'FabrikTable');
		$c->load($commentid);
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
	 * Prepate diqus comment system
	 *
	 * @return  void
	 */

	protected function _disqus()
	{
		$params = $this->getParams();
		$app = JFactory::getApplication();
		$input = $app->input;

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
		$app = JFactory::getApplication();
		$input = $app->input;
		$jcomments = JPATH_SITE . '/components/com_jcomments/jcomments.php';

		if (JFile::exists($jcomments))
		{
			require_once $jcomments;

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
}
