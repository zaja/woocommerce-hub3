<?php
/*
* Plugin Name: HUB3 2D Barcode
* Plugin URI:  https://svejedobro.hr
* Description: Create HUB3 - 2D barcode on Order Details page, regarding specification of Croatian Banking Association
* Version:     1.0
* Author:      Goran Zajec
* Author URI:  https://svejedobro.hr
* Domain Path: /languages/
* Text Domain: hub3-barcode
* License:     GPLv2
* License URI: http://www.gnu.org/licenses/gpl-3.0
*/
if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}
/**
 * Load Plugin Translation
 */
function myplugin_init()
{
	$plugin_rel_path = basename(dirname(__FILE__)) . '/languages';
	/* Relative to WP_PLUGIN_DIR */
	load_plugin_textdomain('hub3-barcode', false, $plugin_rel_path);
}
add_action('plugins_loaded', 'myplugin_init');


function plugin_add_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=hub3_2d_barcode">' .  __( 'Setting', 'hub3-barcode' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'plugin_add_settings_link' );

/* *
* Check if WooCommerce is active
*/
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
{
	/**
	 * Equeue scripts and styles
	 */
	function hub3barcode_scripts()
	{
		if (is_wc_endpoint_url('order-received') OR is_wc_endpoint_url('view-order'))
		{
			wp_enqueue_script('bcmath-min', plugin_dir_url(__FILE__) . 'js/bcmath-min.js', array() , '1.0.0', true);
			wp_enqueue_script('pdf417-min', plugin_dir_url(__FILE__) . 'js/pdf417-min.js', array() , '1.0.0', true);
			wp_enqueue_style('hub-barcode', plugin_dir_url(__FILE__) . 'css/hub-barcode.css', array() , '1.0.0', 'all');
		}
	}
	add_action('wp_enqueue_scripts', 'hub3barcode_scripts');
	/**
	 * Filter za dodavanje uputa za placanje na listu narudzbi
	 */
	if (get_option('enable_basc_on_email') !== "no") {
	add_action( 'woocommerce_order_details_after_order_table', 'view_order_custom_payment_instruction', 5, 1); // Email notifications
	}
	else {
	add_action( 'woocommerce_order_details_after_customer_details', 'view_order_custom_payment_instruction'); 
	}
	function view_order_custom_payment_instruction($order)
	{
		if (in_array($order->get_status() , array(
			'on-hold',
			'processing'
		)) && is_wc_endpoint_url('order-received') OR is_wc_endpoint_url('view-order'))
		{
			if ( get_option( 'enable_hub3_on_pages' ) !== "no" ) {
			require_once ( plugin_dir_path( __FILE__ ) . 'generator.php');
			}
			if ( get_option( 'enable_basc_on_pages' ) !== "no" && is_wc_endpoint_url('view-order')) {
			do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
			}
		}
	}
}

class HUB3_2D_barcode_sett {

    /**
     * Bootstraps the class and hooks required actions & filters.
     *
     */
    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_hub3_2d_barcode', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_hub3_2d_barcode', __CLASS__ . '::update_settings' );
    }
    
    
    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['hub3_2d_barcode'] = __( 'HUB3 2D Barcode', 'hub3-barcode' );
        return $settings_tabs;
    }


    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }


    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function get_settings() {

        $settings = array(
         'section_title' => array(
                'name'     => __( 'HUB3 2D Barcode Setting', 'hub3-barcode' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_hub3_2d_barcode_section_title'
            ),
			 'enable_hub3' => array(
                'name'    => __( 'Enable HUB3 Barcode', 'hub3-barcode' ),
                'desc'    => __( 'Check it to enable HUB3 barcode generation on Order Details and Thank you pages.' ),
                'id'      => 'enable_hub3_on_pages',
                'default' => 'no',
                'type'    => 'checkbox'
            ),
			 'enablebasc' => array(
                'name'    => __( 'Enable Bank details', 'hub3-barcode' ),
                'desc'    => __( 'Check it to enable bank info(BANK NAME, IBAN, etc..) on Order Details pages. This option will show bank info for order status  On Hold.' ),
                'id'      => 'enable_basc_on_pages',
                'default' => 'no',
                'type'    => 'checkbox'
            ),
			 'enablebascemail' => array(
                'name'    => __( 'Enable Bank details in order email', 'hub3-barcode' ),
                'desc'    => __( 'Check it to enable bank info(BANK NAME, IBAN, etc..) to be sent to buyer in order notification' ),
                'id'      => 'enable_basc_on_email',
                'default' => 'no',
                'type'    => 'checkbox'
            ),
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_hub3_2d_barcode_section_end'
            )
			
        );

        return apply_filters( 'wc_hub3_2d_barcode_settings', $settings );
    }
}
HUB3_2D_barcode_sett::init();
