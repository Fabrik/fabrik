<?php
defined('JPATH_BASE') or die;

$d = $displayData;
?>

<div class ="g-recaptcha" data-sitekey="<?php echo $d->site_key; ?>"></div>

<script src="https://www.google.com/recaptcha/api.js?hl=<?php echo $d->lang; ?>" async defer></script>