=== WooCommerce e-conomic Integration ===
Contributors:      WooConomics
Plugin Name:       WooCommerce e-conomic Plugin
Plugin URI:        www.wooconomics.com
Tags:              WooCommerce, Order, E-Commerce, Accounting, Bookkeeping, invoice, invoicing, e-conomic, WooCommerce, order sync, customer sync, product sync, sync, Customers, Integration, woocommerce e-conomic integration, woocommerce integration, economic integration, woocommerceeconomic, wocommerce economic, woocomerce economic, wocomerce economic
Author URI:        www.wooconomics.com
Author:            wooconomics
Requires at least: 3.8
Tested up to:      4.1.1
Stable tag:        1.1
Version:           1.1

WooCommerce e-conomic integration synchronizes your WooCommerce Orders, Customers and Products to your e-conomic account.

== Description ==

WooCommerce e-conomic integration synchronizes your WooCommerce Orders, Customers and Products to your e-conomic accounting system. 
e-conomic invoices can be automatically created. This plugin requires the WooCommerce plugin. 
The INVOICE and PRODUCT sync features require a license purchase from http://wooconomics.com. 
WooCommerce e-conomic integration plugin connects to license server hosted at http://onlineforce.net to check the validity of the license key you type in the settings page.

= Data export to e-conomic: =

*	CUSTOMER:
	*	Billing Company / Last Name
	*	Billing Last Name
	*	Billing First Name
	*	Email
	*	Billing Address 1
	*	Billing Address 2
	*	Billing Country
	*	Billing City
	*	Billing Postcode
	*	Shipping Address 1
	*	Shipping Address 2
	*	Shipping Country
	*	Shipping City
	*	Shipping Postcode
*	PRODUCT/ARTICLE:
	*	Product name
	*	ArticleNumber (SKU + product prefix)
	*	Regular Price / Sale Price
	*	Description
*	INVOICE:
	*	Order ID (as reference)
	*	Customer number
	*	Delivery Address
	*	Delivery City
	*	Delivery Postcode
	*	Delivery Country
	*	Product Title
	*	Product quantity
	*	Product Price
	*	Shipping cost (as orderline - workaround) 

Features of WooCommerce e-conomic Integration:

1.	Automatic (and manual) sync of all Customers from WooCommerce to e-conomic invoicing service dashboard.
2.	Automatic (and manual) sync of all Orders from WooCommerce to e-conomic invoicing service dashboard. Sync initiated when order status is changed to 'Completed'.
3.	Automatic (and manual) sync of all products from WooCommerce to e-conomic invoicing service Items. This function also updates products data are modified after initial sync. Supports variable products.
4.	Manual sync of all Shipping methods (excluding the additional cost for flat_shipping) from WooCommerce to e-conomic invoicing service dashboard.
5.	Sync Order, Products, Customers to e-conomic when Order status is changed to 'Completed' at WooCommerce->Orders Management section.

== Plugin Requirement ==

*	PHP version : 5.3 or higher
*	WordPress   : Wordpress 3.8 or higher

== Installation ==

1.	Install WooCommerce e-conomic Integration either via the WordPress.org plugin directory, or by uploading the files to your server
2.	Activate the plugin in your WordPress Admin and go to the admin panel Setting -> WooCommerce e-conomic Integration.
3.	Active the plugin with your License Key that you have received by mail and your e-conomic API-USER ID.
4.	Configure your plugin as needed.
5.	That's it. You're ready to focus on sales, marketing and other cool stuff :-)

== Screenshots ==

1.	*General settings*

2.	*Manual Sync function*

3.	*Support*

4.	*Welcome Screen*

Read the FAQ or business hours mail support except weekends and holidays.

== Frequently Asked Questions ==

http://wooconomics.com/category/faq/

== Changelog ==

= 1.1 =
* Improvements & Issue fixes

= 1.0 =
* Initial Release