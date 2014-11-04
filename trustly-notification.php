<?php
/**
 * Trustly Notification Wrapper
 * @see https://trustly.com/en/developer/api#/notifications
 * "Mixing POST and GET data is not supported so the NotificationURL must not contain a ? ("question mark")."
 * This script should helpful to solve this problem
 */

require_once dirname($_SERVER['SCRIPT_FILENAME']) . '/config.php';

if(isset($HTTP_RAW_POST_DATA)) {
    $http_raw_post_data = $HTTP_RAW_POST_DATA;
}
if (empty($http_raw_post_data)) {
    $http_raw_post_data = file_get_contents('php://input');
}

$post_url = HTTP_SERVER . 'index.php?route=payment/trustly/notification';
if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] === 'on') || ($_SERVER['HTTPS'] === '1'))) {
    $post_url = HTTPS_SERVER . 'index.php?route=payment/trustly/notification';
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $post_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $http_raw_post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$result = curl_exec($ch);
if($result === false) {
	$result = curl_error($ch);
}
curl_close($ch);

echo $result;
