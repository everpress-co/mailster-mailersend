<?php

class MailsterMailerSend {

	private $plugin_path;
	private $plugin_url;
	private $domain;
	private $apikey;

	/**
	 *
	 */
	public function __construct() {

		$this->plugin_path = plugin_dir_path( MAILSTER_MAILERSEND_FILE );
		$this->plugin_url  = plugin_dir_url( MAILSTER_MAILERSEND_FILE );

		register_activation_hook( MAILSTER_MAILERSEND_FILE, array( &$this, 'activate' ) );
		register_deactivation_hook( MAILSTER_MAILERSEND_FILE, array( &$this, 'deactivate' ) );

		load_plugin_textdomain( 'mailster-mailersend' );

		add_action( 'init', array( &$this, 'init' ), 1 );
	}


	/*
	 * init the plugin
	 *
	 * @access public
	 * @return void
	 */
	public function init() {

		if ( ! function_exists( 'mailster' ) ) {

			add_action( 'admin_notices', array( &$this, 'notice' ) );

		} else {

			add_filter( 'mailster_delivery_methods', array( &$this, 'delivery_method' ) );
			add_action( 'mailster_deliverymethod_tab_mailersend', array( &$this, 'deliverytab' ) );

			add_filter( 'mailster_verify_options', array( &$this, 'verify_options' ) );

			if ( mailster_option( 'deliverymethod' ) == 'mailersend' ) {
				add_action( 'mailster_initsend', array( &$this, 'initsend' ) );
				add_action( 'mailster_presend', array( &$this, 'presend' ) );
				add_action( 'mailster_dosend', array( &$this, 'dosend' ) );
				add_action( 'mailster_section_tab_bounce', array( &$this, 'section_tab_bounce' ) );
				add_filter( 'mailster_subscriber_errors', array( $this, 'subscriber_errors' ) );

				if ( isset( $_GET['mailster_mailersend'] ) ) {
					$this->handle_bounce();
				}
			}
		}
	}


	/**
	 * initsend function.
	 *
	 * uses mailster_initsend hook to set initial settings
	 *
	 * @access public
	 * @return void
	 * @param mixed $mailobject
	 */
	public function initsend( $mailobject ) {

		// MailerSend will handle DKIM integration
		$mailobject->dkim = false;
	}


	/**
	 * presend function.
	 *
	 * uses the mailster_presend hook to apply settings before each mail
	 *
	 * @access public
	 * @return void
	 * @param mixed $mailobject
	 */
	public function presend( $mailobject ) {

		$mailobject->pre_send();

		$mailobject->mailersend_object = array();

		if ( $tracking_options = mailster_option( 'mailersend_track' ) ) {
			$open_tracking  = 'opens' == $tracking_options || 'opens,clicks' == $tracking_options;
			$click_tracking = 'clicks' == $tracking_options || 'opens,clicks' == $tracking_options;
		}

		$data = array(
			'mailster_id'   => mailster_option( 'ID' ),
			'campaign_id'   => (string) $mailobject->campaignID,
			'index'         => (string) $mailobject->index,
			'subscriber_id' => (string) $mailobject->subscriberID,
		);

		$data = json_encode( $data );
		$data = base64_encode( $data );

		$mailobject->mailersend_object['tags'] = array( 'mailster' => $data );

		$recipients = array();

		foreach ( $mailobject->to as $i => $to ) {
			if ( ! $mailobject->to[ $i ] ) {
				continue;
			}
			$recipients[] = array(
				'email' => $mailobject->to[ $i ],
				'name'  => $mailobject->to_name[ $i ] ? $mailobject->to_name[ $i ] : null,
			);
		}

		$mailobject->mailersend_object['from']     = array(
			'email' => $mailobject->from,
			'name'  => $mailobject->from_name,
		);
		$mailobject->mailersend_object['to']       = $recipients;
		$mailobject->mailersend_object['text']     = $mailobject->mailer->AltBody;
		$mailobject->mailersend_object['html']     = $mailobject->mailer->Body;
		$mailobject->mailersend_object['subject']  = $mailobject->subject;
		$mailobject->mailersend_object['reply_to'] = array(
			'email' => $mailobject->reply_to,
		);

		if ( ! empty( $mailobject->attachments ) || $mailobject->embed_images ) {

			$org_attachments                              = $mailobject->mailer->getAttachments();
			$mailobject->mailersend_object['attachments'] = array();

			foreach ( $org_attachments as $attachment ) {

				$attachment_obj = array(
					'content'  => base64_encode( file_get_contents( $attachment[0] ) ),
					'filename' => $attachment[1],
				);

				if ( 'inline' === $attachment[6] ) {
					$attachment_obj['id']          = $attachment[7];
					$attachment_obj['disposition'] = 'inline';
				}
				$mailobject->mailersend_object['attachments'][] = $attachment_obj;
			}
		}

		$mailobject->mailersend_object = apply_filters( 'mailster_mailersend_object', $mailobject->mailersend_object, $mailobject );
	}


