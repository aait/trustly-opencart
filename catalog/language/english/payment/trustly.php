<?php
if (!defined('DIR_APPLICATION')) {
    die();
}

$_['text_title'] = 'Payment with Trustly';
$_['text_message_payment_credited'] = 'A Trustly payment of %s %s has been credited to this order at %s, Trustly OrderId %s.';
$_['text_message_order_totals_not_match'] = 'Trustly payment for this order at %s of %s %s does not match the order total of %s %s.';
$_['text_message_payment_debited'] = 'A Trustly debit of %s %s has been charged to this order at %s';
$_['text_message_payment_canceled'] = 'Payment cancelled by Trustly at %s';
$_['text_message_payment_pending'] = 'A Trustly payment of %s %s has been initiated for this order at %s, Trustly OrderId %s.';
$_['text_message_payment_pending_notification'] = 'Waiting notification from Trustly. Please check order later.';
$_['text_order_orders_processed'] = 'Your order has been successfully processed!';
$_['text_order_orders_pending'] = 'Your order has been received and will be processed once payment has been confirmed.';
$_['text_success_customer'] = '<p>You can view your order history by going to the <a href="%s">my account</a> page and by clicking on <a href="%s">history</a>.</p><p>If your purchase has an associated download, you can go to the account <a href="%s">downloads</a> page to view them.</p><p>Please direct any questions you have to the <a href="%s">store owner</a>.</p><p>Thanks for shopping with us online!</p>';
$_['text_success_guest'] = '<p>Please direct any questions you have to the <a href="%s">store owner</a>.</p><p>Thanks for shopping with us online!</p>';
$_['text_error_title'] = 'Trustly Payment Error';
$_['text_error_description'] = 'An error occurred during execution of the payment.';
$_['text_error_link'] = 'Return to checkout';
$_['error_invalid_order'] = 'Invalid order';
$_['error_unknown'] = 'Unknown Error';
$_['error_no_payment_url'] = 'An error occurred during the communication with Trustly web service: No payment url was received.';
$_['error_payment_failed'] = 'Payment failed';
$_['error_order_create'] = 'Could not create payment';
$_['error_trustly'] = 'An error has occurred: %s';
$_['error_message_payment_amount_invalid'] = 'Trustly payment for this order at %s of %s %s does not match the order total of %s %s.';