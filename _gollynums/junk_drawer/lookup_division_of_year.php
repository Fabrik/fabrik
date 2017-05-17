$db = JFactory::getDbo();
$db->setQuery("SELECT id, text FROM #__tablename");
$query = $db->getQuery(true);
$query
    ->select(array('value', 'choice'))
    ->from('gn_lookup')
    ->where('lookup_segment = ' . '{end_division_of_year_selector_raw}');
$db->setQuery($query);,
$rows = $db->loadObjectList();

$options = array();
$options[] = JHTML::_('select.option', '0', 'Please select');
foreach ($rows as $row)
{
    $options[] = JHTML::_('select.option', $row->choice, $row->value);
}
return $options