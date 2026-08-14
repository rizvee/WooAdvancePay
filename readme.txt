=== Advance Deposits for WooCommerce ===
Contributors: hasanrizvee
Tags: woocommerce, deposit, partial payment, cash on delivery, checkout
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Collect an online deposit and leave the remaining balance due on delivery for selected WooCommerce shipping zones.

== Description ==

Advance Deposits for WooCommerce changes the payable checkout total to a percentage or fixed deposit when the customer's address matches a configured WooCommerce shipping zone. The customer pays that deposit with any enabled online gateway. Cash on delivery is hidden for the deposit transaction, and the original total, deposit, and balance due on delivery are recorded on the order.

No external service is contacted by this plugin. Payment data is handled by the WooCommerce payment gateway selected by the customer.

= Features =

* Percentage or fixed-amount deposits.
* Native WooCommerce shipping-zone selector.
* Server-side calculation; customer-submitted amounts are never trusted.
* Deposit and remaining balance shown to customers and administrators.
* WooCommerce High-Performance Order Storage (HPOS) compatible.
* Translation-ready and built with WordPress Settings API and WooCommerce CRUD APIs.

= Important checkout requirement =

This version supports the classic WooCommerce checkout. It declares the Cart and Checkout Blocks incompatible so WooCommerce can warn administrators rather than silently process an incorrect total. At least one online payment gateway must be enabled for applicable zones.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin folder to `/wp-content/plugins/`, or install the ZIP through **Plugins > Add New > Upload Plugin**.
3. Activate Advance Deposits for WooCommerce.
4. Go to **WooCommerce > Advance payments**.
5. Select the deposit type, enter an amount, and select one or more shipping zones.
6. Ensure the checkout page uses the classic `[woocommerce_checkout]` shortcode and an online payment gateway is enabled.

== Frequently Asked Questions ==

= Does the plugin process payment card information? =

No. The enabled WooCommerce payment gateway processes the deposit. Advance Deposits for WooCommerce only calculates the payable deposit and records the remaining balance.

= Why is cash on delivery hidden? =

A deposit must be collected online. For applicable orders, customers choose an enabled online gateway for the deposit; the recorded remainder is collected on delivery.

= Does this support Checkout Blocks? =

Not currently. Use the classic checkout shortcode. The plugin explicitly declares Checkout Blocks incompatible to prevent unexpected totals.

= What happens if a fixed deposit is equal to or greater than the order total? =

The plugin leaves checkout unchanged. A deposit must be greater than zero and less than the original order total.

= Is order storage with HPOS supported? =

Yes. Order data is stored through WooCommerce CRUD methods.

== Changelog ==

= 2.0.0 =
* Rebuilt deposit calculation and checkout integration.
* Added validated native settings and shipping-zone selection.
* Added HPOS-compatible order metadata and customer/admin balance summaries.
* Removed remote CSS, obsolete templates, unsafe output, broken JavaScript, and duplicate order hooks.
* Added dependency and compatibility declarations.

== Upgrade Notice ==

= 2.0.0 =
Review and save your shipping zones under WooCommerce > Advance payments. Classic checkout is required.
