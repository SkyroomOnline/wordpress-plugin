<?php

use \Skyroom\Controller\MaintenanceController;

?>

<script type="application/javascript">
    var skyroom_sync_nonce = {
        'start_sync': '<?php echo wp_create_nonce(MaintenanceController::startActionIdentifier) ?>',
        'sync_status': '<?php echo wp_create_nonce(MaintenanceController::statusActionIdentifier) ?>',
        'purge_data': '<?php echo wp_create_nonce(MaintenanceController::purgeActionIdentifier) ?>',
    };
</script>

<div class="skyroom-maintenance wp-clearfix">
    <div class="skyroom-sync">
        <h1><?php _e('Synchronization', 'skyroom') ?></h1>
        <div class="card">
            <div class="card-inner">
                <p>
                    <?php _e(
                        "If you have had some data before installing skyroom plugin or something bad happened for plugin while contacting with skyroom server, It's the place to synchronize your data with server.",
                        'skyroom'
                    ) ?>
                </p>
                <p>
                    <?php printf(__('Last Sync: %s', 'skyroom'), ($lastSync ? date_i18n('j F Y', $lastSync) : __('Never', 'skyroom'))) ?>
                </p>
                <p>
                    <button type="button" class="button button-primary synchronize-btn">
                        <?php _e('Perform Synchronization', 'skyroom') ?>
                    </button>
                    <span class="dashicons dashicons-update skyroom-spinning-dashicon loading-icon"
                          style="display: none;"></span>
                </p>
            </div>
        </div>
    </div>

    <div class="skyroom-purge">
        <h1><?php _e('Purge plugin data', 'skyroom') ?></h1>
        <div class="card">
            <div class="card-inner">
                <p>
                    <?php _e(
                        'Clear all skyroom data (user ids, room ids and enrollments) from your wordpress database.',
                        'skyroom'
                    ) ?>
                </p>
                <p>
                    <button type="button" class="button button-primary purge-btn">
                        <?php _e('Purge Data', 'skyroom') ?>
                    </button>
                    <span class="dashicons dashicons-update skyroom-spinning-dashicon loading-icon"
                          style="display: none;"></span>
                    <span class="purge-successful-message" style="display: none;">
                        <?php _e('Data purged successfully.', 'skyroom') ?>
                    </span>
                    <span class="purge-failed-message" style="display: none;">
                        <?php _e('Data purge failed.', 'skyroom') ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>
