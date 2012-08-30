<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.filterview
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Adds a sidebar containing a list of filters to filter the list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.filterview
 * @since       3.0
 */

class plgFabrik_ListFilter_view extends plgFabrik_List
{

	protected $buttonPrefix = 'filter_view';

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */

	protected function getAclParam()
	{
		return 'filter_view_access';
	}

	public function onGetContentBeforeList($params, $model, $args)
	{
		$opts = json_decode($params->get('filter_view_settings'));
		$labels = $opts ? $opts->label : array();
		$db = $model->getDb();
		$item = $model->getTable();
		$href = 'index.php?option=com_fabrik&view=list&listid=' . $model->getId();
		$html = array();
		$html[] = '<div class="filter_view" style="width:200px">';
		if (!empty($labels))
		{
			$links = $opts->url;
			$html[] = '<ul class="fabrik_filter_view">';
			for ($i = 0; $i < count($labels); $i++)
			{
				$base = JURI::base();
				$base .= JString::strpos($base, '?') ? '&' : '?';
				$class = $links[$i] == urldecode($_SERVER['QUERY_STRING']) ? 'active' : '';
				$links[$i] = str_replace('+', '%2B', $links[$i]);
				$url = $base . $links[$i];

				$subhtml = array();
				if (strstr($links[$i], 'group_by'))
				{

					$pairs = explode('&', $links[$i]);
					$subSel = false;
					foreach ($pairs as $pair)
					{
						list($key, $val) = explode("=", $pair);

						if ($key == 'group_by')
						{
							$query = $db->getQuery(true);
							$query = $model->_buildQueryWhere(false, $query);
							$element = $model->getFormModel()->getElement($val);
							if (!$element)
							{
								JError::raiseNotice(500, 'could not load group by element ' . $val);
								continue;
							}
							$aFields = array();
							$aAsFields = array();
							$element->getAsField_html($aFields, $aAsFields);
							$pval = str_replace('___', '.', $val);
							$query->select(implode(', ', $aFields))->from($db->quoteName($item->db_table_name));
							$query = $model->_buildQueryJoin($query);
							$query->group($db->quoteName($pval));
							$query->order($pval . ' ASC');
							$db->setQuery($query);
							$rows = $db->loadObjectList();

							$subhtml[] = '<ul class="floating-tip">';
							$vraw = $val . '_raw';
							foreach ($rows as $row)
							{
								//$qs = $val.'_raw='.$row->$vraw;
								$qs = str_replace("$key=$val", $val . '_raw=' . $row->$vraw, $links[$i]);
								if ($qs == $_SERVER['QUERY_STRING'])
								{
									$subclass = 'active';
									$subSel = true;
								}
								else
								{
									$subclass = '';
								}
								$subhtml[] = '<li class="' . $subclass . '"><a style="display:block" href="' . $base . $qs . '">' . $row->$val
									. '</a></li>';
							}
							$subhtml[] = '</ul>';
						}
					}
					if ($class == '' && $subSel)
					{
						$class = 'active';
					}
					$class .= " hasSubOptions";
					$url = '#';
				}
				$html[] = '<li class="' . $class . '"><a style="display:block" href="' . $url . '">' . $labels[$i] . '</a></li>';
				$html = array_merge($html, $subhtml);
			}
			$html[] = '</ul>';
		}
		$html[] = '</div>';
		$this->html = implode("\n", $html);
	}

	public function onGetContentBeforeList_result()
	{
		return $this->html;
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   object  $params  plugin parameters
	 * @param   object  $model   list model
	 * @param   array   $args    array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */

	public function onLoadJavascriptInstance($params, $model, $args)
	{
		parent::onLoadJavascriptInstance($params, $model, $args);
		$opts = $this->getElementJSOptions($model);
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListFilterView($opts)";
		return true;
	}

}
?>