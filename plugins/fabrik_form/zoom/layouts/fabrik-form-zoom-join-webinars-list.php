<?php
/**
 * Zoom join webinar layout - used in details view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.zoom
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access

defined('_JEXEC') or die;
$d = $displayData;

if ($d->attending)
{
    $attendingClass = '';
    $notAttendingClass = 'class="fabrikHide"';
}
else
{
    $attendingClass = 'class="fabrikHide"';
    $notAttendingClass = '';
}

?>
<div <?php echo $attendingClass; ?>>
    <button type="button" class="zoom zoomAttending"
            data-attending="1"
            data-user-id="<?php echo $d->userId; ?>"
            data-thing-id="<?php echo $d->thingId; ?>"
            data-zoom-id="<?php echo $d->zoomId; ?>"
            data-form-id="<?php echo $d->formId; ?>"
            data-render-order="<?php echo $d->renderOrder; ?>"
    >
        <?php echo JText::_('PLG_FORM_ZOOM_WEBINARS_ATTENDING_LEAVE'); ?>
    </button>
</div>

<div <?php echo $notAttendingClass; ?>>
    <button type="button" class="zoom zoomNotAttending"
            data-attending="0"
            data-user-id="<?php echo $d->userId; ?>"
            data-thing-id="<?php echo $d->thingId; ?>"
            data-zoom-id="<?php echo $d->zoomId; ?>"
            data-form-id="<?php echo $d->formId; ?>"
            data-render-order="<?php echo $d->renderOrder; ?>">
        <?php echo JText::_('PLG_FORM_ZOOM_WEBINARS_ATTENDING_JOIN'); ?>
    </button>
</div>

<div class="zoomAttendingError fabrikHide">

</div>
