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
}
