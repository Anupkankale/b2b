(function () {
	'use strict';

	window.addEventListener('load', function () {
		if (typeof bvipData === 'undefined') return;

		try {
			fetch(bvipData.restUrl + 'b2b-analytics/v1/track', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce':   bvipData.nonce,
				},
				body: JSON.stringify({
					post_id:      parseInt(bvipData.postId, 10) || 0,
					referrer:     document.referrer || '',
					screen_width: window.innerWidth || 0,
					page_url:     window.location.href || '',
				}),
			});
		} catch (e) {
			// Fail silently — never break the page
		}
	});
}());
