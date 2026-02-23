<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap bvip-wrap">
	<h1>🖱️ Click Tracking</h1>
	<p class="bvip-subtitle">What buttons and links visitors click — last 30 days</p>

	<!-- Click type summary cards -->
	<div class="bvip-cards bvip-cards-small">
		<?php foreach ( $clicks_type as $type ) : ?>
		<div class="bvip-card">
			<div class="bvip-card-icon"><?php echo $type->element_type === 'button' ? '🔘' : '🔗'; ?></div>
			<div class="bvip-card-number"><?php echo esc_html( number_format( $type->count ) ); ?></div>
			<div class="bvip-card-label"><?php echo esc_html( ucfirst( $type->element_type ) ); ?> Clicks</div>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Clicks table -->
	<div class="bvip-section">
		<h2>Most Clicked Elements</h2>
		<?php if ( empty( $clicks ) ) : ?>
			<div class="bvip-empty">
				<p>No click data yet. Clicks are tracked automatically once visitors start using your site.</p>
			</div>
		<?php else : ?>
		<table class="wp-list-table widefat fixed striped bvip-table">
			<thead>
				<tr>
					<th>Element Text</th>
					<th>Type</th>
					<th>Clicks</th>
					<th>Unique Visitors</th>
					<th>Target URL</th>
					<th>Page</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $clicks as $click ) :
					$target = ! empty( $click->target_url ) ? parse_url( $click->target_url, PHP_URL_PATH ) : '—';
					$page   = ! empty( $click->page_url )   ? parse_url( $click->page_url,   PHP_URL_PATH ) : '—';
					$icon   = $click->element_type === 'button' ? '🔘' : '🔗';
				?>
				<tr>
					<td>
						<strong><?php echo esc_html( $click->element_text ?: '(no text)' ); ?></strong>
					</td>
					<td><?php echo esc_html( $icon . ' ' . ucfirst( $click->element_type ) ); ?></td>
					<td>
						<strong><?php echo esc_html( $click->click_count ); ?></strong>
						<div class="bvip-mini-bar">
							<div style="width:<?php echo esc_attr( min( ($click->click_count / max(1,$clicks[0]->click_count)) * 100, 100 ) ); ?>%"></div>
						</div>
					</td>
					<td><?php echo esc_html( $click->unique_clickers ); ?></td>
					<td><span class="bvip-url-pill"><?php echo esc_html( $target ); ?></span></td>
					<td><span class="bvip-url-pill"><?php echo esc_html( $page ); ?></span></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>
</div>
