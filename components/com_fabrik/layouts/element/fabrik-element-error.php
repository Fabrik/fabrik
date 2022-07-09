<?php
/**
 * Element error JLayout
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
defined('JPATH_BASE') or die;

$d = $displayData;
$usersConfig = JComponentHelper::getParams('com_fabrik');
$icon        = $usersConfig->get('error_icon', 'warning');
?>
<span class="fabrikErrorMessage">

<?php
if ($d->err !== '') :
	?>
	<a href="#" class="fabrikTip" title="<span><?php echo $d->err;?></span>" opts='{"notice":true}'>
	<?php echo FabrikHelperHTML::image($icon, 'form', $d->tmpl);?>
	</a>
<?php
endif;
?>

</span>
