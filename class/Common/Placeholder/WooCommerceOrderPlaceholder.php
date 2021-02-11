<?php

namespace EmailWP\Common\Placeholder;

use EmailWP\Common\PlaceholderInterface;
use EmailWP\Container;

class WooCommerceOrderPlaceholder extends AbstractWooCommercePlaceholder implements
    PlaceholderInterface
{

    public function get_id()
    {
        return 'wc_order';
    }

    public function get_variables()
    {
        return [
            'id' => [$this, 'replace_id'],
            'email' => [$this, 'replace_email'],
            'items' => [$this, 'replace_items'],
            'generate_coupon' => [$this, 'replace_coupon'],
            'first_name' => [$this, 'replace_first_name'],
            'last_name' => [$this, 'replace_last_name'],
            'full_name' => [$this, 'replace_full_name'],
        ];
    }

    public function save_data($data)
    {
        return intval($data);
    }

    public function load_data($data)
    {
        return wc_get_order($data);
    }

    public function get_items()
    {
        $orders = wc_get_orders(['numberposts' => 200]);
        // $query = new \WP_Query(['post_type' => 'shop_order', 'posts_per_page' => 200]);
        return array_reduce($orders, function ($carry, $item) {
            $carry[] = ['value' => $item->id, 'label' => 'Order #' . $item->id];
            return $carry;
        }, []);
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_id($data, $args = [])
    {
        /**
         * @var \WC_Order $data
         */
        $order = $data[$this->get_id()];

        return $order->get_id();
    }



    /**
     * @param array $data
     * @return integer
     */
    public function replace_items($data, $args = [])
    {
        /**
         * @var \WC_Order $data
         */
        $order = $data[$this->get_id()];
        $template = isset($args['template']) ? $args['template'] : 'items';
        $review_url = isset($args['review_url']) ? $args['review_url'] : '';

        $text_align  = is_rtl() ? 'right' : 'left';
        $image_size = array(32, 32);
        $show_image = true;

        ob_start(); ?>
        <div style="margin-bottom: 40px;">
            <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
                <thead>
                    <tr>
                        <th class="td" scope="col" style="text-align:<?php echo esc_attr($text_align); ?>;"><?php esc_html_e('Product', 'woocommerce'); ?></th>
                        <th class="td" scope="col" style="text-align:<?php echo esc_attr($text_align); ?>;"><?php
                                                                                                            if ($template === 'review') {
                                                                                                                esc_attr_e('Review', 'woocommerce');
                                                                                                            } else {
                                                                                                                esc_attr_e('Quantity', 'woocommerce');
                                                                                                            } ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order->get_items() as $item) :

                        if (!apply_filters('woocommerce_order_item_visible', true, $item)) {
                            continue;
                        }

                        /**
                         * @var \WC_Product $product
                         */
                        $product = $item->get_product();
                        $link = '';
                        $image = '';

                        if (is_object($product)) {
                            $link = $product->get_permalink() . $review_url;
                            $image = $product->get_image($image_size);
                        }
                    ?>
                        <tr class="order_item">
                            <td class="td" style="text-align:<?php echo esc_attr($text_align); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                                <?php
                                // Show title/image etc.
                                if ($show_image) {
                                    echo wp_kses_post($image);
                                }

                                // Product name.
                                echo wp_kses_post($product->get_name());
                                ?>
                            </td>
                            <td class="td" style="text-align:<?php echo esc_attr($text_align); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
                                <?php if ($template === 'review') : ?>
                                    <a href="<?= esc_attr($link); ?>">Leave Review</a>
                                <?php else :
                                    echo $item->get_quantity();

                                endif; ?>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php
        return ob_get_clean();
    }
}
