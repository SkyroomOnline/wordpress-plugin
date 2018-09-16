jQuery(function ($) {

    // Events menu
    if (pagenow.indexOf('page_skyroom-events') !== -1) {
        $('.wp-list-table .show-details').on('click', function (e) {
            e.preventDefault();
            var data = $(this).data('details');
            var $table = $('<table>').addClass('widefat striped');
            var $tbody = $('<tbody>').appendTo($table);
            $.each(data, function (key, value) {
                var $tr = $('<tr>');
                $tr.append('<th>' + skyroom_l10n[key] + '</th>');
                $tr.append('<td>' + value + '</td>');
                $tr.appendTo($tbody);
            });

            alertify.alert()
                .setting({
                    title: skyroom_l10n.event_details,
                    label: skyroom_l10n.ok,
                    message: $table.get(0).outerHTML,
                    transition: 'zoom'
                }).show();
        });
    }

    /*******************************\
     * WooCommerce specific scripts *
     \*******************************/

    // Show woocommerce metabox general tab for skyroom product type
    $('.options_group.pricing').addClass('show_if_skyroom');
});
