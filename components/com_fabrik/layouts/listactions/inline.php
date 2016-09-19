<?php
/**
 * Layout: list row buttons - rendered 'inline' as a Bootstrap button group
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
?>
<div class="btn-group">
<?php echo implode(' ', $displayData['items']); ?>
</div>
