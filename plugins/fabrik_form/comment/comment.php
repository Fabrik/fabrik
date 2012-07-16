<?php
/**
* @package		Joomla.Plugin
* @subpackage	Fabrik.form.comment
* @copyright	Copyright (C) 2005 Fabrik. All rights reserved.
* @license		GNU General Public License version 2 or later; see LICENSE.txt
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
* Insert a comment plugin into the bottom of the form
* Various different plugin systems supported
*  * Internal
*  * disqus
*  * Intensedebate
*  * JComments
*
* @package		Joomla.Plugin
* @subpackage	Fabrik.form.comment
*/

class FabrikTableComment extends FabTable
{

	function __construct(&$_db)
	{
		parent::__construct('#__{package}_comments', 'id', $_db);
	}

}

class plgFabrik_FormComment extends plgFabrik_Form {

	/**@var string html comment form */
	var $commentform = null;

	var $commentsLocked = null;

	protected $_data = array();
	
	function getEndContent_result($c)
	{
		return $this->_data;
	}

	/**
	 * determine if you can add new comments
	 * @param	object	$params
	 * @param	object	$formModel
	 */

	function commentsLocked(&$params, &$formModel)
	{
		if (is_null($this->commentsLocked))
		{
			$this->commentsLocked = false;
			$lock = trim($params->get('comment_lock_element'));
			if ($lock !== '')
			{
				$lock = str_replace('.', '___', $lock) . '_raw';
				$lockval = $formModel->_data[$lock];
				if ($lockval == 1)
				{
					$this->commentsLocked = true;
				}
			}

		}
		return $this->commentsLocked;
	}

	function getEndContent(&$params, &$formModel)
	{
		$rowid = $formModel->getRowId();
		if ( $rowid == 0 || $rowid == '')
		{
			return;
		}
		$this->commentsLocked($params, $formModel);
		$method = $params->get('comment_method', 'disqus');
		switch($method)
		{
			default:
			case 'disqus':
				$this->_disqus($params);
				break;
			case 'intensedebate':
				$this->_intensedebate($params);
				break;
			case 'jskit':
				$this->_jskit($params);
				break;
			case 'internal':
				$this->_internal($params, $formModel);
				break;
			case 'jcomment':
				$this->_jcomment($params, $formModel);
				break;
		}
		return true;
	}

	protected function loadDiggJsOpts()
	{
		FabrikHelperHTML::script('plugins/fabrik_element/digg/table-fabrikdigg.js');
		$opts = new stdClass();
		$digg = $this->getDigg();
		$opts->livesite = COM_FABRIK_LIVESITE;
		$opts->row_id = JRequest::getInt('rowid');
		$opts->voteType = 'comment';

		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/digg/images/', 'image', 'form', false);
		$opts->imageover = FabrikHelperHTML::image("heart-off.png", 'form', $this->tmpl, array(), true);
		$opts->imageout = FabrikHelperHTML::image("heart.png", 'form', $this->tmpl, array(), true);
		$opts->formid = $this->formModel->getId();
		$opts->listid = $this->formModel->getListModel()->getTable()->id;
		$opts = json_encode($opts);
		return $opts;
	}

	/**
	 * prepare local comment system
	 * @param	object	$params
	 * @param	object	form model
	 */

