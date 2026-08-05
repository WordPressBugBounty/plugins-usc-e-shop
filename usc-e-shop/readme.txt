=== Welcart e-Commerce ===
Contributors: uscnanbu, welcart
Tags: Welcart, e-Commerce, shopping, cart, store
Requires at least: 5.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.11.33
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Welcart is a free WordPress e-commerce plugin with the top market share in Japan.

== Description ==

Welcart is a free WordPress e-commerce plugin with the top market share in Japan. It offers extensive features and flexibility to help you build your own online store with ease. Compatible with PHP 7.4 to 8.3.

= SHOPPING CART SYSTEM =

Sell physical products with no limits on the number of items or categories. Manage inventory with SKU codes and configure flexible pricing and shipping options. Additional extension plugins are available to support digital content sales and subscriptions. Over 16 payment services can be added through the official Welcart website.

[Welcart Payment services (Japanese)](https://www.welcart.com/wc-settlement/)

= DESIGN =

A free responsive theme (Welcart Basic) is available, along with premium themes. You can customize the design and layout however you like.

[Welcart Theme downloads (Japanese)](https://www.welcart.com/archives/category/item/itemgenre/template/)

= MANAGING SYSTEM =

Order data is automatically saved and updated in the database. The order list page offers powerful filtering by customer information, date, product type, and more. From the order editing page, you can modify order details, send confirmation emails, download receipt PDFs, and more.

= MEMBERSHIP SYSTEM =

Welcart includes a built-in membership system with no additional plugins required. The member list page supports searching by customer information and purchase history. A point system is also available for members.

== Installation ==

= AUTOMATIC INSTALLATION =

In your WordPress admin panel, go to Plugins > Add New, search for "Welcart", and click "Install Now".

= MANUAL INSTALLATION =

Download the plugin ZIP file. Then go to Plugins > Add New > Upload Plugin in your WordPress admin panel, and upload the ZIP file to install.

= ATTENTION =

During activation, Welcart writes data to tables such as postmeta, options, and terms. If you are installing on an existing site, backing up your data beforehand is strongly recommended. Welcart is not liable for any damages resulting from the use of this plugin. All operations are performed at the user's own risk.

= RECOMMENDED ENVIRONMENT =

- WordPress 5.6 or greater
- PHP 7.4 or greater
- MySQL 5.5 or greater
- Own domain with SSL (shared SSL is not recommended)

[Server Requirements (Japanese)](https://www.welcart.com/wc-condition/)

== Frequently Asked Questions ==

Please see [Welcart Forum (Japanese)](https://www.welcart.com/community/forums).

== Screenshots ==

1. Item List page on the admin screen
2. Editing orders page on the admin screen
3. Top page (Free official theme 'Welcart Basic')

== Changelog ==

5 Aug 2026
= V2.11.33 =
-----------
* Security enhancement (Cross-Site Scripting). Follow-up to V2.11.32.
* Security enhancement (Payment Notification Spoofing).

30 Jul 2026
= V2.11.32 =
-----------
* Security enhancement (Cross-Site Scripting).
* Security enhancement (SQL Injection).
* Added an action hook that fires when a member login attempt fails.
* Fixed the width of the quantity field on the order data editing page.
* Fixed PHP errors.

2 Jul 2026
= V2.11.31 =
-----------
* [e-SCOTT / WelcartPay] Fixed a bug that caused an error with the product name in online payment collection transactions.
* Fixed a bug in the filtering of the "Order List CSV" and "Order Details List CSV" exported from the legacy order list.
* Fixed translation file issues.
* Fixed PHP errors.

30 Jun 2026
= V2.11.30 =
-----------
* [Zeus] Updated the mobile phone number validation for Buy Now Pay Later payment.
* [Paidy] Fixed parameters.
* Updated the Japan Post tracking URL.
* Changed the order list and member list downloads in the admin screen to allow selective export.
* Fixed translation file issues.
* Fixed PHP errors.

For older versions, please see the [Changelog Archive (Japanese)](https://www.welcart.com/about/changelog/).
