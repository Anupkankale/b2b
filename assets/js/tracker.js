 (function () {
	'use strict';
	if (typeof bvipData === 'undefined') return;

	var visitId     = null;
	var sessionHash = null;
	var pageStart   = Date.now();
	var restBase    = bvipData.restUrl + 'b2b-analytics/v1/';
	var nonce       = bvipData.nonce;

	// ── 1. Register Page Visit ─────────────────────────────────────────────
	window.addEventListener('load', function () {
		fetch(restBase + 'track', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
			body: JSON.stringify({
				post_id:      parseInt(bvipData.postId, 10) || 0,
				referrer:     document.referrer || '',
				screen_width: window.innerWidth || 0,
				page_url:     bvipData.pageUrl || window.location.href,
				page_title:   bvipData.pageTitle || document.title,
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