	protected function _internal(&$params, &$formModel)
	{
		$data = array();
		$document = JFactory::getDocument();
		$this->inJDb = $formModel->getTableModel()->inJDb();
		$this->formModel = $formModel;
		JHTML::stylesheet('/plugins/fabrik_form/comment/comments.css');
		FabrikHelperHTML::script('/plugins/fabrik_form/comment/comments.js');
		FabrikHelperHTML::script('/plugins/fabrik_form/comment/inlineedit.js');

		if ($this->doDigg())
		{
			$digopts = $this->loadDiggJsOpts();
		}
		else
		{
			$digopts ="{}";
		}

		$db = FabrikWorker::getDbo();
		$user = JFactory::getUser();
		$data[] = '<div id="fabrik-comments">';
		$rowid = JRequest::getVar('rowid');
		if (strstr($rowid, ':'))
		{
			// SLUG
			$rowid = array_shift(explode(':', $rowid));
		}

		$comments = $this->getComments($formModel->get('id'), $rowid);

		$data[] = '<h3><a href="#" name="comments">';
		if (empty($comments))
		{
			$data[] = JText::_('PLG_FORM_COMMENT_NO_COMMENTS');
		}
		else
		{
			if ($params->get('comment-show-count-in-title'))
			{
				$data[] = count($comments) . ' ';
			}
			$data[] = JText::_('PLG_FORM_COMMENT_COMMENTS');
		}
		$data[] = '</a></h3>';

		$data[] = $this->writeComments($params, $comments);

		$anonymous = $params->get('comment-internal-anonymous');
		if (!$this->commentsLocked)
		{
			if ($user->get('id') == 0 && $anonymous == 0)
			{
				$data[] = '<h3>' . JText::_('PLG_FORM_COMMENT_PLEASE_SIGN_IN_TO_LEAVE_A_COMMENT') . '</h3>';
			}
			else
			{
				$data[] = '<h3>' . JText::_('PLG_FORM_COMMENT_ADD_COMMENT') . '</h3>';
			}
			$data[] = $this->getAddCommentForm($params, 0, true);
		}
		//form

		$data[] = '</div>';

		$opts = new stdClass();
		$opts->formid = $formModel->get('id');
		$opts->rowid = JRequest::getVar('rowid');
		$opts->admin = $user->authorise('core.delete', 'com_fabrik');
		$opts->label = '';
		foreach ($formModel->_data as $k => $v)
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

		$script = "head.ready(function() {
		var comments = new FabrikComment('fabrik-comments', $opts);";

		if ($this->doDigg())
		{
			$script .= "\n comments.digg = new FbDiggTable(".$this->formModel->getId().", $digopts);";
		}
		$script .= "\n});";
		FabrikHelperHTML::addScriptDeclaration($script);
		$this->_data = implode("\n", $data);
	}

	/**
	 * 
	 * build the html for the internal comment form
	 * @param	object	$params
	 * @param	int		$reply_to
	 * @param	bool	$master
	 * @return	string
	 */
	
