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

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('trustly_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

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
        $query = sprintf('INSERT INTO `' . DB_PREFIX . 'trustly_orders` (order_id, trustly_order_id, url) VALUES (%d, %d, "%s");',
            $this->db->escape((int)$order_id),
            $this->db->escape($trustly_order_id),
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
        $query = sprintf("SELECT * FROM " . DB_PREFIX . "trustly_orders WHERE order_id=%d;",
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
        $query = sprintf("SELECT trustly_order_id FROM " . DB_PREFIX . "trustly_orders WHERE order_id=%d;",
            $this->db->escape((int)$order_id)
        );

        $orders = $this->db->query($query);
        if ($orders->num_rows === 0) {
            return false;
        }

        $order = array_shift($orders->rows);
        return (int)$order['trustly_order_id'];
    }

    /**
     * Get OpenCart OrderId by Trustly Order Id
     * @param $trustly_order_id
     * @return bool|int
     */
    public function getOrderIdByTrustlyOrderId($trustly_order_id)
    {
        $query = sprintf("SELECT order_id FROM " . DB_PREFIX . "trustly_orders WHERE trustly_order_id=%d;",
            $this->db->escape((int)$trustly_order_id)
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
        $query = sprintf("DELETE FROM " . DB_PREFIX . "trustly_orders WHERE trustly_order_id=%d;",
            $this->db->escape((int)$trustly_order_id)
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
        $query = sprintf('INSERT INTO `' . DB_PREFIX . 'trustly_notifications` (notification_id, trustly_order_id, method, amount, currency, date) VALUES (%d, %d, "%s", %s, %s, "%s");',
            $this->db->escape($notification_id),
            $this->db->escape($trustly_order_id),
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
        $query = sprintf("SELECT * FROM " . DB_PREFIX . "trustly_notifications WHERE trustly_order_id=%d;",
            $this->db->escape((int)$trustly_order_id)
        );

        $notifications = $this->db->query($query);
        if ($notifications->num_rows === 0) {
            return array();
        }

        return $notifications->rows;
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
}
