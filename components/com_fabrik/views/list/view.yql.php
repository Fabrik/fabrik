<?php
/**
 * Display the template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

/**
 * List YQL view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.6
 */
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
		$model = $this->getModel();
		$input = $this->app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('listid', $usersConfig->get('listid')));
		$model->render();
		$table = $model->getTable();
		$this->doc->title = $table->label;
		$this->doc->description = $table->introduction;
		$this->doc->copyright = '';
		$this->doc->listid = $table->id;
		$this->doc->items = $model->getData();
	}
}
