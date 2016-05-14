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
        
        // Add your own hooks/filters
        add_action( 'init', array(&$this, 'init') );
        add_filter( 'eStore_below_cart_checkout_filter', array( &$this, 'addBeanstreamButton'));
    }
    
    function addBeanstreamButton( $parm){
        $button_input_data = '<input type="image" src="'.
                    $this->plugin_url.'img/Beanstream-logo.png" 
                    name="submit" 
                    class="eStore_paypal_checkout_button" alt="Checkout" />';
    
        $button_text = $this->plugin_url.'img/Beanstream-logo.png';
        $content .= '<div>'.$button_input_data.'</div>';
        return $content;
    }
    
    function activate( $network_wide ) {
    }
    
    function deactivate( $network_wide ) {
    }
    
    function init() {
    }
}
new EStoreBeanstream();
