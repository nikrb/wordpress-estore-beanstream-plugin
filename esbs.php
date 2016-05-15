<?PHP
/*
Plugin Name: Basic plugin
Description: basic plugin
*/
defined( 'ABSPATH' ) or die( 'This is a wordpress plugin!' );

class EStoreBeanstream {
    private $plugin_path;
    private $plugin_url;
    function __construct() 
    {	
        // Set up default vars
        $this->plugin_path = plugin_dir_path( __FILE__ ); // has trailing /
        $this->plugin_url = plugin_dir_url( __FILE__ );
        // Set up activation hooks
        register_activation_hook( __FILE__, array(&$this, 'activate') );
        register_deactivation_hook( __FILE__, array(&$this, 'deactivate') );
        // Set up l10n
        // load_plugin_textdomain( 'plugin-name-locale', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

        add_action( 'wp_enqueue_scripts', array( $this, 'queScripts'));
        
        add_action('admin_init', array( $this, 'adminInit'));
        add_action('admin_menu', array( $this, 'addMenu'));
        
        // Add your own hooks/filters
        add_action( 'init', array( $this, 'init') );
        add_filter( 'eStore_below_cart_checkout_filter', array( $this, 'addBeanstreamButton'));
    }
    
    function adminInit(){
        register_setting('esbs-plugin-settings', 'merchant_id');
        register_setting('esbs-plugin-settings', 'api_key');
    }
    function addMenu(){
        add_options_page('ESBS settings', 
                        'ESBS menu', 
                        'manage_options', 
                        'esbs-plugin-settings', 
                        array( $this, 'pluginSettinsPage'));
    }
    
    function pluginSettinsPage(){
        if(!current_user_can('manage_options')){
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
    
        // Render the settings template
        include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
    }
    
    function queScripts(){
        wp_enqueue_script( "esbs-beanstream-js", $this->plugin_url."js/esbsBeanstream.js", array( 'jquery') );
    }
    
    function addBeanstreamButton( $parm){
        $html_form = 
        '<div>
            <button type="button" onclick="ESBS.toggleBeanstreamForm();">
                <img src="'.$this->plugin_url.'img/Beanstream-logo.png" 
                                class="eStore_paypal_checkout_button" >
            </button>
        </div>
        <div id="beanstream_form" style="display:none" >
            <table style="width: 100%;">
                <tbody>
                    <tr class="eStore_cart_item_value">
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            Card Type
                        </td>
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            <form method="post" action="" style="display:inline">
                                <select name="esbs-card-type">
                                    <option value="Visa" selected>Visa</option> 
                                    <option value="MasterCard">MasterCard</option>
                                    <option value="Discover">Discover</option>
                                    <option value="American Express">American Express</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <tr class="eStore_cart_item_value">
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            Card Number
                        </td>
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            <form method="post" action="" style="display:inline">
                                <input type="text" name="esbs-card-number" class="eStore_cart_item_qty" />
                            </form>
                        </td>
                    </tr>
                    <tr class="eStore_cart_item_value">
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            Card Expiry (mm/dd)
                        </td>
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            <form method="post" action="" style="display:inline">
                                <input type="text" name="esbs-card-number" class="eStore_cart_item_qty required" />
                            </form>
                        </td>
                    </tr>
                    <tr class="eStore_cart_item_value">
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            Card CVC
                        </td>
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            <form method="post" action="" style="display:inline">
                                <input type="text" name="esbs-card-cvc" class="eStore_cart_item_qty required" />
                            </form>
                        </td>
                    </tr>
                    <tr class="eStore_cart_item_value">
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            <form method="post" action="" style="display:inline">
                                <input type="submit" name="esbs-card-cvc" class="eStore_paypal_checkout_button" value="Submit" />
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>';
    
        $content .= '<div>'.$html_form.'</div>';
        return $content;
    }
    
    function activate( $network_wide ) {
    }
    
    function deactivate( $network_wide ) {
    }
    
    function init() {
    }
}
$esbs_beanstream = new EStoreBeanstream();
if(isset( $esbs_beanstream)){
    // Add the settings link to the plugins page
    function plugin_settings_link( $links){ 
        $settings_link = '<a href="options-general.php?page=esbs-plugin-settings">Settings</a>'; 
        array_unshift($links, $settings_link); 
        return $links; 
    }

    $plugin = plugin_basename(__FILE__); 
    add_filter("plugin_action_links_$plugin", 'plugin_settings_link');
}
