=== Welcart e-Commerce ===
Contributors: uscnanbu, welcart
Tags: Welcart, e-Commerce, shopping, cart, store
Requires at least: 5.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.12.3
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

11 Sep 2026
= V2.12.3 =
-----------
* Security enhancement (Cross-Site Scripting). Escaped the output on the settlement log and the settlement error log screens, and added a client IP check to the Telecom Credit payment notification. Thanks to minhgalaxy - GalaxyOne for the report.
* Security enhancement (Missing Authorization). Added an origin check to the payment notification handler of SB Payment Service.
* Security enhancement (Missing Authorization). Restricted access to the wcsite linkage endpoint and reduced the setting information output on the administration screen.
* The purchase nonce is now required for non-gateway settlements such as cash on delivery and bank transfer, and the entry data is re-validated before an order is registered. Themes that build the checkout form without usces_purchase_button() need to output the wc_purchase_nonce field.

For older versions, please see the [Changelog Archive (Japanese)](https://www.welcart.com/about/changelog/).
