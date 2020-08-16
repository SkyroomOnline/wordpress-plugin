<?php


namespace Skyroom\Entity;

/**
 * Class SkyroomWidget
 * @package Skyroom\Entity
 */
class SkyroomWidget extends \WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'SkyroomWidget',
            __('Skyroom wplms widget', 'skyroom'),
            array(
                'description' => __('This widget skyroom for wplms courses and and it does not work in any other plugins', 'skyroom'),
                'classname' => 'skyroom widget',
                'customize_selective_refresh' => true,
            )
        );
    }

    /**
     * Skyroom idget register
     */
    public function register_widget(){
        register_widget(new \Skyroom\Entity\SkyroomWidget());
    }

    /**
     * @param $args
     * @param $instance
     */
    public function widget($args, $instance)
    {
        $title = isset($instance['title']) ? $instance['title'] : '';

        if ( function_exists('bp_course_get_ID') ) {
  
            $title = apply_filters('widget_title', $title, $instance, $this->id_base);
            $product_id = get_post_meta(bp_course_get_ID(), 'vibe_product', true);
            echo $args['before_widget'];
            if (isset($product_id)) {
                $skyroom_id = get_post_meta($product_id, '_skyroom_id', true);
                if (!empty($skyroom_id)) {
                    $user_id = get_current_user_id();
                    $purchased = wc_customer_bought_product(null, $user_id, $product_id);
    
                    echo $args['before_title'] . $title . $args['after_title'];
                    if ($purchased) {
                        echo "<a class='full button' href='" . home_url('redirect-to-room/' . $product_id) . "' target='_blank'>";
                        echo _e('Enter room', 'skyroom');
                        echo "</a>";
                    }
                }
            }
            echo $args['after_widget'];
            
        }
    }

    /**
     * @param $instance
     */
    public function form($instance)
    {
        if (!empty($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('Join to course room', 'skyroom');
        }
        ?>
        <p>
            <labale for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title', 'skyroom'); ?></labale>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo $title; ?>">
        </p>
        <?php
    }

    /**
     * @param $new_instance
     * @param $old_instance
     * @return array
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();

        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';

        return $instance;
    }
}