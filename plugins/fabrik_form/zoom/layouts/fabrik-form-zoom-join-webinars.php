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
        <?php
        if ($d->showLeave) :
            ?>
        <button class="zoomAttending" data-attending="1">
            <?php echo JText::_($d->leaveButtonLabel); ?>
        </button>
        <?php
        else :
            echo JText::_($d->leaveAltText);
        endif;
        ?>
    </div>

    <div class="<?php echo $notAttendingClass; ?>">
        <?php
        if ($d->showJoin) :
        ?>
        <div class="zoomOptInNotConfirmed">
            <button class="zoomNotAttending">
                <?php echo JText::_($d->joinButtonLabel); ?>
            </button>
        </div>
        <div class="zoomOptIn fabrikHide">
            <div class="zoomOptInMessage">
                <?php echo JText::_('PLG_FORM_ZOOM_WEBINARS_ATTENDING_OPT_IN_MESSAGE'); ?>
            </div>
            <button class="zoomOptInConfirm" data-attending="0">
                <?php echo JText::_('PLG_FORM_ZOOM_WEBINARS_ATTENDING_OPT_IN_CONFIRM'); ?>
            </button>
            <button class="zoomOptInCancel">
                <?php echo JText::_('PLG_FORM_ZOOM_WEBINARS_ATTENDING_OPT_IN_CANCEL'); ?>
            </button>
        </div>
        <?php
        else :
            echo JText::_($d->joinAltText);
        endif;
        ?>
    </div>
    <div class="zoomAttendingError fabrikHide">

    </div>
</div>
