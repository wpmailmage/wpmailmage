<?php

namespace EmailWP\Common\Model;

use EmailWP\Common\Properties\Properties;
use EmailWP\Container;
use Exception;

class AutomationWoocommerceCart
{
    protected $_id;
    protected $_session_id;
    protected $_user_id;
    protected $_cart;
    protected $_data;
    protected $_abandoned;
    protected $_created;
    protected $_modified;

    public function __construct($data = null)
    {
        $this->setup_data($data);
    }

    private function setup_data($data)
    {
        if (is_array($data)) {

            $this->_id = isset($data['id']) && intval($data['id']) > 0 ? intval($data['id']) : null;
            $this->_session_id = isset($data['session_id']) ? $data['session_id'] : null;
            $this->_user_id = isset($data['user_id']) ? $data['user_id'] : null;
            $this->set_cart_raw(isset($data['cart']) ? $data['cart'] : null);
            $this->set_data_raw(isset($data['data']) ? $data['data'] : null);
            $this->_abandoned = isset($data['abandoned']) ? $data['abandoned'] : null;
            $this->_created = isset($data['created']) ? $data['created'] : current_time('mysql');
            $this->_modified = isset($data['modified']) ? $data['modified'] : current_time('mysql');
            return;
        } elseif (strlen($data) > 0) {

            /**
             * @var \WPDB $wpdb
             */
            global $wpdb;

            /**
             * @var Properties $properties
             */
            $properties = Container::getInstance()->get('properties');
            if (is_numeric($data)) {
                $record = $wpdb->get_row("SELECT * FROM {$properties->table_automation_woocommerce_carts} WHERE  `user_id`=" . intval($data) . " LIMIT 1", ARRAY_A);
            } else {
                $record = $wpdb->get_row("SELECT * FROM {$properties->table_automation_woocommerce_carts} WHERE `session_id` = '" . strval($data) . "' LIMIT 1", ARRAY_A);
            }

            if (!$record) {
                return $this->setup_data(['session_id' => strval($data)]);
            }

            return $this->setup_data($record);
        }

        throw new Exception("Invalid WC Session");
    }

    public function set_cart_raw($raw)
    {
        $cart = (array)maybe_unserialize($raw);
        $this->_cart = $cart;
    }

    public function set_cart($raw)
    {
        $this->_modified = current_time('mysql');
        $this->set_cart_raw($raw);
    }

    public function set_data_raw($raw)
    {
        $data = (array)maybe_unserialize($raw);
        $this->_data = $data;
    }

    public function set_data($raw)
    {
        $this->_modified = current_time('mysql');
        $this->set_data_raw($raw);
    }

    public function save($force = false)
    {

        if (!$force && WC()->session != null && intval(WC()->session->get('ewp_abandoned_cart')) > 0) {
            return;
        }
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        /**
         * @var Properties $properties
         */
        $properties = Container::getInstance()->get('properties');

        $data = !empty($this->_data) ? serialize($this->_data) : null;
        $cart = !empty($this->_cart) ? serialize($this->_cart) : null;

        $mysql_data = [
            'data' => $data,
            'cart' => $cart,
            'abandoned' => $this->_abandoned,
            'modified' => $this->_modified,
        ];

        if (is_user_logged_in()) {
            $mysql_data['user_id'] = get_current_user_id();
        }

        if (intval($this->_id) > 0) {
            $result = $wpdb->update($properties->table_automation_woocommerce_carts, $mysql_data, ['id' => $this->_id]);
        } else {

            // Dont save unless we have a valid email address
            if (empty($this->get_billing_email())) {
                return false;
            }

            $mysql_data['session_id'] = $this->_session_id;
            $mysql_data['created'] = $this->_created;
            $result = $wpdb->insert($properties->table_automation_woocommerce_carts, $mysql_data);
        }

        return $result;
    }

