=== Parsian Bank Payment Gateway for WooCommerce ===
Contributors: saeidafshari
Tags: woocommerce, payment gateway, parsian bank, inpage, payment, iran, e-commerce
Requires at least: 5.6
Tested up to: 6.7
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A secure and modern payment gateway for Parsian Bank (Pec.ir) compatible with WooCommerce. Developed with focus on security, performance, and user experience.

== Description ==
The **Parsian Bank Payment Gateway** plugin enables seamless integration between your WooCommerce store and Parsian Bank's online payment system (Pec.ir). Built with best practices in mind, this gateway ensures secure transactions, clean code, and full compatibility with the latest versions of WooCommerce and WordPress.

This plugin was developed by **Saeid Afshari**, a software enthusiast and developer passionate about clean code and digital solutions. While primarily focused on audio hypnosis at [kabook.ir](https://kabook.ir), Saeid actively contributes to the open-source community through technical projects like this.

Key Features:
- ✅ Full support for Parsian Bank InPage API
- ✅ Secure SSL and HTTPS communication
- ✅ Customizable success/failure messages
- ✅ Custom logo upload for payment page
- ✅ Advanced error logging and debugging
- ✅ Optimized for WooCommerce 8.x
- ✅ Prevents common security issues (CSRF, XSS, etc.)

Perfect for Iranian e-commerce stores seeking a reliable, up-to-date, and well-maintained payment solution.

== Installation ==
1. Upload the plugin zip file via **Plugins > Add New > Upload Plugin** in WordPress
2. Activate the plugin
3. Go to **WooCommerce > Settings > Payments**
4. Enable "Parsian Bank" and enter your credentials

== Settings ==
* **Terminal ID**: Your terminal ID provided by Parsian Bank (required)
* **Custom Logo URL**: Full URL of an image to display on the payment page (optional)
* **Success Message**: Message shown after successful payment
* **Failed Message**: Message shown if payment fails

== Changelog ==
= 1.2.0 - 2024-06-14 =
* Fixed "Access Denied" error when saving Terminal ID
* Added default Parsian Bank logo on payment page
* Added support for custom logo upload
* Enhanced SSL security for bank communication
* Optimized code to prevent security warnings
* Improved compatibility with WooCommerce 8.x

= 1.1.0 - 2024-05-05 =
* Added support for latest WooCommerce versions
* Improved error handling
* Added full Persian translation
* Implemented advanced logging system

= 1.0.0 - 2024-03-30 =
* Initial release
* Implemented core payment flow
* Callback and verification handling
* Transaction management

== Frequently Asked Questions ==
= Does this plugin require SOAP? =
Yes. Your server must have PHP SOAP extension enabled.  
On Ubuntu: `sudo apt install php-soap && sudo systemctl restart apache2`

= Is HTTPS required? =
Yes. This plugin will not work on HTTP sites. A valid SSL certificate is required.

= How to fix security errors (ModSecurity)? =
If you encounter security blocks:
- Temporarily disable ModSecurity in hosting panel
- Or whitelist your IP in security settings

= Can I customize the bank logo? =
Yes! Enter any image URL (recommended: PNG, 120x60px) in the settings.

== Support ==
For technical support or feedback:  
📧 support@kabook.ir  
🌐 [kabook.ir - Open Source Projects](https://kabook.ir)

We welcome bug reports, suggestions, and contributions via GitHub (if public).

== Credits ==
Developed by **Saeid Afshari**  
Passionate about software development and digital wellness.  
Visit [kabook.ir](https://kabook.ir) for audio hypnosis programs and technical projects.

== License ==
This plugin is open source and released under the GPL license. You are free to use, modify, and distribute it under the terms of GPLv2 or later.