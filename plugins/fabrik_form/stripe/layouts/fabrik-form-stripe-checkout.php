<?php
/**
 * Stripe bottom form layout
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.stripe
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access

defined('_JEXEC') or die;

$d = $displayData;

if (class_exists('NumberFormatter'))
{
	$formatter = new NumberFormatter($d->langTag, NumberFormatter::CURRENCY);
	$d->amount = $formatter->formatCurrency($d->amount, $d->currencyCode);
}

$d->bottomText = str_ireplace('{stripe_amount}', '<span class="fabrikStripePrice">' . $d->amount . '</span>', $d->bottomText);
$d->bottomText = str_ireplace('{stripe_item}', '<span class="fabrikStripeItem">' . $d->item . '</span>', $d->bottomText);

if ($d->testMode) :
	?>
    <div class="fabriStripeTestMode">
		<?php echo FText::_('PLG_FORM_STRIPE_TEST_MODE_TEXT'); ?>
    </div>
<?php
endif;

if ($d->showCoupon) :
	?>
    <div class="fabrikStripeCouponText">
		<?php echo FText::_('PLG_FORM_STRIPE_COUPON_NO_COUPON_TEXT'); ?>
    </div>
<?php
endif;
?>

<div class="fabrikStripeBottomText">
	<?php echo $d->bottomText; ?>
</div>
