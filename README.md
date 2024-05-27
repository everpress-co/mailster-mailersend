# Mailster MailerSend Integration

Contributors: everpress, mailster, xaverb  
Tags: mailersend, mailster, deliverymethod, newsletter, mailsteresp, email  
Requires at least: 6.0  
Tested up to: 6.5  
Stable tag: 1.1.3  
License: GPLv2 or later

## Description

> This Plugin requires [Mailster Newsletter Plugin for WordPress](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=MailerSend)

Uses MailerSend to deliver emails for the [Mailster Newsletter Plugin for WordPress](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=MailerSend).

## Installation

1. Upload the entire `mailster-mailersend` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings => Newsletter => Delivery and select the `MailerSend` tab
4. Enter your API Key and save the settings
5. Send a testmail

## Screenshots

### 1. Option Interface

![Option Interface.](https://ps.w.org/mailster-mailersend/assets/screenshot-1.png)

## Changelog

### 1.1.3

- fixed: waringin on PHP >8.1

### 1.1.2

- Do not handle soft bounces within the plugin but wait for a hard bounce

### 1.1.1

- fixed: issue with embedded images

### 1.1

- improvements on bounce methods and web hook creation

### 1.0

- initial release

## Additional Info

This Plugin requires [Mailster Newsletter Plugin for WordPress](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=MailerSend)
