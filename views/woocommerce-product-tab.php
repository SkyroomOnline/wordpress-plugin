<div id="skyroom_data" class="panel woocommerce_options_panel">
    <div class="options_group">
        <?php

        woocommerce_wp_text_input([
            'id' => '_skyroom_name',
            'label' => __('Room Name', 'skyroom'),
            'value' => $name,
            'desc_tip' => true,
            'description' => __('Contains of only latin letters and -_ characters', 'skyroom'),
        ]);

        woocommerce_wp_text_input([
            'id' => '_skyroom_title',
            'label' => __('Room Title', 'skyroom'),
            'value' => $title,
            'desc_tip' => true,
            'description' => __('Title of counterpart room on skyroom', 'skyroom'),
        ]);

        woocommerce_wp_text_input([
            'id' => '_skyroom_capacity',
            'label' => __('Room Capacity', 'skyroom'),
            'value' => $capacity,
        ]);
        ?>
    </div>
</div>