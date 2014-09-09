<?php
if (!defined('DIR_APPLICATION')) {
	die();
}

$_['heading_title'] = 'Trustly';
$_['text_payment'] = 'Payment';
$_['text_trustly']   = '<a href="https://trustly.com/" target="_blank"><img src="view/image/payment/trustly.png" alt="Trustly" title="Trustly" style="border: 1px solid #eee;" /></a>';
$_['text_success'] = 'Success: Trustly settings saved.';
$_['text_settings'] = 'Settings';
$_['text_backoffice_info'] = 'You can monitor your transactions and get support in the Trustly backoffice.';
$_['text_backoffice_link_live'] = 'Trustly Live backoffice:';
$_['text_backoffice_link_test'] = 'Trustly Test backoffice:';
$_['text_orders'] = 'Trustly Orders';
$_['text_username'] = 'Username';
$_['text_password'] = 'Password';
$_['text_private_key'] = 'Your private key';
$_['text_test_mode'] = 'Use the Trustly test servers for payments';
$_['text_notify_http'] = 'Requests notifications over HTTP rather then HTTPS';
$_['text_total'] = 'Total:<br /><span class="help">The checkout total the order must reach before this payment method becomes active.</span>';
$_['text_complete_status'] = 'Completed Status';
$_['text_pending_status'] = 'Pending Status';
$_['text_canceled_status'] = 'Canceled Status';
$_['text_failed_status'] = 'Failed Status';
$_['text_refunded_status'] = 'Refunded Status';
$_['text_geo_zone'] = 'Geo Zone';
$_['text_yes'] = 'Yes';
$_['text_no'] = 'No';
$_['text_status'] = 'Status';
$_['text_sort_order'] = 'Sort Order';
$_['text_order_id'] = 'Order Id';
$_['text_trustly_order_id'] = 'Trustly Order Id';
$_['text_notification_id'] = 'Notification Id';
$_['text_amount'] = 'Amount';
$_['text_date'] = 'Date';
$_['text_actions'] = 'Actions';
$_['text_wait'] = 'Please wait...';
$_['text_refund'] = 'Refund';
$_['text_refunded'] = 'Refunded';
$_['text_refund_performed'] = 'Refund operation is performed. Amount: %s %s. Date: %s';
$_['error_permission'] = 'Warning: You do not have permission to modify payment Trustly!';
$_['error_php_extension_openssl'] = 'PHP OpenSSL extension is not enabled';
$_['error_php_extension_curl'] = 'PHP Curl extension is not enabled';
$_['error_php_extension_bcmath'] = 'PHP BCMath extension is not enabled';
$_['error_php_extension_mbstring'] = 'PHP mbstring extension is not enabled';
$_['error_php_extension_json'] = 'PHP json extension is not enabled';
$_['error_username'] = 'Invalid username';
$_['error_password'] = 'Invalid password';
$_['error_private_key'] = 'Invalid private key';
$_['error_auth'] = 'API Authentication error, check your username/password';
$_['error_response_code'] = 'API Response code %d (%s)';
$_['error_response_http'] = 'API Responded with HTTP %d';
$_['error_cannot_connect'] = 'Cannot connect to Trustly services: %s';
$_['error_check_your_firewall'] = 'Check your firewall and network configuration or ask your hosting service to do it for you';
$_['error_cannot_verify'] = 'Cannot verify the authenticity of Trustly communication.';
$_['error_failed_communicate'] = 'Failed to communicate with Trustly.';
$_['error_cannot_access'] = 'Cannot access payment gateway: %s';

