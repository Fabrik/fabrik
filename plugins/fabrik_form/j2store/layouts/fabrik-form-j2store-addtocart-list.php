<?php
/**
 * J2Store add to cart layout - used in list view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.j2store
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access

defined('_JEXEC') or die;

$product = $displayData->product;

$action  = 'index.php?option=com_j2store&view=carts&task=addItem&product_id=' . $product->j2store_product_id;
?>
<div class="cart-action-complete" style="display:none;">
	<p class="text-success">
		<?php echo JText::_('J2STORE_ITEM_ADDED_TO_CART');?>
		<a href="<?php echo $product->checkout_link; ?>" class="j2store-checkout-link">
			<?php echo JText::_('J2STORE_CHECKOUT'); ?>
		</a>
	</p>
</div>

<input type="number" name="product_qty" data-product_id="<?php echo $product->j2store_product_id; ?>" value="1" class="form-control" style="width:4em" placeholder="Quantity">
<a class="btn btn-default j2store_add_to_cart_button"
	href="<?php echo JRoute::_($action); ?>" data-product_qty="1" data-product_id="<?php echo $product->j2store_product_id; ?>"
	rel="nofollow">
	<span class="icon-cart"></span><?php echo $product->addtocart_text; ?>
</a>
