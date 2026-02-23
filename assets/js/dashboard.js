(function ($) {
	'use strict';
	var d = typeof bvipChartData !== 'undefined' ? bvipChartData : {};

	var BLUE = '#1E3A5F', ACCENT = '#2E86C1', GREEN = '#1E8449', ORANGE = '#D35400', PURPLE = '#7D3C98';
	var COLORS = [BLUE, ACCENT, GREEN, ORANGE, PURPLE, '#2980B9', '#16A085', '#C0392B'];

	// Traffic line chart
	var trafficEl = document.getElementById('bvip-traffic-chart');
	if (trafficEl && d.dates) {
		new Chart(trafficEl.getContext('2d'), {
			type: 'line',
			data: {
				labels: d.dates,
				datasets: [{ label: 'Page Views', data: d.pageviews,
					borderColor: BLUE, backgroundColor: 'rgba(30,58,95,0.08)',
					borderWidth: 2, pointRadius: 3, tension: 0.4, fill: true }]
			},
			options: { responsive: true, plugins: { legend: { display: false } },
				scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { maxTicksLimit: 10 } } } }
		});
	}

	// Source doughnut
	var sourceEl = document.getElementById('bvip-source-chart');
	if (sourceEl && d.sourceLbls) {
		new Chart(sourceEl.getContext('2d'), {
			type: 'doughnut',
			data: { labels: d.sourceLbls, datasets: [{ data: d.sourceCnts, backgroundColor: COLORS, borderWidth: 2 }] },
			options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
		});
	}

	// Device doughnut
	var deviceEl = document.getElementById('bvip-device-chart');
	if (deviceEl && d.deviceLbls) {
		new Chart(deviceEl.getContext('2d'), {
			type: 'doughnut',
			data: { labels: d.deviceLbls, datasets: [{ data: d.deviceCnts, backgroundColor: [BLUE, GREEN, ORANGE], borderWidth: 2 }] },
			options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
		});
	}

	// Country bar chart
	var countryEl = document.getElementById('bvip-country-chart');
	if (countryEl && d.countryLbls) {
		new Chart(countryEl.getContext('2d'), {
			type: 'bar',
			data: {
				labels: d.countryLbls,
				datasets: [{ label: 'Visits', data: d.countryCnts,
					backgroundColor: d.countryLbls.map(function(_, i){ return COLORS[i % COLORS.length]; }),
					borderRadius: 4 }]
			},
			options: { responsive: true, indexAxis: 'y',
				plugins: { legend: { display: false } },
				scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
		});
	}

	// Realtime counter
	function updateRealtime() {
		$.post(d.ajaxUrl, { action: 'bvip_realtime', nonce: d.nonce }, function (res) {
			if (res.success) $('#bvip-realtime-count').text(res.data.count);
		});
	}
	if ($('#bvip-realtime-count').length) { updateRealtime(); setInterval(updateRealtime, 30000); }

}(jQuery));
