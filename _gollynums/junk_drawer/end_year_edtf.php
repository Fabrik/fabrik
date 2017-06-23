$y = '{gn_event___end_year}';
$a = '{gn_event___end_year_accuracy';
$c = '{gn_event___end_year_confidence}';

$y = ($a == "Exact") ? $y : $y . "?";
$y = ($c == "Certain") ? $y : $y . "~";
$end_year_edtf = $y;

return $end_year_edtf;