<?php

use \Skyroom\Controller\SyncTaskController;

?>

<script type="application/javascript">
    var skyroom_sync_nonce = {
        'start_sync': '<?php echo wp_create_nonce(SyncTaskController::startActionIdentifier) ?>',
        'sync_status': '<?php echo wp_create_nonce(SyncTaskController::statusActionIdentifier) ?>',
    };
</script>

<div class="wrap" id="skyroom_sync">
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
                <button type="button" class="button button-primary" id="synchronize">
                    <?php _e('Perform Synchronization', 'skyroom') ?>
                </button>
                <span class="dashicons dashicons-update skyroom-spinning-dashicon loading-icon"
                      style="display: none;"></span>
            </p>
        </div>
    </div>
</div>
