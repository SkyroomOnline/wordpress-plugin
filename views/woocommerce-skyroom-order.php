<section class="wocommerce-order-skyroom">
    <h2 class="woocommerce-order-downloads__title"><?php esc_html_e('Skyroom', 'woocommerce'); ?></h2>
    <table class="woocommerce-table woocommerce-table--order-skyroom shop_table shop_table_responsive order_details">
        <thead>
        <tr>
            <?php foreach ($columns as $columnId => $columnName) : ?>
                <th class="<?php echo esc_attr($columnId); ?>">
                    <span class="nobr"><?php echo esc_html($columnName); ?></span></th>
            <?php endforeach; ?>
        </tr>
        </thead>

        <?php foreach ($products as $product) : ?>
            <tr>
                <?php foreach ($columns as $columnId => $columnName) : ?>
                    <td class="<?php echo esc_attr($columnId); ?>" data-title="<?php echo esc_attr($columnName); ?>">
                        <?php
                        switch ($columnId) {
                            case 'skyroom-product':
                                echo '<a href="'.esc_url($product->get_permalink()).'">';
                                echo esc_html($product->get_title());
                                echo '</a>';
                                break;
                            case 'skyroom-enter':
                                echo '<a href="/go.skyroom.php?room='.$product->get_skyroom_id()
                                    .'" class="button alt woocommerce-MyAccount-skyroom-enter">';
                                echo esc_html__('Enter room', 'skyroom');
                                echo '</a>';
                                break;
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
</section>