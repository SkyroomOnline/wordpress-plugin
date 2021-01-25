<?php

use \Skyroom\Controller\UserController;

?>

<script type="application/javascript">
    var skyroom_user_data = {
        'set_data': '<?php echo wp_create_nonce(UserController::setUserData) ?>',
        'get_data': '<?php echo wp_create_nonce(UserController::getUserData) ?>',
    };
</script>
<div class="wrap">
    <h1><?php _e('Users', 'skyroom') ?></h1>
    <form id="users-form" method="get">
        <?php $table->display() ?>
    </form>
</div>
