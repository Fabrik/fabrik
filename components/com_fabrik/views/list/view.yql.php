<?php
/**
 * Display the template
 *
 * @package     Joomla
 * @subpackage	Fabik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

class FabrikViewList extends FabrikViewListBase
{

	/**
	 * Display the template
	 *
	 * @param   sting  $tpl  template
	 *
	 * @return void
	 */

	public function display($tpl = null)
	{
		$document = JFactory::getDocument();
		$model = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('listid', $usersConfig->get('listid')));
		$model->render();
		$table = $model->getTable();

		$document->title = $table->label;
		$document->description = $table->introduction;
		$document->copyright = '';
		$document->listid = $table->id;

		$document->items = $model->getData();

	}
}
