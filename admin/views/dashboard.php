<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap bvip-wrap">

	<div class="bvip-header">
		<div class="bvip-header-left">
			<h1>🏢 B2B Visitor Intelligence</h1>
			<p>See which companies are visiting your website right now</p>
		</div>
		<div class="bvip-header-right">
			<div class="bvip-realtime-box">
				<span class="bvip-realtime-dot"></span>
				<span id="bvip-realtime-count">–</span> visitors right now
			</div>
		</div>
	</div>

	<!-- Summary Cards -->
	<div class="bvip-cards">
		<div class="bvip-card">
			<div class="bvip-card-number"><?php echo esc_html( number_format( $stats['total_visits'] ) ); ?></div>
			<div class="bvip-card-label">Total Page Views (30d)</div>
		</div>
		<div class="bvip-card bvip-card-blue">
			<div class="bvip-card-number"><?php echo esc_html( $stats['companies_identified'] ); ?></div>
			<div class="bvip-card-label">Companies Identified</div>
		</div>
		<div class="bvip-card">
			<div class="bvip-card-number"><?php echo esc_html( $stats['avg_pages_per_session'] ); ?></div>
			<div class="bvip-card-label">Avg Pages / Session</div>
		</div>
		<div class="bvip-card bvip-card-orange">
			<div class="bvip-card-number"><?php echo esc_html( $stats['bounce_rate'] ); ?>%</div>
			<div class="bvip-card-label">Bounce Rate</div>
		</div>
	</div>

	<!-- Charts Row -->
	<div class="bvip-charts-row">
		<div class="bvip-chart-box bvip-chart-large">
			<h3>📈 Traffic Last 30 Days</h3>
			<canvas id="bvip-traffic-chart" height="100"></canvas>
		</div>
		<div class="bvip-chart-box">
			<h3>🌐 Traffic Sources</h3>
			<canvas id="bvip-source-chart"></canvas>
		</div>
		<div class="bvip-chart-box">
			<h3>📱 Devices</h3>
			<canvas id="bvip-device-chart"></canvas>
		</div>
	</div>

	<!-- Company Table -->
	<div class="bvip-section">
		<div class="bvip-section-header">
			<h2>🔥 Company Visitors</h2>
			<span class="bvip-badge"><?php echo count( $companies ); ?> companies identified</span>
		</div>

		<?php if ( empty( $companies ) ) : ?>
			<div class="bvip-empty">
				<p>🔍 No company data yet. Make sure you have added your IPinfo API key in <a href="<?php echo esc_url( admin_url( 'admin.php?page=bvip-settings' ) ); ?>">Settings</a>.</p>
				<p>Visit your website from an office or corporate network to test.</p>
			</div>
		<?php else : ?>
		<table class="wp-list-table widefat fixed striped bvip-table">
			<thead>
				<tr>
					<th>Company</th>
					<th>Location</th>
					<th>Visits</th>
					<th>Pages Viewed</th>
					<th>Sessions</th>
					<th>Last Seen</th>
					<th>Intent</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $companies as $company ) :
					$last_seen = human_time_diff( strtotime( $company->last_visit ) ) . ' ago';
					$intent    = $company->visit_count >= 5 ? '🔥 Hot' : ( $company->visit_count >= 3 ? '👀 Warm' : '❄️ Cold' );
					$row_class = $company->visit_count >= 5 ? 'bvip-hot-row' : '';
				?>
				<tr class="<?php echo esc_attr( $row_class ); ?>">
					<td><strong><?php echo esc_html( $company->company_name ); ?></strong></td>
					<td><?php echo esc_html( $company->city . ', ' . $company->region ); ?></td>
					<td><strong><?php echo esc_html( $company->visit_count ); ?></strong></td>
					<td><?php echo esc_html( $company->unique_pages ); ?></td>
					<td><?php echo esc_html( $company->sessions ); ?></td>
					<td><?php echo esc_html( $last_seen ); ?></td>
					<td><?php echo esc_html( $intent ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<!-- Top Pages -->
	<?php if ( ! empty( $top_pages ) ) : ?>
	<div class="bvip-section">
		<h2>📄 Top Pages This Month</h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr><th>Page</th><th>Views</th></tr>
			</thead>
			<tbody>
				<?php foreach ( $top_pages as $page ) : ?>
				<tr>
					<td><?php echo esc_html( $page->post_title ?: $page->post_id ); ?></td>
					<td><?php echo esc_html( $page->views ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

</div>
