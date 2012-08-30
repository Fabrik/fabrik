<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die();

require_once COM_FABRIK_FRONTEND . '/helpers/arrayhelper.php';
require_once COM_FABRIK_FRONTEND . '/models/connection.php';

/**
 * $$$ moofoo 
 * This is a temporary helper for dealing with situations where it's not readily clear (to me) how to derive the database name 
 * from the available objects/models within the scope of some given function (like getDBFields in the List model).
 */		

class FConnectionHelper 
{

	/**
	 * Get a hash of connection information. For example, an associative array where the 'id' property is the index (key), and the 'database' property is the value.
	 *
	 * @param   string $keyProp connection property to use as hash index. If null, returns an indexed array instead
	 * @param   string $valueProp connection property to use as hash value. If null, returns hash of connection objects indexed by $keyProp.
	 *
	 * @return array tables
	 */	

	public function getConnectionHash($keyProp, $valueProp = NULL)
	{
		$connections = FabrikFEModelConnection::getConnections();		
		return FArrayHelper::getHash($connections, $keyProp, $valueProp);
	}

	/**
	 * given a fabrik connection id, return the name of the related database
	 *
	 * @param  integer $conn_id a fabrik connection id
	 *
	 * @return string the name of the database connected to the fabrik connection where connection id = $conn_id
	 */	

	public function getDbFromConnection($conn_id){
		static $connection_list;
		static $cache;

		if(!$cache[$conn_id])
		{
			if(empty($connection_list))
			{
				$connection_list = self::getConnectionHash('id', 'database');
			}

			$cache[$conn_id] = $connection_list[$conn_id];
		}

		return $cache[$conn_id];	
	}

	/**
	 * given the name of a table, return the name of the fabrik connection (database) that has that table.
	 *
	 * @param  string $tbl the name of a table
	 *
	 * @return string the name of the fabrik connection (database) database that contains the table
	 */	

	public function getDbFromTable($tbl){
		static $table_list;
		static $cache;

		if(!$cache[$tbl]){

			if(empty($table_list))
			{
				$db_list = self::getConnectionHash(NULL, 'database');
				$db =& JFactory::getDBO();
				$s = sizeof($db_list);
				$db_str = "'".$db_list[0]."'";
				for($x = 1; $x < $s; $x++){
					$db_str .= ",'".$db_list[$x]."'";
				}

				$sql = "select table_schema, table_name FROM information_schema.tables WHERE table_schema IN (".$db_str.") ORDER BY table_schema ASC, table_name ASC";
				$db->setQuery($sql);
				$schemas_tables = $db->loadAssocList();
				if($db->getErrorNum()){echo $db->stderr();}
				$s = sizeof($schemas_tables);
				$table_list = array();

				for($x = 0; $x < $s; $x++){
					$schema = $schemas_tables[$x]['table_schema'];
					$table = $schemas_tables[$x]['table_name'];
					if(!array_key_exists($schema, $table_list)){
						$table_list[$schema] = array();
					}

					array_push($table_list[$schema], $table);
				}
			}

			
			$result = '';
			$index = 0;

			foreach($table_list AS $dbName => $tbls){
				$index = array_search($tbl, $tbls);
				if($index !== FALSE){
					$result = $dbName;
					unset($table_list[$dbName][$index]);
					$table_list[$dbName] = array_values($table_list[$dbName]);
					break;
				}
			}

			$cache[$tbl] = $result;
		}

		return $cache[$tbl];
	}

	/**
	 * Returns the name of the fabrik connection (database) related to the passed in argument.
	 * FabrikFEModelConnection
	 * @param  integer $arg a fabrik connection id
	 * @param  string $arg the name of a table (function returns the database containing the table)
	 * @param  object $arg either a List, Element or Form Model, or a JDatabase object.
	 *
	 * @return string the name of the fabrik connection (database) database
	 */		

	public function getDbFromArg($arg){	

		$ret = '';

		$app = JFactory::getApplication();
		$prefix = $app->getCfg('dbprefix');

		if($arg === 0 || $arg === NULL || $arg === '' || stripos($arg, $prefix) !== FALSE)
		{
			$conf = JFactory::getConfig();
			$ret = $conf->get('db');

		}else {
			
			if(is_numeric($arg))
			{	
				$ret = self::getDbFromConnection($arg);

			}else if(is_string($arg))
			{
				$ret = self::getDbFromTable($arg);								

			}else if(is_object($arg))
			{
				if(is_a($arg, 'FabrikFEModelList'))
				{
					$ret = $arg->getConnection()->getConnection()->database;

				}else if(is_a($arg, 'FabrikFEModelForm') || is_a($arg, 'plgFabrik_Element'))
				{
					$listModel = $arg->getListModel();
					$ret = $listModel->getConnection()->getConnection()->database;

				}else if (is_a($arg, 'FabrikFEModelConnection'))
				{
					$ret = $arg->getConnection()->database;

				}else if(is_a($arg, 'JDatabase'))
				{
					$arg->setQuery("SELECT DATABASE()");
					$ret = $arg->loadResult();								
				}
			}
		}

		return $ret;
	}	

}
?>