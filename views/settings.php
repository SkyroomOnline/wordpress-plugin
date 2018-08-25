<div class="wrap">
    <h1><?php _e('Skyroom Settings', 'skyroom') ?></h1>
    <?php if (isset($success) && $success) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Your changes saved successfully', 'skyroom') ?></p>
        </div>
    <?php endif ?>
    <?php if (isset($error)) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo $error ?></p>
        </div>
    <?php endif ?>
    <div class="card skyroom-logo-card">
        <img src="<?php echo $pluginUrl ?>admin/images/skyroom-logo.png" alt="skyroom">
    </div>
    <form method="post" id="skyroom_config">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="skyroom_site_url"><?php _e('Skyroom service url:', 'skyroom') ?></label>
                </th>
                <td>
                    <input type="text" name="skyroom_site_url" id="skyroom_site_url" class="regular-text"
                           value="<?php echo ($skyroomSiteUrl ? esc_attr($skyroomSiteUrl) : '') ?>"
                           placeholder="<?php esc_attr(printf('e.g: %s', 'https://skyroom.ir')) ?>">
                    <p id="skyroom_site_url_desc">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="skyroom_api_key"><?php _e('Your API key:', 'skyroom') ?></label>
                </th>
                <td>
                    <input type="text" name="skyroom_api_key" id="skyroom_api_key" class="regular-text"
                           value="<?php echo ($skyroomApiKey ? esc_attr($skyroomApiKey) : '') ?>">
                    <p class="description"><?php _e('API key should be provided to you by skyroom support', 'skyroom') ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <p class="submit">
            <button type="submit" name="save" id="save" class="button button-primary" value="save">
                <?php _e('Save Changes', 'skyroom') ?>
            </button>
        </p>
    </form>
</div>
