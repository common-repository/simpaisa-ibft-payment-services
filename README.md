=== Simpaisa IBFT Payment Services ===
Contributors: maqsoodali
Tags: woocommerce, bank, account, HBL, MCB, IBFT, NIFT, Meezan, UBL
Requires PHP: 5.4
Requires at least: 4.4
Tested up to: 6.4.2
Stable tag: 1.0.9
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Providing Easy To Integrate IBFT Digital Payment Services.

== Upgrade Notice ==

This is an upgrade of Simpaisa IBFT Payment Services version 1.0.9

== Description ==

* Simpaisa IBFT (Inter Bank Funds Transfer)
Simpaisa Plug-in for IBFT  covers two methods and makes it easier for customers to complete their payments directly from their bank accounts.
= Method 1: Push IBFT =
This method leads to a payment token once the details have been entered. The token or voucher can easily be paid from any e-banking portal along with mobile wallet accounts.
Once the payment has been made against the token the merchant can easily monitor success transactions in the reporting portal.
= Method 2: Pull IBFT =
This method saves a lot of time since it deducts money directly from the bank account of the user once the correct details have been entered. Only the user having an active e-banking or online banking can pay via pull IBFT method.
All the transactions which are done via simpaisa plugins can be easily traced on simpaisa portal, In order to use the plug-in one must have merchant ID assigned by Simpaisa Team.
Note: To collect your merchant ID and discuss the process, please contact simpaisa team today.

> **Support policy**
> * If you need assistance, please open a support request in the **[Support section, above](https://wordpress.org/support/plugin/simpaisa-wallet-payment-services/)**, and we will look into it as soon as possible (usually within a couple of days).
> * If you need support urgently, or you require a customisation, you can avail of our paid support and consultancy services. To do so, please contact us (https://www.simpaisa.com), specifying that you are using our WooCommerce Simpaisa Mobile Wallet plugin. You will receive direct assistance from our team, who will troubleshoot your site and help you to make it work smoothly. We can also help you with installation, customisation and development of new features.

= IMPORTANT =
**Make sure that you read and understand the plugin requirements and the FAQ before installing this plugin**. Almost all support requests we receive are related to missing requirements, and incomplete reading of the message that is displayed when such requirements are not met.

= Included localisations =
* English (GB)

= Requirements =
* A Simpaisa Merchant account. The plugin was not tested with personal accounts and might not work correctly with them.
* WordPress 4.4 upto 6.4.2
* PHP 5.4 or greater
* WooCommerce 4.9.0 upto 8.3.1

= Current limitations =
* Plugin does not yet support pre-authorisation or subscriptions.

= Notes =
* This plugin is provided as a **Free** alternative to the many commercial plugins that add the Simpaisa payment services to WooCommerce. See FAQ for more details.

== Automatic Installation ==
Automatic installation is the easiest option — WordPress will handle the file transfer, and you won't need to leave your web browser. To do an automatic install of Simpaisa, log in to your WordPress dashboard, navigate to the Plugins menu, and click "Add New."

In the search field type "Simpaisa" then click "Search Plugins." Once you've found us, you can view details about it such as the point release, rating, and description. Most importantly of course, you can install it by! Click "Install Now," and WordPress will take it from there.

== Manual Installation ==
1. Manual installation method requires downloading the WooCommerce plugin and uploading it to your web server via your favorite FTP application. 
2. Extract the zip file and drop the contents in the ```wp-content/plugins/``` directory of your WordPress installation.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Go to ```WooCommerce > Settings > Payments > Simpaisa``` to configure the plugin.

For more information about installation and management of plugins, please refer to [WordPress documentation](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

= Setup =
On the settings page, the following settings are required:

* **MerchantID**: this is the ID associated to your Simpaisa merchant account.
* **ActionUrl**: this is the action url provided by Simpaisa support.

If you wish to get more details about Simpaisa, please refer to [Simpaisa website](https://www.simpaisa.com/) or Email : hasan.iqbal@simpaisa.com

== Changelog ==

= 1.0 =
* First official release
= 1.0.1 =
* Implement Postback/Webhook Url for Notifcations
= 1.0.2 =
* Integrate general postback
= 1.0.3 =
* Postback bug fixes
= 1.0.4 =
* Added error log
= 1.0.5 =
* Added access log
= 1.0.6 =
* Multi postback bug fixes
= 1.0.7 =
* Postback improvement
= 1.0.8 =
* Woocommerce version support
= 1.0.9 =
* Wordpress version support