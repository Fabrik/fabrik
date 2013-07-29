<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

$data = $listModel->getData();
$data = $data[0][0];

$db = FabrikWorker::getDbo();

$location = $db->quote("(".$data->gate_user___latitude .",".$data->gate_user___longitude."):4");
$time = $db->quote($data->gate_user___time_stamp_raw);
$db->setQuery("SELECT COUNT(*) FROM moose_location WHERE location = $location AND time_date = $time");
$count = (int) $db->loadResult();

if ($count === 0) {
  $db->setQuery("INSERT INTO moose_location (`location`, `time_date`) VALUES ($location, $time)");
  $db->execute();
}

//also update any time worn values:

$db->setQuery("select * from moose_sightings where dontcalculate = 0 order by id DESC");
$rows = $db->loadObjectList();
$prevTime = '';
$prevId = 0;
$c = 0;

foreach($rows as $row) {
  $date = JFactory::getDate($row->time_date);
  $prevDate = JFactory::getDate($prevTime);
  $diff = $prevDate->toUnix() - $date->toUnix() ;

	if($diff != 0) {
	  $newDate = JFactory::getDate($diff);
	  $diff = $newDate->toFormat('000-00-00 %H:%M:%S');
	}else{
	  $diff = '000-00-00 00:00:00';
	}
  $query = "update moose_sightings set time_worn = '" . $diff . "'";
  if($c !== 0) {
    $query .= ", dontcalculate = 1";
  }
  $query .= ' where id = ' . (int) $row->id;
  $db->setQuery($query) ;
  $db->execute();
  $c++;
  $prevId = $row->id;
  $prevTime = $row->time_date;

}


exit;
?>