<?php
  /**
   * WooCommerce logger for Kargo National Shipping plugin
   *
   * @author  Elizabeth Meyer <elizabeth@hereandnowdigital.co.za>
   * @package kargo-shipping
   * Text Domain: kargo-shipping
   */

namespace KARGOSHIPPING;

if ( ! defined( constant_name: 'ABSPATH' ) ) 
	exit;

class woo_logger {

	/**
     * Singleton instance
     * @var self|null
     */
    private static ?self $instance = null;

	/**
	 * WooCommerce logger instance.
	 *
	 * @var \WC_Logger
	 */
	protected \WC_Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param string $source Optional log source name.
	 */
	public function __construct( protected string $source, protected bool $enabled = false ) {
		
		$this->enabled = get_option( $this->source . '_debug', true );
		
		//if ( $this->enabled ) 
			$this->logger = wc_get_logger();
	}

 /**
     * Initialize the logger singleton
     */
    public static function init( string $source ): void {
        if ( self::$instance === null ) {
            self::$instance = new self( $source );
        }
    }

    /**
     * Get instance
     */
    private static function get_instance(): self {
        if ( self::$instance === null ) {
            wp_die('Logger not initialized. Call woo_logger::init($source) first.');
        }
        return self::$instance;
    }

    public static function create( $plugin_basename ): static {
      return new static( $plugin_basename  );
    }

	/**
	 * Write a log entry.
	 *
	 * @param string $level Log level: emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message The message to log.
	 * @param array  $context Optional additional context.
	 */
	private static function log(string $level, string $message, array $context = []): void {
        $instance = self::get_instance();
        if ( ! $instance->enabled ) return;

        $instance->logger->log($level, $message, array_merge(['source' => $instance->source], $context));
    }


	public static function emergency ( $message, array $context = [] ) {
		self::log( 'emergency', $message, $context );
	}

	public static function alert ( $message, array $context = [] ) {
		self::log( 'alert', $message, $context );
	}

	public static function critical ( $message, array $context = [] ) {
		self::log( 'critical', $message, $context );
	}

	public static function error ( $message, array $context = [] ) {
		self::log( 'error', $message, $context );
	}

	public static function warning ( $message, array $context = [] ) {
		self::log( 'warning', $message, $context );
	}

	public static function notice ( $message, array $context = [] ) {
		self::log( 'error', $message, $context );
	}

	public static function info ( $message, array $context = [] ) {
		self::log( 'warning', $message, $context );
	}

	public static function debug ( $message, array $context = [] ) {
		self::log( 'warning', $message, $context );
	}

}
