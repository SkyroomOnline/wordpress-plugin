<div class="wrap">
    <h1><?php _e('User', 'skyroom') ?></h1>
    <form id="users-form" method="post" action="<?php menu_page_url('skyroom-users') ?>">
        <input type="text" name="name" id="name">

        <button type="submit" name="save" id="save" class="button button-primary" value="save">
            <?php _e('Save Changes', 'skyroom') ?>
        </button>

    </form>
</div>
