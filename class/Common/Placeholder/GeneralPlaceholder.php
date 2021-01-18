<?php

namespace EmailWP\Common\Placeholder;

use EmailWP\Common\PlaceholderInterface;

class GeneralPlaceholder extends AbstractPlaceholder implements PlaceholderInterface
{
    public function get_id()
    {
        return 'general';
    }

    public function get_variables()
    {
        return [
            'posts' => [$this, 'replace_posts']
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
}
