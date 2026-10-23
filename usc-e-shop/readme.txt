=== Welcart e-Commerce ===
Contributors: uscnanbu, welcart
Tags: Welcart, e-Commerce, shopping, cart, store
Requires at least: 5.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.12.6
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

= Where do I report security bugs found in this plugin? =
Please report security vulnerabilities found in the Welcart e-Commerce plugin to support@welcart.com. Please do not post them on the public forum.

Please see [Welcart Forum (Japanese)](https://www.welcart.com/community/forums).

== Screenshots ==

1. Item List page on the admin screen
2. Editing orders page on the admin screen
3. Top page (Free official theme 'Welcart Basic')

== Changelog ==

23 Oct 2026
= V2.12.6 =
-----------
* Security enhancement (SQL Injection). Fixed the SQL injection in the download and deletion of the payment error log. Thanks to Karthik Ramakrishnan - WPScan for the report.
* Security enhancement. The redirect destination after adding an item to the cart is now limited to the own site. Thanks to Jasper Weijts (Onvio Pentesting) - WPScan for the report.
* Security enhancement. Added the permission check to the Ajax processing of the item SKU and image on the item edit screen. Thanks to Usama Arshad - WPScan for the report.
* Security enhancement (SQL Injection). [ZEUS] Fixed the SQL injection in retrieving the payment information on the order edit screen.
* Security enhancement. Added the permission check to the Ajax processing of the item image, SKU and option on the item edit screen.
* Security enhancement. The item CSV export no longer includes private items and passwords of other users.
* Security enhancement. The return destination of "Continue shopping" on the cart page is now limited to the own site.
* Security enhancement (XSS). The values in the payment information field on the order edit screen are now escaped.
* Security enhancement. The member information update now updates only the logged-in member, and is no longer applied before the email verification.
* Security enhancement. [PayPal] The order ID used to check the order details request of the credit card payment can no longer be overwritten by the request parameters.
* Fixed the bug that the desired delivery date was not recalculated after the postal code search on Safari.

For older versions, please see the [Changelog Archive (Japanese)](https://www.welcart.com/about/changelog/).
