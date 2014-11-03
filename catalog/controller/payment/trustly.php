<?php
if (!defined('DIR_APPLICATION')) {
    die();
}
require_once DIR_SYSTEM . 'library/trustly-client-php/Trustly.php';

class ControllerPaymentTrustly extends Controller
{
    protected $_module_name = 'trustly';
    protected $_module_version = '1.0.0';
    protected $api;

    /**
     * Index Action
     */
    public function index()
    {
        $this->language->load('payment/trustly');
        $this->load->model('payment/trustly');

        // Get Order Id
        $order_id = $this->session->data['order_id'];
        if (empty($order_id)) {
            $this->session->data['error'] = $this->language->get('error_invalid_order');
            $this->response->redirect($this->url->link('payment/' . $this->_module_name . '/error', '', 'SSL'));
        }

        $data = array();
        // Get Trustly Payment URL
        $trustly_order = $this->model_payment_trustly->getTrustlyOrder($order_id);
        if (!$trustly_order) {
            // Retrieve Payment Url from Trustly
            $response = $this->retrievePaymentUrl($order_id);

            if($response !== FALSE && $response->isSuccess()) {
                $trustly_order_id = $response->getData('orderid');
                $url = $response->getData('url');

                // Save Trustly Order Id
                $this->model_payment_trustly->addTrustlyOrder($order_id, $trustly_order_id, $url);

                $trustly_order = array(
                    'trustly_order_id' => $trustly_order_id,
                    'order_id' => $order_id,
                    'url' => $url
                );
            } else {
                $error = $this->language->get('error_order_create');
                if($response === FALSE) {
                    $this->model_payment_trustly->addLog('Failed to create the Trustly order');
                } else {
                    $this->model_payment_trustly->addLog('Failed to create the Trustly order: ' . $response->getErrorCode() . ' - ' . $response->getErrorMessage());
                    $error = $response->getErrorMessage() . ' (' . $response->getErrorCode() . ')';
                }

                // Show Error
                $data['warning'] = sprintf($this->language->get('error_trustly'), htmlspecialchars($error));
            }
        }

        if(!isset($data['warning'])) {

            // Check Payment URL
            if (empty($trustly_order['url'])) {
                $this->model_payment_trustly->removeTrustlyOrder($trustly_order['trustly_order_id']);
                throw new Exception($this->language->get('error_no_payment_url'));
            }

            $data['text_title'] = $this->language->get('text_title');
            $data['action'] = '';
            $data['trustly_iframe_url'] = $trustly_order['url'];
            $data['trustly_order_id'] = $trustly_order['trustly_order_id'];
        }

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/trustly.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/payment/trustly.tpl', $data);
        } else {
            return $this->load->view('default/template/payment/trustly.tpl', $data);
        }
    }

    /**
     * Confirm Action
     */
    public function confirm()
    {
        $this->language->load('payment/trustly');

        $this->load->model('checkout/order');
        $this->load->model('payment/trustly');

        $order_id = @$this->session->data['order_id'];
        if (empty($order_id)) {
            $this->session->data['error'] = $this->language->get('error_invalid_order');
            $this->response->redirect($this->url->link('payment/' . $this->_module_name . '/error', '', 'SSL'));
        }

        // Waiting to receive notifications
        $notification_methods = array();
        $trustly_order_id = $this->model_payment_trustly->getTrustlyOrderId($order_id);
        if ($trustly_order_id) {
            for ($i = 0; $i < 60; $i++) {
                $notifications = $this->model_payment_trustly->getTrustlyNotifications($trustly_order_id);
                if (count($notifications) === 0) {
                    // Wait half a second and try again
                    usleep(500000);
                } else {
                    foreach ($notifications as $item) {
                        $notification_methods[] = $item['method'];
                    }

                    break;
                }
            }
        }

        // Set Pending status
        if (!in_array('credit', $notification_methods) && !in_array('pending', $notification_methods)) {
            $this->model_checkout_order->addOrderHistory($order_id,
                $this->config->get('trustly_pending_status_id'), 
                $this->language->get('text_message_payment_pending_notification'),
                true);
        }

        $this->response->redirect($this->url->link('payment/' . $this->_module_name . '/success', '', 'SSL'));
    }

    /**
     * Success Action
     */
    public function success()
    {
        $this->language->load('checkout/success');
        $this->language->load('payment/trustly');
        $this->load->model('payment/trustly');

        $trustly_order_id = $this->model_payment_trustly->getTrustlyOrderId(@$this->session->data['order_id']);
        if (!$trustly_order_id) {
            $this->session->data['error'] = $this->language->get('error_invalid_order');
            $this->response->redirect($this->url->link('payment/' . $this->_module_name . '/error', '', 'SSL'));
        }

        if (isset($this->session->data['order_id'])) {
            $this->cart->clear();


            // Add to activity log
            $this->load->model('account/activity');
            if ($this->customer->isLogged()) {
                $activity_data = array(
                    'customer_id' => $this->customer->getId(),
                    'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
                    'order_id'    => $this->session->data['order_id']
                );
                $this->model_account_activity->addActivity('order_account', $activity_data);
            } else {
                $activity_data = array(
                    'name'     => $this->session->data['guest']['firstname'] . ' ' . $this->session->data['guest']['lastname'],
                    'order_id' => $this->session->data['order_id']
                );
                $this->model_account_activity->addActivity('order_guest', $activity_data);
            }
            

            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['guest']);
            unset($this->session->data['comment']);
            unset($this->session->data['order_id']);
            unset($this->session->data['coupon']);
            unset($this->session->data['reward']);
            unset($this->session->data['voucher']);
            unset($this->session->data['vouchers']);
            unset($this->session->data['totals']);
        }

        $this->language->load('checkout/success');

        $this->document->setTitle($this->language->get('heading_title'));

        $data = array();
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'href'      => $this->url->link('common/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'href'      => $this->url->link('checkout/cart'),
            'text'      => $this->language->get('text_basket'),
            'separator' => $this->language->get('text_separator')
        );

        $data['breadcrumbs'][] = array(
            'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
            'text'      => $this->language->get('text_checkout'),
            'separator' => $this->language->get('text_separator')
        );

        $data['breadcrumbs'][] = array(
            'href'      => $this->url->link('checkout/success'),
            'text'      => $this->language->get('text_success'),
            'separator' => $this->language->get('text_separator')
        );

        $data['heading_title'] = $this->language->get('heading_title');


        // Analyze notifications
        $is_pending = false;
        $is_credit = false;
        $notifications = $this->model_payment_trustly->getTrustlyNotifications($trustly_order_id);
        foreach ($notifications as $notification) {
            switch ($notification['method']) {
                case 'pending':
                    $is_pending = true;
                    break;
                case 'credit':
                    $is_credit = true;
                    break;
            }
        }

        // Get message for Customer
        if (!$is_pending && !$is_credit) {
            // No notifications
            $message = $this->language->get('text_order_orders_pending');
        } elseif ($is_credit) {
            // Credited
            $message = $this->language->get('text_order_orders_processed');
        } else {
            $message = $this->language->get('text_order_orders_pending');
        }

        if ($this->customer->isLogged()) {
            $data['text_message'] = $message . sprintf($this->language->get('text_success_customer'), $this->url->link('account/account', '', 'SSL'), $this->url->link('account/order', '', 'SSL'), $this->url->link('account/download', '', 'SSL'), $this->url->link('information/contact'));
        } else {
            $data['text_message'] = $message . sprintf($this->language->get('text_success_guest'), $this->url->link('information/contact'));
        }

        $data['button_continue'] = $this->language->get('button_continue');

        $data['continue'] = $this->url->link('common/home');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/common/success.tpl')) {
            $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/common/success.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view('default/template/common/success.tpl', $data));
        }
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
            $this->response->redirect($this->url->link('payment/' . $this->_module_name . '/error', '', 'SSL'));
        }

        $order = $this->model_checkout_order->getOrder($order_id);
        if ($order) {
            $this->model_checkout_order->addOrderHistory($order_id,
                $this->config->get('trustly_failed_status_id'),
                $this->language->get('error_payment_failed'),
                false);
        }

        $this->response->redirect($this->url->link('checkout/cart', '', 'SSL'));
    }

    /**
     * Error Action
     */
    public function error()
    {
        $this->language->load('payment/trustly');

        $data = array();

        $data['heading_title'] = $this->language->get('text_error_title');
        if (!empty($this->session->data['error'])) {
            $data['description'] = $this->session->data['error'];
        } else {
            $data['description'] = $this->language->get('text_error_description');
        }

        $data['link_text'] = $this->language->get('text_error_link');
        $data['link'] = $this->url->link('checkout/checkout', '', 'SSL');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        // Unset Error message
        unset($this->session->data['error']);

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/trustly_error.tpl')) {
            $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/trustly_error.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view('default/template/payment/trustly_error.tpl', $data));
        }
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
        if(isset($HTTP_RAW_POST_DATA)) {
            $http_raw_post_data = $HTTP_RAW_POST_DATA;
        }
        if (empty($http_raw_post_data)) {
            $http_raw_post_data = file_get_contents('php://input');
        }

        if (empty($http_raw_post_data)) {
            $this->model_payment_trustly->addLog('Received empty post data.');
            exit();
        }

        try {
            $notification = $this->getAPI()->handleNotification($http_raw_post_data);
        } catch(Trustly_SignatureException $e) {
            // Invalid signature, respond with failure
            $this->model_payment_trustly->addLog(sprintf('Incoming message from Trustly with the wrong signature?! %s', $e->getMessage()));
            exit();
        } catch(Trustly_JSONRPCVersionException $e) {
            // This will likely be the cause when the notification was not
            // really a valid json rpc call at all, just ignore it.
            $this->model_payment_trustly->addLog(sprintf('Incoming message from Trustly with the wrong jsonrpc version?! %s', $e->getMessage()));
            exit();
        }

        $notification_method = $notification->getMethod();
        if (empty($notification_method)) {
            $this->model_payment_trustly->addLog('Notification don\'t have method.');
            exit();
        }

        $trustly_order_id = $notification->getData('orderid');
        $trustly_notification_id = $notification->getData('notificationid');
        $order_id = $this->model_payment_trustly->getOrderIdByTrustlyOrderId($trustly_order_id);
        if (!$order_id) {
            $this->model_payment_trustly->addLog('Can\'t to get original Order Id by Trustly Order Id: ' . $trustly_order_id);
            exit();
        }

        $order = $this->model_checkout_order->getOrder($order_id);
        if (!$order) {
            $this->model_payment_trustly->addLog('Can\'t load order #' . $order_id);
            exit();
        }

        $lock_id = $this->model_payment_trustly->lockOrderForProcessing($order_id);
        if($lock_id === false) {
            $this->model_payment_trustly->addLog('Can\'t lock order #' . $order_id . ' for processing, aborting');
            exit();
        }


        // Get Order Notifications
        $notifications = $this->model_payment_trustly->getTrustlyNotifications($trustly_order_id);
        $methods = array();
        foreach ($notifications as $item) {
            // Check Notification is already registered
            if ($item['notification_id'] === $trustly_notification_id) {
                $this->model_payment_trustly->unlockOrderAfterProcessing($order_id, $lock_id);
                // Show Notification Response
                $response = $this->getAPI()->notificationResponse($notification, true);
                $response_json = $response->json();
                $this->model_payment_trustly->addLog('Reusing previous response for notification ' . $trustly_notification_id . ' for trustly order ' . $trustly_order_id);
                exit($response_json);
            }

            $methods[] = $item['method'];
        }

        $payment_currency = $notification->getData('currency');
        $payment_amount = $notification->getData('amount');
        if($notification->getData('timestamp')) {
            $payment_date = date('Y-m-d H:i:s', strtotime($notification->getData('timestamp')));
        } else {
            $payment_date = date('Y-m-d H:i:s');
        }

        $this->model_payment_trustly->addLog(sprintf('Notification Id: %s, Notification method: %s, Order Id: %s, Trustly Order Id: %s, Payment amount: %s %s',
            $trustly_notification_id, $notification_method, $order_id, $trustly_order_id, $payment_amount, $payment_currency));


        switch ($notification_method) {
            case 'pending':
                if (in_array($notification_method, $methods)) {
                    $this->model_payment_trustly->addLog('Incoming pending notification, but Order #' . $order_id . ' already pending.');
                    break;
                }

                if (in_array('credit', $methods)) {
                    $this->model_payment_trustly->addLog('Incoming pending notification, but Order #' . $order_id . ' already credited.');
                    break;
                }

                if (in_array('cancel', $methods)) {
                    $this->model_payment_trustly->addLog('Incoming pending notification, but Order #' . $order_id . ' has been canceled.');
                    break;
                }

                $notification_message = sprintf($this->language->get('text_message_payment_pending'),
                    $payment_amount,
                    $payment_currency,
                    $payment_date,
                    $trustly_order_id
                );

                // Set Order status
                $this->model_checkout_order->addOrderHistory($order_id,
                    $this->config->get('trustly_pending_status_id'),
                    $notification_message,
                    true);

                $this->model_payment_trustly->addLog('Updated order status to ' . $this->config->get('trustly_pending_status_id') . ' for order #' . $order_id);
                break;
            case 'credit':
                if (in_array('cancel', $methods)) {
                    $this->model_payment_trustly->addLog('Incoming credit notification, but Order #' . $order_id . ' has been canceled.');
                    break;
                }

                // Validate amount
                $order_amount = $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false);
                if (bccomp($order_amount, $payment_amount, 2) !== 0 || $order['currency_code'] !== $payment_currency) {
                    $notification_message = sprintf($this->language->get('error_message_payment_amount_invalid'),
                        $payment_date,
                        $payment_amount,
                        $payment_currency,
                        $order_amount,
                        $order['currency_code']
                    );
                    $notification_message .= "\n" . $this->language->get('error_wrong_payment_amount');

                    $this->model_checkout_order->addOrderHistory($order_id,
                        $this->config->get('trustly_failed_status_id'),
                        $notification_message,
                        true);

                    $this->model_payment_trustly->addLog(sprintf('Incoming payment with wrong amount/currency, is %s %s, expected %s %s. Setting order status to %s for order #%s',
                        $payment_amount,
                        $payment_currency,
                        $order_amount,
                        $order['currency_code'],
                        $this->config->get('trustly_failed_status_id'),
                        $order_id
                    ));
                } else {
                    $notification_message = sprintf($this->language->get('text_message_payment_credited'),
                        $payment_amount,
                        $payment_currency,
                        $payment_date,
                        $trustly_order_id
                    );

                    // Confirm Order
                    $this->model_checkout_order->addOrderHistory($order_id,
                        $this->config->get('trustly_completed_status_id'),
                        $notification_message,
                        true);

                    $this->model_payment_trustly->addLog('Updated order status to ' . $this->config->get('trustly_completed_status_id') . ' for order #' . $order_id);
                }
                break;
            case 'debit':
                if (in_array('cancel', $methods)) {
                    $this->model_payment_trustly->addLog('Incoming debit notification, but Order #' . $order_id . ' has been canceled.');
                    break;
                }

                $notification_message = sprintf($this->language->get('text_message_payment_debited'),
                    $payment_amount,
                    $payment_currency,
                    $payment_date
                );

                // Set Order status
                $this->model_checkout_order->addOrderHistory($order_id,
                    $this->config->get('trustly_refunded_status_id'),
                    $notification_message,
                    true);
                $this->model_payment_trustly->addLog('Updated order status to ' .  $this->config->get('trustly_refunded_status_id') . ' for order #' . $order_id);
                break;
            case 'cancel':
                if (in_array($notification_method, $methods)) {
                    $this->model_payment_trustly->addLog('Order #' . $order_id . ' already canceled.');
                    break;
                }

                $notification_message = sprintf($this->language->get('text_message_payment_canceled'),
                    $payment_date
                );

                // Set Order status
                $this->model_checkout_order->addOrderHistory($order_id,
                    $this->config->get('trustly_canceled_status_id'),
                    $notification_message,
                    true);
                $this->model_payment_trustly->addLog('Updated order status to ' . $this->config->get('trustly_canceled_status_id') . ' for order #' . $order_id);
                break;
            default:
                $this->model_payment_trustly->addLog('Not processing ' . $notification_method . ' notification for order #' . $order_id);
                break;
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

        $this->model_payment_trustly->unlockOrderAfterProcessing($order_id, $lock_id);
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
        $this->load->model('payment/trustly');

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
        $version_string = sprintf('OpenCart %s/%s %s', VERSION, 'Trustly',  $this->_module_version);

        // Mixing POST and GET data is not supported so the NotificationURL must not contain a ? ("question mark")
        // Use wrapper to solve this problem
        $notification_url = $this->url->link('payment/' . $this->_module_name . '/notification', '', ($this->config->get('trustly_notify_http') ? 'NONSSL' : 'SSL'));
        if (mb_strpos($notification_url, '?') !== false) {
            $notification_url = ($this->config->get('trustly_notify_http') ? HTTP_SERVER : HTTPS_SERVER) . 'trustly-notification.php';
        }

        $failed_url = $this->url->link('payment/' . $this->_module_name . '/failed', '', 'SSL');
        $success_url = $this->url->link('payment/' . $this->_module_name . '/confirm', '', 'SSL');

        try {
            $response = $this->getAPI()->deposit(
                $notification_url,					//NotificationURL
                $enduserid,							//EnduserID
                $messageid,							//MessageID
                $locale,							//Locale
                number_format($amount, 2, '.', ''),	//Amount
                $currency,							//Currency
                $country,							//Country
                $phone,								//MobilePhone
                $first_name,						//Firstname
                $last_name,							//Lastname
                null,								//NationalIdentificationNumber
                $store_name,						//ShopperStatement
                $client_ip,							//Host
                $success_url,						//SuccessURL
                $failed_url,						//FailURL
                null,								//TemplateURL
                null,								//URLTarget
                null,								//SuggestedMinAmount
                null,								//SuggestedMaxAmount
                $version_string						//IntegrationModule
            );

            return $response;
        } catch (Trustly_ConnectionException $e) {
            $this->model_payment_trustly->addLog('Deposit call failed with Trustly_ConnectionException: ' .  $e->getMessage());
        } catch (Trustly_DataException $e) {
            $this->model_payment_trustly->addLog('Deposit call failed with Trustly_DataException: ' .  $e->getMessage());
        } catch (Exception $e) {
            $this->model_payment_trustly->addLog('Deposit call failed with Error: ' .  $e->getMessage());
        }

        return false;
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
            'se' => 'sv_SE',
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
