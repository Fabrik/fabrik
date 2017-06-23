$y = '{gn_event___end_month_or_season}';
$a = '{gn_event___end_month_or_season_accuracy';
$c = '{gn_event___end_month_or_season_confidence}';

$y = ($a == "Exact") ? $y : $y . "?";
$y = ($c == "Certain") ? $y : $y . "~";
$end_month_or_season_edtf = $y;

return $end_month_or_season_edtf;