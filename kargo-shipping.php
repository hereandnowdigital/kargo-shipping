<?php
  /**
   * Plugin Name: Kargo National Shipping
   * Description: Shipping method for WooCommerce that integrates with Kargo National shipping services.
   * Version: 0.0.2
   * Requires at least: 6.7
   * Requires PHP: 8.2
   * WC requires at least: 3.0.0
   * WC tested up to: 9.5.0
   * WC HPOS compatible: true
   * Contributors: Elizabeth Meyer <elizabeth@hereandnowdigital.co.za>
   * Text Domain: kargo-shipping
   *
   * @author  Dezel
   * @package kargo-shipping
   *
   **/

  if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly.


  // Define plugin constants
  define('KARGO_NS_VERSION', '0.0.2');
  define('KARGO_NS_PLUGIN_DIR', plugin_dir_path(__FILE__));
  define('KARGO_NS_PLUGIN_URL', plugin_dir_url(__FILE__));
  define('KARGO_NS_PLUGIN_BASENAME', plugin_basename(__FILE__));
  $slug = sanitize_title( dirname( KARGO_NS_PLUGIN_BASENAME ) ); 
  $slug = str_replace( '-', '_', $slug ); 
  define('KARGO_NS_PLUGIN_SLUG', $slug );

$slug = str_replace( '-', '_', $slug ); 

  add_action( 'before_woocommerce_init', 'kargo_shipping_HPOS' );
  function kargo_shipping_HPOS() {
      if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
          \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
              'custom_order_tables', // HPOS feature
              __FILE__,              // this plugin file
              true                   // true = compatible, false = not compatible yet
          );
      }
  };


  add_action('plugins_loaded', 'load_kargoshipping_plugin');
  function load_kargoshipping_plugin() {
    require_once   KARGO_NS_PLUGIN_DIR . '/includes/' . '_autoloader.php';
    if ( class_exists( 'KARGOSHIPPING\plugin' ) ) 
        KARGOSHIPPING\plugin::create( KARGO_NS_PLUGIN_SLUG );
  }

  