	/**
	 * dosend function.
	 *
	 * uses the mailster_dosend hook and triggers the send
	 *
	 * @access public
	 * @param mixed $mailobject
	 * @return void
	 */
	public function dosend( $mailobject ) {

		if ( ! isset( $mailobject->mailersend_object ) ) {
			$mailobject->set_error( __( 'MailerSend options not defined', 'mailster-mailersend' ) );
			$mailobject->sent = false;
			return false;
		}

		$response = $this->do_post( 'email', $mailobject->mailersend_object, 60 );

		if ( is_wp_error( $response ) ) {
			$code = $response->get_error_code();
			if ( 403 == $code ) {
				$errormessage = __( 'Not able to send message. Make sure your API Key is allowed to read and write Transmissions!', 'mailster-mailersend' );
			} else {
				$errormessage = $response->get_error_message();
			}
			$mailobject->set_error( $errormessage );
			$mailobject->sent = false;
		} else {
			$mailobject->sent = true;
		}
	}



	/**
	 * delivery_method function.
	 *
	 * add the delivery method to the options
	 *
	 * @access public
	 * @param mixed $delivery_methods
	 * @return void
	 */
	public function delivery_method( $delivery_methods ) {
		$delivery_methods['mailersend'] = 'MailerSend';
		return $delivery_methods;
	}


	/**
	 * deliverytab function.
	 *
	 * the content of the tab for the options
	 *
	 * @access public
	 * @return void
	 */
	public function deliverytab() {

		$verified = mailster_option( 'mailersend_verified' );

		$this->maybe_create_webhooks();

		include $this->plugin_path . '/views/settings.php';
	}


	public function do_get( $endpoint, $args = array(), $timeout = 15 ) {
		return $this->do_call( 'GET', $endpoint, $args, $timeout );
	}
	public function do_post( $endpoint, $args = array(), $timeout = 15 ) {
		return $this->do_call( 'POST', $endpoint, $args, $timeout );
	}


