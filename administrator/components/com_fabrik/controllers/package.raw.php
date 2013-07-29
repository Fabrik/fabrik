<?php
/**
 * Raw Package controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Raw Package controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikControllerPackage extends JControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_PACKAGE';

	/**
	 * Constructor
	 *
	 * @param   array  $config  options
	 */

	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	/**
	 * list of items
	 *
	 * @return  null
	 */

	public function dolist()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$list = $input->get('list', 'form');
		$selected = $input->get('selected');
		$query->select('id, label')->from('#__fabrik_' . $list . 's');
		if ($selected != '')
		{
			// $query->where('id NOT IN ('.$selected.')');
		}
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		echo "<ul id=\"$list-additems\">";
		if (empty($rows))
		{
			echo "<li>" . JText::sprintf('COM_FABRIK_NO_FREE_ITEMS_FOUND') . "</li>";
		}
		else
		{
			foreach ($rows as $row)
			{
				echo "<li><a href=\"#\" id=\"$row->id\">$row->label</a>";
			}
		}
		echo "</ul>";
		$script = "$('$list-additems').getElements('a').addEvent('click', function(e){
			Fabrik.fireEvent('fabrik.package.item.selected', [e]);
		});";
		FabrikHelperHTML::addScriptDeclaration($script);
	}

}
