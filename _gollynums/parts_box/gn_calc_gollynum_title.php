require_once JPATH_ROOT . '/_gollynums/inflector.php';

$type = '{gn_gollynum___type}';
$subject= urldecode('{gn_gollynum___subject}' . ' // ');
$dimension_length = $type == 'Length' ? '{gn_gollynum___dimension_length} = ' : '';
$qualifier = '{gn_gollynum___qualifier}';
$quantity = (FLOAT) '{gn_gollynum___quantity}' . ' ';
$measure = '{gn_gollynum___uom}';

$measure = abs($quantity) == 1 ? $measure : inflector::pluralize($measure);
$qualifier = strlen($qualifier) == 0 ? '' : ' (' . $qualifier . ')';

$title = rawurldecode($subject . $dimension_length . $quantity . $measure . $qualifier);
return $title;