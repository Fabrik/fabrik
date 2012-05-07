<?php
/**
 * Plugin element to render cascading dropdown
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'plugins'.DS.'fabrik_element'.DS.'databasejoin'.DS.'databasejoin.php');

class plgFabrik_ElementNotes extends plgFabrik_ElementDatabasejoin
{


	/** @var int last row id to be inserted via ajax call */
	protected $loadRow = null;
	
	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->rowid = $this->getFormModel()->getRowId();
		$opts->id = $this->_id;
		$opts = json_encode($opts);
		return "new FbNotes('$id', $opts)";
	}

	/**
	 * child classes can then call this function with
	 * return parent::renderListData($data, $oAllRowsData);
	 * to perform rendering that is applicable to all plugins
	 *
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderListData($data, $oAllRowsData )
	{
		return parent::renderListData($data, $oAllRowsData);
	}

	protected function getNotes()
	{
		$db = $this->getDb();
	}
	/**
	 * draws the form element
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns field element
	 */

	function render($data, $repeatCounter = 0)
	{
		$str = array();
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$name = $this->getHTMLName($repeatCounter);
		$tmp = $this->_getOptions($data, $repeatCounter, true);
		$rowid = $this->getFormModel()->getRowId();
		$str[] = '<div id="'.$id.'">';
		$str[] = '<div style="overflow:auto;height:150px;" class=""><ul>';
		$i = 0;
		foreach ($tmp as $row) {
			$txt = $this->getDisplayLabel($row);
			$str[] = '<li class="oddRow' . $i . '">' . $txt . '</li>';
			$i = 1 - $i;
		}
		$str[] = '</ul></div>';
		$str[] = '<div class="noteHandle" style="height:3px;"></div>';
		//Jaanus - Submitting notes before saving form data results with the notes belonging to nowhere but new, not submitted forms.
		if ($rowid > 0) {
		if ($params->get('fieldType', 'textarea') == 'field') {
			$str[] = '<input class="fabrikinput inputbox text" name="'.$name.'"  />';
		} else {
			$str[] = '<textarea class="fabrikinput inputbox text" name="'.$name.'" cols="50" rows="3" /></textarea>';
		}
		$str[] = '<input type="button" class="button" value="' . JText::_('PLG_ELEMENT_NOTES_ADD') . '"></input>';
		}
		else {
		$str[] = JText::_('PLG_ELEMENT_NOTES_SAVEFIRST');
		}
		$str[] = '</div>';
		return implode("\n", $str);
	}
	
	protected function getDisplayLabel($row)
	{
		$params = $this->getParams();
		if ($params->get('showuser', true)) {
			$txt = $this->getUserNameLinked($row) . ' '  .$row->text;
		} else {
			$txt = $row->text;
		}
		return $txt;
	}
	
	protected function getUserNameLinked($row)
	{
		if ($this->hasComponent('com_uddeim')) {
			if (isset($row->username)) {
 			return '<a href="index.php?option=com_uddeim&task=new&recip=' . $row->userid . '">' . $row->username . '</a> ';
			}
		}
		return '';
	}
	
	protected function hasComponent($c)
	{
		if (!isset($this->components)) {
			$this->components = array();
		}
		if (!array_key_exists($c, $this->components)) {
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('COUNT(id)')->from('#__extensions')->where('name = ' . $db->Quote($c));
			$db->seQuery($query);
			$found = $db->loadResult();
			$this->components[$c] = $found;
		}
		return $this->components[$c];
	}
	
	function _buildQueryWhere($data = array(), $incWhere = true)
	{
		$params = $this->getParams();
		$db = $this->getDb();
		$field = $params->get('notes_where_element');
		$value = $params->get('notes_where_value');
		$fk = $params->get('join_fk_column', '');
		$rowid = $this->getFormModel()->getRowId();
		// Jaanus - commented out as unnecessary, some variables moved above 
		/*if ($field == '') {
			return '';
		}
		*/
		$where = array();
		// Jaanus: here we can choose whether WHERE has to have single or (if field is the same as FK then only) custom (single or multiple) criterias,
		if ($value != '') {
			if ($field != '' && $field !== $fk) {
			$where[] = $db->nameQuote($field) . ' = ' . $db->Quote($value);
			}
			else {
			$where[] = $value;
			}
		}
		// Jaanus: when we choose WHERE field to be the same as FK then WHERE criteria is automatically FK = rowid, custom criteria(s) above may be added
		if ($fk !== '' && $field === $fk && $rowid != '') {
			$where[] = $db->nameQuote($fk) . ' = ' . $rowid;
		}
		if ($this->loadRow != '') {
			$pk = $db->nameQuote($this->getJoin()->table_join_alias) . '.' .  $db->nameQuote($params->get('join_key_column')) ; 
			$where[] = $pk . ' = ' . $this->loadRow;
		}
		return 'WHERE ' . implode(" OR ", $where); //Jaanus: not sure why AND was originally here
	}
	
	protected function getOrderBy()
	{
		$params = $this->getParams();
		$db = $this->getDb();
		$orderBy = $params->get('notes_order_element');
		if ($orderBy == '') {
			return '';
		} else {
			return " ORDER BY " . $db->nameQuote($orderBy) . ' ' . $params->get('notes_order_dir', 'ASC');
		}
	}
	
	/**
	* @since 3.0rc1
	* if _buildQuery needs additional fields then set them here, used in notes plugin
	* @return string fields to add e.g return ',name, username AS other'
	*/
	
	protected function getAdditionalQueryFields()
	{
		$fields = '';
		$db = $this->getDb();
		$params = $this->getParams();
		if ($params->get('showuser', true)) {
			$user = $params->get('userid', '');
			if ($user !== '') {
				$tbl = $db->nameQuote($this->getJoin()->table_join_alias);
				$fields .= ',' . $tbl . '.' . $db->nameQuote($user) . 'AS userid, u.name AS username';
			}
		}
		return $fields;
	}
	
	/**
	* @since 3.0rc1
	* if _buildQuery needs additional joins then set them here, used in notes plugin
	* @return string join statement to add
	*/
	
	protected function buildQueryJoin()
	{
		$join= '';
		$db = $this->getDb();
		$params = $this->getParams();
		if ($params->get('showuser', true)) {
			$user = $params->get('userid', '');
			if ($user !== '') {
				$tbl = $db->nameQuote($this->getJoin()->table_join_alias);
				$join .= ' LEFT JOIN #__users AS u ON u.id = ' . $tbl . '.' . $db->nameQuote($user);
			} else {
				
			}
		}
		return $join;
	}
	
	/**
	* @since 3.0b
	* do you add a please select option to the cdd list
	* @return boolean
	*/
	
	protected function showPleaseSelect()
	{
		return false;
	}
	
	public function onAjax_addNote()
	{
		$this->loadMeForAjax();
		$return = new stdClass();
		$db = $this->getDb();
		$query = $db->getQuery(true);
		$params = $this->getParams();
		$table = $db->nameQuote($params->get('join_db_name'));
		$col = $params->get('join_val_column');
		$key = $db->nameQuote($params->get('join_key_column'));
		$v = $db->Quote(JRequest::getVar('v'));
		$rowid = $this->getFormModel()->getRowId();
		
		//Jaanus - avoid inserting data when the form is 'new' not submitted ($rowid == 0)
		if ($rowid > 0) {
		$query->insert($table)
		->set($col . ' = ' . $v);
		
		//Jaanus - commented the $field related code out as it doesn't seem to have sense and it generated "ajax failed" error in submission when where element was selected
		/*$field = $params->get('notes_where_element', '');
		if ($field !== '') {
			$query->set($db->nameQuote($field) . ' = ' . $db->Quote($params->get('notes_where_value')));
		}
		*/
		$user = $params->get('userid', '');
		if ($user !== '') {
			$query->set($db->nameQuote($user) . ' = ' . (int)JFactory::getUser()->get('id'));
		}
		
		$fk = $params->get('join_fk_column', '');
		if ($fk !== '') {
			$query->set($db->nameQuote($fk) . ' = ' . $db->Quote(JRequest::getVar('rowid')));
		}
		
		$db->setQuery($query);
		
		if (!$db->query()) {
			JError::raiseError(500, 'db insert failed');
		} else {
			$this->loadRow = $db->Quote($db->insertid());
			$opts = $this->_getOptions();
			$row = $opts[0];
		/* 	$query->clear();
			$query->select('*')->from($table)->where($key . ' = ' . $inertId);
			$db->setQuery($query);
			$row = $db->loadObject();*/
			
			$return->msg = 'note added';
			$return->data = $row;
			$return->label = $this->getDisplayLabel($row);
			echo json_encode($return);
		}
	}
		
	}
	

}
?>