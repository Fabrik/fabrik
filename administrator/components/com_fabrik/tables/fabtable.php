<?php
/**
 * Base Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Base Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabTable extends JTable
{

	/**
	 * Static method to get an instance of a JTable class if it can be found in
	 * the table include paths.  To add include paths for searching for JTable
	 * classes @see JTable::addIncludePath().
	 *
	 * @param   string  $type    The type (name) of the JTable class to get an instance of.
	 * @param   string  $prefix  An optional prefix for the table class name.
	 * @param   array   $config  An optional array of configuration values for the JTable object.
	 *
	 * @return  mixed	A JTable object if found or boolean false if one could not be found.
	 */

	public static function getInstance($type, $prefix = 'JTable', $config = array())
	{
		$config['dbo'] = FabrikWorker::getDbo(true);
		return parent::getInstance($type, $prefix, $config);
	}

	/**
	 * Batch set a properties and params
	 *
	 * @param   array  $batch  properties and params
	 *
	 * @since   3.0.7
	 *
	 * @return  bool
	 */

	public function batch($batch)
	{
		$batchParams = JArrayHelper::getValue($batch, 'params');
		unset($batch['params']);
		$query = $this->_db->getQuery(true);
		$this->bind($batch);
		$params = json_decode($this->params);
		foreach ($batchParams as $key => $val)
		{
			$params->$key = $val;
		}
		$this->params = json_encode($params);
		return $this->store();
	}

}
