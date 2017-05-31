<?php
// _ includes/languages/english/extra_definitions/_GGC/customer_box_defines.php
// _ includes/modules/sideboxes/_GGC/customer_box.php
// X includes/templates/_GGC/sideboxes/tpl_customer_box.php
$cart_image = zen_image($template->get_template_dir('cart.gif', DIR_WS_TEMPLATE, $current_page_base, 'images/icons') . '/' . 'cart.gif', CUSTOMER_BOX_ICON_CART);
$info_image = zen_image($template->get_template_dir('info.gif', DIR_WS_TEMPLATE, $current_page_base, 'images/icons') . '/' . 'info.gif', CUSTOMER_BOX_ICON_VIEW);

$html[] = '<div class="customerBox">';
$html[] = '<div class="sideBoxContent">';
// *=====================================*
// * Determine if customer is logged in  *
// *=====================================*
if (!$customer_box_logged_in) {
    // *---------------------------*
    // * Customer IS NOT logged in *
    // *---------------------------*
    $html[] = zen_draw_form('customer_box_visitor', zen_href_link(FILENAME_LOGIN, 'action=process', 'SSL'));
	$html[] = '<div>';
    $html[] = CUSTOMER_BOX_EMAIL_ADDRESS;
    $html[] = '<br />';
	$html[] = zen_draw_input_field('email_address');
    $html[] = '</div>';
	$html[] = '<div>';
    $html[] = CUSTOMER_BOX_PASSWORD;
    $html[] = '<br />';
    $html[] = zen_draw_password_field('password');
    $html[] = zen_draw_hidden_field('securityToken', $_SESSION['securityToken']);
    $html[] = '</div>';
    $html[] = '<div style="text-align: right;">';
    $html[] = zen_image_submit(BUTTON_IMAGE_LOGIN, BUTTON_LOGIN_ALT);
    $html[] = '</div>';
    $html[] = '</form>';
    $html[] = '<hr />';
    $html[] = '<ul>';
    $html[] = '<li>';
    $html[] = '<a href="' . zen_href_link(FILENAME_PASSWORD_FORGOTTEN, '', 'SSL') . '">';
    $html[] = CUSTOMER_BOX_PASSWORD_FORGOTTEN;
    $html[] = '</a>';
    $html[] = '</li>';
    $html[] = '<li>';
    $html[] = '<a href="' . zen_href_link(FILENAME_LOGIN, '', 'SSL') . '">';
    $html[] = CUSTOMER_BOX_NEW_ACCOUNT;
    $html[] = '</a>';
    $html[] = '</li>';
    $html[] = '</ul>';
//    $html[] = '<br />';
} else {
    // *---------------------------*
    // * Customer IS logged in     *
    // *---------------------------*
    $html[] = '<h4>';
    $html[] = '<a href="' . zen_href_link(FILENAME_ACCOUNT, '', 'SSL') . '">';
    $html[] = CUSTOMER_BOX_LOGGED_IN_AS;
    $html[] = '</a>';
    $html[] = '</h4>';
    $html[] = '<div class="customerBox_name">';
    $html[] = $_SESSION['customer_first_name'] . ' ' . $_SESSION['customer_last_name'];
    $html[] = '</div>';
    $html[] = '<div class="customerBox_email">';
    $html[] = '(' . $_SESSION['customer_email_address'] . ')';
    $html[] = '</div>';

    $html[] = zen_draw_form('customer_box_member', zen_href_link(FILENAME_LOGOFF, '', 'SSL'));
    $html[] = '<div style="text-align: right;">';
    $html[] = zen_image_submit(BUTTON_IMAGE_LOGOFF, BUTTON_LOGOFF_ALT);
    $html[] = '</div>';
    $html[] = '</form>';

    $html[] = '<hr />';
    $html[] = '<ul>';
    $html[] = '<li>';
    $html[] = '<a href="' . zen_href_link(FILENAME_ACCOUNT, '', 'SSL') . '">';
    $html[] = CUSTOMER_BOX_EXISTING_ACCOUNT;
    $html[] = '</a>';
    $html[] = '</li>';
    $html[] = '</ul>';
}
// *===========================*
// * Display the Shopping Cart *
// *===========================*
$html[] = '<hr />';
$html[] = '<hr />';
$html[] = '<h4>';
$html[] = '<a href="' . zen_href_link(FILENAME_SHOPPING_CART) . '">';
$html[] = CUSTOMER_BOX_SHOPPING_CART;
$html[] = '</a>';
$html[] = '</h4>';
if ($customer_box_shopping_cart_empty) {
    // *------------------------*
    // * Shopping Cart is empty *
    // *------------------------*
    $html[] = '<div>';
    $html[] = CUSTOMER_BOX_SHOPPING_CART_EMPTY;
    $html[] = '</div>';
} else {
    // *--------------------------------------------*
    // * The Shopping Cart contains at least 1 item *
    // *--------------------------------------------*
    $html[] = '<div>';
    for ($i = 0, $n = sizeof($products_in_cart); $i < $n; $i++) {
        $span_class = $products_in_cart[$i]['new'] ? 'newItemInCart' : 'sideBoxContents';
        $span_tag = '<span class="' . $span_class . '">';
        $html[] = $span_tag;
        $html[] = $products_in_cart[$i]['quantity'];
        $html[] = '</span>';
        $html[] = '&nbsp;x&nbsp;';
        $html[] = '<a href="' . zen_href_link(zen_get_info_page($products_in_cart[$i]['id']), 'products_id=' . $products_in_cart[$i]['id']) . '">';
        $html[] = $span_tag;
        $html[] = $products_in_cart[$i]['name'];
        $html[] = '</span>';
        $html[] = '</a>';
        $html[] = '<br />';

        if (($_SESSION['new_products_id_in_cart']) && ($_SESSION['new_products_id_in_cart'] == $products_in_cart[$i]['id'])) {
            $_SESSION['new_products_id_in_cart'] = '';
        }
    }
    $html[] = '</div>';

    if (SHOW_TOTALS_IN_CART != '0') {
        $html[] = '<hr />';

        $html[] = '<div style="text-align: right;">';
        $html[] = $subtotal;
        $html[] = '</div>';

        $html[] = '<div style="text-align: right;">';
        $html[] = $items;
        $html[] = '</div>';

        if ($weight != '') {
            $html[] = '<div style="text-align: right;">';
            $html[] = $weight;
            $html[] = '</div>';
            $html[] = '<br />';
        }
    }

    $html[] = '<div class="main" style="text-align: right;">';
    $html[] = '<a href="' . zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL') . '">';
    $html[] = zen_image_button(BUTTON_IMAGE_CHECKOUT, BUTTON_CHECKOUT_ALT);
    $html[] = '</a>';
    $html[] = '</div >';
}
// *------------------*
// * Gift vouchers    *
// *------------------*
$gv_query = "select amount" ;
$gv_query .= "  from " . TABLE_COUPON_GV_CUSTOMER . " ";
$gv_query .= "  where customer_id='" . $_SESSION['customer_id'] . "'";
$gv_result = $db->Execute($gv_query);

