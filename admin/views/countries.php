<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap bvip-wrap">
	<h1>🌍 Visitors by Country</h1>
	<p class="bvip-subtitle">Last 30 days — which countries your visitors come from</p>

	<?php if ( empty( $countries ) ) : ?>
		<div class="bvip-empty"><p>No country data yet. Make sure your IPinfo API key is set in Settings.</p></div>
	<?php else : ?>

	<!-- Flag grid summary -->
	<div class="bvip-country-grid">
		<?php foreach ( array_slice( $countries, 0, 12 ) as $country ) : ?>
		<div class="bvip-country-card">
			<div class="bvip-flag"><?php echo esc_html( strtolower( $country->country_code ) ); ?></div>
			<div class="bvip-country-name"><?php echo esc_html( $country->country_name ?: $country->country_code ); ?></div>
			<div class="bvip-country-visits"><?php echo esc_html( number_format( $country->visits ) ); ?> visits</div>
			<div class="bvip-country-meta"><?php echo esc_html( $country->companies ); ?> companies · <?php echo esc_html( $country->sessions ); ?> sessions</div>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Full table -->
	<div class="bvip-section">
		<h2>All Countries</h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Country</th>
					<th>Code</th>
					<th>Total Visits</th>
					<th>Sessions</th>
					<th>Companies Identified</th>
					<th>% of Traffic</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$total_visits = array_sum( array_column( $countries, 'visits' ) );
				foreach ( $countries as $country ) :
					$pct = $total_visits > 0 ? round( ( $country->visits / $total_visits ) * 100, 1 ) : 0;
				?>
				<tr>
					<td><strong><?php echo esc_html( $country->country_name ?: $country->country_code ); ?></strong></td>
					<td><?php echo esc_html( $country->country_code ); ?></td>
					<td><?php echo esc_html( number_format( $country->visits ) ); ?></td>
					<td><?php echo esc_html( $country->sessions ); ?></td>
					<td><?php echo esc_html( $country->companies ); ?></td>
					<td>
						<div class="bvip-bar-wrap">
							<div class="bvip-bar" style="width:<?php echo esc_attr( min( $pct * 3, 100 ) ); ?>%"></div>
							<span><?php echo esc_html( $pct ); ?>%</span>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
</div>
