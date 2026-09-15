<?php

defined('JPATH_BASE') or die;

$d = $displayData;

$pw1Attributes = array();
$pw2Attributes = array();

foreach ($d->pw1Attributes as $key => $val)
{
	$pw1Attributes[] = $key . '="' . $val . '" ';
}

$pw1Attributes = implode("\n", $pw1Attributes);


foreach ($d->pw2Attributes as $key => $val)
{
	$pw2Attributes[] = $key . '="' . $val . '" ';
}

$pw2Attributes = implode("\n", $pw2Attributes);


?>
<input <?php echo $pw1Attributes; ?>  />

<?php
if ($d->showStrengthMeter) :
	if ($d->j3) :
		?>
		<div class="strength progress progress-striped <?php echo $d->bootstrapClass; ?>" style="margin-left:6px; <?php echo $d->extraStyle; ?>"></div>
	<?php
	else :
		?>
		<span class="strength"></span>
	<?php
	endif;
endif;

if ($d->showPasswordRequirements) :
	?>
	<div class="" id="pw-requirements-indicator" style="margin-left:6px; margin-bottom: 20px; font-size: 0.95em; <?php echo $d->extraStyle; ?>">
		
		<span style="color: #DB1717;"><span class="icon-cancel-2"> </span>
			<?php echo FText::_('PLG_ELEMENT_PASSWORD_MIN_LENGTH') . ": " . $d->passwordMinLength; ?>
		</span><br/>
		
		<span style="color: #DB1717;"><span class="icon-cancel-2"> </span>
			<?php echo FText::_('PLG_ELEMENT_PASSWORD_MIN_INTEGERS') . ": " . $d->passwordMinIntegers; ?>
		</span><br/>

		<span style="color: #DB1717;"><span class="icon-cancel-2"> </span>
			<?php echo FText::_('PLG_ELEMENT_PASSWORD_MIN_SYMBOLS') . ": " . $d->passwordMinSymbols . " (" . FText::_('PLG_ELEMENT_PASSWORD_ONE_OF') . ": @$!%*#?&)"; ?>
		</span><br/>

		<span style="color: #DB1717;"><span class="icon-cancel-2"> </span>
			<?php echo FText::_('PLG_ELEMENT_PASSWORD_MIN_UPPERCASE') . ": " . $d->passwordMinUppercase; ?>
		</span><br/>

		<span style="color: #DB1717;"><span class="icon-cancel-2"> </span>
			<?php echo FText::_('PLG_ELEMENT_PASSWORD_MIN_LOWERCASE') . ": " . $d->passwordMinLowercase; ?>
		</span><br/>

		<span style="color: #DB1717;"><span class="icon-cancel-2"> </span>
			<?php echo FText::_('PLG_ELEMENT_PASSWORD_DONT_MATCH') ?>
		</span>

	</div>
	<?php
endif;
?>

<input <?php echo $pw2Attributes; ?>"  />
