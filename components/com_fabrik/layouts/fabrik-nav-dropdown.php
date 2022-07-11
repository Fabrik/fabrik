<?php
/**
 * Layout: Bootstrap dropdown
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.3.3
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
$d = $displayData;

?>

<li class="dropdown">
	<a href="#" class="dropdown-toggle groupBy" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
		<?php echo $d->icon;?>
		<?php echo $d->label; ?>
		<b class="caret"></b>
	</a>
	<ul class="dropdown-menu">
		<?php foreach ($d->links as $link) :?>
			<li><?php echo $link;?></li>
			<?php
		endforeach;?>
	</ul>
</li>