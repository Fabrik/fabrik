<?php
/**
 * Fabrik Coverflow Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.coverflow
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';

/**
 * Fabrik Coverflow Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.coverflow
 * @since       3.0
 */

class FabrikModelCoverflow extends FabrikFEModelVisualization
{

	/**
	 * Internally render the plugin, and add required script declarations
	 * to the document
	 *
	 * @return  void
	 */

	public function render()
	{
		require_once COM_FABRIK_FRONTEND . '/helpers/html.php';
		$app = JFactory::getApplication();
		$params = $this->getParams();
		$config = JFactory::getConfig();
		$document = JFactory::getDocument();
		$w = new FabrikWorker;

		$document->addScript("http://api.simile-widgets.org/runway/1.0/runway-api.js");
		$c = 0;
		$images = (array) $params->get('coverflow_image');
		$titles = (array) $params->get('coverflow_title');
		$subtitles = (array) $params->get('coverflow_subtitle');

		$config = JFactory::getConfig();

		$listids = (array) $params->get('coverflow_table');
		$eventdata = array();
		foreach ($listids as $listid)
		{
			$listModel = JModel::getInstance('List', 'FabrikFEModel');
			$listModel->setId($listid);
			$list = $listModel->getTable();
			$nav = $listModel->getPagination(0, 0, 0);
			$image = $images[$c];
			$title = $titles[$c];
			$subtitle = $subtitles[$c];
			$data = $listModel->getData();
			if ($listModel->canView() || $listModel->canEdit())
			{
				$elements = $listModel->getElements();
				$imageElement = JArrayHelper::getValue($elements, FabrikString::safeColName($image));
				$action = $app->isAdmin() ? "task" : "view";
				$nextview = $listModel->canEdit() ? "form" : "details";

				foreach ($data as $group)
				{
					if (is_array($group))
					{
						foreach ($group as $row)
						{
							$event = new stdClass;
							if (!method_exists($imageElement, 'getStorage'))
							{
								// JError::raiseError(500, 'Looks like you selected a element other than a fileupload element for the coverflows image element');
								switch (get_class($imageElement))
								{
									case 'FabrikModelFabrikImage':
										$rootFolder = $imageElement->getParams()->get('selectImage_root_folder');
										$rootFolder = JString::ltrim($rootFolder, '/');
										$rootFolder = JString::rtrim($rootFolder, '/');
										$event->image = COM_FABRIK_LIVESITE . 'images/stories/' . $rootFolder . '/' . $row->{$image . '_raw'};
										break;
									default:
										$event->image = isset($row->{$image . '_raw'}) ? $row->{$image . '_raw'} : '';
										break;
								}
							}
							else
							{
								$event->image = $imageElement->getStorage()->pathToURL($row->{$image . '_raw'});
							}
							$event->title = (string) strip_tags($row->$title);
							$event->subtitle = (string) strip_tags($row->$subtitle);
							$eventdata[] = $event;
						}
					}
				}
			}
			$c++;
		}
		$json = json_encode($eventdata);
		$str = "var coverflow = new FbVisCoverflow($json);";
		$srcs = FabrikHelperHTML::framework();
		$srcs[] = $this->srcBase . 'coverflow/coverflow.js';
		FabrikHelperHTML::script($srcs, $str);
	}

	/**
	 * Set an array of list id's whose data is used inside the visualaziation
	 *
	 * @return  void
	 */

	protected function setListIds()
	{
		if (!isset($this->listids))
		{
			$params = $this->getParams();
			$this->listids = (array) $params->get('coverflow_table');
		}
	}
}
