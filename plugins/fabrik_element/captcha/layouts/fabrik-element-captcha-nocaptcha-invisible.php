<?php
defined('JPATH_BASE') or die;

$d = $displayData;
?>
<script>
	function nocaptchaSubmitForm() {
		Fabrik.getBlock('<?php echo $d->formId; ?>').doSubmit();
	}
</script>

<div class ="g-recaptcha" data-sitekey="<?php echo $d->site_key; ?>" data-bind="<?php echo $d->btnId; ?>" data-callback="nocaptchaSubmitForm"></div>

<script src="https://www.google.com/recaptcha/api.js?hl=<?php echo $d->lang; ?>" async defer></script>