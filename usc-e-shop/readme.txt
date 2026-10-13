=== Welcart e-Commerce ===
Contributors: uscnanbu, welcart
Tags: Welcart, e-Commerce, shopping, cart, store
Requires at least: 5.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.12.5
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

13 Oct 2026
= V2.12.5 =
-----------
* Security enhancement (SQL Injection). Stopped restoring the search condition SQL from the cookie on the old order list screen. Thanks to Wordfence PRISM - Wordfence for the report.
* Security enhancement. [PayPal] The order details request of the credit card payment now returns only the order created in the current session.
* Security enhancement. The member login and the member information update on the cart page are no longer processed when the nonce verification fails while a member is logged in.
* Security enhancement. [Payment For] Added a client IP check to the card payment notification.
* Security enhancement. [ZEUS] The payment amount sent to the payment gateway is now taken from the order data instead of the request parameters.
* Security enhancement. The log of the login attempt limit is now stored in the database instead of a file.
* [Paygent] Fixed the bug that the sending process continued even if the initial setting of the communication failed, and the cause of the error was not displayed correctly.
* [Paygent] Fixed the bug that the payment status on the order list and the order edit screen sometimes did not change to the cleared status after the sales processing.
* Fixed the bug that orders were not displayed on the old order list screen when the filtered result fit on a single page.

For older versions, please see the [Changelog Archive (Japanese)](https://www.welcart.com/about/changelog/).
