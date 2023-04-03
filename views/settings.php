<div class="wrap">
    <h1><?php _e('Skyroom Settings', 'skyroom') ?></h1>
    <?php if (!empty($success)) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Your changes saved successfully', 'skyroom') ?></p>
        </div>
    <?php endif ?>
    <?php if (!empty($error)) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo $error ?></p>
        </div>
    <?php endif ?>
    <div class="card skyroom-logo-card">
        <img src="<?php echo $this->pluginUrl ?>admin/images/skyroom-header.jpg" alt="skyroom">

        <form method="post" id="skyroom_config">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="skyroom_site_url"><?php _e('Skyroom service url:', 'skyroom') ?></label>
                    </th>
                    <td>
                        <input type="text" name="skyroom_site_url" id="skyroom_site_url" class="regular-text ltr"
                               value="<?php echo ($skyroomSiteUrl ? esc_attr($skyroomSiteUrl) : 'https://skyroom.online') ?>"
                               placeholder="<?php echo esc_attr(sprintf(__('e.g: %s', 'skyroom'), 'https://skyroom.online')) ?>">
                        <p id="skyroom_site_url_desc">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="skyroom_api_key"><?php _e('Your API key:', 'skyroom') ?></label>
                    </th>
                    <td>
                        <input type="text" name="skyroom_api_key" id="skyroom_api_key" class="regular-text ltr"
                               value="<?php echo ($skyroomApiKey ? esc_attr($skyroomApiKey) : '') ?>">
                        <p class="description"><?php _e('API key should be provided to you by skyroom support', 'skyroom') ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="skyroom_site_url"><?php _e('Room entry link expiration time :', 'skyroom') ?></label>
                    </th>
                    <td>
                        <input type="text" name="skyroom_link_ttl" id="skyroom_link_ttl" class="regular-text ltr"
                               value="<?php echo ($skyroomLinkTtl ? esc_attr($skyroomLinkTtl) : '60') ?>"
                               placeholder="<?php echo esc_attr(sprintf(__('e.g: %s', 'skyroom'), '60')) ?>">
                        <select name="skyroom_link_ttl_unit" id="skyroom_link_ttl_unit">
                            <option value="sec"<?php echo $skyroomLinkTtlUnit === 'sec' ? ' selected' : '' ?>>
                                <?php _e('Second', 'skyroom') ?>
                            </option>
                            <option value="min"<?php echo $skyroomLinkTtlUnit === 'min' ? ' selected' : '' ?>>
                                <?php _e('Minute', 'skyroom') ?>
                            </option>
                        </select>
                        <p id="skyroom_link_ttl_desc">
                            <?php _e('ttl or Time To Live is the validity period of the link. After this period, the generated link is not valid and if used, the user will encounter an error invalidating the link.', 'skyroom') ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="skyroom_integrated_plugin"><?php _e('Plugin you want to integrate skyroom with:', 'skyroom') ?></label>
                    </th>
                    <td>
                        <select name="skyroom_integrated_plugin" id="skyroom_integrated_plugin">
                            <option value="woocommerce"<?php echo $skyroomIntegratedPlugin === 'wocommerce' ? ' selected' : '' ?>>
                                <?php _e('WooCommerce', 'skyroom') ?>
                            </option>
                        </select>
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

</div>
