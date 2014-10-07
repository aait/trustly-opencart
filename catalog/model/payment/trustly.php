<?php
if (!defined('DIR_APPLICATION')) {
    die();
}

class ModelPaymentTrustly extends Model
{
    /**
     * Returns information about Payment Method for the checkout process
     * @param $address
     * @param $total
     * @return array
     */
    public function getMethod($address, $total)
    {
        $this->load->language('payment/trustly');

        $query = $this->db->query(sprintf('SELECT * FROM `' . DB_PREFIX . 'zone_to_geo_zone` WHERE geo_zone_id = %d AND country_id = %d AND (zone_id = %s OR zone_id = 0)',
            $this->db->escape((int)$this->config->get('trustly_geo_zone_id')),
            $this->db->escape((int)$address['country_id']),
            $this->db->escape((int)$address['zone_id'])
        ));

        if ($this->config->get('trustly_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('trustly_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code' => 'trustly',
                'title' => $this->language->get('text_title'),
                'sort_order' => $this->config->get('trustly_sort_order')
            );
        }

        return $method_data;
    }

    /**
     * Add Trustly Order
     * @param $trustly_order_id
     * @param $order_id
     * @param $url
     * @return bool
     */
    public function addTrustlyOrder($order_id, $trustly_order_id, $url)
    {
        $query = sprintf('INSERT INTO `' . DB_PREFIX . 'trustly_orders` (order_id, trustly_order_id, url) VALUES (%d, "%s", "%s");',
            $this->db->escape((int)$order_id),
            $this->trustlyID($trustly_order_id),
            $this->db->escape($url)
        );

        try {
            return $this->db->query($query);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get Trustly Order
     * @param $order_id
     * @return bool|int
     */
    public function getTrustlyOrder($order_id)
    {
        $query = sprintf('SELECT * FROM `' . DB_PREFIX . 'trustly_orders` WHERE order_id=%d;',
            $this->db->escape((int)$order_id)
        );

        $orders = $this->db->query($query);
        if ($orders->num_rows === 0) {
            return false;
        }

        return array_shift($orders->rows);
    }

    /**
     * Get Trustly Order Id by OpenCart Order Id
     * @param $order_id
     * @return bool|int
     */
    public function getTrustlyOrderId($order_id)
    {
        $query = sprintf('SELECT trustly_order_id FROM `' . DB_PREFIX . 'trustly_orders` WHERE order_id=%d;',
            $this->db->escape((int)$order_id)
        );

        $orders = $this->db->query($query);
        if ($orders->num_rows === 0) {
            return false;
        }

        $order = array_shift($orders->rows);
        return $order['trustly_order_id'];
    }

    /**
     * Get OpenCart OrderId by Trustly Order Id
     * @param $trustly_order_id
     * @return bool|int
     */
    public function getOrderIdByTrustlyOrderId($trustly_order_id)
    {
        $query = sprintf('SELECT order_id FROM `' . DB_PREFIX . 'trustly_orders` WHERE trustly_order_id="%s";',
            $this->trustlyID($trustly_order_id)
        );

        $orders = $this->db->query($query);
        if ($orders->num_rows === 0) {
            return false;
        }

        $order = array_shift($orders->rows);
        return (int)$order['order_id'];
    }

    /**
     * Remove Trustly Order
     * @param $trustly_order_id
     * @return mixed
     */
    public function removeTrustlyOrder($trustly_order_id)
    {
        $query = sprintf('DELETE FROM `' . DB_PREFIX . 'trustly_orders` WHERE trustly_order_id="%s";',
            $this->trustlyID($trustly_order_id)
        );

        return $this->db->query($query);
    }

    /**
     * Add Trustly Notification
     * @param $notification_id
     * @param $trustly_order_id
     * @param $method
     * @param $amount
     * @param $currency
     * @param $date
     * @return bool
     */
    public function addTrustlyNotification($notification_id, $trustly_order_id, $method, $amount, $currency, $date)
    {
        $query = sprintf('INSERT INTO `' . DB_PREFIX . 'trustly_notifications` (notification_id, trustly_order_id, method, amount, currency, date) VALUES ("%s", "%s", "%s", %s, %s, "%s");',
            $this->trustlyID($notification_id),
            $this->trustlyID($trustly_order_id),
            $this->db->escape($method),
            $this->nullFloat($amount),
            $this->nullStr($currency),
            $this->db->escape($date)
        );

        try {
            return $this->db->query($query);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get Trustly Notifications
     * @param $trustly_order_id
     * @return array
     */
    public function getTrustlyNotifications($trustly_order_id)
    {
        $query = sprintf('SELECT * FROM `' . DB_PREFIX . 'trustly_notifications` WHERE trustly_order_id="%s";',
            $this->trustlyID($trustly_order_id)
        );

        $notifications = $this->db->query($query);
        if ($notifications->num_rows === 0) {
            return array();
        }

        return $notifications->rows;
    }

    /**
     * Place a (max) 1 minute lock on the order in terms of processing from 
     * trustly. Use this lock to prevent concurrent order updates on this order.
     *
     * @param integer $orderid Opencart orderid for the order to lock
     * @return FALSE/lockid Save the lockid for late lock release. Boolean false means we failed to acquire a lock of the order.
     */
    public function lockOrderForProcessing($order_id) {

        $lock_id = mt_rand(0, 2147483647);
        $query = sprintf('UPDATE `' . DB_PREFIX . 'trustly_orders` SET lock_id=%d, lock_timestamp=NOW() WHERE order_id=%d AND (lock_timestamp IS NULL OR lock_timestamp < NOW() - INTERVAL 1 minute);',
            $this->db->escape($lock_id),
            $this->db->escape($order_id)
        );

        try {
            $this->db->query($query);
        } catch (Exception $e) {
            $this->addLog('Failed to execute query: ' . $query . ': ' . $e->getMessage());
            return false;
        }

        $query = sprintf('SELECT lock_id FROM `' . DB_PREFIX . 'trustly_orders` WHERE order_id=%d;',
            $order_id);
        $lock_rows = $this->db->query($query);
        if ($lock_rows->num_rows === 0) {
            $this->addLog('Cannot find lock when reading back order');
            return false;
        }
        $lock_row = array_shift($lock_rows->rows);
        $verify_lockid = $lock_row['lock_id'];


        if($verify_lockid == $lock_id) {
            return $lock_id;
        }
        return false;
    }

    /**
     * Release a previously acquired lock on the order and allow others to process it.
     *
     * @param integer $order_id Opencart order_id for the order to release lock for
     * @param integer $lock_id Lock identifier. Should have been acquired via
     * lockOrderForProcessing() before
     * @return boolean Revealing wether we held the lock in the first place.
     */
    public function unlockOrderAfterProcessing($order_id, $lock_id) {
        $query = sprintf(
            'UPDATE `' . DB_PREFIX . 'trustly_orders` SET lock_id=NULL , lock_timestamp=NULL WHERE order_id=%d AND lock_id=%d',
            $this->db->escape($order_id),
            $this->db->escape($lock_id)
        );

        try {
            return $this->db->query($query);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Return either a NULL value if the value is not set or not numerical or the value in float form. Suitable as db operations with %s format in printf.
     * @param $v
     * @return string/float
     */
    protected function nullFloat($v) {
        if (is_null($v)) {
            return 'NULL';
        } elseif (is_numeric($v)) {
            return (float)$v;
        } else {
            return 'NULL';
        }
    }

    /**
     * Return either a NULL value if the value is not set or the value in a suitable form for database operations. Suitable as db operations with %s format in printf.
     * @param $v
     * @return string
     */
    protected function nullStr($v) {
        if (is_null($v)) {
            return 'NULL';
        } else {
            return '"' . $this->db->escape($v) . '"';
        }
    }

    /**
     * Check if the input parameter is a representation of a trustly ID, if so return the string, otherwise return NULL
     * @param $id
     * @return string/NULL
     */
    protected function trustlyID($id) {
        if($id === NULL) {
            return NULL;
        }
        $id = (string)$id;
        if(ctype_digit($id)) {
            $len = strlen($id);
            if($len > 5 && $len < 19) {
                return $id;
            }
        }
        return NULL;
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
     * Send Confirmation Mail
     * @param $order_id
     * @param string $comment
     */
    public function sendConfirmationMail($order_id, $comment = '')
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
     * Update Order Status
     * @param $order_id
     * @param $order_status_id
     * @param string $comment
     * @param bool $notify
     * @return bool
     */
    public function setOrderStatus($order_id, $order_status_id, $comment = '', $notify = false) {
        try {
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
        } catch (Exception $e) {
            $this->addLog('Failed to change status or order #' . $order_id . '. Details: ' . $e->getMessage());
            return false;
        }

        if ($notify) {
            $this->sendConfirmationMail($order_id, $comment);
        }

        return true;
    }
}
