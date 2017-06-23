$y = '{gn_event___end_day_of_month}';
$a = '{gn_event___end_day_of_month_accuracy';
$c = '{gn_event___end_day_of_month_confidence}';

$y = ($a == "Exact") ? $y : $y . "?";
$y = ($c == "Certain") ? $y : $y . "~";
$end_day_of_month_edtf = $y;

return $end_day_of_month_edtf;