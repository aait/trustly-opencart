<?php
if (!defined('DIR_APPLICATION')) {
    die();
}
require_once DIR_SYSTEM . 'library/trustly-client-php/Trustly.php';

class ControllerPaymentTrustly extends Controller
{
    protected $_module_name = 'trustly';
    protected $api;

    /**
     * Index Action
     */
    protected function index()
    {
        $this->language->load('payment/trustly');
        $this->load->model('payment/trustly');

        // Get Order Id
        $order_id = $this->session->data['order_id'];
        if (empty($order_id)) {
            $this->session->data['error'] = $this->language->get('error_invalid_order');
            $this->redirect($this->url->link('payment/' . $this->_module_name . '/error', '', 'SSL'));
        }

        // Get Trustly Payment URL
        $trustly_order = $this->model_payment_trustly->getTrustlyOrder($order_id);
        if (!$trustly_order) {
            // Retrieve Payment Url from Trustly
            $response = $this->retrievePaymentUrl($order_id);
            $trustly_order_id = $response->getData('orderid');
            $url = $response->getData('url');

            // Save Trustly Order Id
            $this->model_payment_trustly->addTrustlyOrder($order_id, $trustly_order_id, $url);

            $trustly_order = array(
                'trustly_order_id' => $trustly_order_id,
                'order_id' => $order_id,
                'url' => $url
            );
        }

        // Check Payment URL
        if (empty($trustly_order['url'])) {
            $this->model_payment_trustly->removeTrustlyOrder($trustly_order['trustly_order_id']);
            throw new Exception($this->language->get('error_no_payment_url'));
        }

        $this->data['text_title'] = $this->language->get('text_title');
        $this->data['action'] = '';
        $this->data['trustly_iframe_url'] = $trustly_order['url'];
        $this->data['trustly_order_id'] = $trustly_order['trustly_order_id'];

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/trustly.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/trustly.tpl';
        } else {
            $this->template = 'default/template/payment/trustly.tpl';
        }

