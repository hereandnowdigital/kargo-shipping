<?php
  /**
   * WooCommerce shipping method class for Kargo National Shipping plugin
   *
   * @author  Elizabeth Meyer <elizabeth@hereandnowdigital.co.za>
   * @package kargo-shipping
   * Text Domain: kargo-shipping
   */

  namespace KARGOSHIPPING;

  // Exit if accessed directly.
  if ( ! defined( 'ABSPATH' ) )
    exit;

  class shipping_method extends \WC_Shipping_Method {

    private string $slug;
    private int $max_allowed_length = 120; //cm
    private int $max_allowd_width  = 120; //cm
    private int $max_allowed_height = 120; //cm
    private int $max_allowed_weight = 50; //kg

    private array $allowed_country_codes = ['ZA'];

    private ?api $api = null;

    private string $account_number = '';
    private string $username = '';
    private string $password = '';
    private string $origin_postcode = '';
    private bool $debug_mode = false;
    private bool $is_enabled = true;
    private string $destination_postcode = '';
    private int $total_weight = 1;

    /**
     * Constructor for shipping method class
     */
    public function __construct( $instance_id = 0 ) {
        $this->slug = plugin::$slug;
        $this->id                 = 'kargo_national_shipping';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __('Kargo National Shipping', 'kargo-shipping');
        $this->title = $this->method_title ;   
        $this->description = __('Provides real-time shipping rates through integration with Kargo National Shipping services.', 'kargo-shipping');
        $this->supports           = array(
            'shipping-zones',
            'instance-settings',
        );
        
        $this->init();
        $this->set_properties();

    }

    /**
     * Initialize shipping method settings
     */
    public function init(): void {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();       
    }

    private function set_properties() {
        $this->username        = $this->get_option('username', '');
        $this->password        = $this->get_option('password', '');
        $this->account_number  = $this->get_option('account_number', '');
        $this->origin_postcode = $this->get_option('origin_postcode', '');
        $this->debug_mode      = $this->get_option('debug', false);
        $this->is_enabled      = $this->get_option('enabled', true);
        update_option( $this->slug . '_debug', $this->debug_mode, false );
    }

    private function register_actions(): void {
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    private function register_filters(): void {
    }


    /**
     * Initialize form fields
     */
    public function init_form_fields(): void {
        $this->instance_form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable to turn on this shipping method.',
                'default' => $this->enabled,
                'type' => 'checkbox'),
            'account_number' => array(
                'title' => 'Account Number',
                'description' => 'Your Kargo National account number',
                'default' => $this->account_number,
                'type' => 'text'
            ),                
            'username' => array(
                'title' => 'Username',
                'description' => 'Your Kargo National API username',
                'default' => $this->username,
                'type' => 'text'
            ),
            'password' => array(
                'title' => 'Password',
                'description' => 'Your Kargo National API password',
                'default' => $this->password,
                'type' => 'password'
            ),
            'origin_postcode' => array(
                'title'       => __('Origin Postal Code', 'kargo-national-shipping'),
                'type'        => 'text',
                'description' => __('Enter the postal code from where you ship your products.  Defaults to store base location postal code.', 'kargo-national-shipping'),
                'default'     => $this->origin_postcode,
                'desc_tip'    => true,
            ),
            'debug' => array(
                'title'       => __('Enable debug mode?', 'kargo-national-shipping'),
                'type'        => 'checkbox',
                'label'       => __('Enable debug mode', 'kargo-national-shipping'),
                'default'     =>  $this->debug_mode,
                'description' => __('Enable debug mode to log API requests and responses.', 'kargo-national-shipping'),
            ),
        );
    }

    public function process_admin_options(): void {
        parent::process_admin_options();
    }

    /**
     * Check if shipping method is available
     */
    public function is_available( $package ): bool {

        if (!$this->is_enabled)
          return false;

        if ( ! parent::is_available( $package ) )
            return false;

        if ( isset( $package['destination']['country'] )
             && ! in_array( $package['destination']['country'], $this->allowed_country_codes, true ) ) :
        
             woo_logger::warning( 'Destination not allowed: ' .  $package['destination']['country'] );
            return false;
        endif;


        if ( $this->exceeds_max_dimensions( $package ) )
            return false;

        $api = api::create(
            $this->slug, 
            $this->username,
            $this->password,
            $this->account_number,
            $this->origin_postcode,
        );        
    
        if ( !$api->available( ) )
            return false;

        return true;
    }

    private function exceeds_max_dimensions( $package ): bool {
        $unit = get_option( 'woocommerce_dimension_unit', 'cm' );
        // Conversion multipliers to cm
        $conversion_factors = [
            'mm' => 0.1,
            'cm' => 1,
            'm'  => 100,
            'in' => 2.54,
            'yd' => 91.44,
        ];

        $factor = $conversion_factors[$unit] ?? 1;

        foreach ( $package['contents'] as $item ) {

            $product = $item['data'];

            if ( ! $product instanceof \WC_Product )
                continue;

            // Convert dimensions to cm
            $length = (float) $product->get_length() * $factor;
            $width  = (float) $product->get_width()  * $factor;
            $height = (float) $product->get_height() * $factor;

            if ( $length >= $this->max_allowed_length || $width >= $this->max_allowd_width|| $height >= $this->max_allowed_height )
                return true;

        }

        return false;

    }


    /**
     * Calculate shipping cost based on API.
     */
    public function calculate_shipping( $package = [] ) {

        $cart_items = [];

        $this->destination_postcode = $package['destination']['postcode'];

        if ( empty( $this->destination_postcode ) )
            return false;
    
        foreach ( $package['contents'] as $cart_item ) :
            $product = $cart_item['data'];
            if ( ! $product instanceof \WC_Product )
                continue;

            $cart_items[] = $cart_item;
        endforeach;

        if ( !$this->all_items_have_size_or_weight( $cart_items ) )
            return false;

        $this->weight = max( 1, WC()->cart->get_cart_contents_weight() );

        $shipping_cost =  $this->api_fetch_shipping_rate( $cart_items );
        $shipping_cost = (float) $shipping_cost;
            

        if ( $shipping_cost <= 0 )
            return;
   
        $rate_id = $this->id . ':' . ($this->instance_id ?? 0);

        // Register the rate
        $rate = array(
            'id'      => $rate_id,
            'label'   => $this->title,
            'cost'    => $shipping_cost,
            'package' => $package,
        );

        $this->add_rate($rate);
    }

    /**
     * Check if all products have weight and dimensions
     */
    private function all_items_have_size_or_weight( array $cart_items  ): bool {
        $items_missing_size_or_weight = [];

        foreach ( $cart_items as $item ) :
            $product = $item['data'];

            if ( $product->is_virtual() || $product->is_downloadable() ) 
                continue;

            if ( !$product->has_weight() || !$product->has_dimensions() ) 
                $items_missing_size_or_weight[] = $product->get_name();

        endforeach;

        if ( empty( $items_missing_size_or_weight )) 
            return true;

        woo_logger::warning('items_missing_size_or_weight: ' . print_r($items_missing_size_or_weight, true));


        return false;

    }

    /**
     * Get shipping cost from Kargo API
     */
    private function api_fetch_shipping_rate( array $cart_items = [] ) {

        if ( empty( $cart_items ) )
            return false;
  
        $shipping_rate = null;

        $this->api = api::create(
            $this->slug, 
            $this->username,
            $this->password,
            $this->account_number,
            $this->origin_postcode,
        );        

        $api_response = $this->api->get_shipping_rate( $this->destination_postcode, $this->total_weight, $cart_items );
  
        woo_logger::warning('api_response: ' . print_r($api_response, true));


        if ( is_array( $api_response )
             && ! empty( $api_response['AccountActive'] )
             && $api_response['AccountActive'] === 'true'
             && isset( $api_response['Subtotal'] )
             && is_numeric( $api_response['Subtotal'] )
             && $api_response['Subtotal'] > 0 ) 
                $shipping_rate = $api_response['Subtotal'];
        return $shipping_rate;
    }

  }