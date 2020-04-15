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
                $tr.append('<th>' + skyroom_data[key] + '</th>');
                $tr.append('<td>' + value + '</td>');
                $tr.appendTo($tbody);
            });

            alertify.alert()
                .setting({
                    title: skyroom_data.event_details,
                    label: skyroom_data.ok,
                    message: $table.get(0).outerHTML,
                    transition: 'zoom'
                }).show();
        });
    }

    // Synchronize page
    if (pagenow.indexOf('page_skyroom-sync') !== -1) {
        var $sync = $('#skyroom_sync');
        $sync.find('#synchronize').on('click', function () {
            $(this).prop('disabled', true).next().show();
            $sync.find('.error').remove();

            $.get(
                ajaxurl,
                {
                    action: 'skyroom_sync_start',
                    nonce: skyroom_sync_nonce.start_sync,
                },
                function (response) {
                    $sync.find('#synchronize').prop('disabled', false).next().hide();

                    if (response.success) {
                        startSyncing(response.data);
                    } else {
                        $('#skyroom_sync').find('.card-inner').append(
                            '<p class="error"><span class="dashicons dashicons-dismiss skyroom-sync-error-icon"></span> ' + response.data + '</p>'
                        );
                    }
                }
            )
        });

        function startSyncing(initialData) {
            $sync.find('.card-inner').slideUp(200, function () {
                $(this).empty().height(0).show();

                // Show initial sync data
                showSyncStatus(initialData);

                // Trigger checking sync status regularly
                triggerCheckingSyncStatus();
            });
        }

        function triggerCheckingSyncStatus() {
            $.get(
                ajaxurl,
                {
                    action: 'skyroom_sync_status',
                    nonce: skyroom_sync_nonce.sync_status,
                },
                function (data) {
                    showSyncStatus(data);

                    // Trigger next status request
                    if (data.status === 'busy') {
                        setTimeout(triggerCheckingSyncStatus, 1000);
                    }
                }
            )
        }

        function showSyncStatus(data) {
            var $ul = $('<ul class="skyroom-sync-status-list" />');
            $.each(data.messages, function (index, item) {
                var clazz = '';
                switch (item.type) {
                    case 'error':
                        clazz = 'dismiss';
                        break;

                    case 'done':
                        clazz = 'yes';
                        break;

                    case 'pending':
                        clazz = 'update skyroom-spinning-dashicon';
                        break;
                }
                $ul.append('<li><span class="dashicons dashicons-' + clazz + '"></span> ' + item.message + '</li>');
            });

            var $cardInner = $sync.find('.card-inner');
            $cardInner.html($ul);
            $cardInner.animate({height: ($ul.height() + 16) + 'px'}, 200);
        }
    }

    /*******************************\
     * WooCommerce specific scripts *
     \*******************************/

    // Show woocommerce metabox general tab for skyroom product type
    $('.options_group.pricing').addClass('show_if_skyroom');
});
