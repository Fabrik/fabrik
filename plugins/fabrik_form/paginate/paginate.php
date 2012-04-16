<?php
/**
 * Form record next/prev scroll plugin
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-form.php');

class plgFabrik_FormPaginate extends plgFabrik_Form {

	/**
	 * get the html to insert at the bottom of the form(non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelFormPlugin#getBottomContent_result()
	 */

	function getBottomContent_result($c)
	{
		return $this->_data;
	}

	/**
	 * store the html to insert at the bottom of the form(non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelFormPlugin#getBottomContent()
	 */

	function getBottomContent(&$params, &$formModel)
	{
		if (!$this->show($params, $formModel)) {
			return;
		}
		$app = JFactory::getApplication();

		$formId = $formModel->getForm()->id;
		$mode = strtolower(JRequest::getCmd('view'));
		$this->ids = $this->getNavIds($formModel);
		$linkStartPrev = $this->ids->index == 0 ? ' disabled' : '';
		$linkNextEnd = $this->ids->index == $this->ids->lastKey ? ' disabled' : '';
		if ($app->isAdmin()) {
			$url = 'index.php?option=com_fabrik&view='.$mode.'&formid='.$formId.'&rowid=';
		} else {
			$url = 'index.php?option=com_fabrik&view='.$mode.'&formid='.$formId.'&rowid=';
		}
		$ajax = (bool)$params->get('paginate_ajax', true);
		$firstLink = ($linkStartPrev) ? '<span>&lt;&lt;</span>' . JText::_('COM_FABRIK_START') : '<a href="'.JRoute::_($url.$this->ids->first).'" class="pagenav paginateFirst '.$linkStartPrev.'"><span>&lt;&lt;</span>' . JText::_('COM_FABRIK_START').'</a>';
		$prevLink = ($linkStartPrev) ? '<span>&lt;</span>' . JText::_('COM_FABRIK_PREV') : '<a href="'.JRoute::_($url.$this->ids->prev).'" class="pagenav paginatePrevious '.$linkStartPrev.'"><span>&lt;</span>' . JText::_('COM_FABRIK_PREV').'</a>';

		$nextLink = ($linkNextEnd) ? JText::_('COM_FABRIK_NEXT') . '<span>&gt;</span>' : '<a href="'.JRoute::_($url.$this->ids->next).'" class="pagenav paginateNext'.$linkNextEnd.'">'.JText::_('COM_FABRIK_NEXT') . '<span>&gt;</span></a>';
		$endLink = ($linkNextEnd) ? JText::_('COM_FABRIK_END') . '<span>&gt;&gt;</span>' : '<a href="'.JRoute::_($url.$this->ids->last).'" class="pagenav paginateLast'.$linkNextEnd.'">'.JText::_('COM_FABRIK_END'). '<span>&gt;&gt;</span></a>';
		$this->_data = '<ul id="fabrik-from-pagination" class="pagination">
				<li>'.$firstLink.'</li>
				<li>'.$prevLink.'</li>
				<li>'.$nextLink.'</li>
				<li>'.$endLink.'</li>
		</ul>';
		FabrikHelperHTML::stylesheet('plugins/fabrik_form/paginate/paginate.css');
		return true;
	}

	/**
	 * get the first last, prev and next record ids
	 * @param object $formModel
	 */
	protected function getNavIds($formModel)
	{
		$listModel = $formModel->getListModel();
		$table = $listModel->getTable();
		$db = $listModel->getDb();

		$join = $listModel->_buildQueryJoin();
		$where = $listModel->_buildQueryWhere();
		$order = $listModel->_buildQueryOrder();

		// @ rob as we are selecting on primary key we can select all rows - 3000 records load in 0.014 seconds
		$query = "SELECT $table->db_primary_key FROM $table->db_table_name $join $where $order";

		$db->setQuery($query);
		$rows = $db->loadColumn();
		$keys = array_flip($rows);
		$o = new stdClass();
		$o->index = JArrayHelper::getValue($keys, $formModel->_rowId, 0);

		$o->first = $rows[0];
		$o->lastKey = count($rows)-1;
		$o->last = $rows[$o->lastKey];
		$o->next = $o->index + 1 > $o->lastKey ? $o->lastKey : $rows[$o->index + 1];
		$o->prev = $o->index - 1 < 0 ? 0 : $rows[$o->index - 1];
		return $o;
	}

	protected function show($params, $formModel)
	{
# Nobody except form model constuctor sets _editable property yet -
# it sets in view.html.php only and after render() - too late I think
# so no pagination output for frontend details veiw for example.
# Let's set it here before use it
		$formModel->checkAccessFromListSettings();

$where = $params->get('paginate_where');
		switch($where) {
			case 'both':
				return true;
				break;
			case 'form':
				return (int)$formModel->_editable == 1;
				break;
			case 'details':
				return (int)$formModel->_editable == 0;
				break;
		}
	}

	/**
	 * process the plugin, called when form is submitted
	 *
	 * @param object $params
	 * @param object form
	 */

	function onAfterJSLoad(&$params, &$formModel)
	{
		if (!$this->show($params, $formModel)) {
			return;
		}
		if ($params->get('paginate_ajax') == 0) {
			return;
		}
		$opts = new stdClass();
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$opts->view = JRequest::getCmd('view');
		$opts->ids = $this->ids;
		$opts->pkey = FabrikString::safeColNameToArrayKey($formModel->getTableModel()->getTable()->db_primary_key);
		$opts = json_encode($opts);
		$form =& $formModel->getForm();
		$container = $formModel->_editable ? 'form' : 'details';
		$container .= "_".$form->id;
		
		$scripts = array(
		'plugins/fabrik_form/paginate/scroller.js',
		'media/com_fabrik/js/encoder.js'
		);
		$code = "$container.addPlugin(new FabRecordSet($container, $opts));";
		FabrikHelperHTML::script($scripts, $code);
		
		/* if (JRequest::getVar('tmpl') != 'component') {
			FabrikHelperHTML::script('scroller.js', 'components/com_fabrik/plugins/form/paginate/');
			FabrikHelperHTML::script('encoder.js', 'media/com_fabrik/js/');
			FabrikHelperHTML::addScriptDeclaration("
			window.addEvent('load', function() {
			$container.addPlugin(new FabRecordSet($container, $opts));
	 		});");
		} else {
			// included scripts in the head don't work in mocha window
			// read in the class and insert it into the body as an inline script
			$class = JFile::read(JPATH_BASE."/components/com_fabrik/plugins/form/paginate/scroller.js");
			FabrikHelperHTML::addScriptDeclaration($class);
			$class = JFile::read(JPATH_BASE."/media/com_fabrik/js/encoder.js");
			FabrikHelperHTML::addScriptDeclaration($class);
			//there is no load event in a mocha window - use domready instead
			FabrikHelperHTML::addScriptDeclaration("
				window.addEvent('domready', function() {
				$container.addPlugin(new FabRecordSet($container, $opts));
	 		});");
		} */
	}

	function onXRecord()
	{
		$this->xRecord();
	}
	/**
	 * called from plugins ajax call
	 */

	function xRecord()
	{
		$formid = JRequest::getInt('formid');
		$rowid = JRequest::getVar('rowid');
		$mode = JRequest::getVar('mode', 'details');
		$model =& JModel::getInstance('Form', 'FabrikFEModel');
		$model->setId($formid);
		$model->_rowId = $rowid;
		$ids = $this->getNavIds($model);
		$url = COM_FABRIK_LIVESITE.'index.php?option=com_fabrik&format=raw&controller=plugin&g=form&task=pluginAjax&plugin=paginate&method=xRecord&formid='.$formid.'&rowid='.$rowid;
		$url = COM_FABRIK_LIVESITE.'index.php?option=com_fabrik&c=form&view='.$mode.'&fabrik='.$formid.'&rowid='.$rowid.'&format=raw';
		$ch = curl_init();
 	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
 	  curl_close($ch);
 	  //apend the ids to the json array
 	  $data = json_decode($data);
 	  //$data['ids'] = $ids;
 	  $data->ids = $ids;
 	  echo json_encode($data);

	}

}
?>