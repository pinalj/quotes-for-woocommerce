=== Quotes for WooCommerce ===

Contributors: pinal.shah
Tags: woocommerce, quotes, proposals, hide-price, request-a-quote, woocommerce-request-quote
Requires at least: 4.5
Tested up to: 6.0
Stable tag: 1.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

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
4. Settings are available to enable/disable prices for quote products.
5. User can add such products to the cart and place orders without making a payment.
6. Payments can be collected once a quote email is sent to the user.

== Screenshots ==

1. Plugin settings.

2. Product page.

3. WooCommerce Orders page.

4. Quote email.

== Changelog ==

= 1.9 (25.05.2022) =
* Enhancement - Add an order note to the WooCommerce Order once a quote email has been sent.
* Enhancement - Added .po file for Spanish. Thank you @
* Tweak - Allow site admin to change the field list displayed/hidden at Checkout when 'Hide Address fields at Checkout' is enabled using <a href="https://github.com/pinalj/quotes-for-woocommerce/wiki/Hooks-&-Filters#display-country-field-in-billing-at-checkout-when-hide-address-fields-at-checkout-is-enabled" target="_blank">a hook.<a>
* Tweak - Add <a href="https://github.com/pinalj/quotes-for-woocommerce/wiki/Hooks-&-Filters#change-conflict-message-displayed-on-the-cart-page-when-products-containing-quotes-and-without-quotes-are-added-at-the-same-time" target="_blank">hook</a> to change the conflict message displayed when products with quotes are added to carts containing normal WooCommerce products and vice-versa.
* Tweak - Added <a href="https://github.com/pinalj/quotes-for-woocommerce/wiki/Hooks-&-Filters#change-page-title-for-checkout-page-and-pay-for-order-page" target="_blank">hook</a> to change the page title for Checkout and Order Payment page.
* Fix - Prices are hidden in the WordPress admin dashboard when quotes are enabled for products in the Product listing page.

= 1.8 (10.08.2021) =
* Tweak - Added the ability to display item attributes in the initial quote emails sent to the site admin & customer.'
* Tweak - Converted product names to links to redirect to front-end product page in the initial quote emails sent to the site admin & customer.'
* Tweak - Included a filter which can be used modify product quote status on the front end.'

= 1.7.3 (02.05.2021) =
* Enhancement - Made the plugin compatible with YayMail Pro.
* Tweak - Added a filter to allow the site admin the ability to change the Payment medium name.
* Tweak - Added a filter to add new rows to the new quote request email sent to admin.
* Fix - Plain Text Email Template file name was incorrect.
* Fix - Incorrect text domain listed in the plugin file.
* Fix - Incorrect text domain was used for the Subtotal text.

= 1.7.2 (03.04.2021) =
* Fix - The PHP email templates were being copied to an incorrect location in the theme folder.
* Tweak - Added nl_NL, fr_FR, ru_RU translation files.

= 1.7.1 (18.01.2021) =
* Fix - Internal Server Error with WooCommerce 4.9.0.
* Fix - XML Sitemap showing error.
* Tweak - Added a hook to allow the admin to send emails for statuses other than Pending Payment.

= 1.7.0 (20.06.2020) =
* Fix - Price was displayed in html tags in customer email.
* Fix - Appearance->menus disappear when the plugin is activated.
* Fix - Product Added to Cart message is incomplete when the global settings are not saved.
* Enhancement - Add a new setting to change Place Order button text for carts containing quotable products.
* Fix - Incorrect Add to Cart button text for non quote products which do not have a price set.
* Fix - WordPress crashes when the plugin is activated with themes like Jevelin & Avada.
* Tweak - Add Settings link on the Plugins page.
* Fix - Mini cart displays cart total when quotable products are present in the cart.

= 1.6.4 (27.01.2020) =
* Fix - Order total prices were displayed with HTML in the Quote emails.

= 1.6.3 (05.12.2019) =
* Enhancement - Made the plugin compatible with Loco Translate.
* Enhancement - Added billing first name, last name, email & phone merge tags for the admin email.
* Fix - Prices were displayed on the My Account->Orders page.
* Fix - Double headers & footers in the plugin emails.
* Fix - Made the plugin WPCS compatible.

= 1.6.2 (20.07.2019) =
* Enhancement - Made the plugin compatible by adding .pot file & text domain details.
* Enhancement - Added a new setting to allow the admin to modify the text displayed for the Add to Cart button.
* Fix - Updated the parameters used in the hook 'woocommerce_email_before_order_table'.

= 1.6.1 (11.04.2019) =
* Fix - Internal 500 Error when updating to version 1.6
* Fix - Deprecated WooCommerce filter was being used. Replaced with an active one.
* Tweak - Modified the variables being passed to the email templates.

= 1.6 (25.03.2019) =
* Added a new setting in Quotes->Settings that allow to change the name of the Cart page when the cart contains only quotable products.
* Added a new setting in Quotes->Settings that allows the admin to disable Billing & Shipping addresses for quote orders.
* Fixed a conflict error with Stream plugin.
* Fixed an issue where the {blogname} merge tag is not replaced with the correct details.
* Email subject & headings were not customizable from WooCommerce->Settings->Eamils. Fixed the same.
* Fixed an error being logged in the debug log for quote emails. 

= 1.5 (31.10.2018) =
* When the order contains only variable products, the Checkout process fails as no Payment medium is found. Fixed the same.
* Added a new menu Quote->Settings. This menu can now be used to create quote settings for all products at once.
* Fixed an issue where turning off the Quote emails from WooCommerce->Settings->Emails was not stopping the mails from being sent.

= 1.4 (19.06.2018) =
* Fixed an issue where a warning is entered in debug.log when a quote email is sent to the Customer.
* Added a new setting using which the site admin can display product prices for quotable products.
* Added a new email template to be sent to the customer when a request for a quote is raised.

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