    public function set_abandoned($is_abandoned = true)
    {
        $prev_is_abandoned = $this->_abandoned;
        if ($is_abandoned) {
            $this->_abandoned = current_time('mysql');
        } else {
            $this->_abandoned = null;
        }

        $this->save();

        if (is_null($prev_is_abandoned) && !$this->customer_has_ordered() && $this->cart_has_items()) {
            do_action('ewp/automation_woocommerce_cart/abandoned', $this);
        }
    }

    /**
     * Check to see if the customer has made an order since the cart was created
     *
     * @return bool
     */
    public function customer_has_ordered()
    {
        $query = new \WC_Order_Query([
            'date_created' => '>=' . $this->get_created(),
            'limit' => -1
        ]);
        $query->set('customer', $this->get_billing_email());

        /**
         * @var \WC_Order[] $orders
         */
        $orders = $query->get_orders();

        // escape if an order has been placed since cart abandoned
        if (!empty($orders)) {
            return true;
        }

        return false;
    }

    public function get_abanoned()
    {
        return $this->_abandoned;
    }

    public function get_created()
    {
        return $this->_created;
    }

    public function get_modified()
    {
        return $this->_modified;
    }

    public function cart_has_items()
    {
        return !empty($this->_cart);
    }

    public function restore_cart()
    {
        WC()->cart->empty_cart();
        wc_clear_notices();

        if (empty($this->_cart)) {
            return;
        }

        foreach ($this->_cart as $cart_item) {

            $variation_data = [];
            $product_id = $cart_item['product_id'];
            $variation_id = $cart_item['variation_id'];
            $quantity = $cart_item['quantity'];

            if (!empty($cart_item['variation'])) {
                foreach ($cart_item['variation']  as $key => $value) {
                    $variation_data[$key] = $value;
                }
            }

            WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_data, $cart_item);
        }
    }

    public function set_user_id($user_id)
    {
        if (intval($this->_id) > 0) {
            /**
             * @var \WPDB $wpdb
             */
            global $wpdb;

            /**
             * @var Properties $properties
             */
            $properties = Container::getInstance()->get('properties');

            $wpdb->update($properties->table_automation_woocommerce_carts, ['user_id' => $user_id], ['id' => $this->_id]);
            $this->_user_id = $user_id;
        }
    }

    public function get_session_id()
    {
        return $this->_session_id;
    }

    public function get_billing_email()
    {
        if (intval($this->_user_id) > 0) {
            $userdata = get_userdata(intval($this->_user_id));
            return $userdata->user_email;
        }
        return $this->_data['billing_email'];
    }

    public function get_billing_first_name()
    {
        return $this->_data['billing_first_name'];
    }

    public function get_billing_last_name()
    {
        return $this->_data['billing_last_name'];
    }

    public function get_formatted_billing_full_name()
    {
        return trim(sprintf(_x('%1$s %2$s', 'full name', 'woocommerce'), $this->get_billing_first_name(), $this->get_billing_last_name()));
    }

    /**
     * Fetch cart items
     *
     * @return array
     */
    public function get_items()
    {
        $items = [];

        foreach ($this->_cart as $item) {
            $product_id = absint($item['product_id']);
            $variation_id = absint($item['variation_id']);
            if ($variation_id > 0) {
                $product = wc_get_product($variation_id);
            } else {
                $product = wc_get_product($product_id);
            }

            if (!$product->is_purchasable()) {
                continue;
            }

            $tmp = $item;
            $tmp['product_id'] = $product_id;
            $tmp['variation_id'] = $variation_id;
            $tmp['product'] = $product;
            $items[] = $tmp;
        }

        return $items;
    }

    public function get_item_ids()
    {
        $output = [];

        $items = $this->get_items();
        if (!empty($items)) {
            foreach ($items as $item) {
                $product_id = absint($item['product_id']);
                $variation_id = absint($item['variation_id']);
                $output[$product_id . '-' . $variation_id] = [$product_id, $variation_id];
            }
        }

        return $output;
    }
}