        $this->render();
    }

    /**
     * Success Action
     */
    public function success()
    {
        $this->language->load('payment/trustly');

        $this->load->model('checkout/order');
        $this->load->model('payment/trustly');

        $order_id = $this->session->data['order_id'];
        if (empty($order_id)) {
            $this->session->data['error'] = $this->language->get('error_invalid_order');
            $this->redirect($this->url->link('payment/' . $this->_module_name . '/error', '', 'SSL'));
        }

        // Waiting to receive notifications
        $notifications = array();
        $trustly_order_id = $this->model_payment_trustly->getTrustlyOrderId($order_id);
        if ($trustly_order_id) {
            for ($i = 0; $i < 60; $i++) {
                $notifications = $this->model_payment_trustly->getTrustlyNotifications($trustly_order_id);
                if (count($notifications) === 0) {
                    // Wait half a second and try again
                    usleep(500000);
                } else {
                    break;
                }
            }
        }

        // Set Pending status
        if (count($notifications) === 0) {
            $this->model_checkout_order->confirm($order_id, $this->config->get('trustly_pending_status_id'), $this->language->get('text_message_payment_pending_notification'), false);
        }

        $this->redirect($this->url->link('checkout/success', '', 'SSL'));
    }

    /**
     * Failed Action
     */
    public function failed()
    {
        $this->language->load('payment/trustly');
        $this->load->model('checkout/order');

        $order_id = $this->session->data['order_id'];
        if (empty($order_id)) {
            $this->session->data['error'] = $this->language->get('error_invalid_order');
            $this->redirect($this->url->link('payment/' . $this->_module_name . '/error', '', 'SSL'));
        }

        $order = $this->model_checkout_order->getOrder($order_id);
        if ($order) {
            $this->model_checkout_order->confirm($order_id, $this->config->get('trustly_failed_status_id'), $this->language->get('error_payment_failed'), false);
        }

        $this->redirect($this->url->link('checkout/cart', '', 'SSL'));
    }

    /**
     * Error Action
     */
    public function error()
    {
        $this->language->load('payment/trustly');

        $this->data['heading_title'] = $this->language->get('text_error_title');
        if (!empty($this->session->data['error'])) {
            $this->data['description'] = $this->session->data['error'];
        } else {
            $this->data['description'] = $this->language->get('text_error_description');
        }

        $this->data['link_text'] = $this->language->get('text_error_link');
        $this->data['link'] = $this->url->link('checkout/checkout', '', 'SSL');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/trustly_error.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/trustly_error.tpl';
        } else {
            $this->template = 'default/template/payment/trustly_error.tpl';
        }

        $this->children = array(
            'common/column_left',
            'common/column_right',
            'common/content_top',
            'common/content_bottom',
            'common/footer',
            'common/header'
        );

        // Unset Error message
        unset($this->session->data['error']);

        $this->response->setOutput($this->render());
    }

    /**
     * Notification Action
     */
    public function notification()
    {
        $this->language->load('payment/trustly');

        $this->load->model('payment/trustly');
        $this->load->model('checkout/order');

        // Get Raw Post Data
        global $HTTP_RAW_POST_DATA;
        $http_raw_post_data = $HTTP_RAW_POST_DATA;
        if (empty($http_raw_post_data)) {
            $http_raw_post_data = file_get_contents('php://input');
        }

        if (empty($http_raw_post_data)) {
            $this->addLog('Received empty post data.');
            exit();
        }

        try {
            $notification = $this->getAPI()->handleNotification($http_raw_post_data);
        } catch(Trustly_SignatureException $e) {
            // Invalid signature, respond with failure
            $this->addLog(sprintf('Incoming message from Trustly with the wrong signature?! %s', $e->getMessage()));
            exit();
        } catch(Trustly_JSONRPCVersionException $e) {
            // This will likely be the cause when the notification was not
            // really a valid json rpc call at all, just ignore it.
            $this->addLog(sprintf('Incoming message from Trustly with the wrong jsonrpc version?! %s', $e->getMessage()));
            exit();
        }

        $notification_method = $notification->getMethod();
        if (empty($notification_method)) {
            $this->addLog('Notification don\'t have method.');
            exit();
        }

        $trustly_order_id = $notification->getData('orderid');
        $trustly_notification_id = $notification->getData('notificationid');
        $order_id = $this->model_payment_trustly->getOrderIdByTrustlyOrderId($trustly_order_id);
        if (!$order_id) {
            $this->addLog('Can\'t to get original Order Id by Trustly Order Id: ' . $trustly_order_id);
            exit();
        }

        $order = $this->model_checkout_order->getOrder($order_id);
        if (!$order) {
            $this->addLog('Can\'t load order Id: ' . $order_id);
            exit();
        }

        $payment_currency = $notification->getData('currency');
        $payment_amount = $notification->getData('amount');
        $payment_date = date('Y-m-d H:i:s', strtotime($notification->getData('timestamp')));

        $this->addLog('Notification Id: ' . $trustly_notification_id);
        $this->addLog('Notification method: ' . $notification_method);
        $this->addLog('Order Id: ' . $order_id);
        $this->addLog('Trustly Order Id: ' . $trustly_order_id);
        $this->addLog('Payment amount: ' . $payment_amount . ' ' . $payment_currency);
        switch ($notification_method) {
            case 'pending':
                $notification_message = sprintf($this->language->get('text_message_payment_pending'),
                    $payment_amount,
                    $payment_currency,
                    $payment_date,
                    $trustly_order_id
                );
                $this->model_checkout_order->confirm($order_id, $this->config->get('trustly_pending_status_id'), $notification_message, true);
                $this->addLog('Updated order status for order #' . $order_id);
                break;
            case 'credit':
                $notification_message = sprintf($this->language->get('text_message_payment_credited'),
                    $payment_amount,
                    $payment_currency,
                    $payment_date,
                    $trustly_order_id
                );
                $this->model_checkout_order->confirm($order_id, $this->config->get('trustly_completed_status_id'), $notification_message, true);
                $this->addLog('Updated order status for order #' . $order_id);
                break;
            case 'debit':
                $notification_message = sprintf($this->language->get('text_message_payment_debited'),
                    $payment_amount,
                    $payment_currency,
                    $payment_date
                );
                $this->model_checkout_order->confirm($order_id, $this->config->get('trustly_refunded_status_id'), $notification_message, true);
                $this->addLog('Updated order status for order #' . $order_id);
                break;
            case 'cancel':
                $notification_message = sprintf($this->config->get('text_message_payment_canceled'),
                    $payment_date
                );
                $this->model_checkout_order->confirm($order_id, $this->config->get('trustly_canceled_status_id'), $notification_message, true);
                $this->addLog('Updated order status for order #' . $order_id);
                break;
            default:
                //
        }

        // Save Notification
        $this->model_payment_trustly->addTrustlyNotification(
            $trustly_notification_id,
            $trustly_order_id,
            $notification_method,
            $payment_amount,
            $payment_currency,
            $payment_date
        );

        // Show Notification Response
        $response = $this->getAPI()->notificationResponse($notification, true);
        $response_json = $response->json();
        exit($response_json);
    }

    /**
     * Retrieve Payment Url from Trustly
     * @param $order_id
     * @param $amount
     * @return Trustly_Data_Response
     * @throws Exception
     */
    protected function retrievePaymentUrl($order_id, $amount = null)
    {
        $this->load->model('checkout/order');

        $order = $this->model_checkout_order->getOrder($order_id);
        if (!$order) {
            throw new Exception('Cannot retrieve data of order ' . $order_id);
        }

        if (!$amount) {
            $amount = $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false);
        }

        // Get all the data needed to process the payment
        $locale = $this->getLocale($this->language->get('code'));
        $currency = $order['currency_code'];
        $country = $order['payment_iso_code_2'];
        $client_ip = $order['ip'];
        $phone = $order['telephone'];
        $first_name = $order['payment_firstname'];
        $last_name = $order['payment_lastname'];
        $enduserid = strtolower($order['email']);
        $messageid = uniqid($order['order_id'] . '-', true);
        $store_name = html_entity_decode($order['store_name'], ENT_QUOTES, 'UTF-8');
        $version_string = sprintf('OpenCart %s/%s %s', VERSION, 'Trustly', '1.0.0');

        // Mixing POST and GET data is not supported so the NotificationURL must not contain a ? ("question mark")
        // Use wrapper to solve this problem
        $notification_url = $this->url->link('payment/' . $this->_module_name . '/notification', '', 'SSL');
        if (mb_strpos($notification_url, '?') !== false) {
            $notification_url = HTTPS_SERVER . 'trustly-notification.php';
        }

        $failed_url = $this->url->link('payment/' . $this->_module_name . '/failed', '', 'SSL');
        $success_url = $this->url->link('payment/' . $this->_module_name . '/success', '', 'SSL');

        try {
            $response = $this->getAPI()->deposit(
                $notification_url,
                $enduserid,
                $messageid,
                $locale,
                number_format($amount, 2, '.', ''),
                $currency,
                $country,
                $phone,
                $first_name,
                $last_name,
                null,
                $store_name,
                $client_ip,
                $success_url,
                $failed_url,
                null,
                null,
                null,
                null,
                $version_string
            );

            return $response;
        } catch (Trustly_ConnectionException $e) {
            $this->addLog('Error: ' .  $e->getMessage());
        } catch (Trustly_DataException $e) {
            $this->addLog('Error: ' .  $e->getMessage());
        } catch (Exception $e) {
            $this->addLog('Error: ' .  $e->getMessage());
        }

        return false;
    }

    /**
     * Add message to Log
     * @param $message
     */
    protected function addLog($message)
    {
        $log = new Log('trustly.log');
        $log->write($message);
    }

    /**
     * Get Locale
     * @param $lang
     * @return string
     */
    protected function getLocale($lang)
    {
        $allowedLangs = array(
            'en' => 'en_US',
            'sv' => 'sv_SE',
            'nb' => 'nb_NO',
            'da' => 'da_DK',
            'es' => 'es_ES',
            'de' => 'de_DE',
            'fi' => 'fi_FI',
            'fr' => 'fr_FR',
            'pl' => 'pl_PL',
            'et' => 'et_EE',
            'it' => 'it_IT'
        );

        if (isset($allowedLangs[$lang])) {
            return $allowedLangs[$lang];
        }

        return 'en_US';
    }

    /**
     * Return a newly constructed instance of the Trustly Signed Api object
     * using the module configured parameters. This will throw exceptions if
     * failing to configure the Api. Normally method functions should use
     * $this->getAPI() function instead.
     * @param $username
     * @param $password
     * @param $privatekey
     * @param $is_test_mode
     * @return Trustly_Api_Signed Object Instance of the Trustly API.
     */
    protected function configureAPI($username, $password, $privatekey, $is_test_mode = false)
    {
        $json_rpc_target_host = 'trustly.com';

        if ($is_test_mode) {
            $json_rpc_target_host = 'test.trustly.com';
        }

        $api = new Trustly_Api_Signed(
            null,
            $username,
            $password,
            $json_rpc_target_host,
            443,
            true);

        $api->useMerchantPrivateKey($privatekey);
        return $api;
    }

    /**
     * Get the active instance of the Trustly API configured for this module,
     * reuses a previously created instance if it exists or creates a new one
     * if it does not.
     * @return Trustly_Api_Signed Object A usable instance of the Trustly Signed API.
     */
    protected function getAPI()
    {
        if (!$this->api) {
            $username = $this->config->get('trustly_username');
            $password = $this->config->get('trustly_password');
            $privatekey = $this->config->get('trustly_private_key');
            $is_test_mode = $this->config->get('trustly_test_mode');
            $this->api = $this->configureAPI($username, $password, $privatekey, $is_test_mode);
        }

        return $this->api;
    }
}