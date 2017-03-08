<?php
defined('JPATH_BASE') or die;

$d = $displayData;
?>

<script>
var onloadCallback = function() {
	grecaptcha.render('<?php echo $d->id; ?>', {
		'sitekey' : '<?php echo $d->site_key; ?>'
	});
};
</script>

<div class="captcha_input" id="<?php echo $d->id; ?>">
</div>

<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=<?php echo $d->lang; ?>" async defer></script>
