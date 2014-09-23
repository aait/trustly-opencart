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
					$this->addLog('Failed to create the Trustly order');
				} else {
					$this->addLog('Failed to create the Trustly order: ' . $response->getErrorCode() . ' - ' . $response->getErrorMessage());
                    $error = $response->getErrorMessage() . ' (' . $response->getErrorCode() . ')';
				}

                // Show Error
                echo '<div class="warning">' . sprintf($this->language->get('error_trustly'), htmlspecialchars($error)) . '</div>';
                return;
			}
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
     * Confirm Action
     */
    public function confirm()
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
            $this->model_checkout_order->update($order_id, $this->config->get('trustly_pending_status_id'), $this->language->get('text_message_payment_pending_notification'), false);
            $this->sendConfirmationMail($order_id, $this->language->get('text_message_payment_pending_notification'));
        }

        $this->redirect($this->url->link('payment/' . $this->_module_name . '/success', '', 'SSL'));
    }

    /**
     * Success Action
     */
    public function success()
    {
        $this->language->load('checkout/success');
        $this->language->load('payment/trustly');
        $this->load->model('payment/trustly');

        $trustly_order_id = $this->model_payment_trustly->getTrustlyOrderId($this->session->data['order_id']);
        if (!$trustly_order_id) {
            $this->session->data['error'] = $this->language->get('error_invalid_order');
            $this->redirect($this->url->link('payment/' . $this->_module_name . '/error', '', 'SSL'));
        }

        if (isset($this->session->data['order_id'])) {
            $this->cart->clear();

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

        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'href'      => $this->url->link('common/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'href'      => $this->url->link('checkout/cart'),
            'text'      => $this->language->get('text_basket'),
            'separator' => $this->language->get('text_separator')
        );

        $this->data['breadcrumbs'][] = array(
            'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
            'text'      => $this->language->get('text_checkout'),
            'separator' => $this->language->get('text_separator')
        );

        $this->data['breadcrumbs'][] = array(
            'href'      => $this->url->link('checkout/success'),
            'text'      => $this->language->get('text_success'),
            'separator' => $this->language->get('text_separator')
        );

        $this->data['heading_title'] = $this->language->get('heading_title');


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
            $this->data['text_message'] = $message . sprintf($this->language->get('text_success_customer'), $this->url->link('account/account', '', 'SSL'), $this->url->link('account/order', '', 'SSL'), $this->url->link('account/download', '', 'SSL'), $this->url->link('information/contact'));
        } else {
            $this->data['text_message'] = $message . sprintf($this->language->get('text_success_guest'), $this->url->link('information/contact'));
        }

        $this->data['button_continue'] = $this->language->get('button_continue');

        $this->data['continue'] = $this->url->link('common/home');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/common/success.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/common/success.tpl';
        } else {
            $this->template = 'default/template/common/success.tpl';
        }

        $this->children = array(
            'common/column_left',
            'common/column_right',
            'common/content_top',
            'common/content_bottom',
            'common/footer',
            'common/header'
        );

        $this->response->setOutput($this->render());
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

		// Get Order Notifications
		$notifications = $this->model_payment_trustly->getTrustlyNotifications($trustly_order_id);
		$methods = array();
		foreach ($notifications as $item) {
			// Check Notification is already registered
			if ($item['notification_id'] === $trustly_notification_id) {
        		// Show Notification Response
        		$response = $this->getAPI()->notificationResponse($notification, true);
        		$response_json = $response->json();
        		exit($response_json);
			}

			$methods[] = $item['method'];
		}

        $payment_currency = $notification->getData('currency');
        $payment_amount = $notification->getData('amount');
        $payment_date = date('Y-m-d H:i:s', strtotime($notification->getData('timestamp')));

		$this->addLog(sprintf('Notification Id: %s, Notification method: %s, Order Id: %s, Trustly Order Id: %s, Payment amount: %s %s',
			$trustly_notification_id, $notification_method, $order_id, $trustly_order_id, $payment_amount, $payment_currency));

        // Set Order status to "Pending"
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$this->config->get('trustly_pending_status_id') . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");


        switch ($notification_method) {
            case 'pending':
				if (in_array($notification_method, $methods)) {
					$this->addLog('Incoming pending notification, but Order #' . $order_id . ' already pending.');
					break;
				}
                if (in_array('credit', $methods)) {
                    $this->addLog('Incoming pending notification, but Order #' . $order_id . ' already credited.');
                    break;
                }

                $notification_message = sprintf($this->language->get('text_message_payment_pending'),
                    $payment_amount,
                    $payment_currency,
                    $payment_date,
                    $trustly_order_id
                );

                // Set Order status
                $this->model_checkout_order->update($order_id, $this->config->get('trustly_pending_status_id'), $notification_message, false);
                $this->sendConfirmationMail($order_id, $notification_message);
                $this->addLog('Updated order status to ' . $this->config->get('trustly_pending_status_id') . ' for order #' . $order_id);
                break;
            case 'credit':
				// Validate amount
				//$order_amount = $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false);
				if (bccomp($order['total'], $payment_amount, 2) !== 0 || $this->config->get('config_currency') !== $payment_currency) {
					$notification_message = sprintf($this->language->get('error_message_payment_amount_invalid'),
						$payment_date,
						$payment_amount,
						$payment_currency,
						$order['total'],
						$this->config->get('config_currency')
					);

					// Add Order History
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$this->config->get('trustly_pending_status_id') . "', notify = '0', comment = '" . $this->db->escape($notification_message) . "', date_added = NOW()");
				}

                $notification_message = sprintf($this->language->get('text_message_payment_credited'),
                    $payment_amount,
                    $payment_currency,
                    $payment_date,
                    $trustly_order_id
                );

                // Confirm Order
                $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '0', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
                $this->model_checkout_order->confirm($order_id, $this->config->get('trustly_completed_status_id'), $notification_message, true);
                //$this->sendConfirmationMail($order_id, $notification_message);
                $this->addLog('Updated order status to ' . $this->config->get('trustly_completed_status_id') . ' for order #' . $order_id);
                break;
            case 'debit':
                $notification_message = sprintf($this->language->get('text_message_payment_debited'),
                    $payment_amount,
                    $payment_currency,
                    $payment_date
                );

                // Set Order status
                $this->model_checkout_order->update($order_id, $this->config->get('trustly_refunded_status_id'), $notification_message, false);
                $this->sendConfirmationMail($order_id, $notification_message);
                $this->addLog('Updated order status to ' .  $this->config->get('trustly_refunded_status_id') . ' for order #' . $order_id);
                break;
            case 'cancel':
				if (in_array($notification_method, $methods)) {
					$this->addLog('Order #' . $order_id . ' already canceled.');
					break;
				}

                $notification_message = sprintf($this->config->get('text_message_payment_canceled'),
                    $payment_date
                );

                // Set Order status
                $this->model_checkout_order->update($order_id, $this->config->get('trustly_canceled_status_id'), $notification_message, false);
                $this->sendConfirmationMail($order_id, $notification_message);
                $this->addLog('Updated order status to ' . $this->config->get('trustly_canceled_status_id') . ' for order #' . $order_id);
                break;
            default:
				$this->addLog('Not processing ' . $notification_method . ' notification for order #' . $order_id);
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
            //$amount = $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false);
            $amount = $order['total'];
        }

        // Get all the data needed to process the payment
        $locale = $this->getLocale($this->language->get('code'));
        //$currency = $order['currency_code'];
        $currency = $this->config->get('config_currency');
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
        $notification_url = $this->url->link('payment/' . $this->_module_name . '/notification', '', 'SSL');
        if (mb_strpos($notification_url, '?') !== false) {
            $notification_url = HTTPS_SERVER . 'trustly-notification.php';
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
            $this->addLog('Deposit call failed with Trustly_ConnectionException: ' .  $e->getMessage());
        } catch (Trustly_DataException $e) {
            $this->addLog('Deposit call failed with Trustly_DataException: ' .  $e->getMessage());
        } catch (Exception $e) {
            $this->addLog('Deposit call failed with Error: ' .  $e->getMessage());
        }

        return false;
    }

    /**
     * Send Confirmation Mail
     * @param $order_id
     * @param string $comment
     */
    protected function sendConfirmationMail($order_id, $comment = '')
    {
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
        foreach ($order_product_query->rows as $order_product) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

            $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product['order_product_id'] . "'");
            foreach ($order_option_query->rows as $option) {
                $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$option['product_option_value_id'] . "' AND subtract = '1'");
            }
        }
        //$this->cache->delete('product');

        // Send out order confirmation mail
        $language = new Language($order_info['language_directory']);
        $language->load($order_info['language_filename']);
        $language->load('mail/order');

        $order_status_id = $order_info['order_status_id'];
        $order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");

        $order_status = '';
        if ($order_status_query->num_rows) {
            $order_status = $order_status_query->row['name'];
        }

        $subject = sprintf($language->get('text_new_subject'), $order_info['store_name'], $order_id);

        // HTML Mail
        $template = new Template();

        $template->data['title'] = sprintf($language->get('text_new_subject'), html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'), $order_id);

        $template->data['text_greeting'] = sprintf($language->get('text_new_greeting'), html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
        $template->data['text_link'] = $language->get('text_new_link');
        $template->data['text_download'] = $language->get('text_new_download');
        $template->data['text_order_detail'] = $language->get('text_new_order_detail');
        $template->data['text_instruction'] = $language->get('text_new_instruction');
        $template->data['text_order_id'] = $language->get('text_new_order_id');
        $template->data['text_date_added'] = $language->get('text_new_date_added');
        $template->data['text_payment_method'] = $language->get('text_new_payment_method');
        $template->data['text_shipping_method'] = $language->get('text_new_shipping_method');
        $template->data['text_email'] = $language->get('text_new_email');
        $template->data['text_telephone'] = $language->get('text_new_telephone');
        $template->data['text_ip'] = $language->get('text_new_ip');
        $template->data['text_payment_address'] = $language->get('text_new_payment_address');
        $template->data['text_shipping_address'] = $language->get('text_new_shipping_address');
        $template->data['text_product'] = $language->get('text_new_product');
        $template->data['text_model'] = $language->get('text_new_model');
        $template->data['text_quantity'] = $language->get('text_new_quantity');
        $template->data['text_price'] = $language->get('text_new_price');
        $template->data['text_total'] = $language->get('text_new_total');
        $template->data['text_footer'] = $language->get('text_new_footer');
        $template->data['text_powered'] = $language->get('text_new_powered');
        $template->data['text_new_order_status'] = $language->get('text_new_order_status');

        $template->data['logo'] = $this->config->get('config_url') . 'image/' . $this->config->get('config_logo');
        $template->data['store_name'] = $order_info['store_name'];
        $template->data['store_url'] = $order_info['store_url'];
        $template->data['customer_id'] = $order_info['customer_id'];
        $template->data['link'] = $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_id;

        // Order Status
        $template->data['order_status'] = $order_status;

        // Downloads
        $order_download_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_download WHERE order_id = '" . (int)$order_id . "'");
        if ($order_download_query->num_rows) {
            $template->data['download'] = $order_info['store_url'] . 'index.php?route=account/download';
        } else {
            $template->data['download'] = '';
        }

        $template->data['order_id'] = $order_id;
        $template->data['date_added'] = date($language->get('date_format_short'), strtotime($order_info['date_added']));
        $template->data['payment_method'] = $order_info['payment_method'];
        $template->data['shipping_method'] = $order_info['shipping_method'];
        $template->data['email'] = $order_info['email'];
        $template->data['telephone'] = $order_info['telephone'];
        $template->data['ip'] = $order_info['ip'];
        $template->data['comment'] = !empty($comment) ? nl2br($comment) : '';

        if ($order_info['payment_address_format']) {
            $format = $order_info['payment_address_format'];
        } else {
            $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
        }

        $find = array(
            '{firstname}',
            '{lastname}',
            '{company}',
            '{address_1}',
            '{address_2}',
            '{city}',
            '{postcode}',
            '{zone}',
            '{zone_code}',
            '{country}'
        );

        $replace = array(
            'firstname' => $order_info['payment_firstname'],
            'lastname' => $order_info['payment_lastname'],
            'company' => $order_info['payment_company'],
            'address_1' => $order_info['payment_address_1'],
            'address_2' => $order_info['payment_address_2'],
            'city' => $order_info['payment_city'],
            'postcode' => $order_info['payment_postcode'],
            'zone' => $order_info['payment_zone'],
            'zone_code' => $order_info['payment_zone_code'],
            'country' => $order_info['payment_country']
        );

        $template->data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

        if ($order_info['shipping_address_format']) {
            $format = $order_info['shipping_address_format'];
        } else {
            $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
        }

        $find = array(
            '{firstname}',
            '{lastname}',
            '{company}',
            '{address_1}',
            '{address_2}',
            '{city}',
            '{postcode}',
            '{zone}',
            '{zone_code}',
            '{country}'
        );

        $replace = array(
            'firstname' => $order_info['shipping_firstname'],
            'lastname' => $order_info['shipping_lastname'],
            'company' => $order_info['shipping_company'],
            'address_1' => $order_info['shipping_address_1'],
            'address_2' => $order_info['shipping_address_2'],
            'city' => $order_info['shipping_city'],
            'postcode' => $order_info['shipping_postcode'],
            'zone' => $order_info['shipping_zone'],
            'zone_code' => $order_info['shipping_zone_code'],
            'country' => $order_info['shipping_country']
        );

        $template->data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

        // Products
        $template->data['products'] = array();

        foreach ($order_product_query->rows as $product) {
            $option_data = array();

            $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$product['order_product_id'] . "'");

            foreach ($order_option_query->rows as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $value = utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.'));
                }

                $option_data[] = array(
                    'name' => $option['name'],
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                );
            }

            $template->data['products'][] = array(
                'name' => $product['name'],
                'model' => $product['model'],
                'option' => $option_data,
                'quantity' => $product['quantity'],
                'price' => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
                'total' => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value'])
            );
        }

        // Vouchers
        $template->data['vouchers'] = array();

        $order_voucher_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");
        foreach ($order_voucher_query->rows as $voucher) {
            $template->data['vouchers'][] = array(
                'description' => $voucher['description'],
                'amount' => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']),
            );
        }

        // Order Totals
        $order_total_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order ASC");
        $template->data['totals'] = $order_total_query->rows;

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/mail/trustly_order.tpl')) {
            $html = $template->fetch($this->config->get('config_template') . '/template/mail/trustly_order.tpl');
        } else {
            $html = $template->fetch('default/template/mail/trustly_order.tpl');
        }

        $mail = new Mail();
        $mail->protocol = $this->config->get('config_mail_protocol');
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->hostname = $this->config->get('config_smtp_host');
        $mail->username = $this->config->get('config_smtp_username');
        $mail->password = $this->config->get('config_smtp_password');
        $mail->port = $this->config->get('config_smtp_port');
        $mail->timeout = $this->config->get('config_smtp_timeout');
        $mail->setTo($order_info['email']);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender($order_info['store_name']);
        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
        $mail->setHtml($html);
        $mail->send();
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