if ($gv_result->fields['amount'] > 0) {
    $html[] = '<hr />';
    $html[] = '<div class="smalltext" style="text-align: right;">';
    $html[] = VOUCHER_BALANCE;
    $html[] = $currencies->format($gv_result->fields['amount']);
    $html[] = '</div>';
    $html[] = '<div class="smalltext">';
    $html[] = '<a href="' . zen_href_link(FILENAME_GV_SEND) . '">';
    $html[] = BOX_SEND_TO_FRIEND;
    $html[] = '</a>';
    $html[] = '</div>';
}
// *------------------*
// * Coupons          *
// *------------------*
if ($_SESSION['gv_id']) {
    $gv_query = "select coupon_amount";
    $gv_query .= "  from " . TABLE_COUPONS . " ";
    $gv_query .= "  where coupon_id='" . $_SESSION['gv_id'] . "'";
    $coupon = $db->Execute($gv_query);

    $html[] = zen_draw_separator();
    $html[] = '<div class="smalltext" style="text-align: right;">';
    $html[] = VOUCHER_REDEEMED;
    $html[] = $currencies->format($coupon->fields['coupon_amount']);
    $html[] = '</div>';
}
// *------------------*
// * Coupons          *
// *------------------*
if ($_SESSION['cc_id']) {
    $html[] = '<script language="javascript" type="text/javascript">';
    $html[] = '<!--';
    $html[] = 'function couponpopupWindow(url)';
    $html[] = '{';
    $html[] = 'window.open(url,"popupWindow","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=450,height=280,screenX=150,screenY=150,top=150,left=150")';
    $html[] = '}';
    $html[] = '//-->';
    $html[] = '</script>';

    $html[] = '<hr />';
    $html[] = '<div class="smalltext" style="text-align: right;">';
    $html[] = CART_COUPON;
    $html[] = '<a href = "javascript:couponpopupWindow(\'' . zen_href_link(FILENAME_POPUP_COUPON_HELP, 'cID=' . $_SESSION['cc_id']) . '\')" > ';
    $html[] = CART_COUPON_INFO;
    $html[] = '</a> ';
    $html[] = '</div>';
}
// *------------------*
// * Recent purchases *
// *------------------*
if ($show_recent_purchases) {
    $html[] = '<h4>';
    $html[] = '<a href="index.php?main_page=account_history">';
    $html[] = CUSTOMER_BOX_ORDER_HISTORY;
    $html[] = '</a>';
    $html[] = '</h4>';
    $html[] = '<table border="0" width="100%" cellspacing="0" cellpadding="3">';
    $html[] = '<div align="left">';
    for ($item = 1; $item <= sizeof($recent_purchases); $item++) {
        $html[] = '<tr valign="top">';
        $html[] = '<td>';
        $html[] = '<a href="' . zen_href_link(basename($PHP_SELF), zen_get_all_get_params(array('action')) . 'action=cust_order&pid=' . $recent_purchases[$item]['id']) . '">';
        $html[] = $cart_image;
        $html[] = '</a>';
        $html[] = '</td>';
        $html[] = '<td>';
        $html[] = '<a href="' . zen_href_link(zen_get_info_page($recent_purchases[$item]['id']), 'products_id=' . $recent_purchases[$item]['id']) . '">';
        $html[] = $info_image;
        $html[] = '</td>';
        $html[] = '<td>';
        $html[] = '<a href="' . zen_href_link(zen_get_info_page($recent_purchases[$item]['id']), 'products_id=' . $recent_purchases[$item]['id']) . '">';
        $html[] = $recent_purchases[$item]['name'];
        $html[] = '</a>';
        $html[] = '</td>';
        $html[] = '</tr>';
    }
    $html[] = '</div>';
    $html[] = '</table>';
}
$html[] = '</div>';
$html[] = '</div>';
$content = implode("\n", $html) . "\n";
unset($html);

?>