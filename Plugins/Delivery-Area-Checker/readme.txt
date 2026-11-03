=== Delivery Area Checker ===
Tags: woocommerce, shipping, postcode, free shipping, delivery
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a simple UK postcode checker to your WooCommerce store to tell customers whether free delivery is available in a selected shipping zone.

== Description ==

Delivery Area Checker adds a postcode checker to your WooCommerce store. Customers enter their UK postcode and the plugin checks whether WooCommerce matches it to the shipping zone you selected and whether that zone has an enabled Free Shipping method.

Features:

* Uses WooCommerce's own shipping-zone matching, including zone order and postcode rules.
* Lets you select the shipping zone to check in the WooCommerce admin.
* Supports a shortcode zone override for individual instances.
* Lets you customise the success and failure messages, button text, button colour and text colour.
* Works with Elementor's Shortcode widget and the WordPress shortcode block.
* Uses an AJAX request and does not send postcode data to an external service.
* Does not change the WooCommerce checkout or shipping configuration.

The plugin checks UK postcodes only.

== Installation ==

1. Upload the `delivery-area-checker` folder to the `/wp-content/plugins/` directory, or install the ZIP through Plugins > Add New Plugin.
2. Activate Delivery Area Checker.
3. Make sure WooCommerce is installed and active.
4. Go to WooCommerce > Delivery Area Checker.
5. Select the WooCommerce shipping zone you want the checker to test and save the settings.
6. Add the `[delivery_area_checker]` shortcode to a page, post, or Elementor Shortcode widget.

== Shortcode ==

Use the saved default shipping zone:

`[delivery_area_checker]`

To check a different shipping zone on one page, supply its WooCommerce zone ID:

`[delivery_area_checker zone="3"]`

The zone override applies only to that shortcode instance and does not change the saved default.

== How eligibility is checked ==

The plugin asks WooCommerce which shipping zone matches the submitted postcode for the United Kingdom (GB). It then checks whether that exact zone is the selected zone and whether it contains an enabled Free Shipping method.

WooCommerce zone order and postcode rules are respected. If a postcode matches a higher-priority zone, it will not be treated as belonging to a lower-priority selected zone.

== Important limitation ==

The checker confirms that an enabled Free Shipping method exists in the matched zone. It does not evaluate basket-dependent Free Shipping requirements, such as a minimum spend or coupon requirement, because the shortcode checker does not have the customer's cart context. WooCommerce continues to enforce those requirements at checkout.

== Frequently Asked Questions ==

= Does this plugin calculate shipping costs? =

No. It checks whether the selected WooCommerce shipping zone matches the customer's UK postcode and has an enabled Free Shipping method.

= Does it check minimum spend or coupon requirements? =

No. The checker is postcode-only and cannot evaluate requirements that depend on the customer's basket. WooCommerce applies those rules at checkout.

= Can I use more than one checker on a site? =

Yes. You can add the shortcode more than once. Use the zone attribute to check a different shipping zone in an individual instance.

= Does the plugin use an external postcode lookup service? =

No. Postcode validation and shipping-zone matching are handled by WooCommerce.

= Does it work with Elementor? =

Yes. Add the shortcode using Elementor's Shortcode widget.

== Support ==

For help using the plugin or to report a bug, please open an issue in the [GitHub repository](https://github.com/LensDigitalUK/WordPress/issues).

== Changelog ==

= 1.0.1 =
* Prepare plugin headers and readme for WordPress.org submission.
* Improve settings sanitisation and ensure WooCommerce managers can save plugin settings.
* Reject overlong postcode submissions on the AJAX endpoint.

= 1.0.0 =
* Initial release.
