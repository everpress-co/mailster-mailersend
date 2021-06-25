<?php $mailersend_apikey = mailster_option( 'mailersend_apikey' ); ?>
<table class="form-table">
	<?php if ( ! $verified ) : ?>
	<tr valign="top">
		<th scope="row">&nbsp;</th>
		<td><p class="description"><?php echo sprintf( __( 'You need a %s account to use this service!', 'mailster-mailersend' ), '<a href="https://refer.mailersend.com/mailster" class="external">MailerSend</a>' ); ?></p>
		</td>
	</tr>
	<?php endif; ?>
	<tr valign="top">
		<th scope="row"><?php esc_html_e( 'MailerSend API token', 'mailster-mailersend' ); ?></th>
		<td>
			<?php if ( ! empty( $mailersend_apikey ) ) : ?>
			<input type="password" name="mailster_options[mailersend_apikey]" value="<?php echo esc_attr( $mailersend_apikey ); ?>" class="regular-text">
			<?php else : ?>
				<textarea rows="10" cols="40" class="large-text code" name="mailster_options[mailersend_apikey]"></textarea>
			<?php endif; ?>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">&nbsp;</th>
		<td>
			<?php if ( $verified && $mailersend_apikey ) : ?>
			<span style="color:#3AB61B">&#10004;</span> <?php esc_html_e( 'Your API Token is ok!', 'mailster-mailersend' ); ?>
			<?php else : ?>
			<span style="color:#D54E21">&#10006;</span> <?php esc_html_e( 'Your API Token is WRONG!', 'mailster-mailersend' ); ?>
			<?php endif; ?>

			<input type="hidden" name="mailster_options[mailersend_verified]" value="<?php echo $verified; ?>">
		</td>
	</tr>
</table>
<?php if ( 'mailersend' == mailster_option( 'deliverymethod' ) ) : ?>
<div class="<?php echo ( ! $verified ) ? 'hidden' : ''; ?>">
<table class="form-table">
	<tr valign="top">
		<th scope="row"><?php esc_html_e( 'Sending Domain', 'mailster-mailersend' ); ?></th>
		<td>
		<?php $domains = $verified ? $this->get_sending_domains() : array(); ?>
		<?php if ( is_wp_error( $domains ) ) : ?>
			<div class="error inline"><p><strong><?php esc_html_e( 'Not able to get your domains. Make sure your API Token is allowed to read them! Mailster will use your Master Account.', 'mailster-mailersend' ); ?></strong></p></div>
		<?php else : ?>
		<p class="howto"><?php esc_html_e( 'Send From Following Domain', 'mailster-mailersend' ); ?></p>
		<select name="mailster_options[mailersend_domain]">
			<option value=""><?php esc_html_e( 'Choose domain', 'mailster-mailersend' ); ?></option>
			<?php foreach ( $domains as $domain ) : ?>
			<option value="<?php echo esc_attr( $domain->id ); ?>" <?php selected( mailster_option( 'mailersend_domain' ), $domain->id ); ?>><?php echo esc_html( $domain->name . ' (' . $domain->is_verified . ')' ); ?></option>
		<?php endforeach; ?>
		</select> <a href="https://app.mailersend.com/domains" class="external"><?php esc_html_e( 'Manage your Domains', 'mailster-mailersend' ); ?></a>
		<?php endif; ?>
		</td>
	</tr>
</table>
</div>
<?php else : ?>
<input type="hidden" name="mailster_options[mailersend_domain]" value="<?php echo esc_attr( mailster_option( 'mailersend_domain' ) ); ?>">
	<?php if ( $verified ) : ?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">&nbsp;</th>
			<td><div class="notice notice-warning inline"><p><strong><?php esc_html_e( 'Please save your settings to access further delivery options!', 'mailster-mailersend' ); ?></strong></p></div></td>
		</tr>
	</table>
	<?php endif; ?>
<?php endif; ?>
<input type="hidden" name="mailster_options[mailersend_key]" value="<?php echo esc_attr( mailster_option( 'mailersend_key' ) ); ?>">
