<?php if ( ! defined( 'ABSPATH' ) ) exit;
function bvip_fmt_time_dur( $s ) {
	if ( ! $s ) return '—';
	if ( $s < 60 )   return $s . ' sec';
	if ( $s < 3600 ) return floor($s/60) . 'm ' . ($s%60) . 's';
	return floor($s/3600) . 'h ' . floor(($s%3600)/60) . 'm';
}
?>
<div class="wrap bvip-wrap">
	<h1>⏱️ Time on Page</h1>
	<p class="bvip-subtitle">How long visitors actually read each page — last 30 days</p>

	<div class="bvip-section">
		<?php if ( empty( $durations ) ) : ?>
			<div class="bvip-empty">
				<p>No duration data yet. Time on page is recorded when visitors leave or switch tabs.</p>
				<p>This data will appear after visitors start browsing your site.</p>
			</div>
		<?php else : ?>
		<table class="wp-list-table widefat fixed striped bvip-table">
			<thead>
				<tr>
					<th>Page</th>
					<th>Total Views</th>
					<th>Avg Time on Page</th>
					<th>Longest Session</th>
					<th>Engagement</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$max_avg = max( array_column( (array) $durations, 'avg_seconds' ) );
				foreach ( $durations as $d ) :
					$pct = $max_avg > 0 ? round( ($d->avg_seconds / $max_avg) * 100 ) : 0;
					if ( $d->avg_seconds >= 120 )      $engagement = '🟢 High';
					elseif ( $d->avg_seconds >= 45 )   $engagement = '🟡 Medium';
					else                                $engagement = '🔴 Low';
				?>
				<tr>
					<td><strong><?php echo esc_html( $d->page_title ?: '(ID: ' . $d->post_id . ')' ); ?></strong></td>
					<td><?php echo esc_html( $d->views ); ?></td>
					<td>
						<strong><?php echo esc_html( bvip_fmt_time_dur( $d->avg_seconds ) ); ?></strong>
						<div class="bvip-mini-bar"><div style="width:<?php echo esc_attr($pct); ?>%"></div></div>
					</td>
					<td><?php echo esc_html( bvip_fmt_time_dur( $d->max_seconds ) ); ?></td>
					<td><?php echo esc_html( $engagement ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="bvip-legend">
			<span>🟢 High engagement = 2+ minutes avg</span>
			<span>🟡 Medium = 45s–2min avg</span>
			<span>🔴 Low = under 45s avg</span>
		</div>
		<?php endif; ?>
	</div>
</div>
