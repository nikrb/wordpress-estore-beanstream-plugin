<?php
/*
Plugin Name: Basic plugin
Description: basic plugin
*/
defined( 'ABSPATH' ) or die( 'This is a wordpress plugin!' );

require ABSPATH.'vendor/autoload.php';

class EStoreBeanstream {
    private $plugin_path;
    private $plugin_url;
    
    // beanstream bits, get from wp options
    private $beanstream_message = "";
    private $api_version = 'v1'; //default
    private $platform = 'www'; //default

    private $card_name = "";
    private $card_number = "";
    private $expiry_month = "";
    private $expiry_year = "";
    private $card_cvc = "";
    private $cart_total = "";
    
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
    
    function activate( $network_wide ) {
    }
    
    function deactivate( $network_wide ) {
    }
    
    function init() {
        // FIXME: add nonce
        $this->beanstream_message = "";
        if( isset( $_POST['esbs-card-submit'])){
            error_log( "form submitted");
            $this->beanstream_message = "Approved - thank-you";
            $this->card_name = isset(  $_POST['esbs-card-name']) ?  $_POST['esbs-card-name'] : "";
            $this->card_number = isset( $_POST['esbs-card-number']) ? $_POST['esbs-card-number'] : "";
            $bits = explode( "/", $_POST['esbs-card-expiry']);
            $this->expiry_month = "00";
            $this->expiry_year = "00";
            if( count( $bits) == 2){
                $this->expiry_month = $bits[0];
                $this->expiry_year = $bits[1];
                if( strlen( $this->expiry_month) == 1) $this->expiry_month = "0".$this->expiry_month;
                if( strlen( $this->expiry_year) == 1) $this->expiry_year = "0".$this->expiry_year;
            }
            $this->card_cvc = isset( $_POST['esbs-card-cvc']) ? $_POST['esbs-card-cvc'] : "";
            $this->cart_total = isset( $_POST['esbs-cart-total']) ? $_POST['esbs-cart-total'] : "";
            
            if( $this->card_name == "" || $this->card_number == "" ||
                    strlen( $this->expiry_month) != 2 || strlen( $this->expiry_year) != 2 ||
                    $this->card_cvc == "" || $this->cart_total == "") {
                $this->beanstream_message = "Invalid Details";
                error_log( "invalid detail: name[".$this->card_name."] number[".$this->card_number."] ".
                            "month[".$this->expiry_month."] year[".$this->expiry_year."] ".
                            "cvc[".$this->card_cvc."] amount[".$this->cart_total."]");
                error_log( "post data:".print_r( $_POST, true));
            } else {
                $merchant_id = get_option('merchant_id'); // '300202958'; //INSERT MERCHANT ID (must be a 9 digit string)
                $api_key = get_option('api_key'); // '8dD10Cdd001241A9BCE728a8cc86A5F1'; //INSERT API ACCESS PASSCODE
                $beanstream = new \Beanstream\Gateway( $merchant_id, $api_key, $this->platform, $this->api_version);
                
                $payment_data = array(
                        'order_number' => uniqid(), // 'a1b2c6', // 'a1b2c3',
                        'amount' => $this->cart_total, // 1.00,
                        'payment_method' => 'card',
                        'card' => array(
                            'name' => $this->card_name, // 'Mr. Card Testerson',
                            'number' => $this->card_number, // '4030000010001234',
                            'expiry_month' => $this->expiry_month, // '07',
                            'expiry_year' => $this->expiry_year, // '22',
                            'cvd' => $this->card_cvc // '123'
                        )
                );
                $complete = TRUE; //set to FALSE for PA
                
                try {
                	$result = $beanstream->payments()->makeCardPayment($payment_data, $complete);
                	if( isset( $result['approved']) && $result['approved'] == 1){
                	    $this->beanstream_message = "Thank-you";
                	    error_log( "beanstream payment approved transaction id".$result['id']);
                	} else {
                	    $this->beanstream_message = "Failed:".$result['message'];
                	    error_log( "beanstream payment failed:".print_r( $result, true));
                	}
                } catch (\Beanstream\Exception $e) {
                    $error_code = $e->getErrorMessage();
                    $this->beanstream_message = "Error:".$error_code;
                    error_log( "beanstream payment failed:". print_r( $e, true));
                }
            }
        } else {
            error_log( "form not submitted");
            // FIXME: test only
            $this->card_name = "Mr. Card Testerson";
            $this->card_number = '4030000010001234';
            $this->expiry_month = '07';
            $this->expiry_year = '22';
            $this->card_cvc = '123';
        }
    }
    
    function addBeanstreamButton( $parm){
        $show_div = ($this->beanstream_message == "")?"none":"block";
        $html_form = 
        '<div>
            <button type="button" onclick="ESBS.toggleBeanstreamForm();">
                <img src="'.$this->plugin_url.'img/Beanstream-logo.png" 
                                class="eStore_paypal_checkout_button" >
            </button>
        </div>
        <div style="display:'.$show_div.'" >
            <span>
                '. $this->beanstream_message .'
            <span>
        </div>
        <div id="beanstream_form" style="display:'. $show_div.'" >
            <form method="post" action="" style="display:inline">
            <table style="width: 100%;">
                <tbody>
                    <tr class="eStore_cart_item_value">
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            Name on Card
                        </td>
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                                <input type="text" name="esbs-card-name" class="eStore_cart_item_qty" 
                                    value="'.$this->card_name.'" />
                        </td>
                    </tr>
                    <tr class="eStore_cart_item_value">
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            Card Type
                        </td>
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                                <select name="esbs-card-type">
                                    <option value="Visa" selected>Visa</option> 
                                    <option value="MasterCard">MasterCard</option>
                                    <option value="Discover">Discover</option>
                                    <option value="American Express">American Express</option>
                                </select>
                        </td>
                    </tr>
                    <tr class="eStore_cart_item_value">
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            Card Number
                        </td>
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                                <input type="text" name="esbs-card-number" class="eStore_cart_item_qty" 
                                    value="'.$this->card_number.'" />
                        </td>
                    </tr>
                    <tr class="eStore_cart_item_value">
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            Card Expiry (mm/dd)
                        </td>
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                                <input type="text" name="esbs-card-expiry" class="eStore_cart_item_qty required" 
                                    value="'.$this->expiry_month.'/'.$this->expiry_year.'" />
                        </td>
                    </tr>
                    <tr class="eStore_cart_item_value">
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                            Card CVC
                        </td>
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                                <input type="text" name="esbs-card-cvc" class="eStore_cart_item_qty required" 
                                    value="'.$this->card_cvc.'" />
                        </td>
                    </tr> 
                    <tr class="eStore_cart_item_value">
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;"></td>
                        <td class="eStore_cart_item_name_value" style="overflow: hidden;">
                                <input type="hidden" name="esbs-cart-total" id="esbs-cart-total" value="'.$this->cart_total.'" />
                                <input type="submit" name="esbs-card-submit" class="eStore_paypal_checkout_button" value="Submit" />
                        </td>
                    </tr>
                </tbody>
            </table>
            </form>
        </div>';
    
        $content .= '<div>'.$html_form.'</div>';
        return $content;
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
