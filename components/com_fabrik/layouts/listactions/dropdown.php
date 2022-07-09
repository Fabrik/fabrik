<?php
/**
 * Layout: list row buttons - rendered as a Bootstrap dropdown
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$class = 'btn-group fabrik_action';

if ($displayData['align'] == 'right')
{
	$class .= ' pull-right';
}

?>
<div class="<?php echo $class?>">
	<a class="dropdown-toggle btn btn-mini" data-toggle="dropdown" href="#">
		<span class="caret"></span>
	</a>
	<ul class="dropdown-menu"><li>
	<?php echo implode('</li>' . "\n" . '<li>', $displayData['items']); ?></li>
	</ul>
</div>
