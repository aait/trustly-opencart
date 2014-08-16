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
$_['text_error_title'] = 'Trustly Payment Error';
$_['text_error_description'] = 'An error occurred during execution of the payment.';
$_['text_error_link'] = 'Return to checkout';
$_['error_invalid_order'] = 'Invalid order';
$_['error_unknown'] = 'Unknown Error';
$_['error_no_payment_url'] = 'An error occurred during the communication with Trustly web service: No payment url was received.';
$_['error_payment_failed'] = 'Payment failed';

