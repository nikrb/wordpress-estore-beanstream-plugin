<div class="wrap">
    <h2>EStore plugin for Beanstream</h2>
    <form method="post" action="options.php"> 
        <?php @settings_fields('esbs-plugin-settings'); ?>
        <?php @do_settings_fields('esbs-plugin-settings'); ?>

        <table class="form-table">  
            <tr valign="top">
                <th scope="row"><label for="merchant_id">Merchant Id</label></th>
                <td><input type="text" name="merchant_id" id="merchant_id" value="<?php echo get_option('merchant_id'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="api_key">Beanstream API Key</label></th>
                <td><input type="text" name="api_key" id="api_key" value="<?php echo get_option('api_key'); ?>" /></td>
            </tr>
        </table>

        <?php @submit_button(); ?>
    </form>
</div>