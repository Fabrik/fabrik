<?php
/**
 * Base Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
	 * JSON encoded JFormField param options
	 *
	 * @var string
	 */
	public $params = '';

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

		$instance = parent::getInstance($type, $prefix, $config);

		/**
		 * $$$ hugh - we added $params in this commit:
		 * https://github.com/Fabrik/fabrik/commit/d98ad7dfa48fefc8b2db55dd5c7a8de16f9fbab4
		 * ... but the FormGroup table doesn't have a params column.  For now, zap the params for FormGroup,
		 * until we do another release and can add an SQL update to add it.
		 *
		 * $$$ hugh - neither does the comments table ...
		 */
		if ($type === 'FormGroup' || $type === 'comment')
		{
			unset($instance->params);
		}

		return $instance;
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
		$batchParams = FArrayHelper::getValue($batch, 'params');
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

	/**
	 * Get the columns from database table.
	 *
	 * @param   bool  $reload  flag to reload cache
	 *
	 * @return  mixed  An array of the field names, or false if an error occurs.
	 *
	 * @since   11.1
	 * @throws  UnexpectedValueException
	 */
	public function getFields($reload = false)
	{
		static $cache = array();

		if (FArrayHelper::getValue($cache, $this->_tbl) === null || $reload)
		{
			// Lookup the fields for this table only once. PER TABLE NAME!
			$name   = $this->_tbl;
			$fields = $this->_db->getTableColumns($name, false);

			if (empty($fields))
			{
				throw new UnexpectedValueException(sprintf('No columns found for %s table', $name));
			}

			$cache[$this->_tbl] = $fields;
		}

		return $cache[$this->_tbl];
	}
}
