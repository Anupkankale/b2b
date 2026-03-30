 <?php if ( ! defined( 'ABSPATH' ) ) exit;
function bvip_fmt_time( $s ) {
	if ( $s < 60 )  return $s . 's';
	if ( $s < 3600 ) return floor($s/60) . 'm ' . ($s%60) . 's';
	return floor($s/3600) . 'h ' . floor(($s%3600)/60) . 'm';
}
?>
<div class="wrap bvip-wrap">
	<div class="bvip-header">
		<div>
			<h1>🏢 B2B Visitor Intelligence</h1>
			<p>Which companies visit · where they come from · what they click · how long they stay</p>
		</div>
		<div class="bvip-realtime-box">
			<span class="bvip-realtime-dot"></span>
			<span id="bvip-realtime-count">–</span> visitors right now
		</div>
	</div>

	<!-- Stat Cards -->
	<div class="bvip-cards">
		<div class="bvip-card">
			<div class="bvip-card-icon">📊</div>
			<div class="bvip-card-number"><?php echo esc_html( number_format($stats['total_visits']) ); ?></div>
			<div class="bvip-card-label">Page Views (30d)</div>
		</div>
		<div class="bvip-card bvip-card-blue">
			<div class="bvip-card-icon">🏢</div>
			<div class="bvip-card-number"><?php echo esc_html( $stats['companies_identified'] ); ?></div>
			<div class="bvip-card-label">Companies Identified</div>
		</div>
		<div class="bvip-card bvip-card-green">
			<div class="bvip-card-icon">🌍</div>
			<div class="bvip-card-number"><?php echo esc_html( $stats['countries'] ); ?></div>
			<div class="bvip-card-label">Countries</div>
		</div>
		<div class="bvip-card">
			<div class="bvip-card-icon">⏱️</div>
			<div class="bvip-card-number"><?php echo esc_html( bvip_fmt_time($stats['avg_duration_seconds']) ); ?></div>
			<div class="bvip-card-label">Avg Session Duration</div>
		</div>
		<div class="bvip-card">
			<div class="bvip-card-icon">📄</div>
			<div class="bvip-card-number"><?php echo esc_html( $stats['avg_pages_per_session'] ); ?></div>
			<div class="bvip-card-label">Pages / Session</div>
		</div>
		<div class="bvip-card bvip-card-orange">
			<div class="bvip-card-icon">↩️</div>
			<div class="bvip-card-number"><?php echo esc_html( $stats['bounce_rate'] ); ?>%</div>
			<div class="bvip-card-label">Bounce Rate</div>
		</div>
	</div>

	<!-- Charts Row -->
	<div class="bvip-charts-row">
		<div class="bvip-chart-box bvip-chart-large">
			<h3>📈 Traffic — Last 30 Days</h3>
			<canvas id="bvip-traffic-chart" height="90"></canvas>
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

	<!-- Country Bar Chart -->
	<div class="bvip-section">
		<h2>🌍 Top Countries <a href="<?php echo esc_url(admin_url('admin.php?page=bvip-countries')); ?>" class="bvip-view-all">View All →</a></h2>
		<canvas id="bvip-country-chart" height="60"></canvas>
	</div>

	<!-- Company Table -->
	<div class="bvip-section">
		<div class="bvip-section-header">
			<h2>🔥 Company Visitors</h2>
			<span class="bvip-badge"><?php echo esc_html( count( $companies ) ); ?> identified</span>
		</div>
		<?php if ( empty($companies) ) : ?>
			<div class="bvip-empty">
				<p>🔍 No company data yet. Add your <a href="<?php echo esc_url(admin_url('admin.php?page=bvip-settings')); ?>">IPinfo API key</a> and visit your site from an office network.</p>
			</div>
		<?php else : ?>
		<table class="wp-list-table widefat fixed striped bvip-table">
			<thead>
				<tr>
					<th>Company</th><th>Location</th><th>Country</th>
					<th>Visits</th><th>Pages</th><th>Avg Time</th><th>Last Seen</th><th>Intent</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $companies as $c ) :
					$intent = $c->visit_count >= 5 ? '🔥 Hot' : ($c->visit_count >= 3 ? '👀 Warm' : '❄️ Cold');
					$hot    = $c->visit_count >= 5 ? 'bvip-hot-row' : '';
					$time   = $c->avg_time_on_page > 0 ? bvip_fmt_time($c->avg_time_on_page) : '—';
				?>
				<tr class="<?php echo esc_attr($hot); ?>">
					<td><strong><?php echo esc_html($c->company_name); ?></strong></td>
					<td><?php echo esc_html(trim($c->city . ', ' . $c->region, ', ')); ?></td>
					<td><?php echo esc_html($c->country_name ?: $c->country_code); ?></td>
					<td><strong><?php echo esc_html($c->visit_count); ?></strong></td>
					<td><?php echo esc_html($c->unique_pages); ?></td>
					<td><?php echo esc_html($time); ?></td>
					<td><?php echo esc_html(human_time_diff(strtotime($c->last_visit)) . ' ago'); ?></td>
					<td><?php echo esc_html($intent); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<!-- Top Pages with Time -->
	<?php if ( ! empty($top_pages) ) : ?>
	<div class="bvip-section">
		<div class="bvip-section-header">
			<h2>📄 Top Pages</h2>
			<a href="<?php echo esc_url(admin_url('admin.php?page=bvip-duration')); ?>" class="bvip-view-all">See Time on Page →</a>
		</div>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>Page</th><th>Views</th><th>Avg Time on Page</th></tr></thead>
			<tbody>
				<?php foreach ( $top_pages as $p ) : ?>
				<tr>
					<td><?php echo esc_html($p->page_title ?: '(ID: ' . $p->post_id . ')'); ?></td>
					<td><?php echo esc_html($p->views); ?></td>
					<td><?php echo $p->avg_time > 0 ? esc_html(bvip_fmt_time($p->avg_time)) : '—'; ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

</div>
