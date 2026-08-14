<?php
/** Core deposit and order logic. */

defined( 'ABSPATH' ) || exit;

class WooAdvancePay {
	const META_ORIGINAL_TOTAL = '_wooadvancepay_original_total';
	const META_DEPOSIT        = '_wooadvancepay_deposit_amount';
	const META_REMAINING      = '_wooadvancepay_remaining_due';
	const META_ZONE           = '_wooadvancepay_shipping_zone';

	/** Prevent calculated-total re-entry. */
	private $calculating = false;

	public function __construct() {
		add_filter( 'woocommerce_calculated_total', array( $this, 'reduce_checkout_total' ), 1000, 2 );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'remove_cod_for_deposit' ) );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'store_order_details' ), 20, 2 );
		add_action( 'woocommerce_checkout_order_created', array( $this, 'clear_session' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'show_order_balance' ) );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'show_admin_balance' ) );
	}

	/** Return configured zone IDs in a backwards-compatible form. */
	public static function get_zone_ids() {
		$value = get_option( 'wooadvancepay_advance_payment_localities', array() );
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}
		return array_values( array_unique( array_map( 'absint', array_filter( (array) $value, 'strlen' ) ) ) );
	}

	/** Find matching zones for every current shipping package. */
	public static function get_current_zone_ids() {
		$zones = array();
		if ( ! WC()->shipping() ) {
			return $zones;
		}
		foreach ( WC()->shipping()->get_packages() as $package ) {
			$zones[] = (int) WC_Shipping_Zones::get_zone_matching_package( $package )->get_id();
		}
		return array_unique( $zones );
	}

	/** Whether a deposit applies to the current cart. */
	public static function is_applicable() {
		if ( ! WC()->cart || ! WC()->session || ! WC()->cart->needs_shipping() ) {
			return false;
		}
		return (bool) array_intersect( self::get_zone_ids(), self::get_current_zone_ids() );
	}

	/** Calculate a validated deposit. */
	public static function calculate_deposit( $total ) {
		$total = max( 0, (float) $total );
		$type  = get_option( 'wooadvancepay_payment_type', 'percentage' );
		if ( 'fixed_amount' === $type ) {
			$deposit = (float) get_option( 'wooadvancepay_advance_payment_fixed_amount', 0 );
		} else {
			$percentage = min( 100, max( 0, (float) get_option( 'wooadvancepay_advance_payment_percentage', 10 ) ) );
			$deposit    = $total * $percentage / 100;
		}
		return (float) wc_format_decimal( min( $total, max( 0, $deposit ) ), wc_get_price_decimals() );
	}

	/** Make the amount sent to the selected online gateway equal the deposit. */
	public function reduce_checkout_total( $total, $cart ) {
		if ( $this->calculating || ! is_checkout() || is_wc_endpoint_url( 'order-pay' ) || ! self::is_applicable() ) {
			return $total;
		}
		$this->calculating = true;
		$deposit           = self::calculate_deposit( $total );
		if ( $deposit > 0 && $deposit < (float) $total ) {
			WC()->session->set( 'wooadvancepay_original_total', (float) $total );
			WC()->session->set( 'wooadvancepay_deposit', $deposit );
			$total = $deposit;
		} else {
			$this->clear_session();
		}
		$this->calculating = false;
		return $total;
	}

	/** COD cannot collect an online deposit; all other enabled gateways remain available. */
	public function remove_cod_for_deposit( $gateways ) {
		if ( self::is_applicable() && WC()->session->get( 'wooadvancepay_deposit' ) ) {
			unset( $gateways['cod'] );
		}
		return $gateways;
	}

	/** Persist server-calculated values using HPOS-compatible CRUD methods. */
	public function store_order_details( $order, $data ) {
		$original = (float) WC()->session->get( 'wooadvancepay_original_total' );
		$deposit  = (float) WC()->session->get( 'wooadvancepay_deposit' );
		if ( $original <= 0 || $deposit <= 0 || $deposit >= $original || ! self::is_applicable() ) {
			return;
		}
		$order->update_meta_data( self::META_ORIGINAL_TOTAL, $original );
		$order->update_meta_data( self::META_DEPOSIT, $deposit );
		$order->update_meta_data( self::META_REMAINING, $original - $deposit );
		$order->update_meta_data( self::META_ZONE, implode( ',', self::get_current_zone_ids() ) );

		// Reconcile the item sum with the amount charged without changing product or tax records.
		$balance_item = new WC_Order_Item_Fee();
		$balance_item->set_name( __( 'Balance due on delivery', 'wooadvancepay' ) );
		$balance_item->set_amount( 0 - ( $original - $deposit ) );
		$balance_item->set_total( 0 - ( $original - $deposit ) );
		$balance_item->set_tax_status( 'none' );
		$order->add_item( $balance_item );

		$order->add_order_note( sprintf( __( 'Online deposit: %1$s. Balance due on delivery: %2$s.', 'wooadvancepay' ), wp_strip_all_tags( wc_price( $deposit ) ), wp_strip_all_tags( wc_price( $original - $deposit ) ) ) );
	}

	public function clear_session() {
		if ( WC()->session ) {
			WC()->session->__unset( 'wooadvancepay_original_total' );
			WC()->session->__unset( 'wooadvancepay_deposit' );
		}
	}

	public function show_order_balance( $order ) {
		$this->render_balance( $order, false );
	}

	public function show_admin_balance( $order ) {
		$this->render_balance( $order, true );
	}

	private function render_balance( $order, $admin ) {
		$remaining = (float) $order->get_meta( self::META_REMAINING );
		if ( $remaining <= 0 ) {
			return;
		}
		$deposit = (float) $order->get_meta( self::META_DEPOSIT );
		$tag     = $admin ? 'div' : 'section';
		/* translators: %s: formatted deposit amount. */
		$paid = sprintf( __( 'Paid online: %s', 'wooadvancepay' ), wp_strip_all_tags( wc_price( $deposit, array( 'currency' => $order->get_currency() ) ) ) );
		/* translators: %s: formatted balance due amount. */
		$due = sprintf( __( 'Due on delivery: %s', 'wooadvancepay' ), wp_strip_all_tags( wc_price( $remaining, array( 'currency' => $order->get_currency() ) ) ) );
		printf( '<%1$s class="wooadvancepay-order-balance"><h2>%2$s</h2><p>%3$s<br>%4$s</p></%1$s>', esc_attr( $tag ), esc_html__( 'Advance payment', 'wooadvancepay' ), esc_html( $paid ), esc_html( $due ) );
	}
}
