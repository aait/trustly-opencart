<?php
if (!defined('DIR_APPLICATION')) {
    die();
}

require_once DIR_SYSTEM . 'library/trustly-client-php/Trustly.php';

class ControllerPaymentTrustly extends Controller
{
    protected $_module_name = 'trustly';
    protected $api;
    private $error = array();

    protected $_options = array(
        'trustly_username',
        'trustly_password',
        'trustly_private_key',
        'trustly_test_mode',
        'trustly_notify_http',
        'trustly_total',
        'trustly_completed_status_id',
        'trustly_pending_status_id',
        'trustly_canceled_status_id',
        'trustly_failed_status_id',
        'trustly_refunded_status_id',
        'trustly_geo_zone_id',
        'trustly_status',
        'trustly_sort_order',
    );

    protected $_texts = array(
        'button_save',
        'button_cancel',
        'button_credit',
        'heading_title',
        'text_settings',
        'text_backoffice_info',
        'text_backoffice_link_live',
        'text_backoffice_link_test',
        'text_orders',
        'text_username',
        'text_password',
        'text_private_key',
        'text_test_mode',
        'text_notify_http',
        'text_total',
        'text_complete_status',
        'text_pending_status',
        'text_canceled_status',
        'text_failed_status',
        'text_refunded_status',
        'text_geo_zone',
        'text_all_zones',
        'text_status',
        'text_enabled',
        'text_disabled',
        'text_sort_order',
        'text_success',
        'text_order_id',
        'text_trustly_order_id',
        'text_notification_id',
        'text_amount',
        'text_date',
        'text_actions',
        'text_wait',
        'text_refund',
        'text_refunded',
        'text_refund_performed',
        'text_new_private_key',
        'text_show_public_key',
        'text_rsa_keys',
        'text_failed_generate_key',
        'text_warning_private_key_exists',
        'text_new_key_generated'
    );

