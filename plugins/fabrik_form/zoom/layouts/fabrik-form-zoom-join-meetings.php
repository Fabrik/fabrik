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
    $attendingClass = 'zoomButton';
    $notAttendingClass = 'zoomButton fabrikHide';
}
else
{
    $attendingClass = 'zoomButton fabrikHide';
    $notAttendingClass = 'zoomButton';
}

?>

<div class="zoomButtons">
    <div class="<?php echo $attendingClass; ?>">
        <button class="zoomAttending" data-attending="1">
			<?php echo JText::_('PLG_FORM_ZOOM_MEETINGS_ATTENDING_LEAVE'); ?>
        </button>
    </div>

    <div class="<?php echo $notAttendingClass; ?>">
        <div class="zoomOptInNotConfirmed">
            <button class="zoomNotAttending">
                <?php echo JText::_('PLG_FORM_ZOOM_MEETINGS_ATTENDING_JOIN'); ?>
            </button>
        </div>
        <div class="zoomOptIn .fabrikHide">
            <div class="zoomOptInMessage">
                <?php echo JText::_('PLG_FORM_ZOOM_MEETINGS_ATTENDING_OPT_IN_MESSAGE'); ?>
            </div>
            <button class="zoomOptInConfirm" data-attending="0">
		        <?php echo JText::_('PLG_FORM_ZOOM_MEETINGS_ATTENDING_OPT_IN_CONFIRM'); ?>
            </button>
            <button class="zoomOptInCancel">
		        <?php echo JText::_('PLG_FORM_ZOOM_MEETINGS_ATTENDING_OPT_IN_CANCEL'); ?>
            </button>
        </div>
    </div>

    <div class="zoomAttendingError fabrikHide">
    </div>
</div>
