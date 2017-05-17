$day  = '{gn_event___xxxxx_day_of_month}';
$acc  = '{gn_event___xxxxx_day_of_month_accuracy}';
$con  = '{gn_event___xxxxx_day_of_month_confidence}';

$result = ($acc == "Exact")   ? $day : $day . "?";
$result = ($con == "Certain") ? $day : $day . "~";

return $result;