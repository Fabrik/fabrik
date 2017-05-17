$year = '{gn_event___xxxxx_year}';
$acc  = '{gn_event___xxxxx_year_accuracy}';
$con  = '{gn_event___xxxxx_year_confidence}';

$result = ($acc == "Exact")   ? $year : $year . "?";
$result = ($con == "Certain") ? $year : $year . "~";

return $result;