	private function do_call( $method, $endpoint, $args = array(), $timeout = 15 ) {

		$args                = wp_parse_args( $args, array() );
		$body                = null;
		$apikey              = isset( $this->apikey ) ? $this->apikey : mailster_option( 'mailersend_apikey' );
		$domain              = isset( $this->domain ) ? $this->domain : mailster_option( 'mailersend_domain' );
		$mailersend_endpoint = 'https://api.mailersend.com/v1/';
		$url                 = $mailersend_endpoint . $endpoint;

		$headers = array(
			'Authorization' => 'Bearer ' . $apikey,
		);

		if ( 'GET' == $method ) {
			$url = add_query_arg( $args, $url );
		} elseif ( 'POST' == $method ) {
			$body = $args;
		} else {
			return new WP_Error( 'method_not_allowed', 'This method is not allowed' );
		}

		$response = wp_remote_request(
			$url,
			array(
				'method'  => $method,
				'headers' => $headers,
				'timeout' => $timeout,
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 202 != $code && 201 != $code && 200 != $code ) {
			$body = json_decode( $body );
			if ( isset( $body->errors ) ) {
				$message = '';
				foreach ( $body->errors as $key => $error ) {
					$message .= '[' . $key . '] ' . implode( ' ', $error );
				}
			} else {
				$message = wp_remote_retrieve_response_message( $response );
			}
			return new WP_Error( $code, $message );
		} else {
			$body = json_decode( $body );
			if ( ! empty( $body ) && 'POST' == $method ) {
				return new WP_Error( $body->warnings[0]->type, $body->warnings[0]->message, $body->warnings[0]->recipients );
			}
		}

		return $body;
	}


	/**
	 *
	 * @access public
	 * @return void
	 */
	public function verify( $apikey = null ) {

		if ( ! is_null( $apikey ) ) {
			$this->apikey = $apikey;
		}

		$response = $this->get_sending_domains();

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $response;
	}


	/**
	 *
	 * @access public
	 * @return void
	 */
	public function get_sending_domains() {

		$this->domain = '';
		$response     = $this->do_get( 'domains', 'limit=100' );

		$this->domain = null;

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$domains = $response->data;

		return $domains;
	}



	/**
	 *
	 * @access public
	 * @param mixed $options
	 * @return void
	 */
	public function verify_options( $options ) {

		if ( $timestamp = wp_next_scheduled( 'mailster_mailersend_cron' ) ) {
			wp_unschedule_event( $timestamp, 'mailster_mailersend_cron' );
		}

		if ( $options['deliverymethod'] == 'mailersend' ) {

			$old_apikey          = mailster_option( 'mailersend_apikey' );
			$old_delivery_method = mailster_option( 'deliverymethod' );

			if ( ! wp_next_scheduled( 'mailster_mailersend_cron' ) ) {
				wp_schedule_event( time(), 'mailster_cron_interval', 'mailster_mailersend_cron' );
			}

			if ( $old_apikey != $options['mailersend_apikey'] || ! $options['mailersend_verified'] || $old_delivery_method != 'mailersend' ) {
				$response = $this->verify( $options['mailersend_apikey'] );

				if ( is_wp_error( $response ) ) {
					$options['mailersend_verified'] = false;
					add_settings_error( 'mailster_options', 'mailster_options', __( 'Not able to get Account details. Make sure your API Key is correct and allowed to read Account details!', 'mailster-mailersend' ) );
				} else {

					$options['mailersend_verified'] = true;
				}
			}
		}

		return $options;
	}



	public function maybe_create_webhooks( $token = null ) {

		if ( mailster_is_local() ) {
			return;
		}
		if ( $key = mailster_option( 'mailersend_key' ) ) {
			return;
		}
		if ( ! ( $domain_id = mailster_option( 'mailersend_domain' ) ) ) {
			return;
		}
		if ( ! ( $verified  = mailster_option( 'mailersend_verified' ) ) ) {
			return;
		}

		$response = $this->do_get( 'webhooks', array( 'domain_id' => $domain_id ) );
		$endpoint = add_query_arg( array( 'mailster_mailersend' => '' ), home_url( '/' ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$hooks = $response->data;
		$found = null;

		foreach ( $hooks as $hook ) {
			if ( false === strpos( $hook->url, $endpoint ) ) {
				continue;
			}
			$found = $hook;
		}

		if ( $found ) {
			$url   = $found->url;
			$query = parse_url( $found->url, PHP_URL_QUERY );
			parse_str( $query, $data );
			if ( isset( $data['mailster_mailersend'] ) ) {
				$key = $data['mailster_mailersend'];
			} else {
				$found = null;
			}
		}

		if ( ! $found ) {
			$key      = md5( uniqid() );
			$args     = array(
				'url'       => add_query_arg( array( 'mailster_mailersend' => $key ), $endpoint ),
				'name'      => 'Mailster (' . preg_replace( '/^https?:\/\//', '', home_url() ) . ')',
				'events'    => array( 'activity.soft_bounced', 'activity.hard_bounced', 'activity.unsubscribed', 'activity.spam_complaint' ),
				'enabled'   => true,
				'domain_id' => $domain_id,
			);
			$response = $this->do_post( 'webhooks', $args );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		mailster_update_option( 'mailersend_key', $key );
	}


	private function handle_bounce() {

		if ( mailster_option( 'mailersend_key' ) == $_GET['mailster_mailersend'] && $_SERVER['REQUEST_METHOD'] == 'POST' ) {

			if ( ! ( $data = file_get_contents( 'php://input' ) ) ) {
				wp_die( 'This page handles the Bounces and messages from MailerSend for Mailster.', 'Mailster MailerSend Endpoint' );
			}

			$obj = json_decode( $data );

			$domain_id = mailster_option( 'mailersend_domain' );
			$MID       = mailster_option( 'ID' );

			if ( $domain_id != $obj->domain_id ) {
				return;
			}

			if ( ! isset( $obj->data->email->tags->mailster ) ) {
				return;
			}

			$data = json_decode( base64_decode( $obj->data->email->tags->mailster ) );

			if ( ! isset( $data->mailster_id ) || $data->mailster_id != $MID ) {
				return;
			}

			if ( isset( $data->subscriber_id ) && $data->subscriber_id ) {
				$subscriber = mailster( 'subscribers' )->get( $data->subscriber_id );
			} else {
				$subscriber = mailster( 'subscribers' )->get_by_mail( $obj->data->email->recipient->email );
			}
			if ( ! $subscriber ) {
				return;
			}
			if ( isset( $data->campaign_id ) ) {
				$campaign_id = $data->campaign_id;
			} else {
				$campaign_id = null;
			}
			if ( isset( $data->index ) ) {
				$index = $data->index;
			} else {
				$index = null;
			}

			switch ( $obj->type ) {
				case 'rejected':
					break;
				case 'activity.hard_bounced':
					$reason = trim( '[' . $obj->data->morph->object . '] ' . $obj->data->morph->reason );
					mailster( 'subscribers' )->bounce( $subscriber->ID, $campaign_id, true, $reason, $index );
					break;
				case 'activity.unsubscribed':
				case 'activity.spam_complaint':
					mailster( 'subscribers' )->unsubscribe( $subscriber->ID, $campaign_id, $result->event, $index );
					break;
				default:
					break;
			}
		}
	}




	public function subscriber_errors( $errors ) {
		$errors[] = 'All of the recipients provided have been suppressed.';
		$errors[] = 'Some of the recipients have been suppressed.';
		return $errors;
	}


	/**
	 * section_tab_bounce function.
	 *
	 * displays a note on the bounce tab
	 *
	 * @access public
	 * @param mixed $options
	 * @return void
	 */
	public function section_tab_bounce() {

		?>
		<div class="error inline"><p><strong><?php esc_html_e( 'Bouncing is handled by MailerSend so all your settings will be ignored', 'mailster-mailersend' ); ?></strong></p></div>

		<?php
	}



	/**
	 * Notice if Mailster is not available
	 *
	 * @access public
	 * @return void
	 */
	public function notice() {
		?>
	<div id="message" class="error">
		<p>
		<strong>MailerSend integration for Mailster</strong> requires the <a href="https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=plugin&utm_term=MailerSend">Mailster Newsletter Plugin</a>, at least version <strong><?php echo MAILSTER_MAILERSEND_REQUIRED_VERSION; ?></strong>.
		</p>
	</div>
		<?php
	}



	/**
	 * activate function
	 *
	 * @access public
	 * @return void
	 */
	public function activate() {

		if ( function_exists( 'mailster' ) ) {

			mailster_notice( sprintf( __( 'Change the delivery method on the %s!', 'mailster-mailersend' ), '<a href="edit.php?post_type=newsletter&page=mailster_settings&mailster_remove_notice=delivery_method#delivery">' . __( 'Settings Page', 'mailster-mailersend' ) . '</a>' ), '', 360, 'delivery_method' );

			$defaults = array(
				'mailersend_apikey'   => '',
				'mailersend_domain'   => null,
				'mailersend_track'    => 0,
				'mailersend_tags'     => '',
				'mailersend_verified' => false,
			);

			$mailster_options = mailster_options();

			foreach ( $defaults as $key => $value ) {
				if ( ! isset( $mailster_options[ $key ] ) ) {
					mailster_update_option( $key, $value );
				}
			}
		}
	}


	/**
	 * deactivate function
	 *
	 * @access public
	 * @return void
	 */
	public function deactivate() {

		if ( function_exists( 'mailster' ) ) {
			if ( mailster_option( 'deliverymethod' ) == 'mailersend' ) {
				mailster_update_option( 'deliverymethod', 'simple' );
				mailster_notice( sprintf( __( 'Change the delivery method on the %s!', 'mailster-mailersend' ), '<a href="edit.php?post_type=newsletter&page=mailster_settings&mailster_remove_notice=delivery_method#delivery">Settings Page</a>' ), '', 360, 'delivery_method' );
			}
		}
	}
}
