$div  = '{gn_event___xxxxx_division_of_year}';
$acc  = '{gn_event___xxxxx_division_of_year_accuracy}';
$con  = '{gn_event___xxxxx_division_of_year_confidence}';

$result = ($acc == "Exact")   ? $div : $div . "?";
$result = ($con == "Certain") ? $div : $div . "~";

return $result;