<?php
/**
 * Fabrik Admin Database Importer Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.3.5
 */

defined('JPATH_PLATFORM') or die;

/**
 * MySQLi import driver.
 * Tmp fix until https://issues.joomla.org/tracker/joomla-cms/7378 and
 * https://issues.joomla.org/tracker/joomla-cms/8615
 * are merged
 *
 * @since  11.1
 */
class JDatabaseImporterMysqli2 extends JDatabaseImporterMysqli
{
	/**
	 * Get the SQL syntax to add a table.
	 *
	 * @param   SimpleXMLElement $table The table information.
	 *
	 * @return  string
	 *
	 * @since   11.1
	 * @throws  RuntimeException
	 */
	protected function xmlToCreate(SimpleXMLElement $table)
	{
		$existingTables = $this->db->getTableList();
		$tableName      = (string) $table['name'];

		if (in_array($tableName, $existingTables))
		{
			throw new RuntimeException('The table you are trying to create already exists');
		}

		$createTableStatement = 'CREATE TABLE ' . $this->db->quoteName($tableName) . ' (';

		foreach ($table->xpath('field') as $field)
		{
			$createTableStatement .= $this->getColumnSQL($field) . ', ';
		}

		$newLookup = $this->getKeyLookup($table->xpath('key'));

		// Loop through each key in the new structure.
		foreach ($newLookup as $key)
		{
			$createTableStatement .= $this->getKeySQL($key) . ', ';
		}

		// Remove the comma after the last key
		$createTableStatement = rtrim($createTableStatement, ', ');

		$createTableStatement .= ')';

		return $createTableStatement;
	}

	/**
	 * Get the SQL syntax for a key.
	 *
	 * @param   array $columns An array of SimpleXMLElement objects comprising the key.
	 *
	 * @return  string
	 *
	 * @since   11.1
	 */
	protected function getKeySql($columns)
	{
		// TODO Error checking on array and element types.

		$kNonUnique = (string) $columns[0]['Non_unique'];
		$kName      = (string) $columns[0]['Key_name'];
		$kColumn    = (string) $columns[0]['Column_name'];

		// Rob
		$kLength = (string) $columns[0]['Sub_part'];
		$kLength = $kLength == '' ? '' : '(' . $kLength . ')';

		$prefix = '';

		if ($kName == 'PRIMARY')
		{
			$prefix = 'PRIMARY ';
		}
		elseif ($kNonUnique == 0)
		{
			$prefix = 'UNIQUE ';
		}

		$nColumns = count($columns);
		$kColumns = array();

		if ($nColumns == 1)
		{
			$kColumns[] = $this->db->quoteName($kColumn) . $kLength;
		}
		else
		{
			foreach ($columns as $column)
			{
				$kColumns[] = (string) $column['Column_name'] . $kLength;
			}
		}

		$query = $prefix . 'KEY ' . ($kName != 'PRIMARY' ? $this->db->quoteName($kName) : '') . ' (' . implode(',', $kColumns) . ')';

		return $query;
	}
}