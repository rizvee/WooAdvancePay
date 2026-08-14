<?php
/** Settings screen. */

defined( 'ABSPATH' ) || exit;

class WooAdvancePay_Admin {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function admin_menu() {
		add_submenu_page( 'woocommerce', __( 'Advance payments', 'wooadvancepay' ), __( 'Advance payments', 'wooadvancepay' ), 'manage_woocommerce', 'wooadvancepay', array( $this, 'settings_page' ) );
	}

	public function register_settings() {
		register_setting( 'wooadvancepay', 'wooadvancepay_payment_type', array( 'type' => 'string', 'sanitize_callback' => array( $this, 'sanitize_type' ), 'default' => 'percentage' ) );
		register_setting( 'wooadvancepay', 'wooadvancepay_advance_payment_percentage', array( 'type' => 'number', 'sanitize_callback' => array( $this, 'sanitize_percentage' ), 'default' => 10 ) );
		register_setting( 'wooadvancepay', 'wooadvancepay_advance_payment_fixed_amount', array( 'type' => 'number', 'sanitize_callback' => array( $this, 'sanitize_amount' ), 'default' => 0 ) );
		register_setting( 'wooadvancepay', 'wooadvancepay_advance_payment_localities', array( 'type' => 'array', 'sanitize_callback' => array( $this, 'sanitize_zones' ), 'default' => array() ) );
	}

	public function sanitize_type( $value ) {
		return in_array( $value, array( 'percentage', 'fixed_amount' ), true ) ? $value : 'percentage';
	}

	public function sanitize_percentage( $value ) {
		return min( 100, max( 0, (float) wc_format_decimal( $value ) ) );
	}

	public function sanitize_amount( $value ) {
		return max( 0, (float) wc_format_decimal( $value ) );
	}

	public function sanitize_zones( $value ) {
		return array_values( array_unique( array_map( 'absint', (array) $value ) ) );
	}

	public function settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$type     = get_option( 'wooadvancepay_payment_type', 'percentage' );
		$selected = WooAdvancePay::get_zone_ids();
		$zones    = WC_Shipping_Zones::get_zones();
		$zones[0] = array( 'zone_id' => 0, 'zone_name' => __( 'Locations not covered by your other zones', 'wooadvancepay' ) );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Advance payment settings', 'wooadvancepay' ); ?></h1>
			<p><?php echo esc_html__( 'For matching shipping zones, checkout charges only the deposit through an enabled online payment gateway. Cash on delivery is hidden and the saved balance is due on delivery.', 'wooadvancepay' ); ?></p>
			<?php settings_errors(); ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'wooadvancepay' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php echo esc_html__( 'Deposit type', 'wooadvancepay' ); ?></th><td>
						<label><input type="radio" name="wooadvancepay_payment_type" value="percentage" <?php checked( $type, 'percentage' ); ?>> <?php echo esc_html__( 'Percentage', 'wooadvancepay' ); ?></label><br>
						<label><input type="radio" name="wooadvancepay_payment_type" value="fixed_amount" <?php checked( $type, 'fixed_amount' ); ?>> <?php echo esc_html__( 'Fixed amount', 'wooadvancepay' ); ?></label>
					</td></tr>
					<tr><th scope="row"><label for="wooadvancepay-percentage"><?php echo esc_html__( 'Percentage', 'wooadvancepay' ); ?></label></th><td><input id="wooadvancepay-percentage" name="wooadvancepay_advance_payment_percentage" type="number" min="0" max="100" step="0.01" value="<?php echo esc_attr( get_option( 'wooadvancepay_advance_payment_percentage', 10 ) ); ?>"> %</td></tr>
					<tr><th scope="row"><label for="wooadvancepay-fixed"><?php echo esc_html__( 'Fixed amount', 'wooadvancepay' ); ?></label></th><td><input id="wooadvancepay-fixed" name="wooadvancepay_advance_payment_fixed_amount" type="number" min="0" step="<?php echo esc_attr( pow( 10, -wc_get_price_decimals() ) ); ?>" value="<?php echo esc_attr( get_option( 'wooadvancepay_advance_payment_fixed_amount', 0 ) ); ?>"> <span class="description"><?php echo esc_html( get_woocommerce_currency() ); ?></span></td></tr>
					<tr><th scope="row"><label for="wooadvancepay-zones"><?php echo esc_html__( 'Shipping zones', 'wooadvancepay' ); ?></label></th><td><select id="wooadvancepay-zones" name="wooadvancepay_advance_payment_localities[]" multiple size="<?php echo esc_attr( min( 10, max( 3, count( $zones ) ) ) ); ?>" class="regular-text">
						<?php foreach ( $zones as $zone ) : ?><option value="<?php echo esc_attr( $zone['zone_id'] ); ?>" <?php selected( in_array( (int) $zone['zone_id'], $selected, true ) ); ?>><?php echo esc_html( $zone['zone_name'] ); ?></option><?php endforeach; ?>
					</select><p class="description"><?php echo esc_html__( 'Select one or more zones. Hold Ctrl (Windows) or Command (macOS) to select multiple zones.', 'wooadvancepay' ); ?></p></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
