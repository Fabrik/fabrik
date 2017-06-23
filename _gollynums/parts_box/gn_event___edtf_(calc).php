$year = '{gn_event___start_year}';
$acc  = '{gn_event___start_year_accuracy}';
$con  = '{gn_event___start_year_confidence}';

$year = ($acc == "Exact")   ? $year : $year . "?";
$year = ($con == "Certain") ? $year : $year . "~";

$div  = '{gn_event___start_division_of_year_choices_raw}';
$acc  = '{gn_event___start_division_of_year_accuracy}';
$con  = '{gn_event___start_division_of_year_confidence}';

$div  = ($acc == "Exact")   ? $div : $div . "?";
$div  = ($con == "Certain") ? $div : $div . "~";

$day  = '{gn_event___start_day_of_month}';
$acc  = '{gn_event___start_day_of_month_accuracy}';
$con  = '{gn_event___start_day_of_month_confidence}';

$day  = ($acc == "Exact")   ? $day : $day . "?";
$day  = ($con == "Certain") ? $day : $day . "~";

$start = $year . '•' . $div . '•' . $day;


$year = '{gn_event___end_year}';
$acc  = '{gn_event___end_year_accuracy}';
$con  = '{gn_event___end_year_confidence}';

$year = ($acc == "Exact")   ? $year : $year . "?";
$year = ($con == "Certain") ? $year : $year . "~";

$div  = '{gn_event___end_division_of_year_choices_raw}';
$acc  = '{gn_event___end_division_of_year_accuracy}';
$con  = '{gn_event___end_division_of_year_confidence}';

$div  = ($acc == "Exact")   ? $div : $div . "?";
$div  = ($con == "Certain") ? $div : $div . "~";

$day  = '{gn_event___end_day_of_month}';
$acc  = '{gn_event___end_day_of_month_accuracy}';
$con  = '{gn_event___end_day_of_month_confidence}';

$day  = ($acc == "Exact")   ? $day : $day . "?";
$day  = ($con == "Certain") ? $day : $day . "~";

$end = $year . '•' . $div . '•' . $day;

return $start . ' -- ' . $end;








gn_event___start_year,
gn_event___start_year_accuracy,
gn_event___start_year_confidence,
gn_event___start_division_of_year_choices,
gn_event___start_division_of_year_accuracy,
gn_event___start_division_of_year_confidence,
gn_event___start_day_of_month,
gn_event___start_day_of_month_accuracy,
gn_event___start_day_of_month_confidence,
gn_event___end_year,
gn_event___end_year_accuracy,
gn_event___end_year_confidence,
gn_event___end_division_of_year_choices,
gn_event___end_division_of_year_accuracy,
gn_event___end_division_of_year_confidence,
gn_event___end_day_of_month,
gn_event___end_day_of_month_accuracy,
gn_event___end_day_of_month_confidence