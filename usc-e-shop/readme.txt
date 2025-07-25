=== Welcart e-Commerce ===
Contributors: uscnanbu
Tags: Welcart, e-Commerce, shopping, cart, store
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.11.20
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Welcart is a free e-commerce plugin for Wordpress with top market share in Japan.


== Description ==

Welcart is a free e-commerce plugin for Wordpress with top market share in Japan.
Welcart comes with many features and customizations for making an online store.
You can easily create your own original online store.
This plugin has been tested with PHP versions 7.4 through 8.2.


= SHOPPING CART SYSTEM =

You can sell any type of product (physical, digital, subscriptions).
There is no limit to the number of products, item photos, and categories.
You can manage items by SKU (Stock Keeping Unit) code.
Welcart has many options for pricing and shipping.
You can apply for up to 16 different payment options (Sony Payment, Paypal, Softbank Payment etc.) on Welcart's official website.
Please refer to the link below (Japanese version).

[Welcart Payment services(Japanese)](https://www.welcart.com/wc-settlement/)


= DESIGN =
Welcart has free standard templates and themes.
You can customize the design and layout any way you like.
All themes are compliant to Wordpress standards.
Welcart provides a free responsive design theme (Welcart Basic), from the link below.

[Welcart Theme downloads(Japanese)](https://www.welcart.com/archives/category/item/itemgenre/template/)


= MANAGING SYSTEM =
Order data is automatically stored and updated in the database.
Welcart has a highly functional "order list" page, for viewing and updating orders.
You can narrow your search results by customer information, price, date, item type, etc.
You can also easily manage an order directly through its edit page, where you can edit order details, confirm the purchase, send confirmation emails, and much more.


= MEMBERSHIP SYSTEM =
Welcart has it's very own membership system, eliminating the need for any extra plugins.
Similar to the order list page, a highly functional member list page is also provided.
You can search for member data by customer information, purchase history, etc.
Member orders can be edited individually.
You can also enable a point system for Welcart members.


== Installation ==

= AUTOMATIC INSTALLATION =

In your WordPress admin panel, go to Plugins > New Plugin, search for Welcart e-Commerce for WordPress and click "Install now ".


= MANUAL INSTALLATION =

1. Alternatively, download the plugin and upload the entire usc-e-shop folder to the /wp-content/plugins/ directory.
2. Activate the plugin.


= ATTENTION =

In the process of activation of plugin, Welcart writes data on tables such as postmeta, options, and terms. When you install a blog existing already, to avoid unexpected damages or any other unexpected situation, backing up is strongly recommended.

Welcart is not responsible or does not have any guarantee for any kind of damage that you get by using or installing Welcart.
All the procedures must be done with self-responsibility of each user.


= RECOMMENDED ENVIRONMENT =

WordPress Ver.5.6 or greater
PHP 7.4
MySQL 5.5 or greater
Original domain and SSL (Shared SSL is not recommended)

Note: The server which PHP is working as safe mode is not supported.

Please refer to [Server Requirement (Japanese version)](https://www.welcart.com/wc-condition/)


== Frequently Asked Questions ==

Please see [Welcart Forum(Japanese)](https://www.welcart.com/community/forums).


== Screenshots ==

1. Item List page on the admin screen
2. Editing orders page on the admin screen
3. Top page(Free official theme 'Welcart Basic')
4. Item page(Free official theme 'Welcart Basic')


== Changelog ==

22 Jul 2025
 = V2.11.20 =
-----------
* Unified the title of the link to return to the previous page from the content confirmation page.
* Fixed PHP errors.

4 Jul 2025
 = V2.11.19 =
-----------
* Fixed a bug that prevented purchases when the total amount was 0.
* Fixed a bug where order data for multiple shipping addresses was not registered correctly.

1 Jul 2025
 = V2.11.18 =
-----------
* [Security Enhancement] Enhanced existing specifications to further prevent creation of invalid order data at checkout.
* Fixed PHP errors.

23 Jun 2025
 = V2.11.17 =
-----------
* Implemented countermeasures against object injection (Credit Card Payment Settings [Paygent]).
* Implemented countermeasures against XSS (Credit Card Payment Settings [Paygent]).

13 May 2025
 = V2.11.16 =
-----------
* Fixed a bug where credit card payment was not working.
* Fixed a bug where administrative emails could not be sent from the order data editing screen.

12 May 2025
 = V2.11.15 =
-----------
* Supported WordPress 6.8.
* Fixed an issue that error messages were not cleared when exporting the item CSV.
* Fixed a bug that allowed multiple registrations of the same option name (common options and item options).
* Fixed a bug that allowed registering item options without any common options.
* Added an index to the operation log.
* Fixed PHP errors.

30 Apr 2025
 = V2.11.14 =
-----------
* [Security Enhancement] Strengthened countermeasures against arbitrary file deletion in the bulk item registration page.
* Fixed PHP errors.

23 Apr 2025
 = V2.11.13 =
-----------
* [ZEUS] Added a fee option for post-payment settlements.
* [Paygent] Added an action hook to deposit notifications.
* [SB Payment Service] Modified the behavior so that when using the link type, credit card registration and updates from My Page are also executed in link type.
* Implemented common parts of the import feature.
* Fixed a bug that the titles in the widget list are not translated.
* Fixed PHP errors.

6 Mar 2025
= V2.11.12 =
-----------
* [Security Enhancement] Strengthened countermeasures against object injection (custom member fields, etc.).

26 Feb 2025
= V2.11.11 =
-----------
* [ZEUS] Added consent for personal information acquisition when using EMV 3-D Secure.
* [ZEUS] Added risk-based authentication parameters when using EMV 3-D Secure.
* Fixed a bug that caused email display issues when symbols were included in the name.
* Fixed PHP errors.

5 Feb 2025
= V2.11.10 =
-----------
* [Security Enhancement] Strengthened XSS protection.

21 Jan 2025
= V2.11.9 =
-----------
* [Compatibility] Added support for WordPress 6.7.
* Fixed a bug that caused payment history to not display for recurring subscription members.
* Fixed a bug that the "Display State" filter in the item list did not function.
* Fixed PHP errors.

8 Jan 2025
= V2.11.8 =
-----------
* [ZEUS] Added support for post-payment settlement.
* Fixed a bug that the customer information page in built-in templates did not support DLSeller and AutoDelivery.
* Fixed a bug that caused broken links in the details section of the order data editing page when product data was deleted.
* Fixed PHP errors.

19 Nov 2024
= V2.11.7 =
-----------
* [Compatibility] Added support for PHP 8.2.
* Fixed a bug that caused item categories not to display on the item information edit page in WordPress 6.7.
* Fixed a bug that prevented the registration or update of designated delivery time zones for shipping methods on certain servers.
* [Security Enhancement] Updated the brute force prevention feature for member login to allow specifying exclusion IP addresses.
* [Epsilon] Fixed functionality to enable the use of "3D Secure" alongside "Registered Billing."
* [ZEUS] Updated the "Secure API" feature to make the "Authentication Key" a mandatory field.

11 Nov 2024
= V2.11.6 =
-----------
* [SB Payment Service] Strengthened XSS countermeasures for improved security.

28 Oct 2024
= V2.11.5 =
-----------
* Fixed a bug that caused cookies to function incorrectly when operating with a main domain and subdomain.
* Fixed a bug that prevented "Prefecture" options from updating on the new member registration page when the "Country" selection was changed and two or more sales countries were specified.
* Fixed a bug that redirected users to the member information editing page when returning to the member page from the shipping address registration page.
* Modified the sidebar to avoid displaying "No Category" when there are no categories in "Product Categories."
* Added hooks.

1 Oct 2024
= V2.11.4 =
-----------
* Added hooks.
* Fixed a bug that caused jQuery script errors.

17 Sep 2024
= V2.11.3 =
-----------
* Added hooks.
* Fixed PHP errors.

3 Sep 2024
= V2.11.2 =
-----------
* [Security Enhancement] Improved security on the operation log page.
* [Security Enhancement] Improved security on the shipping settings page.
* Added hooks.
* Fixed PHP errors.

26 Aug 2024
= V2.11.1 =
-----------
* Fixed a bug that the "Register/Change Credit Card" button did not function when "Email Verification for Member Information Editing" was disabled.
* Fixed a bug that two "Edit Member Information" links were displayed when "Email Verification for Member Information Editing" was enabled using the built-in template.
* [Paygent] Fixed a bug that the transaction history was not displayed in chronological order.
* [Paygent] Added "Merchant Transaction ID" to the transaction history.

20 Aug 2024
= V2.11 =
-----------
* [Security Enhancement] Added an optional email verification feature when editing member information.
* [Security Enhancement] Added an optional feature to send an email to members upon login.
* [SB Payment Service] Added support for Rakuten Pay V2.
* Changed the specification to display thumbnails in the product image file tab.

19 Aug 2024
= V2.10.6 =
-----------
* Fixed JavaScript errors.
* Added hooks to the operation log in the admin page.
* Added hooks for PDF output.

16 July 2024
= V2.10.5 =
-----------
* [WelcartPay] Fixed a bug where "card name" was not passed on credit card renewal when using 3DS.
* Fixed a bug that the "description" of no-image images becomes the product detail text.
* Changed the specification to hide the "Delete" link in the order list and member list by default.
* Added hooks after updating member information on the admin page.

26 June 2024
= V2.10.4 =
-----------
* [e-SCOTT/WelcartPay/Metaps Payment] Added 3DS authentication parameters for card payments.
* [ZEUS] Fixed some bugs with 3DS authentication for card payments and corrected error messages.
* [Metaps Payment] Fixed a bug with user ID payments.
* [Metaps Payment/ROBOT PAYMENT] Fixed a bug where payment information was not displayed.
* Fixed a bug where an error occurred when returning via the "Back to List" link after updating order data.
* Fixed a bug where the stock status style was not applied in the order data editing page.
* Added hooks.
* Fixed PHP errors.

8 May 2024
= V2.10.3 =
-----------
* [Paygent] Fixed a bug that "0" (within the day) cannot be set for the "Payment Due Date" of ATM payments.
* [WelcartPay/e-SCOTT] Changed message for card payment errors.
* Added hooks.

16 Apr 2024
= V2.10.2 =
-----------
* Fixed a bug that alternate text for product images cannot be saved.
* Fixed a bug that codes are changed when specific strings are inserted in item codes and item names.
* [Paygent] Fixed a bug that credit card registration/update cannot be completed on "My Page" when the link type is "Paygent".
* Fixed a bug that caused shipping charges to be added when download/service items are bundled in the shopping cart with items for which shipping charges are individually billed when using DL Seller.
* Added various hooks.

2 Apr 2024
= V2.10.1 =
-----------
* Fixed a bug that management order mail in text format cannot be sent.
* Fixed a bug that the code is changed when a specific string is inserted in the SKU code.
* Corrected update plugin notification content.

1 Apr 2024
= V2.10.0 =
-----------
* Enhanced security for nonce authentication when accessing the edit pages from each of the order list and member list.
* Fixed a problem with Welcart's unique permissions, "Editor (no configuration permissions)" and "Editor (no management permissions)," and corrected them to work correctly.

12 Mar 2024
= V2.9.14 =
-----------
* Fixed feature to change Google Recapcha scores.
* Fixed a bug that "Shipping and Payment Method Page" cannot be proceeded when Google Recapcha is enabled.
* Fixed a bug that enrollment completion notification in HTML email format is not displayed correctly for some email addresses.
* Added hook to change Reply-to in order report emails
* Fixed a bug that Kuroneko Yamato's package tracking URL is not displayed correctly when a hyphen is included in the waybill number.
* Fixed a bug that the link to the package tracking URL is not displayed correctly when there are multiple waybill numbers in the order list.

28 Feb 2024
= V2.9.13 =
-----------
* [SB Payment Service] Fixed a bug that PHP error is output when using WCEX DLSeller in combination with card payment.
* Fixed a bug that some HTML mail formats were not breaking lines.
* Changed URL for Kuroneko Yamato package tracking
* Fixed a bug that the radio buttons in the "Display Mode" of the Basic Settings behave incorrectly.

20 Feb 2024
= V2.9.12 =
-----------
* Enhanced referrer checking on content confirmation pages.

19 Feb 2024
= V2.9.11 =
-----------
* Added optional value for credit card security measure.
* Enhanced referrer checking on content confirmation pages.
* Applied Google reCAPTCHA to shipping and payment method pages. ( when working in system settings )
* Added a Reply-to to the email.
* [DSK] Fixed a bug that caused a Warning error in convenience store payments.

23 Jan 2024
= V2.9.10 =
-----------
* [PayPal] Fixed a bug that the payment fails when the processing category is credit (AUTHORIZE) for credit card payment.
* [REMISE] Fixed a bug in which the text on the order completion page is incorrect when multiple payments are made.
* Add a hook to the payment history for automatic recurring billing.

21 Dec 2023
= V2.9.9 =
-----------
* Fixed a bug in the PDF of the standard tax rate form where the amount subject to the tax rate is incorrect when the tax is not included.

20 Dec 2023
= V2.9.8 =
-----------
* [REMISE] Compatible with Carrier Payments and PayPay Payments.
* Added a link to move from the item name in the order edit page to the item information edit page.
* Fixed a bug in the radio button of the payment module in the credit card payment setup page.
* Fixed a bug in the PDF of the standard tax rate form where the amount subject to the tax rate is incorrect when the tax is not included.
* Fixed PHP errors.

22 Nov 2023
= V2.9.7 =
-----------
* Countermeasures against arbitrary file uploads by subscribers.
* Fixed a bug that only the heading was displayed even if the payee information for bank transfers was not set.
* Fixed a bug that caused the radio buttons in the "Display Mode" of the Basic Settings to behave incorrectly.

15 Nov 2023
= V2.9.6 =
-----------
* Countermeasures against PHP object injection.
* Fixed a bug that the cart page (member page) link does not respond when pressed during the preview of the Customizer.

9 Nov 2023
= V2.9.5 =
-----------
* CSRF Countermeasures.
* Countermeasures against arbitrary file uploads by subscribers.
* Countermeasures against unauthenticated PHP object injection.
* Countermeasures against reflective XSS.
* Removed deprecated constants from WordPress 6.4.

24 Oct 2023
= V2.9.4 =
-----------
* Security enhancements (possible SQL injection).
* Changed attributes of units in SKU table.
* Fixed a bug that Welcart information is not displayed.
* Fixed a bug that the item list, old order list, and old member list cannot be reordered.
* Added hooks.

12 Oct 2023
= V2.9.3 =
-----------
* Fixed a bug that the order list and member list cannot be sorted.

10 Oct 2023
= V2.9.2 =
-----------
* Enhanced security. (product list page, order list page, member list page)

5 Oct 2023
= V2.9.1 =
-----------
* [Paidy] Fixed "Paidy Settlement Rakuraku Setup".
* Enhanced security. (cart-related pages, member-related pages)
* Fixed PHP errors.

3 Oct 2023
= V2.9 =
-----------
* [Paidy] Implemented "Paidy Settlement Rakuraku Setup".
* Fixed a bug that caused the tax-qualified amount to be output on all pages in the PDF of the standard tax rate form.
* Corrected items in purchase history on My Page.
* Fixed PHP errors.

25 Sep 2023
= V2.8.23 =
-----------
* Support for invoices (Qualified Invoice Form).
* [PayPal] Modified to allow the prefix to be specified when using the card information saving feature for credit card payments.
* Fixed a bug that inventory data is not updated when changing from managed orders to estimates in the order edit page.
* Added a hook to change the time period of the purchase history on My Page.
* Added a hook for checking when adding SKUs on the item edit page.

14 Sep 2023
= V2.8.22 =
-----------
* Enhanced security ( Item List page and Credit Card Payment Setup page and Order Data Edit page).
* Restricted the types of attachment emails that can be sent from the order data edit page.

28 Aug 2023
= V2.8.21 =
-----------
* [PayPal] Fixed the bug that cart data is not created during automatic order processing for subscription purchases.
* [SB Payment Service] Support for re-payment when automatic billing fails due to unsaved card information.
* [SB Payment Service] Fixed the bug that caused an error in updating the recurring billing member information page.
* [Paidy] Corresponded to inconsistency of transaction IDs in v2.8.2 and lower versions.
* Fixed the bug that the password field in the login widget does not get masked.
* Fixed instance variable for USCES_VERIFY_MEMBERS_EMAIL().

7 Aug 2023
= V2.8.20 =
-----------
* [SB Payment] Fixed the bug that the credit card cannot be updated from My Page.
* Fixed a bug in the display of related products in the built-in template.
* Changed some JavaScript loading to wp_enqueue_script.
* Added hooks.
* Fixed PHP error.

6 Jul 2023
= V2.8.19 =
-----------
* [PayPal] Fixed the bug that normal order data is created even when the status is not normal for credit card payment.
* Fixed the bug that the invoice number is not displayed correctly in the HTML email shipment completion email.
* Fixed to not display "Expired" for order data that has been manually set to "Paid".
* Fixed to save the date and time of payment for convenience store payments.
* Fixed to output option information in receipt PDF.
* Fixed filtering period for purchase history on My Page.
* Added hooks.
* Fixed PHP error.

16 May 2023
= V2.8.18 =
-----------
* [PayPal] Implemented credit card payment 3DS.
* [PayPal] Fixed the bug that PayPal credit card payment cannot be completed on iPhone.
* Fixed the bug that updating order data is not included in the "Payment Status", "Not Paid", and "Equals to" filtering in the order list.
* Fixed the bug that almost blank pages are inserted in PDFs of delivery slips and other documents with reduced tax rates.
* Fixed the bug that common options are not selectable on the item information edit page.
* Fixed the bug that the order of SKUs is rearranged when deleting SKUs on the item information edit page.
* Revised the time period for narrowing down the purchase history on My Page.
* Correct the spelling of the email reply template on the English version of the site.

19 Apr 2023
= V2.8.17 =
-----------
* Fixed the bug that the tax rate is displayed on the "Internal Sales Tax" label of the email when using the reduced tax rate.
* Fixed the bug that a file cannot be attached to an email sent from the order edit page.
* Fixed the bug that an error alert appears on the order edit page and order data cannot be updated.

12 Apr 2023
= V2.8.16 =
-----------
* Fixed the bug that an e-mail to the administrator is not sent when multiple e-mail addresses are specified for orders.
* Fixed the bug that order data using points cannot be updated in the order edit page.

11 Apr 2023
= V2.8.15 =
-----------
* Changed the specification to display the tax rate in the label "Sales Tax" in various emails and PDF files.
* Fixed the bug that consumption tax amount in past order data changes when the consumption tax rate is changed.
* Modified to output common options in system environment information.
* Fixed to prevent members from using more points than they have in their account on the order data edit page.
* Fixed to not display the Cancel button on the My Page for members who are still on auto-renewal billing.
* [ZEUS] Added "2 payments/bonus payment" in the number of payments (*Option application required).
* Added "Republic of Namibia" to "Countries for Sale".
* [Paidy] Fixed the bug that the payment dialog does not appear when the shipping cost is 0 yen.

15 Mar 2023
= V2.8.14 =
-----------
* Implemented function to prevent credit master attacks.
* [Paidy] Fixed the bug that order data is created even if the payment is rejected.
* Changed the specification to prevent the registration of item images when adding a new item.
* Changed the specification to prevent SKUs for SKU select from being generated when adding new products.
* Fixed the bug that a duplicate error occurs when trying to register (or update) multiple SKU codes that contain the same numerical value.
* Fixed the bug in usces_confirm_tax() when "Sales tax subject: total amount”.
* Feedback: Fixed the bug that in some cases, the "Arrival Date" select field does not change to match the delivery address when "Specify a different delivery address" is selected.
* Feedback: Fixed the bug that SKU data is duplicated when uploading all items in CSV format.

22 Feb 2023
= V2.8.13 =
-----------
* Feedback: Revised the wording "(*) is subject to the reduced tax rate" in PDFs of delivery documents when the reduced tax rate is applied to be right-aligned.
* Feedback: Fixed the bug that in some cases, the "Arrival Date" select field does not change to match the delivery address when "Specify a different delivery address" is selected.
* Added the data attribute to the cart table.

14 Feb 2023
= V2.8.12 =
-----------
* Fixed the bug that the unit price after discount of business package discount is displayed incorrectly when the currency display is other than Japanese yen (e.g., U.S. dollars).
* Added "Method of Payment" to receipt PDF.
* Fixed the bug that points are not given when purchasing products with SBPS carrier payment.
* Revised the wording "(*) is subject to the reduced tax rate" in PDFs of delivery documents when the reduced tax rate is applied to be right-aligned.
* Fixed the bug that credit card renewal notification emails are not sent when Google reCAPTCHA is enabled.
* Fixed the bug that item, sku, and opt data are not deleted even after completely deleting items from the recycle bin.
* Fixed the bug that data with NULL product code is registered when updating a post.
* Moved "Operation Log Saving Period" from the System Settings screen to Extended Functions > Operation Log.
* Fixed the bug that in some cases, the "Arrival Date" select field does not change to match the delivery address when "Specify a different delivery address" is selected.
* Fixed the bug that SKU data is duplicated when uploading all items in CSV format.

23 Jan 2023
= V2.8.11 =
-----------
* Fixed inadequate escaping.

27 Dec 2022
= V2.8.10 =
-----------
* Fixed the bug that displayed "out of stock" even though the item is in stock.
* Set the memory limit for 2.7 data update to 150M

23 Dec 2022
= V2.8.9 =
-----------
* Fixed attribute validation and escaping deficiencies in shortcodes.

15 Dec 2022
= V2.8.8 =
-----------
* Fixed the file missing.

15 Dec 2022
= V2.8.7 =
-----------
15 Dec 2022
* Fixed the bug in the display of option values in the order list.
* [Paygent] Fixed the bug that the link cannot be opened from the payment URL in the order confirmation e-mail from the management screen for convenience store payment.
* [ZEUS] Changed the transfer name for bank transfer payments to an input field.
* [ZEUS] Fixed the height of the 3DS Challenge authentication screen.
* [WelcartPay] Fixed the bug in DL Seller's recurring billing member information screen that the display is corrupted when "No card is registered".
* [PayPal] Fixed to not output "GET ORDER ERROR" to the payment error log.
* Fixed "is_purchased_item()" to exclude cancelled order data when retrieving purchase history.
* Added optional switch to enable/disable operation logging.

= V2.8.6 =
-----------
2 Dec 2022
* Fixed the vulnerability that allowed reading of arbitrary files. (2.8.5 feedback)

= V2.8.5 =
-----------
30 Nov 2022
* Fixed the vulnerability that allowed reading of arbitrary files.
* Fixed the bug that incorrect arrival date and time options are displayed on the shipping and payment method page.
* Fixed the bug that payment method cannot be selected when adding service items to the cart in DL Seller.
* [e-SCOTT] Fixed the PHP error output in the payment notification of the online payment service.
* Changed the class of the administration page > business day settings > business day calendar.
* [ZEUS] Fixed the bug that the last 4 digits of the card number are displayed in the member data edit when QuickCharge is "not used".
* Fixed re-setting of changed values in My Page when using Custom Customer & Custom Member fields.

= V2.8.4 =
-----------
16 Nov 2022
* Enhanced XSS and CSRF countermeasures, enhanced privilege checking, and sanitization.
* [PayPal] Fixed the bug that the credit card payment input form doesn't work.

= V2.8.3 =
-----------
31 Oct 2022
* Changed handling of SKU data in all items CSV of item master.
* Fixed to only update in case post_id is item data when registering products in bulk.
* Fixed to prevent duplicate item codes when registering/updating items on the item information edit screen.
* Changed specifications so that purchases cannot be made if the administrator deletes the * membership information of a purchaser who is still logged in.
* Fixed the error when clicking on "Update modules to use".

= V2.8.2 =
-----------
20 Oct 2022
* Changed the initial state of narrowing down the purchase history on My Page.
* Fixed "get_current_screen()" return value not to be rewritten in operation log.
* [PayPal] Fixed the bug that card information cannot be input on the credit card renewal page.
* [PayPal] Changed the specification to avoid error processing even if "GET ORDER ERROR" occurs in Wallet payment.
* [Paidy] Changed the action to be taken when order data is not captured.
* Fixed errors in PHP 8.0.

= V2.8.1 =
-----------
27 Sep 2022
* Fixed the bug that the rules and checks for member passwords are not matched.
* Fixed the bug in updating 2.7 data.
* Fixed the bug that line breaks in renewal request emails are not reflected when using WelcartPay with DL Seller.

= V2.8 =
-----------
16 Sep 2022
* Compatible with PHP8.
* [WelcartPay] Fixed the bug that ATODENE CSV files could not be downloaded.
* [WelcartPay] Fixed the bug that the CSV upload of credit check results could not be uploaded when using ATODENE.
* Fixed the bug that tag information is not copied when product information is copied using "Copy and New".
* Fixed the bug that the order of item options is changed after deleting an item option.
* Fixed the bug with the "Recalculate" button on the order data edit page.
* Added a filtering function to the purchase history on My Page.
* Minor adjustments to the Advanced Cash on Delivery dialog.
* Implemented the function of automatic update when minor updates are released.

= V2.7.8 =
-----------
2 Sep 2022
* Vulnerability has been fixed.
* There was a risk of being able to read arbitrary files.
* The vulnerability affects Welcart 2.6.0 to 2.7.7.

= V2.7.7 =
-----------
2 Sep 2022
* [ZEUS] Fixed the bug that "SESSION ERROR" is output to the error log when credit card payment is made by Secure Link.
* [e-SCOTT]  [WelcartPay] Fixed the bug that causes K31 error when using 3DS.
* Changed the specification to open the invoice number link in a new tab.

= V2.7.6 =
-----------
2 Sep 2022
* Fixed the bug that caused errors in PHP 7.0-7.2 environments.

= V2.7.5 =
-----------
26 Aug 2022
* [e-SCOTT] Fixed the bug that the online payment agent does not receive a payment notification even if the payment has been received.
* [WelcartPay] Changed the transition of the payment screen when "Change card and purchase" with external link type.
* [WelcartPay] Fixed the bug that the page does not open with the payment URL in the order confirmation e-mail from the administration screen when using convenience store payment.
* [WelcartPay] Fixed the bug that the error message is displayed in the credit card registration of My Page when 3DS authentication is used.
* [Paidy] Added handling of order data when it is not received.
* [REMISE] Fixed the bug that the card information remains displayed even after canceling PayQuick.
* Fixed the bug that the validation check is not checked for the shipping phone number on the shipping and payment method page.
* Fixed the bug with two slashes in the initial settlement module path.
* Fixed the bug that "We are sorry, we are unable to process your order" is displayed at the front page when SKU code is only numbers and delimited by ".".
* Changed member_history logic.
* Changed the specification to apply the business package discount when editing order data.
* Fixed the bug in the conditional branching of whether or not an item has been purchased in the past.

= V2.7.4 =
-----------
8 Aug 2022
* [ZEUS] Fixed the bug that 3DS2.0 does not work properly.
* [ZEUS] Fixed the bug that the style of the shipping and payment page is broken on mobile phones.
* Fixed the bug that the front-end display is still in Japanese even if the front-end language is set to a language other than "ja".
* Fixed the bug that "next contract renewal date" and "next billing date" are not displayed in the body of billing date notification email and contract renewal date notification email when using text mail with DL Seller.

= V2.7.3 =
-----------
3 Aug 2022
* [ZEUS] Fixed the bug that the credit card payment cannot be completed on a smartphone.
* [ZEUS] Fixed the bug that the order data is not created when the payment is made by bank transfer and SESSION EMPTY is displayed.
* Improved 2.7 data updating
* [WelcartPay] Fixed the bug that the style of "Provide personal information to a third party" dialog on "My Page" is broken when using the external link type.
* [Paidy] Added description of credit card payment setup screen.

= V2.7.2 =
-----------
29 Jul 2022
* [Epsilon] Compatible with 3D Secure 2.0
* [Paidy]  Added billing information when a different shipping address is selected.
* [WelcartPay] Changed to display the "Providing Personal Information to Third Parties" dialog box even for external link types.
* Fixed the bug that the advance data of SKU is object type.

= V2.7.1 =
-----------
26 Jul 2022
* Fixed the bug that the card payment cannot be completed for some payments.
* [Zeus] Fixed the bug that the default number of payments is not set to "Lump-sum payment".
* Fixed the bug that data is missing in item information edit when the structured data option is turned on.

= V2.7 =
-----------
25 Jul 2022
* Changed the item database specification.
* Stopped bundling Welcart Default Theme.
* Fixed the bug that images are not displayed properly when the new product image feature is turned off.

= V2.6.11 =
-----------
25 Jul 2022
* [ZEUS] Compatible with 3D Secure 2.0
* [Paidy] Added payment module
* [e-SCOTT and WelcartPay] Compatible with 3DS 2.0
* [SB Payment] Compatible with 3D Secure 2.0
* [Epsilon] Added PayPay payment
* Fixed the bug that the information in the Custom Delivery field specified in "Specify a different delivery address" remains after the field is changed to "Same as Customer Information".
* Fixed Notice error in "Issue Receipt".
* Fixed the bug that "Register New Product Image" is not displayed in the system environment information.
* Fixed the bug that the return value of hook_usces_filter_sku_meta_title1 contains an escape process.
* Fixed the bug with "decorated order id".

= V2.6.10 =
-----------
27 Jun 2022
* Fixed the bug that the batch operation "Change Stock Status" in the product list doesn't change the value without reloading the list.
* Fixed the bug that inventory of the same SKU with different option values is not subtracted correctly.
* Fixed the bug that the error message after the completion of the check mode of "Batch Product Registration" is not correct for the selected item type.
* [ZEUS] Fixed the bug that "Update" on the continuous billing member information screen doesn't work.
* [Paygent] Fixed the bug that "Update" on the continuous billing member information screen doesn't work.
* Fixed the bug that the display columns of product code, product name, SKU code, SKU name, option name, and option value are out of alignment when searching in the product information items of the order list.
* Changed specifications to enable updating of "alternate text" and "caption" in the image block of the item information edit in the administration screen.
* Added Post ID as a search criterion to narrow down the item list.
* [PayPal] Change of URL for inquiries.

= V2.6.9 =
-----------
31 May 2022
* Fixed the bug that e-commerce information is not taken when Google Analytics for WordPress by MonsterInsights version 8.5 or higher is used.
* Fixed the bug that values of admin/member fields are not saved in the member edit page of the admin page.

= V2.6.8 =
-----------
25 May 2022
* Compatible with the Revised Specified Commercial Transactions Law.
* Changed specifications to display "Response Status," "Payment Status," and "Invoice Number" in the purchase history on My Page.

= V2.6.7 =
-----------
13 May 2022
* Fixed the bug that the value is corrupted when CSV batch registration is performed when "comma (,)" is used for the select value of the item option.
* Fixed the bug that the "Initial Point Ratio" is not reflected as the initial value at the time of item registration even though the "Initial Point Ratio" is set in the Basic Settings.
* Fixed the bug that backward matching error occurs when acquiring payment logs.
* Fixed the bug that "Delete Completely" link and "Restore" link are not displayed in the list of items in the display state "Trash" after placing an item in the Trash.
* Changed the specification to link to the shipping company's tracking URL as an anchor to the invoice number displayed in the order list.

= V2.6.5 =
-----------
20 Apr 2022
* Fixed the bug that CSV is not imported correctly in the item bulk registration (custom field items).
* Changed the specification that the date format check is not checked when "including" is selected in the narrowing function of the order list.

= V2.6.4 =
-----------
19 Apr 2022
* Fixed the bug that caused display errors due to inconsistencies in item metadata.
* Changed the specification to delete an itemmeta once when multiple itemmeta exist when updating an item.
* Fixed the bug that Notices are displayed in php8.
* Fixed other minor Notices.

= V2.6.3 =
-----------
12 Apr 2022
* Fixed the bug that an eyecatch image cannot be registered or deleted when registering a new product image.
* Changed specifications to display "Payment Status" and "Invoice Number" in the purchase history on the member information edit page of the administration page.
* Fixed the bug that the product batch registration (inventory items) is not updated properly.
* Fixed the bug that the paging function doesn't work on the order list.
* Fixed the bug that the value of the custom field (wccs_XXXXX) is not displayed on the product detail page.

= V2.6.2 =
-----------
8 Apr 2022
* Fixed the bug that the product name is not displayed when adding a new product to the details in the order data edit page.
* Fixed the bug that the total amount subject to standard tax rate, standard tax rate consumption tax amount, total amount subject to reduced tax rate, and reduced tax rate consumption tax amount are not correct on the content confirmation page.

= V2.6.1 =
-----------
5 Apr 2022
* Fixed the bug that update notifications for WCEX extension plug-ins are not displayed.
* Fixed the error in $usces->get_post_custom() function.
* Fixed the bug that category count does not work well in some cases with PHP error & product CSV registration.

= V2.6 =
-----------
4 Apr 2022
* Changed the specification of product image registration.
* Migrated product-related functions to new specifications.
* Implemented a database update function.
* Fixed the progress bar to display correctly when uploading product CSV.
* Fixed the bug that the check box/radio button selection display appears unintentionally in the Custom Order Field column on the order data edit page.

= V2.5.8 =
-----------
28 Mar 2022
* [WelcartPay] Security measures for updating card information on My Page.
* [ESCOTT] Fixed the bug that deprecated warning of capture_session_meta in My Page.
* Changed the specification that a confirmation message is displayed when deleting a SKU.
* [PayPal] Added a process after Capture Order failure.

= V2.5.7 =
-----------
14 Mar 2022
* Fixed the bug that the edited contents are not reflected in the e-mail when sending an e-mail manually from the order data edit screen when using HTML e-mail and editing in the editor (text type).
* Fixed the bug that bank transfer and cash on delivery are not displayed in the payment type of payment method.
* Fixed the bug that order ID cannot be retrieved on the order completion page.
* Fixed the bug that the default value of email template is not inserted in newly installed Welcart.
* [Paygent]  Fixed the bug that the shipping address parameter of Paidy set to the incorrect item.
* Fixed the bug that points are added when the order status is changed by batch operation of the order list.

= V2.5.5 =
-----------
1 Mar 2022
* Fixed the bug that "object from empty value" error appears in the admin panel in some environments.
* [PayPal] Supporting the timeout that occurs in API connection.
* Implemented Google reCAPTCHA on the credit card renewal page in My Page.
* Changed the screen to page 403 instead of "Security check5" when the specified number of errors is reached when the brute force function is enabled.
* Changed the specification of the inquiry email address in the store settings of the basic settings from a required field to an optional field.
* Changed the phrase "number of desired delivery dates" in the shipping settings to "number of desired arrival dates" to match the front page.

= V2.5.4 =
-----------
15 Feb 2022
* Changed the specification to display the information in the past order data even if the custom order field is deleted.
* Changed the field label of "Furigana" in the customer information entry form from Kanji to Katakana.
* [PayPal] Changed the specification to get the settlement amount from the order amount when re-settling.
* [PayPal] Fixed the bug that the automatic recurring billing cannot be re-settled when there is a settlement error.

= V2.5.3 =
-----------
7 Feb 2022
* Fixed the bug that the coupon code is not output to the order data CSV.
* [PayPal] Fixed the bug that member data cannot be deleted from the administration screen when recurring billing is being performed by PayPal.
* Fixed the bug that the Notice error occurs in the system settings screen.
* Fixed the bug that the "object from empty value error" appears in the administration screen in some environments.

= V2.5.2 =
-----------
31 Jan 2022
* Fixed the bug that All In SEO Pack items are not displayed in the item edit page.
* [PayPal] Fixed the bug that credit card payment cannot be completed when not logged in as a member.
* Fixed the bug that some parts are not translated.

= V2.5.0 =
-----------
26 Jan 2022
* [PayPal] Added the Credit Card Payment to PayPal Payment.
* Added the function to add a custom field for administrator use only to member information.
* Added the option switch to include or exclude shipping fees from the amount of payment fees for convenience store fees, etc.
* Changed the specification that only payment modules are enabled that are set to "Use" in the credit card payment module selection.
* [WelcartPay] Fixed the bug that the normal process is not saved when reprocessing after a payment error in subscription purchases.
* Fixed the bug that Google Analytics for WordPress by MonsterInsights doesn't convert when the version is 8.0 or higher.
* Fixed the bug that an error occurs on the product information editing screen in WordPress 5.9 environment.
* Changed the specification of the redirect URL for errors when adding items to the cart to include the port number.
* Fixed the bug that an error message is displayed and member registration cannot be done on the new member registration page when reCAPTCHA is enabled in Welcart extension.

= V2.4.2 =
-----------
11 Jan 2022
* Fixed the specification that the message is displayed if none of the shipping method items are checked when registering an item.
* Fixed the bug that when searching the old order list by specifying the period (start to end), the values of start to end are reset every time.
* Added the option of cool classification to the shipping method.
* Changed the specification of the Order List screen to display checks for each PDF output and for each management mail sending.
* Fixed the bug that when an item with a status of "Cancelled" is set to "Shipped" in the batch operation, it becomes a new acceptance.
* Fixed the bug that some data is not included in the search results when searching by specifying "Order Information Item: Order Date" in the order list.
* Display "Structured Data" in System Environment Information.
* [ZEUS] Added parameters to the amount change API.
* [SBPS] Fixed the bug that credit card payment doesn't return to the website after canceling 3DS authentication.

= V2.4.1 =
-----------
8 Dec 2021
* [ZEUS] Fixed the bug that the Secure API payment doesn't work.

= V2.4 =
-----------
7 Dec 2021
* Compatible with structured data.
* [ZEUS]Compatible with management screen linkage, automatic recurring billing.
* Fixed the issue that the order list loading speed is slow when using WCEX Auto Delivery.Auto Delivery needs to be updated.
* Standardized the descriptions of shipping and delivery.
* Changed the specification of the way "customer name" is displayed in the mail options.Changed the specification to display the customer name in the specified location instead of the beginning when {customer_name} is entered in the header, by using the replacement string {customer_name} for the mail.
* Changed the specification of the "Frequently Used Items" category in the category field of the item information edit page to enable checking and registration of other categories besides the item category.

= V2.3.5 =
-----------
17 Nov 2021
* Fixed the bug where "<br>" is inserted after the customer name in the thank you email.

= V2.3.4 =
-----------
11 Nov 2021
* Changed the specification to display the tracking screen with one click by passing the tracking number to the package tracking URL of each shipping company.
* Fixed the bug that HTML tags can be included in the custom field of all items in the product batch registration.
* Fixed the bug that an error occurs on the Edit Product Information and Add New Product screens under specific conditions when using ACF.
* Fixed the bug that the titles of tax rate categories in CSV of orders and CSV of order details are wrong when the tax rate is reduced.
* Fixed untranslatable parts of the system settings screen.
* Added a hook to change the initial slug of the item category.
* Implemented an update checker for dedicated themes.
* Changed the way of displaying "Customer Name" in Mail Options.

= V2.3.3 =
-----------
6 Oct 2021
* Fixed the bug that the inventory count does not become zero when purchasing.

= V2.3.2 =
-----------
30 Sep 2021
* [Paygent] Renewal of the payment module (Automatic recurring billing and  Subscription payment support)
* [ZEUS] Fixed the bug that quick charge cannot be registered when you register your credit card from My Page.
* [SBPS] Fixed the bug of error judgment in subscription automatic order data.
* [SBPS] Fixed the bug that payment error details are not output in the automatic recurring billing result processing email.
* [PayPal] Changed the function to display an alert about the error that the payment dialog disappears on the content confirmation page.
* [PayPal] Fixed the bug that cancels the billing agreement ID when the first payment for auto recurring billing and subscription fails.
* Fixed the bug in the Order List: If you select a shipping method and search with the "Do not include" condition, the results will be narrowed down to those that are equal to the selected shipping method.
* Fixed the bug that the new mail option is not displayed in the system environment information.
* Fixed the bug that the information set in the membership completion notification email was also displayed in the cancellation completion email.
* Fixed the bug that the Google reCAPTCHA extension sometimes gives a "Verification error" and cannot register new users.
* Fixed the bug that postal codes are registered as double-byte numbers.
* Fixed the bug that "Customer Name" is not displayed in the thank you mail even if it is set to " Display" in the mail settings.
* Implemented cache for some queries.
* Compatible with PHP7.4.

= V2.3.1 =
-----------
26 Aug 2021
* Fixed the bug that the consumption tax amount is displayed one yen higher when the basic setting is "Applicable tax rate: Standard tax rate" and "Consumption tax calculation: Rounding up".
* Fixed the bug that payment error emails may not be sent to members when there is a payment error for subscription purchases.
* Fixed the bug that br tags are inserted when previewing with HTML mail format option.
* Fixed the bug that " Internal sales tax" was not displayed in the order report email and thank you email.
* [ZEUS] Fixed the bug that order data may not be created even if payment is successful.
* [WelcartPay] Fixed the bug that the "Subscriber Name" in the auto-renewal billing processing email did not include the name of the subscriber.
* [SBPS] Fixed the bug that payment errors for subscription purchases are not displayed in the order list.
* [SBPS] Fixed the bug that the continuous billing history is not displayed correctly after the payment is processed in the payment information dialog of the continuous billing member information page.
* [Paygent] Fixed the bug that the certificate file can not be uploaded.

= V2.3.0 =
-----------
11 Aug 2021
* Added the function of the HTML mail option.
* Renewal of the system settings page.
* Renewal of the mail settings page.
* Fixed the bug that the style is broken if the [USER_AGENT] in the order data edit page is too long.
* Fixed the bug that error messages don't appear when exceeding the maximum number of purchases.
* [e-SCOTT/WelcartPay] Fixed the bug that some data is not displayed in the payment information of the order data edit page when the PayPal payment is enabled.
* Fixed the bug that the product cannot be registered correctly if the custom field contains HTML tags in the case of batch registration of all items.
* Fixed the bug that the cart becomes empty after email verification when using Safari on iPhone.

= V2.2.8 =
-----------
31 Jul 2021
* Fixed a vulnerability.

= V2.2.7 =
-----------
20 Jul 2021
* Fixed the problem that a PHP Notice error appears on the Appearance > Widgets screen in WordPress 5.8.
* Fixed the bug that the filtered state is canceled when doing a bulk operation on the member list or order list.
* Fixed the bug that the inside consumption tax amount is displayed one yen more than it should be when the basic setting is "Applicable tax rate: Standard tax rate" and "Consumption tax calculation: Rounding up".

= V2.2.6 =
-----------
15 Jul 2021
* [Paygent] Renewal of the payment module (addition of functions for linking to the management screen, etc.)
* [e-SCOTT/WelcartPay] Fixed the bug that some of the payment information in the order data edit screen is not displayed when PayPal payment is enabled.
* Fixed the bug that newly installed extensions are not displayed in the system environment information.
* Fixed the bug that the warning error appeared in version 2.2.5 in the environment that does not use credit card payment.

= V2.2.5 =
-----------
1 Jul 2021
* [PayPal] Fixed the email header when a payment error occurs in a subscription purchase.
* [PayPal] Fixed the bug that payment error happens when auto-ordering subscriptions.
* [PayPal] Changed the specification to display the billing agreement ID on the subscription data edit page.
* [PayPal] Changed the specification to display the billing agreement ID on the continuous billing member data detail page.
* [Zeus] Removed "Circle K Thanks" from convenience store types.
* Fixed the bug that a checkbox is not checked when a delivery note or other item is printed after an email is sent in the Email/Print field of the order list.
* Fixed the bug that when specifying a period in the order list, the data of "00:00" of the specified start date is not included.
* Fixed the bug that when brute force attack countermeasures are enabled, the login page will not be displayed even though the set number of errors has not been reached when certain operations are performed.
* Fixed the bug that Notice appears on the member list page.
* Fixed the bug that Notice appears when displaying password policy.
* Fixed the bug that required custom customer field error messages appears when logging in on the customer information page.

= V2.2.4 =
-----------
31 May 2021
* Fixed a vulnerability.

= V2.2.3 =
-----------
27 May 2021
* Added the template tag (function) for wc_templates.
* Fixed the bug that when using the "Welcart Recent Posts" widget, the titles of the posts are all set to the title of the currently displayed page.

= V2.2.2 =
-----------
19 May 2021
* [PayPal] Added explanation of "Pay-as-you-go" application to the credit card payment settings screen.
* [PayPal] Fixed the bug that the JavaScript error occurs in the confirmation page when you disable the PayPal payment.
* [PayPal] Fixed the bug that the payment failed when using the Multiple Shipping Plug-in.
* [SBPS] Added the explanation when you change the way of using API type to the credit card payment setting page.
* Changed the display of the basic setting "Member points: give/not give".
* Fixed the bug that the membership agreement description is displayed on the customer information page when the membership system is disabled.
* Fixed the bug that PHP Notice error appears during PDF output.
* Fixed the bug that hints for setting items in the administration page are not displayed when you click on the title.

= V2.2.1 =
-----------
19 Apr 2021
* Fixed the bug that brute force protection option settings failed to be updated.
* Fixed the bug that login error check is omitted in brute force protection.

= V2.2.0 =
-----------
12 Apr 2021
* Implemented new feature that enables the use of Google reCAPTCHA v3 for new member registrations to counter member spam.
* Improved the specification to strictly check and display the password policy at the time of member registration and password change.
* Implemented the function to deny access after consecutive login failures in order to counter brute force attacks on member logins.
* Improved the specification to enable unsorting when sorting by product code or product name in the product list on the administration page.
* Fixed the bug that prevented the conversions in the latest Google Analytics for WordPress by MonsterInsights.
* Fixed the bug that the search condition is canceled when moving to the second page of the product list when specifying the stock status.
* [PayPal] Fixed the bug that prevented re-payment after a payment error in the automatic order for subscription orders.
* [PayPal] Fixed the bug that the transaction amount on the recurring billing member information screen is not formatted in the currency.

= V2.1.8 =
-----------
29 Mar 2021
* [WelcartPay] Fixed the bug that points are not added at the time of postpaid payment (ATODENE).
* [PayPal] Fixed the bug that the mini-browser for payment is not displayed.
* [SBPS] Changed the specification to allow setting the number of days for the payment deadline for convenience store payments.
* [SBPS] Fixed the bug that name, email address and other information are not transferred to the payment screen of convenience store payment.
* [SBPS] Changed the specification to output the response to the payment error log.
* Support for displaying the total amount of consumption tax for business package discounts.
* Fixed the bug that the message of the unused payment module is displayed on the admin page when the subscription plugin "WCEX Auto Delivery" is activated.

= V2.1.7 =
-----------
22 Mar 2021
* [SBPS] Re-fix of V2.1.6

= V2.1.6 =
-----------
19 Mar 2021
* [SBPS] Fixed the bug that the payment information can not be updated from the Welcart management page.

= V2.1.5 =
-----------
12 Mar 2021
* Fixed the bug that WordPress health check shows "The loopback request to the site failed".
* [Metaps Payment] (Pay Design) Fixed the bug that the payment error occurs when the string of the product name is too long.
* [SBPS] Compatible with DLSeller and AutoDelivery.
* [SBPS] Fixed the bug that SESSION error occurred when using 3D Secure for linked payment.
* [SBPS] Basic authentication ID and Basic authentication Password can be entered in the link type.
* Added the function of attaching files to management e-mails from the order data edit page.
* Supported the total consumption tax display of the built-in template.
* Fixed the bug that the "Sales Tax" display in the purchase history is not correct when the sales tax category is switched.
* Fixed the bug that PHP Notice error appears in the member list when adding a custom member field.

= V2.1.4 =
-----------
2 Mar 2021
* Added the template tag for displaying the total amount of consumption tax.

= V2.1.3 =
-----------
22 Feb 2021
* [PayPal] Fixed the bug that DLSeller's auto-recurring billing emails are sent with zero entries.
* [PayPal] Implemented optimization of In-line guest purchase field.
* Fixed the bug that "Next Page" and "Previous Page" in the order data edit page are not displayed.
* Fixed the bug that clicking the "Start Search" button on the light-complex search result page redirects to the cart page.
* Fixed the bug that the message for wrong password in member login is not translated.
* Added an item for system environment information.
* Fixed the bug that the input field for the reduced tax rate is not hidden even if the standard tax rate is selected in the tax rate selection of the basic settings.
* Fixed the bug that the close icon of the dialog for credit card payment is not displayed.
* Changed the specification that "Limit points to total product value only" cannot be selected for point application when supporting reduced tax rate.

= V2.1.2 =
-----------
9 Feb 2021
* [PayPal, SB Payment] Fixed the bug that the sales transaction (sales posting, cancellation, etc.) cannot be processed in the order data edit page.

= V2.1.1 =
-----------
19 Jan 2021
* [WelcartPay] Fixed the bug that there is one extra CSV data item in the "Postpaid Payment Shipment Report Registration CSV Output" in Atodene's Postpaid Payment.
* [PayPal] Added a class to PayPal CP checkout form div.
* [PayPal] Fixed the bug that points are not added to the membership data when using PayPal CP for purchases if the timing of point adding is set to the time of deposit.
* [PayPal] Support locale for PayPal payment button.
* Fixed the bug that there are cases the page break of the delivery PDF doesn't work well when the setting is  "Reduced tax rate" .
* Changed the specification to not limit the scope of application of points when the setting is  "Reduced tax rate" .
* Changed the specification to check the existence of the product at the time of cart submission.
* Fixed the bug that an error displays when performing a batch operation on the Order List page.
* Fixed the bug that the ▼▲ of the "Mail Print Field Display" on the Order Data Edit page is reversed.
* Fixed the bug that the member No. is not reflected in the order data purchased without member registration even if the member information is imported or updated later.
* Fixed the bug that the purge number is all 1 under certain conditions in the delivery note, invoice, and receipt PFDs.
* Fixed the bug that "#NONE#" is selected when operating the smartphone if the product option is set to multi-select or required selection.
* Changed the specification to include an inventory check just before payment.

= V2.1.0 =
-----------
21 Dec 2020
* [PayPal] Implement PayPal Commerce Platform
* [ZEUS] Fixed the bug that causes a rare card payment error in ZEUS payments.
* Improved the error message description at login so that the existence of e-mail addresses is not guessed.
* Fixed the bug in version 2.0 that a 403 error causes after you see the order list and member list.
* Fixed the bug in version 2.0 that the zip code validation check doesn't work correctly when selecting a country outside of Japan.
* Fixed the bug in version 2.0 that a Warning error shows in the system environment.
* Fixed the bug in version 2.0 that  "Hide Operation Fields" is shown as the default in the old member list.
* Fixed the bug in version 2.0 that the filter function in the order list doesn't clear the previous search word.

= V2.0.1 =
-----------
10 Dec 2020
* Fixed the bug that cash on delivery fee was added when paying by credit card.

= V2.0.0 =
-----------
9 Dec 2020
* Resolved errors in WordPress Site Health Check.
* Compatible with jQery3 (WordPress 5.6).
* Compatible with PHP 7.4.
* Added the function of output the environmental information log.
* [WelcartPay] Fixed the bug that the next subscription order date in the email sent to the administrator is wrong when renewing a credit card in My Page.
* [ZEUS] Fixed the bug of a rare card payment error with ZEUS payment.
* [SBPS] Changed the specification that the first step of automatic sales in link type payment from "Automatic Sales" to "Credit" status.

= V1.9.37 =
-----------
24 Nov 2020
* [WelcartPay/e-SCOTT] Added the encryption key and other input fields in the payment setup page.
* Added a class to the DIV tag in the completion page after new member registration.
* Fixed the bug that the products with a unit price of 0 yen are not displayed on the PDF statement when the tax rate is reduced.
* Fixed the bug that the loading images are not displayed.
* Added the shipping company name "Yamato Transport".
* [SBPS] Fixed the bug that a PHP Warning error caused on the member data edit page when using linked type credit card payments.
* Compatible with Multiple Shipping fixes.
* Correct the translation.
* Fixed the bug that a JavaScript error caused when clicking the "Display Options" checkbox in the Order List page of the admin page.
* Fixed the bug that the "Sales Tax" label and amount are displayed incorrectly when the "Sales Tax Category" is changed during the process.

= V1.9.36 =
-----------
21 Oct 2020
* Fixed the vulnerability of PHP Object Injection.

= V1.9.35 =
-----------
19 Oct 2020
* [WelcartPay] Fixed the bug that the purchase completion page does not appear after successful payment with UnionPay.
* [WelcartPay] Fixed the bug that you can't re-authorize after canceling in the admin page.
* Fixed the bug that Notice error occurred when a product image isn't registered.

= V1.9.34 =
-----------
13 Oct 2020
* [SBPS] Added PayPay online payments.
* [SBPS] Added the function that users can cancel and make actual sales of the credit card payments in the admin page.
* [Zeus] Fixed the bug that the payment error happens when returning from 3D Secure authentication with certain conditions.
* [WelcartPay] Changed the specification to not use the session when recovering from three-way communication (external link type).
* [WelcartPay] Fixed the error in UnionPay payment.
* Fixed the unnecessary line feed code at the end of order list CSV.

= V1.9.33 =
-----------
15 Sep 2020
* Added the function to check for zip code validation.
* [PHP7.4 compatible] Fixed the bug that the error causes when generating PDF if certain characters (unknown) are used.
* [e-SCOTT] [WelcartPay] Fixed the bug that the settlement error causes in case of some 3D secure authentication.
* Avoided the inability to enable Welcart if the server is using old RC4-SHA encryption.
* Fixed the issue that Welcart doesn't work in WordPress.com in case of using WP5.5 or later.
* [WelcartPay] Fixed the bug that the deletion of payments doesn't work with WelcartPay's Atodene.

= V1.9.32 =
-----------
18 Aug 2020
* Fixed the bug that the checkboxes in the order list, member list and product list don't work on WP5.5.
* Fixed the bug that the member page transition doesn't work on WP5.5 environment.
* Fixed the bug that the error message appears when activating the plugin.
* Reviewing the delivery company name.

= V1.9.31 =
-----------
8 Jul 2020
* Exclude PHP 5.6 from the supported versions (no longer supported).
* Corrected the display of the number of stock per SKU in Control Panel > Item List.
* Fixed the bug that unnecessary columns are inserted in the download CSV data for "Order List Output", "Member Data Output", and "Order List Output" .
* Fixed the bug that some of the button styles on the admin screen are not working.
* [e-SCOTT] [WelcartPay] Fixed the bug that the display of the number of payments for installment payments is incorrect.
* Fixed the bug that the error happens in checking for update of extension plugins and failing to activate Welcart.
* [WelcartPay] Added the function to return to session when SESSION EMPTY.
* Fixed the error in the getItemDiscount() function.

= V1.9.30 =
-----------
24 Apr 2020
* Added the function to display the update notification of the extended plugin.
* Added the option to show or hide the global message of the settlement errors.
* Fixed the bug that all menu titles in the member page are being the same titles of the member page when using a specific theme.
* Adjusted the style of the input field on the credit settlement settings page.
* Fixed the bug that the "Previous page" and "Next page" buttons on the order data edit page are not displayed.
* Fixed the bug that the custom customer field item isn't displayed in "Order List Output".
* Fixed the bug that the search result of "Order Search" is not reflected in "Order List Output".
* [WelcartPay] Fixed the bug that you can't change the "Next Billing Date" if you change it once.
* [Telecom] Fixed the bug that a duplicate order data is created.
* Added the function to specify the "duration" of the bestseller widget.

= V1.9.29 =
-----------
7 Apr 2020
* Improved the performance of a order list response.
* Added a session recovery function for recover the session empty error of credit settlements.
* [e-SCOTT] [WelcartPay] Fixed the bug that Notice error occurs when there is no "TokenResponseCd".
* Compatible with WordPress 5.4
* [WelcartPay] Changed the specification that an item name is added in a result notification mail of an automatically-renewed time deposits.
* Added the function that a custom field specific input/output to an item CSV.
* Changed the specification that the IP address and useragent of a customer are displayed in a order data edit page.
* Fixed the bug that a data table isn't created on some hosting servers.
* Fixed the bug that you can't move to the next page in a cart page when the site isn't SSL  by supporting Chrome 80.
* Fixed the bug that a database error occurs when the session is time out in an item master page.
* Fixed the bug that the style of the calendar is broken in Welcart Default Theme.

= V1.9.28 =
-----------
9 Mar 2020
* Fixed the bug that you can't move to the next page in a cart page in some browser such as Egde by supporting Chrome 80.
* Fixed the bug that "Security check4" error occurs when you log in with login form in top page in Welcart Default Theme.

= V1.9.27 =
-----------
2 Mar 2020
* Fixed the bug that the item option in the past order list is disappear when you change the item option of the item master.
* Fixed the bug that the calendar id tag of the calendar widget is not unique.
* Compatible with Chrome 80.
* Fixed the bug that the error occurs the member list in MySQL8.

= V1.9.26 =
-----------
30 Jan 2020
* [PayPal] Fixed the bug that "Pay Now" button doesn't work in the cart page.
* [WelcartPay] Fixed the bug of error in UnionPay Payment.
* [WelcartPay] Fixed the bug that a comma "," is attached to the amount in ATODENE's "Post-payment settlement transaction registration CSV".
* [WelcartPay] Changed the specification that limit available company names in ATODENE.
* [ZEUS] Changed the specification that a credit card can be registered in My Page when using QuickCharge and batch processing.
* Changed the specification that a member data can be shared with other Welcart.
* Changed the specification that "+" character can be used in the e-mail address check when sending.
* Fixed the bug that "sama" is inserted in the subject of the automatic reply mail when the language setting of the front page isn't Japanese and the administration page is English.
* Fixed the bug that a JavaScript error appears on the order edit screen in some environments.

= V1.9.25 =
-----------
18 Dec 2019
* Fixed the bug that in case of the mail authentication of "Next while registering a member", the site will be tranferred to the Welcart demo site  when you click  "Return to purchase".

= V1.9.24 =
-----------
16 Dec 2019
* [WelcartPay]Fixed the bug that you can purchase items even if the upper limit of payment is exceeded when using ATODENE.
* [WelcartPay]Added a feature of UnionPay Payment.
* [WelcartPay]Fixed the bug that yen mark is added to the consumption tax in "Postpaid payment transaction registration CSV when using ATODENE.
* Fixed translation of mail authentication function.
* Fixed the bug that menu titles such of footer and etc. are all "member login" on the member login page on the front page.
* Fixed the bug that the hyphen (-) cannot be used for custom member field keys.

= V1.9.23 =
-----------
5 Dec 2019
* Compatible with PHP7.3.
* Added a feature of a mail authentication.
* Fixed the bug that the tax-inclusive price becomes incorrect when updating the order data edit screen when using the reduced tax rate.
* [e-SCOTT][WelcartPay]Changed the specification that recording "3D secure authentication result code" in 3D secure.
* [e-SCOTT][WelcartPay]Corrected some error message translation.
* [WelcartPay]Fixed the bug that [Recording sales] is displayed again when [Change usage amount] after [Recording sales] in the settlement dialog.
* [WelcartPay]Fixed the bug that a CSV output button related ATODENE does't work in the order list.
* [e-SCOTT][WelcartPay]Changed the specification that 3D secure encryption key / initialization vector input is not required.
* [WelcartPay]Changed the specification that adding a history data even when a member error occurs in automatically-renewed time deposits.
* [e-SCOTT][WelcartPay]Fixed th bug that the card number input form doesn't appear in the dialog when there is index.html in Route.
* [e-SCOTT]Fixed th bug that a payment notification for convenience store payment cannot be received when using only convenience store.
* Changed the specification to add  "Manager Memo" item to "Order List" and "Order Details List" output CSV.
* Fixed the bug that an error isn't displayed even if you purchase more than the purchase limit if you set "Do not limit purchase when sold out" in item data editing page.
* Add "CLICKPOST" to shipping company name.
* Fixed the bug that "Discount" becomes 0 when "Apply tax rate after changing" is checked with [Recalculate] button in order data editing.
* Fixed the bug that the product is deleted immediately without alert confirmation when deleting the product on the product edit screen.
* Changed the specification to make it easy to set required address etc.
* Fixed the bug that the page in PDF output breaks unnecessarily when using "Reduced tax rate".
* Fixed the bug that Notice appears when logging out when using DLSeller.
* Fixed the bug that PHP7 Warning error occurs in tracking tag output in the specific environments.

= V1.9.22 =
-----------
20 Sep 2019
* Fixed the bug that the price of incluging tax is calculated by standard tax rate when the settings are "Reduced tax rate" and the item is included tax.
* Fixed the bug that the price which should be discount doesn't discount when the setting is "Reduced tax rate".
* Fixed the bug that the tax price doesn't display when "Consumption tax classification" and "Consumption tax subject" hasn't saved in past order data.
* Fixed the bug that "Shipping date" can't be searched by "more than" and "less than" in OrderList>OrderSearch.
* [WelcartPay] Changed the specification. "Settlement price" is being displayed in the settlement history of subscription settlement member information.
* Changed the specification. The date of Receipt PDF is delivery date(updated date).

= V1.9.21 =
-----------
29 Aug 2019
* Modified the function of Reduced tax rate.
* Added the SKU item mode on item CSV of an item batch registration and an item data output.
* [WelcartPay] Compatible with 3D Secure.
* [e-SCOTT Smart] Compatible with 3D Secure.
* [SBPS] Fixed the bug that the display of option changes to an  incorrect value on wallet settlement.
* Fixed the bug that the style of the order completion page is skewed when not using 'wc_templates'.
* [Paygent] Fixed the bug that the payment method isn't registered on basic settings>payment method>payment method when the settings of the payment is completed.
* Changed the specification. You can't select the payment method when the settings of the credit payment is wrong.
* Changed the specification. The payment method being stopped when the settings of the credit payment changes to unused.
* Changed the specification. The product subtotal is added on the list of the order list output.

= V1.9.20 =
-----------
25 Jun 2019
* [PayPal EC]Added the feature of a bank settlement.
* Added the filter hook for the item CSV.

= V1.9.19 =
-----------
22 May 2019
* Added the feature of Reduced tax rate.
* Added the filter hook for customization of "Desired delivery date".
* [SBPS] Modified words, etc.
* Fixed the bug that you can't update custom order field and custom customer field.
* Fixed the bug that there are cases when payment_id isn't displayed in payment information.
* Fixed the bug that Datepicker in "Desired delivery date" and "Shipping date" doesn't work on new order estimation registration page.

= V1.9.18 =
-----------
22 Apr 2019
* [ZEUS]Fixed the bug that the credit card can't update in My Page in case of using ie11 or iPhone Safari.
* Changed the specification of the hook that the value status of "Check out" button in confirm page is changeable.
* Added the hook to get_member_history().
* Fixed the bug that an error occurs when specifying non-charactor in the second argument in is_status().
* Changed the specification that textarea available in custom order field.
* [Veritrans]Added the hook to the timing of receiving payment notification.
* Added the function that displaying additional information of payment method to the payment method name of administration email.
* Added the hook of judgement of deleting member information for front page.
* Fixed the bug that can't be registered as an error occurs if there are multiple shipping method in item batch registration.

= V1.9.17 =
-----------
5 Mar 2019
* [SBPaymentService]Compatible with Token settlement.
* [ZEUS][WelcartPay]Changed the specification that sending email to administrators when the information of credit card is changed on customer's my page.
* [e-SCOTT][WelcartPay]Changed the specification that administrators can delete the mismatched data of quick payment member on member editing page.
* Changed the specification that the display date isn't limited about "Desired delivery date" and "Shipping date".
* Fixed the bug that displaying "Security check2" in case without wc_templetes when click the button of "Next with membership for register". (Re-fixed)
* Added the hook of the judgement of deleting member information for the front page.
* Added the hook at the status response of the purchase history on the member data edit. (Re-fixed)

= V1.9.16 =
-----------
13 Feb 2019
* [PayPal EC]Fixed the bug that total amount process isn't match  when the currency is dollar.
* Fixed the bug that displaying "Security check4" when reload the page after login to my page.
* Fixed the bug that displaying "Security check2" in case without wc_templetes when click the button of "Next with membership for register".
* Fixed the bug that the item registration number of Welcart Shop Home doesn't decrease when the item delete(into the trash box).
* Fixed the bug that displaying "Notice" on custom order field check.
* Changed the specification that the bank account for money transfer doesn't display in case of the payment method is post-payment.
* Added Serbia to the sales country.
* Added the hook at the status response of the purchase history on the member data edit.

= V1.9.15 =
-----------
15 Jan 2019
* Fixed the bug that the customfield can't registered when new registration by item CSV.
* Fixed the bug that you can't registrate item batch registration using slug name of category.
* Fixed the bug that the serialized customfield become broken when new or update registration by item CSV.
* [SBPS]Changed trade name of SB Payment Sevice.
* [WelcartPay]Fixed the bug that you can't record the sales after doing re-authorization.
* [WelcartPay]The email address which is automatically sending when the transaction of the automatically-renewed payments happens was changed to the member data's email address.
* Fixed the bug that some total amount displays isn't correct  when the currency is USD.
* Fixed the bug that the number of campaign discount calculation is round to the integer in case of the currency which needs to display after the decimal number.
* Changed the specification that the complettion notice become easy to find out on my page when users updated their information.
* Enhanced security of the member log-in.
* Added the hook at $usces->get_post_user_custom().
* Added the argument at $usces->get_post_user_custom().
* Added the hook at usces_action_reg_orderdata_stocks($args).

= V1.9.14 =
-----------
1 Nov 2018
* [Welcart]Compatible with PHP7.2.
* Changed the specification that it doesn't set MyIsam to the data table as the initial engine.
* Improved the taking time to be short when uploading item batch upload, and fixed some errors.
* Fixed the bug that disappearing half-width "+" in the mails sending from admin pages.
* [PayPal EC]Changed the words which are automatically inserted when you select PayPal EC in payment method.

= V1.9.13 =
-----------
3 Sep 2018
* [e-SCOTT][WelcartPay]Changed the specification that it doesn't allow to register "e-SCOTT" and "WelcartPay" at once to "available credit payment module".

= V1.9.12 =
-----------
31 Aug 2018
* Changed the specification that the processing doesn't stop when there are no authority of writing the log file on an article batch registration.
* Fix the bug that the character "&" is automatically changed "&amp;" in the sender name of mail.
* [e-SCOTT][WelcartPay]Fix the bug that the inside display of dialog window of Token settlement is none.
* Fix the bug of notice message appearing when the first activation of welcart.
* [e-SCOTT][WelcartPay]Changed the specification that it doesn't allow to register "e-SCOTT" and "WelcartPay" at once to "available credit payment module".
* Added Kazakhstan to the sales country.
* Fixed the bug that the discount amount is automatically changed to 0 when the recalculating button is clicked on the order data editing page.
* [REMISE Payment]Added the function that changing a credit card is available on "My Page".
* [PayPal EC]Fixed the bug of JavaScript error on cart page without login.
* [WelcartPay]Changed the specification that the maximum amount for fee(fixed) of online settlement can be setting.
* [WelcartPay]Fixed the bug of error on online settlement processing.
* [e-SCOTT][WelcartPay]Changed the specification that the processing category is mandatory.
* [e-SCOTT][WelcartPay]Added the option of using quick payment at every time.
* [ZEUS Paymen]Fixed the bug that the payment module except ZEUS is unavailabe on basic settings>payment method>payment method.
* [Paygent Payment]Fixed the bug that the order data of welcart is automatically generated when changing the settlement amount at Paygent admin page.

= V1.9.11 =
-----------
29 Jun 2018
* [Mizuho factor]changed the connection destination URL of test environment.
* Fix the bug that there are cases when e-commerce tracking is not recognized(sent) correctly.
* [e-SCOTT][WelcartPay]Changed a display of the number of payment  to a blank in case of "lump sum payment only".
* Fixed the bug that site title on a statement of delivery PDF is garbled.
* Added the hook to "company" in a statement of delivery PDF.
* [e-SCOTT][WelcartPay]Changed the field of Token settlement authentication code to mandatory.
* Added the function of welcart membership password authorization. Specifing salt is available and etc.
* [ZEUS Payment]Fixed the bug that the status doesn't change to paid when the notice is reached on convenience payment.
* [Another Lane Payment]Changed the settlement URL.
* [WelcartPay]Fixed the bug that the registration of card member deosn't work in case of purchasing a subscription item at first time on external link type.
* [WelcartPay]Added the action hook to "Close" on payment information dialog.
* Fixed the bug that paging doesn't work on an old order list.
* [RobotPayment(CloudPayment)]Fixed the bug of payment error when using points over a total item price.
* [Yahoo wallet]Fixed the bug of payment error.
* Fix the bug of a decimal calculate of Javascript on order editing page.

= V1.9.10 =
-----------
5 Mar 2018
* Changed the timing of session_start.  It runs before any other plug-ins.
* Changed the design of nounce.  The different value sets to nounce on each session when it is important for security on front page.
* Added httpOnly attribute to session cookie. If AOSSL, added secure attribute.
* Strengthen the sanitizations.

= V1.9.9 =
-----------
2 Feb 2018
* [ZEUS Payment]Fixed bug that is not registered in Quick Charge when using Quick Charge with SecureLink.
* Added the option that is not restrict purchase (not check the stock) even when sold out.
* Added the function of chage the display name of stock status.
* Changed the label of "Use SSL" to "Switching SSL".

= V1.9.8 =
-----------
22 Jan 2018
* [ZEUS Payment]Compatible with Token settlement.
* Changed the timing of session_start.  It runs at welcart construct.
* Fixed the bug in case of the payment status is unexpected value when edit the order data.
* [Paydesign]Fixed the statements of content-type on the function of header.
* [e-SCOTT][WelcartPay] Changed the style of entering credit card number daialog on smart phone.
* [e-SCOTT][WelcartPay] Changed the display of payment information on order edit screen. The last 4 digits of credit card number and the expiration date aren't displayed.
* [WelcartPay] Changed the year-selection of the next contract renewal date to be able to choose until 10 years later on the auto-renewable subscriptions member information.

= V1.9.7 =
-----------
25 Dec 2017
* Fixed the bug of the error of "uscesCart" jQuery.
* [Paygent]Added "Supplementary display classification" on the sending parameter.
* Fixed the bug that the lack of end tag (</div>)  on order editing page.
* [e-SCOTT][WelcartPay]Modified the display position of the card information entering dialog.
* Added the destination name on "Ganbare Tencho!"csv output.
* Modified every PDF output. "ZIP mark" and "TEL" don't display when the address and TEL aren't filled in.
* Added the class fo style of customer information field on confirmation page.
* Added the correspondence status on purchase history of member information editing page on admin screen.
* [WelcartPay]Fixed the bug that the layout of order list is off when using postpay settlement.
* [WelcartPay]Fixed the bug of the error caused by old settlement information.
* [PayDesign]Fixed the bug of the error in case of the item name is long when credit cart payment.
* [e-SCOTT][WelcartPay]Fixed the bug that Token settlement dialogue doesn't display depending on the situation.

= V1.9.6 =
-----------
3 Nov 2017
* Compatible with WordPress4.8.3 design change of sanitizing. Fixed the bug of order list csv.
* [WelcartPay]Fixed the bug that members can't cntrol of payment on admin page when purchasing with changing the card number.

= V1.9.5 =
-----------
30 Oct 2017
* [WelcartPay]Compatible with Token settlement.
* [e-SCOTT]Compatible with Token settlement.
* Fixed the bug that "usces_noreceipt_status" hook doesn't work.
* Fixed the bug of unreflecting the change of "administartor memo" when registering new order estimate.
* Added the filter hook at "Total price" of PDF output.
* Added the filter hook for editing the remarks of order data csv.
* Fixed the untransrated error message at registering menber.
* Added the filter hook of changing font size of our company information on delivety note PDF.
* Changed the style of right justified on member list.

= V1.9.4 =
-----------
12 Sep 2017
* Fixed an object injection vulnerability
  We discovered a dangerous Object Injection vulnerability in front page.
  Please upgrade Ver.1.9.4 immediately. All the past versions are the target.
  Technical countermeasure details are here the link.
  https://plugins.trac.wordpress.org/changeset?sfp_email=&sfph_mail=&reponame=&new=1728429%40usc-e-shop&old=1728428%40usc-e-shop&sfp_email=&sfph_mail=
* Changed the print of the first page footer information on delivery note PDF doesn't show in case of multiple pages.
* Added [UnionPay] option in the payment module of Softbank Payment.
* Changed to no- public category is displayed on the selection on item lists in case of the search item category.
* Changed the consumption tax is included when the payment is points only.
* Fixed the bug of delivery date settings.
* Fixed the duplicate id value of "mailadress1" on "wc_customer_page.php".
* Fixed the lack information of order data in case of PayPal webpayment plus.
* Fixed the bug of point form.
* Corresponded the specifications change of "Yahoo! wallet".

= V1.9.3 =
-----------
5 Jul 2017
* Fixed the bug of duplicated payments on specific servers. [WelcartPay][e-SCOTT Smart]
* Fixed the bug of error message on[e-SCOTT Smart].
* Changed the specification of [WelcartPay].
* Fixed the bug of the item name length of [Pay design].
* Fixed the bug of PayPal settings caused by API expired.
* Fixed the bug of the link display on the member page of [ZEUS].
* Fixed the bug of calculating the price in case of using points.
* Changed JAVA scripts alert on confirmation page to decrease abandonment.

= V1.9.2 =
-----------
28 Apr 2017
* Added the feature of post payment "ATODENE" on [WelcartPay].
* Fixed the bug of calculating the price in case of BankCheck payment on [CloudPayment].
* Fixed the bug of installment payment number on e-mail. [WelcartPay].
* Fixed the bug of the item list page on admin page.
* Corresponded the version upgrade of WCEX DLSeller 3.0.
* Fixed the bug of credit card information display on [WelcartPay].
* Changed to make the failure log of auto-renewable subscriptions on [WelcartPay].
* Fixed the bug of filtering by delivery method on order list.
* Fixed the bug of payment error in case of using coupons on [WelcartPay][SONY Payment].
* Fixed the bug of notice message on admin and order data editing page.
* Fixed the bug of the data getting of receiving agency of online transaction on [WelcartPay].
* Fixed the bug of the link display on my page of [WelcartPay].
* Added the feature of updating the data only "the stock amount" or "the stock status" on item batch registration and item data export.
* Fixed the country code of Sweden.

= V1.9.1 =
-----------
26 Dec 2016
* Added the feature of CSV export that items have multiple custom field and same key.
* Changed the specification of [E-SCOTT] and [WelcartPay] to disable to delete account on My Page.
* Fixed the calculate system of point.
* Added SKU code to item code of item number on [PayPal EC].
* Added the function of item batch registration and item data export to resister/export the category by slug.
* Fixed the bug of point adding on order editing page.
* Fixed the bug of registration member information on front page.
* Fixed the bug of the error in comment meta box on item master.
* Fixed the bug of updating member data.
* Fixed the bug of the base country data getting in payment method page.
* Fixed the bug of the advance value disappearing in case of updating sku information when using WCEX_SKU_SELECT + advance field.
* Added the feature of countermeasure for protect the member registration spam.
* Fixed the bug of character automatically changing from [+] to [ ] on some prints and e-mails.
* Fixed the bug of the totals print on invoice PDF.
* Fixed the bug of the error when the agencies unavailable on credit payment selecting page.
* Changed the color of "Credit sales accounting" on [WelcartPay].
* Fixed the bug of sorting on delivery /payment page.
* Fixed the bug of exporting payment error log.
* Fixed the bug of displaying the status of deposit confirmation on order editing page.

= V1.9 =
-----------
5 Oct 2016
* Added the new payment module "WelcartPay"
* Fixed the bug that the sub-image is not recognized

== Upgrade Notice ==

= 1.9.4 =
This version fixes an object injection vulnerability. Upgrade immediately.
