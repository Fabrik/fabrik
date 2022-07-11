<?php
defined('JPATH_BASE') or die;
$d = $displayData;

if($d->useFieldset) :
?>
<fieldset class="<?php echo $d->fieldsetClass; ?>">
	<legend class="<?php echo $d->legendClass; ?>">
		<?php echo $d->legendText; ?>
	</legend>
<?php
endif;

if ($d->showConsent) :
?>
	<div class="contact_consent">
		<p><?php echo $d->consentIntro; ?></p>
		
		<div class="consentError requireConsent alert alert-error <?php echo $d->consentErrClass ?>">
			<button class="close" data-dismiss="alert">×</button>
			<?php echo $d->consentErrText; ?>
		</div>

        <div class="consentError removeConsent alert alert-error <?php echo $d->removeErrClass ?>">
            <button class="close" data-dismiss="alert">×</button>
			<?php echo $d->removeErrText; ?>
        </div>
	
		<input id="fabrik_contact_consent" type="checkbox" name="fabrik_contact_consent" value="1" style="margin: 0 5px 0 0;">
		<label for="fabrik_contact_consent" style="display: inline;">
			<?php echo $d->consentText; ?>
		</label>
	</div>
<?php
endif;

if($d->useFieldset) :
?>
</fieldset>
<?php
endif;