<?php
  /**
   * API connector class for Kargo National Shipping plugin
   *
   * @author  Elizabeth Meyer <elizabeth@hereandnowdigital.co.za>
   * @package kargo-shipping
   * Text Domain: kargo-shipping
   */

  namespace KARGOSHIPPING;

  // Exit if accessed directly.
  if ( ! defined( 'ABSPATH' ) )
    exit;

  class api {

    /**
     * API URL
     *
     * @var string
     */
    private $wsdl_url = 'http://api.kargo.co.za/API.asmx?WSDL';

    protected string $username = '';
    protected string $password = '';
    protected string $account_number = '';
    protected string $user_active = '';

    protected string $origin_postcode = '';
    protected string $destination_postcode = '';



    public function __construct( ) {
      $this->set_defaults();
      $this->register_actions();
      $this->register_filters();
    }

public static function create( string $slug, string $username, string $password, string $account_number, string $origin_postcode ): self {
    $instance = new self();
    $instance->username       = $slug;
    $instance->username       = $username;
    $instance->password       = $password;
    $instance->account_number = $account_number;
    $instance->origin_postcode = $origin_postcode;
    return $instance;
}

    public function set_defaults() {       

    }

    private function register_actions(): void {
    }

    private function register_filters(): void {
    }

    public function available(  ) {
        if ( empty($this->username) || empty($this->password) || empty($this->account_number ))
            return false;
        #TODO - test api connection and check if client account is active

        return true;
    }


    public function get_shipping_rate( string $destination_postcode, float $total_weight = 1, array $cart_items = [] ) {
      $this->destination_postcode = $destination_postcode;
      
      if (empty($this->username) || empty($this->password) || empty($this->account_number)) :
        woo_logger::error( 'Missing API credentials' );
        return false;
      endif;

      if ( empty($destination_postcode) || (empty($weight) && empty( $cart_items ) ) ) {
        woo_logger::error(' Missing required package info ');
        return false;
      }

      return $this->RateEnquiryWithParcels( $cart_items );

    }

    private function RateEnquiryWithParcels( array $cart_items ) {

      $parcels = [];

      foreach ( $cart_items as $item ) :
        $product  = $item['data'];
        $quantity = (int) $item['quantity'];

        if ( ! $product instanceof WC_Product ) 
            continue;

        // Dimensions (Woo stores in cm, weight in kg by default)
        $length = (float) $product->get_length() ?: 1;
        $width  = (float) $product->get_width()  ?: 1;
        $height = (float) $product->get_height() ?: 1;
        $mass   = (float) $product->get_weight() ?: 1;

        for ( $i = 0; $i < $quantity; $i++ ) {
            $parcels[] = [
                'parcelLength' => $length,
                'parcelWidth'  => $width,
                'parcelHeight' => $height,
                'parcelMass'   => $mass,
            ];
        }

    endforeach;

      
      $params = array(
        'username' => $this->username,
        'password' => $this->password,
        'accountNumber' => $this->account_number,
        'postalCodeOrigin' => $this->origin_postcode,
        'postalCodeDestination' => $this->destination_postcode,
        'weight' => (int) round($this->weight ?? 1),  
        'parcelsList'           => [
          'RateEnquiryParcelsParameters' => $parcels
        ] 
      );
 

      try {
        $client = new \SoapClient( $this->wsdl_url, [
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE
        ] );
    
        $response = $client->RateEnquiryWithParcels( $params );

        if ( isset( $params['password'] ) ) 
          $params['password'] = '[redacted]';

        woo_logger::info('API Request: ' . print_r($params, true));
        woo_logger::info('API Response: ' . print_r($response, true));

        if ( isset( $response->RateEnquiryWithParcelsResult ) && !empty( $response->RateEnquiryWithParcelsResult ) ) 
          return $this->parse_rate_response($response->RateEnquiryWithParcelsResult);

        woo_logger::error('Invalid API response');
        return false;

      } catch (Exception $e) {
        woo_logger::error('API Error: ' . $e->getMessage());
        return false;
      }
    }

        /**
         * Process rate response from API
         *
         * @param $response_json
         *
         * @return array|bool Rate data on success, false on failure
         */
	    private function parse_rate_response($response_json) {
		    $response_array = json_decode( json_encode($response_json), true );
		    $response = $response_array['any'];

		    if (!is_string($response)) {
			    woo_logger::error('Response is not a string.');
			    return false;
		    }

		    // Extract the KREW section as a raw string
		    $start = strpos($response, '<KREW ');
		    if ($start === false) {
			    $start = strpos($response, '<KREW>');
		    }
     
		    if ($start === false) {
			    woo_logger::error('No KREW element found in response');
			    return false;
		    }

		    $end = strpos($response, '</KREW>', $start);
		    if ($end === false) {
			    woo_logger::error('No closing KREW tag found');
			    return false;
		    }

		    // Extract the KREW content with its tags
		    $krew_length = $end - $start + 7; // +7 for '</KREW>'
		    $krew_xml = substr($response, $start, $krew_length);

		    // Extract the fields we need using simple string functions
		    $result = array();
		    $fields = array(
			    'RequestStatusSuccess',
			    'RequestErrorMessage',
			    'Subtotal',
			    'VAT',
			    'Total',
			    'AccountActive',
			    'AccountStatus'
		    );

		    foreach ($fields as $field) {
			    $field_start = strpos($krew_xml, '<' . $field . '>');
			    if ($field_start !== false) {
				    $field_start += strlen($field) + 2; // +2 for '<>'
				    $field_end = strpos($krew_xml, '</' . $field . '>', $field_start);
				    if ($field_end !== false) {
					    $value = substr($krew_xml, $field_start, $field_end - $field_start);
					    $result[$field] = $value;
				    }
			    }
		    }

		    if (!empty($result) && isset($result['Subtotal'])) {
			    woo_logger::info('Successfully extracted response data: ' . print_r($result, true));
			    return $result;
		    }

		    woo_logger::error('Could not extract rate data from response');
		    return false;
	    }

  }