<?php
/**
 * Layout: Search all
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$d = $displayData;

?>

<input
	type="search"
	size="20"
	placeholder="<?php echo $d->searchLabel; ?>"
	title="<?php echo $d->searchLabel; ?>"
	value="<?php echo $d->v; ?>"
	class="<?php echo $d->class; ?>"
	name="<?php echo $d->requestKey; ?>"
	id="<?php echo $d->id; ?>"
/>

<?php
if ($d->advanced) :
	echo '&nbsp;';
	echo JHTML::_('select.genericList', $d->searchOpts, 'search-mode-advanced', "class='fabrik_filter'", 'value', 'text', $d->mode);
endif;
