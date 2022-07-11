<?php
/**
 * J2Store add to cart layout - used in details view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.j2store
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access

/*
 * To get the main image:
 * $product->get_product_images_html('main');
 *
 * To get cross sell products:
 * $product->get_product_cross_sells_html();
 *
 * To get up sell products:
 * $product->get_product_upsells_html();
 */
defined('_JEXEC') or die;

$product = $displayData->product;
echo $product->get_product_images_html('main');
echo $product->get_product_html();
