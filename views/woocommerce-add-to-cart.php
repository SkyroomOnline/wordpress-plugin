<?php

defined('WPINC') || die;

if (!$product->is_purchasable()) {
    return;
}

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
    <!-- TODO show redirect to room button -->
<?php else : ?>
    <?php do_action('skyroom_before_capacity_full') ?>

    <span class="capacity_full">
        <?php echo esc_html(apply_filters('skyroom_capacity_full_text', __('Capacity is full', 'skyroom'))) ?>
    </span>

    <?php do_action('skyroom_after_capacity_full') ?>
<?php endif ?>
