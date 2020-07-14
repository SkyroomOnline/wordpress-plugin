<div id="skyroom_data" class="panel woocommerce_options_panel">
    <div class="options_group">
        <?php

        woocommerce_wp_text_input([
            'id' => '_skyroom_name',
            'label' => __('Room Name', 'skyroom'),
            'value' => $name,
            'desc_tip' => true,
            'custom_attributes' => array( 'required' => 'required' ),
            'class' => 'english_input',
            'description' => __('Contains of only latin letters and -_ characters', 'skyroom'),
        ]);

        woocommerce_wp_text_input([
            'id' => '_skyroom_title',
            'label' => __('Room Title', 'skyroom'),
            'value' => $title,
            'desc_tip' => true,
            'custom_attributes' => array( 'required' => 'required' ),
            'description' => __('Title of counterpart room on skyroom', 'skyroom'),
        ]);

        woocommerce_wp_text_input([
            'id' => '_skyroom_capacity',
            'label' => __('Room Capacity', 'skyroom'),
            'type' => 'number',
            'custom_attributes' => array( 'required' => 'required' , 'min' => 1),
            'value' => $capacity,
        ]);
        ?>
    </div>
</div>