<?php

defined('WPINC') || die;

if (!$product->is_purchasable()) {
    return;
}

$purchased = wc_customer_bought_product(null, $user_id, $product->get_id());

if ($product->is_in_stock() && !$purchased) : ?>

    <?php do_action('woocommerce_before_add_to_cart_form'); ?>

    <form class="cart"
          action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action',
              $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>
        <?php do_action('woocommerce_before_add_to_cart_button'); ?>

        <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>"
                class="single_add_to_cart_button button alt"><?php echo esc_html($product->single_add_to_cart_text()); ?></button>

        <?php do_action('woocommerce_after_add_to_cart_button'); ?>
    </form>

    <?php do_action('woocommerce_after_add_to_cart_form'); ?>

<?php elseif ($purchased) : ?>
    <?php do_action('skyroom_before_enter_room_button') ?>
    <a href="<?php echo home_url('redirect-to-room/' . $product->get_id()) ?>" class="button alt">
        <?php _e('Enter room', 'skyroom') ?>
    </a>
    <?php do_action('skyroom_after_enter_room_button') ?>
<?php else : ?>
    <?php do_action('skyroom_before_capacity_full') ?>

    <span class="capacity_full">
        <?php echo esc_html(apply_filters('skyroom_capacity_full_text', __('Capacity is full', 'skyroom'))) ?>
    </span>

    <?php do_action('skyroom_after_capacity_full') ?>
<?php endif ?>
