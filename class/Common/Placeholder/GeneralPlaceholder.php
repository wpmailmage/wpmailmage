<?php

namespace EmailWP\Common\Placeholder;

use EmailWP\Common\PlaceholderInterface;
use EmailWP\Container;

class GeneralPlaceholder extends AbstractPlaceholder implements PlaceholderInterface
{
    public function get_id()
    {
        return 'general';
    }

    public function get_variables()
    {
        return [
            'posts' => [$this, 'replace_posts'],
            'user_emails' => [$this, 'replace_users'],
            'name' => [$this, 'repalce_name'],
            'description' => [$this, 'replace_description'],
            'button' => [$this, 'replace_button'],
        ];
    }

    public function save_data($data)
    {
        return null;
    }

    public function load_data($data)
    {
        return null;
    }

    public function repalce_name($data, $args = [])
    {
        return get_bloginfo('name');
    }
    public function replace_description($data, $args = [])
    {
        return get_bloginfo('description');
    }

    public function replace_users($data, $args = [])
    {
        $role = isset($args['role']) ? $args['role'] : 'subscriber';
        $user_query = new \WP_User_Query(['role' => $role, 'fields' => 'all', 'number' => -1]);
        return implode(',', wp_list_pluck($user_query->get_results(), 'user_email'));
    }

    public function replace_posts($data, $args = [])
    {
        $limit = isset($args['limit']) ? absint($args['limit']) : 5;
        $post_type = isset($args['post_type']) ? $args['post_type'] : 'post';
        $text_align  = is_rtl() ? 'right' : 'left';

        $posts = new \WP_Query([
            'post_type' => $post_type,
            'posts_per_page' => $limit
        ]);

        if (!$posts->have_posts()) {
            return false;
        }

        ob_start();
?>
        <div>
            <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
                <?php
                while ($posts->have_posts()) {
                    $posts->the_post();
                ?>
                    <tr class="order_item">
                        <td class="td" style="text-align:<?php echo esc_attr($text_align); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </table>
        </div>
<?php
        wp_reset_postdata();

        return ob_get_clean();
    }

    public function replace_button($data, $args = [])
    {
        /**
         * @var ViewManager $view_manager
         */
        $view_manager = Container::getInstance()->get('view_manager');

        ob_start();

        $view_manager->view('emails/elements/button', apply_filters('ewp_email_button_args', $args));

        return ob_get_clean();
    }
}
