<?php
/**
 * Fabrik Admin Database Exporter Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.3.5
 */

defined('JPATH_PLATFORM') or die;

/**
 * MySQLi export driver.
 * Tmp fix until https://issues.joomla.org/tracker/joomla-cms/7378 and
 * https://issues.joomla.org/tracker/joomla-cms/8615
 * are merged
 *
 * @since  11.1
 */
class JDatabaseExporterMysqli2 extends JDatabaseExporterMysqli
{
	public $exception = null;

	/**
	 * Builds the XML structure to export.
	 *
	 * @return  array  An array of XML lines (strings).
	 *
	 * @since   11.1
	 * @throws  Exception if an error occurs.
	 */
	protected function buildXmlStructure()
	{
		$buffer = array();

		foreach ($this->from as $table)
		{
			// Replace the magic prefix if found.
			$table = $this->getGenericTableName($table);

			// Get the details columns information.
			$fields = $this->db->getTableColumns($table, false);
			$keys = $this->db->getTableKeys($table);

			$buffer[] = '  <table_structure name="' . $table . '">';

			foreach ($fields as $field)
			{
				$buffer[] = '   <field Field="' . $field->Field . '"' . ' Type="' . $field->Type . '"' . ' Null="' . $field->Null . '"' . ' Key="' .
						$field->Key . '"' . (isset($field->Default) ? ' Default="' . $field->Default . '"' : '') . ' Extra="' . $field->Extra . '"' .
						' />';
			}

			foreach ($keys as $key)
			{
				$buffer[] = '   <key Table="' . $table . '"' . ' Non_unique="' . $key->Non_unique . '"' . ' Key_name="' . $key->Key_name . '"' .
						' Seq_in_index="' . $key->Seq_in_index . '"' . ' Column_name="' . $key->Column_name . '"' . ' Collation="' . $key->Collation . '"' .
						// Rob
						' Sub_part ="' . $key->Sub_part . '"' .
						' Null="' . $key->Null . '"' . ' Index_type="' . $key->Index_type . '"' . ' Comment="' . htmlspecialchars($key->Comment) . '"' .
						' />';
			}

			$buffer[] = '  </table_structure>';
		}

		return $buffer;
	}

	/**
	 * Magic function to exports the data to a string.
	 * Overriding for Fabrik 'cos __toString cannot throw exceptions (PHP fatal error)
	 *
	 * @return  string
	 *
	 * @since   13.1
	 * @throws  Exception if an error is encountered.
	 */
	public function __toString()
	{
		// Check everything is ok to run first.
		try
		{
			$this->check();

			// Get the format.
			switch ($this->asFormat)
			{
				case 'xml':
				default:
					$buffer = $this->buildXml();
					break;
			}

			return $buffer;
		}
		catch (Exception $e)
		{
			// store the exception in a public var the caller can check if return is empty
			$this->exception = $e;
			return '';
		}
	}
}