    /**
     * Index Action
     */
    function index()
    {
        $this->load->language('payment/' . $this->_module_name);
        $this->load->model('setting/setting');

        $this->document->setTitle($this->language->get('heading_title'));

        // Load texts
        foreach ($this->_texts as $text) {
            $this->data[$text] = $this->language->get($text);
        }

        // Load options
        foreach ($this->_options as $option) {
            if (isset($this->request->post[$option])) {
                $this->data[$option] = $this->request->post[$option];
            } else {
                $this->data[$option] = $this->config->get($option);
            }
        }

        // Load config
        $this->load->model('localisation/order_status');
        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $this->load->model('localisation/geo_zone');
        $this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $this->data['action'] = $this->url->link('payment/' . $this->_module_name, 'token=' . $this->session->data['token'], 'SSL');
        $this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        if (($this->request->server['REQUEST_METHOD'] === 'POST')) {
            if (isset($this->request->post['action'])) {
                // Actions
                switch ($this->request->post['action']) {
                case 'refund':
                    $this->load->model('sale/order');

                    $order_id = $this->request->post['order_id'];
                    $trustly_order_id = $this->request->post['trustly_order_id'];

                    // Check order
                    $order = $this->model_sale_order->getOrder($order_id);
                    if (!$order) {
                        $json = array(
                            'status' => 'error',
                            'message' => 'Invalid order Id'
                        );
                        $this->response->setOutput(json_encode($json));
                        return;
                    }

                    // Refund
                    $amount = number_format($this->request->post['amount'], 2, '.', '');
                    $currency = $this->request->post['currency'];

                    $response = $this->getAPI()->refund($trustly_order_id, $amount, $currency);
                    if (!$response->isSuccess()) {
                        $json = array(
                            'status' => 'error',
                            'message' => sprintf('%s (%s)', $response->getErrorCode(), $response->getErrorMessage())
                        );
                        $this->response->setOutput(json_encode($json));
                        return;
                    }

                    // Set Order Status
                    $this->model_sale_order->addOrderHistory($order_id, array(
                        'order_status_id' => $this->data['trustly_refunded_status_id'],
                        'notify' => false,
                        'comment' => sprintf($this->data['text_refund_performed'], $amount, $currency, date('Y-m-d H:i:s'))
                    ));

                    $json = array(
                        'status' => 'ok',
                        'message' => 'Order successfully refunded.',
                        'label' => $this->data['text_refunded']
                    );
                    $this->response->setOutput(json_encode($json));
                    return;
                case 'trustly_generate_rsa_key':
                    $config = array(
                        "digest_alg" => "sha512",
                        "private_key_bits" => 2048,
                        "private_key_type" => OPENSSL_KEYTYPE_RSA,
                    );

                    $new_key = openssl_pkey_new($config);
                    openssl_pkey_export($new_key, $priv_key);
                    $pub_key = openssl_pkey_get_details($new_key);

                    $json = array(
                        'public_key' => $pub_key['key'],
                        'private_key' => $priv_key
                    );
                    $this->response->setOutput(json_encode($json));

                    return;
                case 'trustly_generate_rsa_public_key':
                    $new_key = openssl_pkey_get_private($this->request->post['private_key']);
                    $pub_key = openssl_pkey_get_details($new_key);

                    $json = array(
                        'public_key' => $pub_key['key']
                    );
                    $this->response->setOutput(json_encode($json));

                    return;
                default:
                    //
                }
            } else {
                // Validate Form
                if ($this->validate()) {
                    // Install DB Tables
                    $this->installDbTables();

                    // Save settings
                    $this->save();
                }
            }
        }

        // Errors
        $this->data['error'] = $this->error;

        // Breadcrumbs
        $this->data['breadcrumbs'] = array();
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/' . $this->_module_name, 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        // Get Data for Pagination
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit = (int)$this->config->get('config_admin_limit');

        // Get Trustly Orders
        $query = sprintf("SELECT SQL_CALC_FOUND_ROWS * FROM `" . DB_PREFIX . "trustly_orders` trustly_orders
            INNER JOIN `" . DB_PREFIX . "trustly_notifications` notifications ON trustly_orders.trustly_order_id = notifications.trustly_order_id
            INNER JOIN `" . DB_PREFIX . "order` opencart_order ON trustly_orders.order_id = opencart_order.order_id
            WHERE notifications.method = 'credit'
            ORDER BY trustly_orders.order_id DESC
            LIMIT %d OFFSET %d;
        ",
            $limit,
            $limit * ($page - 1)
        );

        // Prepare Order Totals
        $orders = $this->db->query($query);
        foreach ($orders->rows as &$order_info) {
            $order_info['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
        }

        $this->data['orders'] = $orders->rows;

        // Get Total
        $total = $this->db->query('SELECT FOUND_ROWS() as total;');

        // Pagination
        $pagination = new Pagination();
        $pagination->total = (int)$total->row;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('payment/trustly', 'token=' . $this->session->data['token'] . '&page={page}', 'SSL');
        $this->data['pagination'] = $pagination->render();

        $this->template = 'payment/trustly.tpl';

        $this->children = array(
            'common/header',
            'common/footer',
        );

        $this->response->setOutput($this->render());
    }

    /**
     * Validate configuration
     */
    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'payment/' . $this->_module_name)) {
            $this->error['warning'] = $this->language->get('error_permission');
            return false;
        }

        // Check PHP Extensions
        if (!extension_loaded('openssl')) {
            $this->error[] = $this->language->get('error_php_extension_openssl');
        }

        if (!extension_loaded('curl')) {
            $this->error[] = $this->language->get('error_php_extension_curl');
        }

        if (!extension_loaded('bcmath')) {
            $this->error[] = $this->language->get('error_php_extension_bcmath');
        }

        if (!extension_loaded('mbstring')) {
            $this->error[] = $this->language->get('error_php_extension_mbstring');
        }

        if (!extension_loaded('json')) {
            $this->error[] = $this->language->get('error_php_extension_json');
        }

        if (count($this->error) > 0) {
            return false;
        }

        // Validate fields
        $username = $this->request->post['trustly_username'];
        $password = $this->request->post['trustly_password'];
        $private_key = $this->request->post['trustly_private_key'];
        $test_mode = isset($this->request->post['trustly_test_mode']) ? (bool)$this->request->post['trustly_test_mode'] : false;

        if (empty($username)) {
            $this->error['trustly_username'] = $this->language->get('error_username');
        }

        if (empty($password)) {
            $this->error['trustly_password'] = $this->language->get('error_password');
        }

        if (empty($private_key)) {
            $this->error['trustly_private_key'] = $this->language->get('error_private_key');
        }

        if (count($this->error) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Save configuration
     */
    protected function save()
    {
        // Default values
        $data = array(
            'trustly_notify_http' => false,
            'trustly_test_mode' => false,
        );

        // Get values
        foreach ($this->_options as $option) {
            if (isset($this->request->post[$option])) {
                switch ($option) {
                    case 'trustly_notify_http';
                    case 'trustly_test_mode':
                        $data[$option] = (bool)$this->request->post[$option];
                        break;
                    default:
                        $data[$option] = $this->request->post[$option];
                }
            }
        }

        // Save values
        $this->model_setting_setting->editSetting($this->_module_name, $data);

        $this->session->data['success'] = $this->language->get('text_success');
        $this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
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

    /**
     * Install Database Tables
     */
    public function installDbTables()
    {
        $res = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "trustly_orders'");
        if ($res->num_rows === 0) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "trustly_orders` (
                    `order_id` int NOT NULL COMMENT 'OpenCart Order Id',
                    `lock_timestamp` timestamp NULL COMMENT 'Timestamp of lock on this order',
                    `lock_id` int unsigned NULL COMMENT 'Id of lock on this order',
                    `trustly_order_id` varchar(20) NOT NULL COMMENT 'Trustly Order Id',
                    `url` varchar(255) DEFAULT NULL COMMENT 'Trustly Payment URL',
                    PRIMARY KEY (`order_id`),
                    UNIQUE KEY `trustly_order_id` (`trustly_order_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Trustly Orders Mapping';
            ");
        }

        $res = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "trustly_notifications'");
        if ($res->num_rows === 0) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "trustly_notifications` (
                    `notification_id` varchar(20) NOT NULL COMMENT 'Trustly Notification Id',
                    `trustly_order_id` varchar(20) NOT NULL COMMENT 'Trustly Order Id',
                    `method` varchar(50) DEFAULT NULL COMMENT 'Trustly Notification Method',
                    `amount` numeric DEFAULT '0' COMMENT 'Payment amount',
                    `currency` varchar(3) DEFAULT NULL COMMENT 'Payment currency',
                    `date` timestamp NULL DEFAULT NULL COMMENT 'Payment date',
                    PRIMARY KEY (`notification_id`),
                    KEY `trustly_order_id` (`trustly_order_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Trustly Payment Notifications';
            ");
        }
    }
}
