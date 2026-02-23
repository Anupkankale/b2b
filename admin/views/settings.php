<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap bvip-wrap">
	<h1>⚙️ B2B Visitor Intelligence — Settings</h1>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'bvip_settings' ); ?>

		<!-- API Configuration -->
		<div class="bvip-settings-section">
			<h2>🔑 API Configuration</h2>
			<table class="form-table">
				<tr>
					<th><label for="bvip_ipinfo_key">IPinfo.io API Key</label></th>
					<td>
						<input type="text" id="bvip_ipinfo_key" name="bvip_ipinfo_key"
							value="<?php echo esc_attr( get_option( 'bvip_ipinfo_key' ) ); ?>"
							class="regular-text" placeholder="your_api_token_here" />
						<p class="description">
							Get your free key at <a href="https://ipinfo.io" target="_blank">ipinfo.io</a>
							— 50,000 requests/month free. This identifies companies from visitor IPs.
						</p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Alert Configuration -->
		<div class="bvip-settings-section">
			<h2>🔔 Hot Lead Alerts</h2>
			<table class="form-table">
				<tr>
					<th><label for="bvip_alert_email">Sales Alert Email</label></th>
					<td>
						<input type="email" id="bvip_alert_email" name="bvip_alert_email"
							value="<?php echo esc_attr( get_option( 'bvip_alert_email', get_option( 'admin_email' ) ) ); ?>"
							class="regular-text" />
						<p class="description">Email address to receive hot lead notifications.</p>
					</td>
				</tr>
				<tr>
					<th><label for="bvip_alert_threshold">Alert Threshold</label></th>
					<td>
						<input type="number" id="bvip_alert_threshold" name="bvip_alert_threshold"
							value="<?php echo esc_attr( get_option( 'bvip_alert_threshold', 3 ) ); ?>"
							min="1" max="50" class="small-text" />
						<p class="description">Send alert when a company visits this many times in 7 days.</p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Data Management -->
		<div class="bvip-settings-section">
			<h2>🗄️ Data Management</h2>
			<table class="form-table">
				<tr>
					<th><label for="bvip_data_retention">Keep Data For</label></th>
					<td>
						<select id="bvip_data_retention" name="bvip_data_retention">
							<?php
							$retention = (int) get_option( 'bvip_data_retention', 365 );
							$options   = array( 90 => '90 Days', 180 => '180 Days', 365 => '1 Year', 0 => 'Forever' );
							foreach ( $options as $val => $label ) {
								printf( '<option value="%d"%s>%s</option>', $val, selected( $retention, $val, false ), esc_html( $label ) );
							}
							?>
						</select>
						<p class="description">Older data will be automatically deleted.</p>
					</td>
				</tr>
				<tr>
					<th>Cache</th>
					<td>
						<button type="button" id="bvip-flush-cache" class="button">
							🔄 Flush Analytics Cache
						</button>
						<span id="bvip-flush-msg" style="margin-left:10px;color:green;display:none;">Cache cleared!</span>
						<p class="description">Clear cached stats to see the latest data immediately.</p>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button( 'Save Settings' ); ?>
	</form>

	<!-- How To Test -->
	<div class="bvip-settings-section bvip-how-to">
		<h2>🧪 How To Test</h2>
		<ol>
			<li>Add your IPinfo.io API key above and save</li>
			<li>Visit your website from your office internet connection (not WiFi from home)</li>
			<li>Come back to this dashboard in a few seconds</li>
			<li>You should see your company name appear in the Company Visitors table</li>
			<li>Residential ISPs show as ISP name — corporate offices show company name</li>
		</ol>
	</div>
</div>

<script>
jQuery(function($){
	$('#bvip-flush-cache').on('click', function(){
		$.post(ajaxurl, {
			action: 'bvip_flush_cache',
			nonce: '<?php echo esc_js( wp_create_nonce( 'bvip_nonce' ) ); ?>'
		}, function(){ $('#bvip-flush-msg').fadeIn().delay(2000).fadeOut(); });
	});
});
</script>
