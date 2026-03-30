(function () {
	'use strict';
	if (typeof bvipData === 'undefined') return;

	var visitId     = null;
	var sessionHash = null;
	var pageStart   = Date.now();
	var restBase    = bvipData.restUrl + 'b2b-analytics/v1/';
	var nonce       = bvipData.nonce;

	function getUTMParams() {
		var result = { utm_source: '', utm_medium: '', utm_campaign: '' };
		var search = window.location.search.substring(1);
		if ( ! search ) return result;
		search.split('&').forEach(function (pair) {
			var idx = pair.indexOf('=');
			if (idx < 1) return;
			var key = decodeURIComponent(pair.substring(0, idx));
			if (key in result) {
				result[key] = decodeURIComponent(pair.substring(idx + 1));
			}
		});
		return result;
	}

	// ── 1. Register Page Visit ─────────────────────────────────────────────
	window.addEventListener('load', function () {
		var utm = getUTMParams();
		fetch(restBase + 'track', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
			body: JSON.stringify({
				post_id:      parseInt(bvipData.postId, 10) || 0,
				referrer:     document.referrer || '',
				screen_width: window.innerWidth || 0,
				page_url:     bvipData.pageUrl || window.location.href,
				page_title:   document.title,
				utm_source:   utm.utm_source,
				utm_medium:   utm.utm_medium,
				utm_campaign: utm.utm_campaign,
			}),
		})
		.then(function (r) { return r.json(); })
		.then(function (d) {
			if (d && d.visit_id) {
				visitId     = d.visit_id;
				sessionHash = d.session_hash;
				window._bvipSessionHash = sessionHash;
			}
		})
		.catch(function () {});
	});

	// ── 2. Report Time on Page ─────────────────────────────────────────────
	function reportDuration() {
		if (!visitId || !sessionHash) return;
		var seconds = Math.round((Date.now() - pageStart) / 1000);
		var payload = JSON.stringify({ visit_id: visitId, session_hash: sessionHash, time_on_page: seconds });
		navigator.sendBeacon(
			restBase + 'duration',
			new Blob([payload], { type: 'application/json' })
		);
	}

	// Send on page hide (tab switch, close, navigate away)
	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState === 'hidden') reportDuration();
	});
	window.addEventListener('pagehide', reportDuration);

}());
