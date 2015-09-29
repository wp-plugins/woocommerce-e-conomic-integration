=== WooCommerce e-conomic Integration ===
Contributors:      WooConomics
Plugin Name:       WooCommerce e-conomic Plugin
Plugin URI:        www.wooconomics.com
Tags:              WooCommerce, Order, E-Commerce, Accounting, Bookkeeping, invoice, invoicing, e-conomic, WooCommerce, order sync, customer sync, product sync, sync, Customers, Integration, woocommerce e-conomic integration, woocommerce integration, economic integration, woocommerceeconomic, wocommerce economic, woocomerce economic, wocomerce economic
Author URI:        www.wooconomics.com
Author:            wooconomics
Requires at least: 3.8
Tested up to:      4.3
Stable tag:        1.9.2
Version:           1.9.2

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
	*	Inventory stock quantity (updated from e-conomic to WooCommerce)
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
3.	Automatic (and manual) sync of all products from WooCommerce to e-conomic invoicing service Items. This function also updates products data modified after initial sync. Supports variable products.
4.	Manual sync of all Shipping methods (excluding the additional cost for flat_shipping) from WooCommerce to e-conomic invoicing service dashboard.
5.	Sync Order, Products, Customers to e-conomic when Order status is changed to 'Completed' at WooCommerce->Orders Management section.
6.  Product stock quantity is imported from e-conomic to WooCommerce.

== Plugin Requirement ==

*	PHP version : 5.3 or higher, tested upto 5.5.X
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

= 1.9.2 =
* Sync Customers and Products in both direction. Added option to select sync direction.
* Bug fixes.

= 1.9.1 =
* Wordpress cron feature for product sync every hour, or twice a day, or every day added.
* Bug fixes.

= 1.9 =
* Bug fixes for fsockopen connection.

= 1.8 =
* Settings to sync orders created before wooconomic installation added.
* Bug fixes.

= 1.7 =
* Bug fix.

= 1.6 =
* Now the plugin can support guest customer checkouts and sync guest customer data to e-conomic.
* Few bug fixes done.

= 1.5 =
* Supports stock/inventory sync from e-conomic to WooCommerce.
* New option to select product sync is added in settings.
* Now supports WordPress 4.3

= 1.4 =
* Bug fixes and automatic e-conomic token access authentication added

= 1.3 =
* Sending PDF inovice for e-conomic payment checkout option added

= 1.2 =
* Option to select between order or invoice added.
* Plugin authentication method changed to Token access ID and Private App ID.
* Language support for Svenska, Dansk, Finnish, Norsk bokmål, Deutsche, Français, Polski, English and Español

= 1.1 =
* Improvements & Issue fixes

= 1.0 =
* Initial Release