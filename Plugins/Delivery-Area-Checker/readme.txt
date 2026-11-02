# Delivery Area Checker

A WordPress plugin for WooCommerce that adds a UK postcode checker for free delivery.

## Installation
1. In WordPress, go to **Plugins → Add New Plugin → Upload Plugin**.
2. Upload the plugin ZIP.
3. Activate **Delivery Area Checker**.
4. Go to **WooCommerce → Delivery Area Checker**.
5. Select the shipping zone to check and save the settings.

## Shortcode
Add this shortcode to an Elementor Shortcode widget or the WordPress editor:

`[delivery_area_checker]`

To override the selected zone for one instance, use its WooCommerce zone ID:

`[delivery_area_checker zone="3"]`

The override does not change the saved default.

## How eligibility is checked
The plugin asks WooCommerce which shipping zone matches the submitted postcode for the UK (`GB`) and checks whether that exact zone is the selected zone. It then checks for an enabled `Free Shipping` method in that zone.

This respects WooCommerce zone matching and zone order. If a postcode matches a higher-priority zone, it will not be treated as belonging to a lower-priority selected zone.

## Important limitation
The checker confirms that an enabled Free Shipping method exists in the matched zone. It does not evaluate basket-dependent Free Shipping requirements such as minimum spend or coupon requirements, because the shortcode checker is postcode-only and has no cart context. WooCommerce continues to enforce those requirements at checkout.

## Requirements
- WordPress
- WooCommerce
- PHP version supported by the installed WordPress/WooCommerce versions
