=== Quotes for WooCommerce ===

Contributors: pinal.shah
Tags: woocommerce, quotes, proposals, hide-price, request-a-quote, woocommerce-request-quote
Requires at least: 4.5
Tested up to: 4.9.1
Stable tag: 4.8.0
License: GPLv2 or later

This WordPress plugin extends the WooCommerce Plugin. It allows the site admin the ability to send quotes for products. All prices are hidden from the user on all pages until the admin sends a quote. No payments will be taken at Checkout. 

== Description ==

Plugins required:
<ol>
<li>WooCommerce 3.0.0 or higher</li>
</ol>

Want to convert your WooCommerce store into a Quote only program? This plugin allows the admin to hide the prices on a per product basis and modify the Add to Cart button to 'Request Quote'.

No payment is taken at Checkout. The prices for the product can be setup in the WooCommerce->Orders page and once a quote is finalized, the plugin will send an email to the end user notifying the same.

The users can then make the payments using the link in the email or the My Accounts page.

== Installation ==

1. Install and activate the plugin from the Wordpress->Plugins page. 
2. Once the plugin is enabled, a product can be setup as quotable using the settings in Product Data->Inventory tab.
3. Once enabled, the price for the product is hidden from the user.
4. User can add such products to the cart and place orders without making a payment.
5. Payments can be collected once a quote email is sent to the user.

== Screenshots ==

1. Plugin settings.

2. Product page.

3. WooCommerce Orders page.

4. Quote email.

== Changelog ==

= 1.3 (18.12.2017) =
* Fixed an issue where an Internal Server Error is thrown at Checkout when the Cart contains quote products.

= 1.2 (28.09.2017) =
* Added an email template to be sent to admin when a request for quote is received.
* Fixed an issue where Add to Cart text was not modified for quote products on the single product page.

= 1.1 (03.09.2017) =
* Added the code to remove the plugin data when deleted.
* Added WC Verison check support.
* Added plugin version data in the DB.

= 1.0 (29.08.2017) =
* Initial release.