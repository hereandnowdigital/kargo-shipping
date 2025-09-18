<?php
  /**
   * Main class for Kargo National Shipping plugin
   *
   * @author  Elizabeth Meyer <elizabeth@hereandnowdigital.co.za>
   * @package kargo-shipping
   * Text Domain: kargo-shipping
   */

  namespace KARGOSHIPPING;

  // Exit if accessed directly.
  if ( ! defined( 'ABSPATH' ) )
    exit;

  class plugin {
    
    static public string $slug;


    public function __construct( $slug ) {
      self::$slug = $slug;
      $this->load_dependencies();
      $this->register_filters();
    }

    public static function create( $slug  ): static {
      return new static( $slug  );
    }

    private function load_dependencies(): void {

      if ( class_exists( 'WooCommerce' ) ) 
        woo_logger::init( self::$slug );

    }

    private function register_filters(): void {
      add_filter( 'woocommerce_shipping_methods', [$this, 'load_shipping_method'] );
    }  

    public function load_shipping_method( $methods ) {  
      $methods['kargo_national_shipping'] = \KARGOSHIPPING\shipping_method::class;
      return $methods;
    }

  }