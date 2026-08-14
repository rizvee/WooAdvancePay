<?php
/** Classic-checkout presentation. */

defined( 'ABSPATH' ) || exit;

class WooAdvancePay_Public {
	public function __construct() {
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'display_notice' ) );
	}

	public function display_notice() {
		if ( ! WooAdvancePay::is_applicable() ) {
			return;
		}
		$original = (float) WC()->session->get( 'wooadvancepay_original_total' );
		$deposit  = (float) WC()->session->get( 'wooadvancepay_deposit' );
		if ( $original <= 0 || $deposit <= 0 || $deposit >= $original ) {
			return;
		}
		wc_print_notice(
			sprintf(
				/* translators: 1: deposit amount, 2: amount due on delivery. */
				esc_html__( 'Pay %1$s securely now. The remaining %2$s is due on delivery.', 'wooadvancepay' ),
				wp_kses_post( wc_price( $deposit ) ),
				wp_kses_post( wc_price( $original - $deposit ) )
			),
			'notice'
		);
	}
}
