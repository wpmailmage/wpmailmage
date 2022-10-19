=== Mail Mage ===
Contributors: mailmage
Tags: cart abandonment, cart recovery, product reviews, customer reviews, review reminder, woocommerce, email
Requires at least: 4.0
Tested up to: 6.0
Stable tag: 0.0.24
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Recover Abandoned WooCommerce cart emails, send WooCommerce Product reminder emails, Automate your WordPress marketing workflows to help convert, retain and recover customers in WordPress and WooCommerce, other popular plugins coming soon.

== Description ==

Easily create marketing automations such as recovering abandoned WooCommerce carts, Send out WooCommerce review reminder emails and many other types of WooCommerce follow up emails can be created with ease. 

Creating automations simply by choosing and configuring when to trigger, how long to wait before sending, and customising what email is sent using template variables to inject data captured from the event. 

= Features =

* Send emails when post, page or custom post type statuses change.
* Send emails when on user registration.
* Send emails on a Monthly, Weekly or Daily schedule.
* Send emails when a WooCommerce order status is changed.
* Send emails when a WooCommerce cart is declared abandoned.
* Send emails when a WooCommerce subscription status changes.
* Send email immediatly when an event is triggered, delay sending of emails by a minimum amount of time, or send emails on a monthly, weekly, or daily schedule. 
* Create emails using WordPress, WooCommerce or custom email templates.
* Allow users to unsubscribe from future emails.
* Create email messages using text variables, [View a full list of available text variables](https://www.wpmailmage.com/docs/automations/variables/)
* Send preview emails.

= Examples =

**WooCommerce abandoned cart recovery emails**

Create and send targeted emails to customers who have left without completing their purchases, once their cart has been declared as abandoned (cart is left dormant for 1 hour and email address has been captured). 

Studies carried out in 2020 on average show that the rate of cart abandonment online ranges between 55-80%, with abandoned cart recovery emails some reports suggest on average a 15% recovery rate.

[View abandoned cart recovery email example](https://www.wpmailmage.com/docs/examples/woocommerce-abandoned-cart-email/)

**WooCommerce product reminder emails**

Increase the amount of WooCommerce product reviews, by automatically generating targeted customer emails listing purchased products each with a review link, scheduling the sending of emails after an order has been marked as complete.

[View review reminder email example](https://www.wpmailmage.com/docs/examples/woocommerce-review-reminder-email/)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/mail-mage` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Tools->Mail Mage screen to create and view your automation

For further documentation on installing and using Mail Mage can be found [here](https://www.wpmailmage.com/docs/)

== Frequently Asked Questions ==

== Screenshots ==

1. WooCommerce Product Review Reminder automation
2. WooCommerce Product Review Reminder email
3. WooCommerce abandoned cart recovery automation
4. WooCommerce abandoned cart recovery email

== Changelog ==

= 0.0.24 = 

* Rename php constants with MAIL_MAGE prefix.
* Fix reporting charts for weeks.

= 0.0.23 = 

* Added text argument to wc_order.items variable to set review link text.

= 0.0.21 = 

* Fixed email reports to display click interactions
* Fixed preview email to initiate placeholder cleanup

= 0.0.20 = 

* Fixed issue with only one automation showing.

= 0.0.19 =

* Added new event to check subscription status changes.
* Added ability to add multiple automations. 

= 0.0.18 =

* Added ability to send email on a schedule.
* Updated ewp_max_hourly_emails default to 0, which means no hourly sending limit.
* Fixed reports not showing for email actions.
* Fix composer issue due to not setting minimum php version.

= 0.0.17 =

* Added total column to abandoned carts table, gets calculated when marked as abandoned.
* Added default email template styling.
* Added email button, used for {{wc_cart.view_button}}, and {{general.button}}, with parameters (text,url, color)

= 0.0.16 =

* Added subscribers table to keep track of unsubscribed emails
* Added check for unsubscribed users before sending
* Added limit to stop email spamming to same address, defaults 10 minutes on same automation
* Added dropdown to enable unsubscribe link added to email footer
* Added basic styled unsubscribe pages
* Added unsubscribe preview link to test email
* Fixed issue with WC_Email
* Add hourly sending limit 

= 0.0.15 = 

* Added compatability for WC < 3.7

= 0.0.14 = 

* Added pagination to Automation queue
* Updated LogAction to only write to file when EWP_DEBUG is defined, while still adding log to activity reports

= 0.0.13 = 

* Updated WC Email template to inline styles.

= 0.0.12 = 

* Added email preview box to bottom of action form.

= 0.0.11 =

* Fixed delay ui issue with hours select showing by default

= 0.0.10 =

* Fixed issue with running email from queue, no longer copies parent scheduled time, and if single email address is present it send straight away, otherwise emails are queue.
* Added {{general.name}} {{general.description}} variables to display site name and description
* Switch from rest to Ajax when storing abandoned carts
* Added schedule settings.

= 0.0.9 =

* Added fallback argument to woocommerce name variables {{\*.first_name | fallback=''}}, {{\*.last_name | fallback=''}}, {{\*.full_name | fallback=''}}
* Added {{general.user_emails | role='subscriber'}} variable to fetch list of wordpress registered users emails.
* Added cc and bcc fields to send email.
* Added parent_id column to queue table.
* Added functionality if multiple 'to' addresses, emails separately added to the queue, allows for tracking per email.
* Added send now, or cancel buttons on automation queue.

= 0.0.8 =

Fix issue with WooCommerce causing 500 error on rest requests.

= 0.0.7 =

Initial plugin release
