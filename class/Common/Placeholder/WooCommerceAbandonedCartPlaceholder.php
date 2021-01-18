<?php

namespace EmailWP\Common\Placeholder;

use EmailWP\Common\Model\AutomationWoocommerceCart;
use EmailWP\Common\PlaceholderInterface;

class WooCommerceAbandonedCartPlaceholder extends AbstractWooCommercePlaceholder implements
    PlaceholderInterface
{

    public function get_id()
    {
        return 'wc_cart';
    }

    public function get_variables()
    {
        return [
            'email' => [$this, 'replace_email'],
            'generate_coupon' => [$this, 'replace_coupon'],
            'first_name' => [$this, 'replace_first_name'],
            'last_name' => [$this, 'replace_last_name'],
            'full_name' => [$this, 'replace_full_name'],
            'view_button' => [$this, 'replace_view_button'],
            'items' => [$this, 'replace_items']
        ];
    }

    /**
     * @param AutomationWoocommerceCart $data
     * @return string
     */
    public function save_data($data)
    {
        return strval($data->get_session_id());
    }

    public function load_data($data)
    {
        return new AutomationWoocommerceCart($data);
    }

    public function replace_view_link($data, $args = [])
    {
        /**
         * @var AutomationWoocommerceCart $cart
         */
        $cart = $data[$this->get_id()];
        return add_query_arg(['ewp_cart' => $cart->get_session_id()], wc_get_cart_url());
    }

    public function replace_view_button($data, $args = [])
    {
        $link = $this->replace_view_link($data, $args);
        $text = isset($args['text']) && !empty($args['text']) ? $args['text'] : 'View cart';
        return '<a href="' . $link . '">' . $text . '</a>';
    }

    public function replace_restore_link($data, $args = [])
    {
        /**
         * @var AutomationWoocommerceCart $cart
         */
        $cart = $data[$this->get_id()];
        return add_query_arg(['ewp_cart' => $cart->get_session_id()], wc_get_cart_url());
    }

    public function replace_items($data, $args = [])
    {
        /**
         * @var AutomationWoocommerceCart $cart
         */
        $cart = $data[$this->get_id()];
        $text_align  = is_rtl() ? 'right' : 'left';
        $image_size = array(32, 32);
        $show_image = true;

        ob_start(); ?>
        <div>
            <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
                <thead>
                    <tr>
                        <th class="td" scope="col" style="text-align:<?php echo esc_attr($text_align); ?>;"><?php esc_html_e('Product', 'woocommerce'); ?></th>
                        <th class="td" scope="col" style="text-align:<?php echo esc_attr($text_align); ?>;"><?php esc_html_e('Quantity', 'woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                    foreach ($cart->get_items() as $item) :
                        $image = '';
                        $product = $item['product'];
                        $image = $product->get_image($image_size);
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
                                <?= wp_kses_post(absint($item['quantity'])); ?>
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