	private function getAddCommentForm($params, $reply_to = 0, $master = false)
	{
		$data = array();
		$user = JFactory::getUser();
		$anonymous = $params->get('comment-internal-anonymous');
		if ($user->get('id') == 0 && $anonymous == 0)
		{
			return;
		}
		$m = $master ? " id='master-comment-form' " : '';
		$data[] = '<form action="index.php" ' . $m . ' class="replyform">';
		$data[] = '<p><textarea style="width:95%" rows="6" cols="3">';
		$data[] = JText::_('PLG_FORM_COMMENT_TYPE_A_COMMENT_HERE') . '</textarea></p>';
		$data[] = '<table class="adminForm" style="width:350px" summary="comments">';
		if ($user->get('id') == 0)
		{
			$data[] = '<tr>';
			$name = trim(JRequest::getVar('ide_people___voornaam', '', 'cookie') . ' '. JRequest::getVar('ide_people___achternaam', '', 'cookie'));
			$email = JRequest::getVar('ide_people___email', '', 'cookie');
			$data[] = '<td>';
			$data[] = '<label for="add-comment-name-' . $reply_to . '">'. JText::_('PLG_FORM_COMMENT_NAME') . '</label>';
			$data[] = '<br />';
			$data[] = '<input class="inputbox" type="text" size="20" id="add-comment-name-' . $reply_to . '" name="name" value="' . $name . '" /></td>';
			$data[] = '<td>';
			$data[] = '<label for="add-comment-email-' . $reply_to . '">'. JText::_('PLG_FORM_COMMENT_EMAIL') . '</label>';
			$data[] = '<br />';
			$data[] = '<input class="inputbox" type="text" size="20" id="add-comment-email-' . $reply_to . '" name="email" value="' . $email . '" /></td>';
			$data[] = '</tr>';
		}

		if ($this->notificationPluginInstalled( $this->formModel))
		{
			if ($params->get('comment-plugin-notify') == 1)
			{
				$data[] = '<tr>';
				$data[] = '<td>';
				$data[] = JText::_('PLG_FORM_COMMENT_NOTIFY_ME');
				$data[] = '<label><input type="radio" name="comment-plugin-notify[]" checked="checked" class="inputbox" value="1">' . JText::_('JNO') . '</label>';
				$data[] = '</td>';
				$data[] = '<td>';
				$data[] = '<label><input type="radio" name="comment-plugin-notify[]" class="inputbox" value="0">' . JText::_('JYES') . '</label>';
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
				$data[] = '<label for="add-comment-rating-' . $reply_to . '">' . JText::_('PLG_FORM_COMMENT_RATING') . '</label><br />';
				$data[] = '<select id="add-comment-rating-' . $reply_to . '" class="inputbox" name="rating">';
				$data[] = '<option value="0">' . JText::_('PLG_FORM_COMMENT_NONE') . '</option>';
				$data[] = '<option value="1">' . JText::_('PLG_FORM_COMMENT_ONE') . '</option>';
				$data[] = '<option value="2">' . JText::_('PLG_FORM_COMMENT_TWO') . '</option>';
				$data[] = '<option value="3">' . JText::_('PLG_FORM_COMMENT_THREE') . '</option>';
				$data[] = '<option value="4">' . JText::_('PLG_FORM_COMMENT_FOUR') . '</option>';
				$data[] = '<option value="5">' . JText::_('PLG_FORM_COMMENT_FIVE') . '</option>\n</select>';
			}

			$data[] = '</td>';
			$data[] = '<td>';
			if ($anonymous)
			{
				$data[] = JText::_('Anonymous') . '<br />';
				$data[] = '<label for="add-comment-anonymous-no-' . $reply_to . '">' . JText::_('JNO') . '</label>';
				$data[] = '<input type="radio" id="add-comment-anonymous-no-' . $reply_to . '" name="annonymous[]" checked="checked" class="inputbox" value="0" />';
				$data[] = '<label for="add-comment-anonymous-yes-' . $reply_to . '">' . JText::_('JYES') . '</label>';
				$data[] = '<input type="radio" id="add-comment-anonymous-yes-' . $reply_to . '" name="annonymous[]" class="inputbox" value="1" />';
			}
			$data[] = '</td>';
			$data[] = '</tr>';
		}
		$data[] = '<tr>';
		$data[] = '<td colspan="2">';
		$data[] = '<input type="button" class="button" style="margin-left:0" value="' . JText::_('PLG_FORM_COMMENT_POST_COMMENT') . '" />';
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
	 * get the comments from the db
	 * @param	int		$formid
	 * @param	int		$rowid
	 * @return	array	replies
	 */
	 
	function getComments($formid, $rowid)
	{
		$rowid = (int) $rowid;
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
		$query->where('formid = ' . $formid . ' AND c.row_id = ' . $rowid . ' AND c.approved = 1')
		->order('c.time_date ASC');
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
	 * append the replies to the comments
	 * @param	object current comment
	 * @param	array	$replies
	 * @param	array	&$return
	 * @param	int		$depth
	 */
	
	private function getReplies($v, $replies, &$return, $depth)
	{
		$depth ++;
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
	 * generate the html for the comments
	 * @param	object	form $params
	 * @param	array	$comments
	 * @return	string
	 */
	
	private function writeComments(&$params, &$comments)
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
	 * write a single comment
	 * @param	object	$params
	 * @param	object	$comment
	 * @return	string
	 */
	
	private function writeComment(&$params, $comment)
	{
		$user = JFactory::getUser();
		$name = (int) $comment->annonymous == 0 ? $comment->name : JText::_('PLG_FORM_COMMENT_ANONYMOUS_SHORT');
		$data = array();
		$data[] = '<div class="metadata">';
		$data[] = $name . ' '  . JText::_('PLG_FORM_COMMENT_WROTE_ON') . ' <small>' . JHTML::date($comment->time_date) .  '</small>';

		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_form/comment/images/', 'image', 'form', false);
		$insrc = FabrikHelperHTML::image("star_in.png", 'form', @$this->tmpl, array(), true);
		if ($params->get('comment-internal-rating') == 1)
		{
			$data[] = '<div class="rating">';
			$r = (int) $comment->rating;
			for ($i = 0; $i < $r; $i++)
			{
				$data[] = '<img src="' . $insrc . '" alt="star" />';
			}
			$data[] = '</div>';
		}
		if ($this->doDigg())
		{
			$digg = $this->getDigg();
			$digg->_editable = true;
			$digg->commentDigg = true;
			$digg->commentId = $comment->id;
			if (JRequest::getVar('listid') == '')
			{
				JRequest::setVar('listid', $this->getListId());
			}
			JRequest::setVar('commentId', $comment->id);
			$id = 'digg_' . $comment->id;
			$data[] = '<div id="' . $id . '" class="digg fabrik_row fabrik_row___' . $this->formModel->getId() . '">';
			$data[] =  $digg->render(array());
			$data[] = '</div>';
		}
		$data[] = '</div>';
		$data[] = '<div class="comment" id="comment-' . $comment->id . '">';
		$data[] = '<div class="comment-content">' . $comment->comment . '</div>';
		$data[] = '<div class="reply">';
		if (!$this->commentsLocked)
		{
			$data[] = '<a href="#" class="replybutton">' . JText::_('PLG_FORM_COMMENT_REPLY') . '</a>';
		}
		if ($user->authorise('core.delete', 'com_fabrik'))
		{
			$data[] = '<div class="admin">';
			$data[] = '<a href="#" class="del-comment">' . JText::_('PLG_FORM_COMMENT_DELETE') . '</a>';
			$data[] = '</div>';
		}
		$data[] = '</div>';
		$data[] = '</div>';
		if (!$this->commentsLocked)
		{
			$data[] = $this->getAddCommentForm($params, $comment->id);
		}
		return implode("\n", $data);
	}

	/**
	 * get list id
	 */
	
	protected function getListId()
	{
		return $this->formModel->getListModel()->getTable()->id;
	}

	/**
	 * get digg element
	 * @return	object	digg element
	 */

	protected function getDigg()
	{
		if (!isset($this->digg))
		{
			$this->digg = FabrikWorker::getPluginManager()->getPlugIn('digg', 'element');
		}
		return $this->digg;
	}

	/**
	 * delete a comment called from ajax request
	 */

	function onDeleteComment()
	{
		$db = FabrikWorker::getDbo();
		$id = JRequest::getInt('comment_id');
		$query = $db->getQuery(true);
		$query->delete('#__{package}_comments')->where('id =' . $id);
		$db->setQuery($query);
		$db->query();
		echo $id;
	}

	/**
	 * update a comment called from ajax request by admin
	 */

	function onUpdateComment()
	{
		$db = FabrikWorker::getDbo();
		$id = JRequest::getInt('comment_id');
		$comment = $db->quote(JRequest::getVar('comment'));
		$query = $db->getQuery(true);
		$query->update('UPDATE #__{package}_comments')->set('comment = ' . $comment)->where('id = ' . $id);
		$db->setQuery($query);
		$db->query();
	}

	/**
	 * set the form model
	 * @return	object form model
	 */
	
	private function setFormModel()
	{
		$formModel = JModel::getInstance('form', 'FabrikFEModel');
		$formModel->setId(JRequest::getVar('formid'));
		$this->formModel = $formModel;
		return $this->formModel;
	}
	/**
	 * add a comment called from ajax request
	 */

	function onAddComment()
	{
		$db = FabrikWorker::getDbo();
		$user = JFactory::getUser();
		$row = FabTable::getInstance('comment', 'FabrikTable');// new TableComment($db);
		$row->bind(JRequest::get('request'));
		$row->ipaddress = $_SERVER['REMOTE_ADDR'];
		$row->user_id = $user->get('id');
		$row->approved = 1;
		//@TODO this isnt set?
		$row->url = JRequest::getVar('HTTP_REFERER', '', 'server');
		$rowid = JRequest::getVar('rowid');
		$row->formid = JRequest::getVar('formid');
		$row->row_id = $rowid;
		if ($user->get('id') != 0)
		{
			$row->name = $user->get('name');
			$row->email = $user->get('email');
		}
		//load up the correct params for the plugin -
		//first load all form params
		$formModel = $this->setFormModel();
		$params = $formModel->getParams();
		$tmp = array();
		$this->renderOrder = JRequest::getVar('renderOrder', 2);
		//then map that data (for correct render order) onto this plugins params
		$params = $this->setParams($params, $this->renderOrder);
		$res = $row->store();
		if ($res === false)
		{
			echo $row->getError();
			exit;
		}
		$obj = new stdClass();
		//do this to get the depth of the comment
		$comments = $this->getComments($row->formid, $row->row_id);
		$row = $comments[$row->id];
		$obj->content =$this->writeComment($params, $row);
		$obj->depth = (int) $row->depth;
		$obj->id = $row->id;
		$notificationPlugin = $this->notificationPluginInstalled($formModel);

		if ($notificationPlugin)
		{
			$this->addNotificationEvent($row, $formModel);
		}
		$comment_plugin_notify = JRequest::getVar('comment-plugin-notify');
		//do we notify everyone?
		if ($params->get('comment-internal-notify') == 1)
		{
			if ($notificationPlugin)
			{
				$this->saveNotificationToPlugin($row, $comments, $formModel);
			}
			else
			{
				$this->sentNotifications($row, $comments, $formModel);
			}
		}
		echo json_encode($obj);
	}

	/**
	 * add notification event
	 * @param	object	$row
	 * @param	object	$formModel
	 */
	function addNotificationEvent($row, $formModel)
	{
		$db = FabrikWorker::getDbo();
		$event = $db->quote('COMMENT_ADDED');
		$user = JFactory::getUser();
		$user_id = (int) $user->get('id');
		$ref = $db->quote($formModel->getlistModel()->getTable()->id . '.' . $formModel->get('id') . '.' . JRequest::getVar('rowid'));
		$date = $db->quote(JFactory::getDate()->toSql());
		$query = $db->getQuery(true);
		$query->insert('#__{package}_notification_event')->set(array('event = ' . $event, 'user_id = ' . $user_id, 'reference = ' . $ref, 'date_time = ' . $date));
		$db->setQuery($query);
		$db->query();
	}

	/**
	 * once we've ensured that the notification plugin is installed
	 * subscribe the user to the notification
	 * If comment-notify-admins is on then also subscribe admins to the notification
	 * @param	object	$row
	 * @param	array	comments objects
	 * @param	object	form model
	 */

	function saveNotificationToPlugin($row, $comments, $formModel)
	{
		$db = FabrikWorker::getDbo();
		$user = JFactory::getUser();
		$user_id = (int) $user->get('id');
		$label = $db->quote(JRequest::getVar('label'));
		$ref = $db->quote($formModel->getlistModel()->getTable()->id . '.' . $formModel->get('id') . '.' . JRequest::getVar('rowid'));
		$query = $db->getQuery(true);
		$query->insert('#__{package}_notification')->set(array('reason = ' . $db->quote('commentor'), 'user_id = ' . $user_id, 'reference = ' . $ref, 'label = ' . $label));
		$db->setQuery($query);
		$db->query();
		$params = $formModel->getParams();
		if ($params->get('comment-notify-admins') == 1)
		{
			$rows = $this->getAdminInfo();
			foreach ($rows as $row)
			{
				if ($row->id != $user_id)
				{
					$query->insert('#__{package}_notification')->set(array('reason = ' . $db->quote('admin observing a comment'), 'user_id = ' . $row->id, 'reference = ' . $ref, 'label = ' . $label));
					$db->setQuery($query);
					$db->query();
				}
			}
		}
	}

	/**
	 * test if the notification plugin is installed
	 * @param	$formModel
	 * @return	unknown_type
	 */

	function notificationPluginInstalled($formModel)
	{
		return FabrikWorker::getPluginManager()->pluginExists('cron', 'notification');
	}

	private function doDigg()
	{
		$params = $this->getParams();
		return $params->get('comment-digg') && FabrikWorker::getPluginManager()->pluginExists('element', 'digg');
	}

	/**
	 * default send notifcations code (sends to all people who have commented PLUS all admins)
	 * @param	object	$row
	 * @param	array	comments objects
	 * @param	object	form model
	 */

	function sentNotifications($row, $comments, $formModel)
	{
		$db = FabrikWorker::getDbo();
		$user = JFactory::getUser();
		$app = JFactory::getApplication();
		$sentto = array();
		$title = JText::_('PLG_FORM_COMMENT_NEW_COMMENT_ADDED_TITLE');
		$message = JText::_('PLG_FORM_COMMENT_NEW_COMMENT_ADDED');
		$message .= "<br /><a href=\"{$row->url}\">" . JText::_('PLG_FORM_COMMENT_VIEW_COMMENT'). "</a>";

		foreach ($comments as $comment)
		{
			if ($comment->id == $row->id)
			{
				//dont sent notification to user who just posted
				continue;
			}
			if (!in_array($comment->email, $sentto))
			{
				JUtility::sendMail($app->getCfg( 'mailfrom'), $app->getCfg( 'fromname'), $comment->email, $title, $message, true);
				$sentto[] = $comment->email;
			}
		}
		//notify original poster (hack for ideenbus
		$listModel = $formModel->getlistModel();
		$rowdata = $listModel->getRow($row->row_id);
		if (!in_array($rowdata->ide_idea___email_raw, $sentto))
		{
			JUtility::sendMail($app->getCfg( 'mailfrom'), $app->getCfg( 'fromname'), $rowdata->ide_idea___email_raw, $title, $message, true);
			$sentto[] = $rowdata->ide_idea___email_raw;
		}

		if ($params->get('comment-notify-admins') == 1) {
			//notify admins
			//get all super administrator
			$rows = $this->getAdminInfo();
			foreach ($rows as $row)
			{
				JUtility::sendMail($mailfrom, $fromname, $row->email, $subject2, $message2);
				if (!in_array($row->email, $sentto))
				{
					JUtility::sendMail($app->getCfg('mailfrom'), $app->getCfg('fromname'), $row->email, $title, $message, true);
					$sentto[] = $row->email;
				}
			}
		}
	}

	public function onGetEmail()
	{
		$commentid = JRequest::getInt('commentid');
		//TableComment
		$c = FabTable::getInstance('Comment', 'FabrikTable');
		$c->load($commentid);
		echo "<a href=\"mailto:$c->email\">$c->email</a>";
	}

	/**
	 * prepare jskit comment system - doesn't require a jskit acount
	 * @param unknown_type $params
	 */
	
	function _jskit(&$params)
	{
		$this->_data = '
 		<div class="js-kit-comments" permalink=""></div>
<script src="http://js-kit.com/comments.js"></script>';
	}

	/**
	 * prepate intense debate comment system
	 * @param unknown_type $params
	 */
	
	function _intensedebate(&$params)
	{
		FabrikHelperHTML::addScriptDeclaration("
var idcomments_acct = '".$params->get('comment-intesedebate-code')."';
var idcomments_post_id;
var idcomments_post_url;");
		$this->_data = '
<span id="IDCommentsPostTitle" style="display:none"></span>
<script type=\'text/javascript\' src=\'http://www.intensedebate.com/js/genericCommentWrapperV2.js\'></script>';
	}

	/**
	 * prepate diqus comment system
	 *
	 * @param object $params
	 */

	protected function _disqus($params)
	{
		if (JRequest::getVar('ajax') == 1)
		{
			$this->_data= '';
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
		document.write('<script type=\"text/javascript\" src=\"http://disqus.com/forums/rotterdamvooruit/get_num_replies.js' + query + '\"></' + 'script>');
	})();
"
		);
		$this->_data = '<div id="disqus_thread"></div><script type="text/javascript" src="http://disqus.com/forums/'.$params->get('comment-disqus-subdomain').'/embed.js"></script><noscript><a href="http://rotterdamvooruit.disqus.com/?url=ref">View the discussion thread.</a></noscript><a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>';;
	}

	/**
	 * prepare JComment system
	 *
	 * @param object $params
	 * @param object $formModel
	 */
	
	function _jcomment(&$params, $formModel)
	{
		$jcomments = JPATH_SITE . '/components/com_jcomments/jcomments.php';
		if (JFile::exists($jcomments))
		{
			require_once($jcomments);
			$this->_data = '<div id="jcomments" style="clear: both;">
                    '.JComments::show(JRequest::getVar('rowid'), "com_fabrik_{$formModel->getId()}").'
                    </div>';
		}
		else
		{
			JError::raiseNotice(500, JText::_('JComment is not installed on your system'));
		}
	}

}

?>