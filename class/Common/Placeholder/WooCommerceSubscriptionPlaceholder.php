<?php

namespace EmailWP\Common\Placeholder;

use EmailWP\Common\PlaceholderInterface;

class WooCommerceSubscriptionPlaceholder extends AbstractPlaceholder implements
    PlaceholderInterface
{

    public function get_id()
    {
        return 'wc_subscription';
    }

    public function get_variables()
    {
        return [
            'end_date' => [$this, 'replace_end_date']
        ];
    }

    public function save_data($data)
    {
        return intval($data);
    }

    public function load_data($data)
    {
        return wcs_get_subscription($data);
    }

    public function get_items()
    {
        $subscriptions = wcs_get_subscriptions(['numberposts' => 200]);
        return array_reduce($subscriptions, function ($carry, $item) {
            $carry[] = ['value' => $item->id, 'label' => 'Subscription #' . $item->id];
            return $carry;
        }, []);
    }

    /**
     * @param array $data
     * @param array $args
     * @return string
     */
    public function replace_end_date($data, $args = [])
    {
        $fallback = isset($args['fallback']) ? $args['fallback'] : '';

        /**
         * @var \WC_Subscription $subscription
         */
        $subscription = $data[$this->get_id()];

        $end_date = $subscription->get_date('end');
        if ($this->is_valid_date($end_date)) {
            return $end_date;
        }

        return $fallback;
    }

    private function is_valid_date($date)
    {

        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
            return true;
        }

        return false;
    }
}
