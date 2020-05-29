<?php
/**
 * Form control group
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4
 */
defined('JPATH_BASE') or die;
$d = $displayData;
?>

<div class="control-group <?php echo $d->class;?> <?php echo $d->span;?>" <?php echo $d->style;?>>
<?php echo $d->row;?>
</div>
