(function ($) {
	'use strict';

	var data = typeof bvipChartData !== 'undefined' ? bvipChartData : {};

	// ── Traffic Line Chart ──────────────────────────────────────────────────
	var trafficCtx = document.getElementById('bvip-traffic-chart');
	if (trafficCtx && data.dates && data.pageviews) {
		new Chart(trafficCtx.getContext('2d'), {
			type: 'line',
			data: {
				labels: data.dates,
				datasets: [{
					label: 'Page Views',
					data: data.pageviews,
					borderColor: '#1E3A5F',
					backgroundColor: 'rgba(30,58,95,0.08)',
					borderWidth: 2,
					pointRadius: 3,
					tension: 0.4,
					fill: true,
				}]
			},
			options: {
				responsive: true,
				plugins: { legend: { display: false } },
				scales: {
					y: { beginAtZero: true, ticks: { precision: 0 } },
					x: { ticks: { maxTicksLimit: 10 } }
				}
			}
		});
	}

	// ── Traffic Source Doughnut ─────────────────────────────────────────────
	var sourceCtx = document.getElementById('bvip-source-chart');
	if (sourceCtx && data.sourceLbls) {
		new Chart(sourceCtx.getContext('2d'), {
			type: 'doughnut',
			data: {
				labels: data.sourceLbls,
				datasets: [{
					data: data.sourceCnts,
					backgroundColor: ['#1E3A5F','#2E86C1','#1E8449','#D35400'],
					borderWidth: 2,
				}]
			},
			options: {
				responsive: true,
				plugins: { legend: { position: 'bottom' } }
			}
		});
	}

	// ── Device Doughnut ─────────────────────────────────────────────────────
	var deviceCtx = document.getElementById('bvip-device-chart');
	if (deviceCtx && data.deviceLbls) {
		new Chart(deviceCtx.getContext('2d'), {
			type: 'doughnut',
			data: {
				labels: data.deviceLbls,
				datasets: [{
					data: data.deviceCnts,
					backgroundColor: ['#2E86C1','#1E8449','#D35400'],
					borderWidth: 2,
				}]
			},
			options: {
				responsive: true,
				plugins: { legend: { position: 'bottom' } }
			}
		});
	}

	// ── Real-Time Counter ───────────────────────────────────────────────────
	function updateRealtime() {
		$.post(data.ajaxUrl, { action: 'bvip_realtime', nonce: data.nonce }, function (res) {
			if (res.success) {
				$('#bvip-realtime-count').text(res.data.count);
			}
		});
	}

	if ($('#bvip-realtime-count').length) {
		updateRealtime();
		setInterval(updateRealtime, 30000);
	}

}(jQuery));
