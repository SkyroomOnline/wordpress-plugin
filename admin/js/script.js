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

    if (pagenow.indexOf('page_skyroom-users') !== -1) {
        $('.wp-list-table .show-details').on('click', function (e) {
            e.preventDefault();
            var data = $(this).data('user');
            var product_id = $(this).data('product');

            var $table = $('<table>').addClass('widefat striped');
            var $tbody = $('<tbody>').appendTo($table);
            var $tr = $('<tr>');
            $tr.append('<td style="width: 50%">دسترسی</td>');
            $tr.append('<td style="width: 50%"><select id="access-level"></select></td>');
            $tr.appendTo($tbody);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'skyroom_get_user_data',
                    nonce: skyroom_user_data.get_data,
                    user_id: data,
                    product_id: product_id
                },
                success: function (result) {
                    $("#access-level").html(result.data);
                }
            });

            alertify.confirm($table.get(0).outerHTML, function () {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'skyroom_set_user_data',
                        nonce: skyroom_user_data.set_data,
                        user_id: data,
                        product_id: product_id,
                        access_level: $('#access-level').val(),
                        access: data,
                    },
                    success: function (result) {
                        if (result.success) {
                            alertify.success(result.data.message);
                        } else {
                            alertify.error(result.data.message);
                        }
                    },
                    error: function (e) {
                        alertify.error('خطایی در سایت شما رخ داده، لطفا مجددا امتحان کنید.');
                    }
                });
            }).set({
                title: skyroom_data.user_access,
                labels: {
                    ok: skyroom_data.save,
                    cancel: skyroom_data.cancel
                },
                padding: false
            }).show();
        });
    }

    // Synchronize actions
    if (pagenow.indexOf('page_skyroom-maintenance') !== -1) {
        var $maintenance = $('.skyroom-maintenance');
        $maintenance.find('.synchronize-btn').on('click', function () {
            $(this).prop('disabled', true).next().show();
            $maintenance.find('.error').remove();

            $.get(
                ajaxurl,
                {
                    action: 'skyroom_sync_start',
                    nonce: skyroom_sync_nonce.start_sync,
                },
                function (response) {
                    $maintenance.find('.synchronize-btn').prop('disabled', false).next().hide();

                    if (response.success) {
                        startSyncing(response.data);
                    } else {
                        $('#skyroom_sync').find('.card-inner').append(
                            '<p class="error"><span class="dashicons dashicons-dismiss skyroom-sync-error-icon"></span> ' + response.data + '</p>'
                        );
                    }
                }
            );

            function startSyncing(initialData) {
                $maintenance.find('.skyroom-sync .card-inner').slideUp(200, function () {
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

                var $cardInner = $maintenance.find('.skyroom-sync .card-inner');
                $cardInner.html($ul);
                $cardInner.animate({height: $ul.height() + 'px'}, 200);
            }
        });

        $maintenance.find('.purge-btn').on('click', function () {
            var agree = confirm(skyroom_data.purge_data_confirm);
            if (agree) {
                var $this = $(this);
                $this.prop('disabled', true).next().show();
                $this.next().next().hide().next().hide();
                $.get(
                    ajaxurl,
                    {
                        action: 'skyroom_purge_data',
                        nonce: skyroom_sync_nonce.purge_data,
                    },
                    function (response) {
                        $this.prop('disabled', false).next().hide();
                        if (response.success) {
                            $this.next().next().show();
                        } else {
                            $this.next().next().next().show();
                        }
                    }
                );
            }
        });
    }

    /*******************************\
     * WooCommerce specific scripts *
     \*******************************/

    // Show woocommerce metabox general tab for skyroom product type
    $('.options_group.pricing').addClass('show_if_skyroom');
});
