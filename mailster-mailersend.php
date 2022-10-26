<?php
/*
Plugin Name: Mailster MailerSend Integration
Plugin URI: https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=plugin&utm_term=MailerSend
Description: Uses MailerSend to deliver emails for the Mailster Newsletter Plugin for WordPress.
Version: 1.1.2
Author: EverPress
Author URI: https://mailster.co
Text Domain: mailster-mailersend
License: GPLv2 or later
*/


define( 'MAILSTER_MAILERSEND_VERSION', '1.1.2' );
define( 'MAILSTER_MAILERSEND_REQUIRED_VERSION', '3.0' );
define( 'MAILSTER_MAILERSEND_FILE', __FILE__ );

require_once dirname( __FILE__ ) . '/classes/mailersend.class.php';
new MailsterMailerSend();
