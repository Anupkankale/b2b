(function () {
	'use strict';
	if (typeof bvipClickData === 'undefined') return;

	var restUrl     = bvipClickData.restUrl + 'b2b-analytics/v1/click';
	var nonce       = bvipClickData.nonce;
	var postId      = parseInt(bvipClickData.postId, 10) || 0;
	var pageUrl     = bvipClickData.pageUrl || window.location.href;
	var sessionHash = null;

	// Get session hash set by tracker.js (wait up to 3 seconds)
	function getSessionHash(cb) {
		if (sessionHash) { cb(sessionHash); return; }
		var tries = 0;
		var interval = setInterval(function () {
			// tracker.js stores session hash on window for click-tracker to read
			if (window._bvipSessionHash) {
				sessionHash = window._bvipSessionHash;
				clearInterval(interval);
				cb(sessionHash);
			}
			if (++tries > 30) clearInterval(interval);
		}, 100);
	}

	// Determine element type
	function getElementType(el) {
		var tag = el.tagName.toLowerCase();
		if (tag === 'button') return 'button';
		if (tag === 'a')      return 'link';
		if (tag === 'input' && (el.type === 'submit' || el.type === 'button')) return 'button';
		if (el.getAttribute('role') === 'button') return 'button';
		return 'element';
	}

	// Climb DOM to find meaningful clickable parent
	function findClickable(el) {
		var current = el;
		var depth   = 0;
		while (current && depth < 5) {
			var tag = current.tagName ? current.tagName.toLowerCase() : '';
			if (tag === 'a' || tag === 'button') return current;
			if (current.getAttribute && current.getAttribute('role') === 'button') return current;
			if (current.type === 'submit') return current;
			current = current.parentElement;
			depth++;
		}
		return el;
	}

	// Get clean text from element
	function getElementText(el) {
		var text = (el.innerText || el.value || el.getAttribute('aria-label') || el.getAttribute('title') || '').trim();
		return text.substring(0, 100);
	}

	document.addEventListener('click', function (e) {
		var target = findClickable(e.target);
		if (!target || !target.tagName) return;

		var tag = target.tagName.toLowerCase();
		// Only track links, buttons, and form submits
		if (!['a','button','input'].includes(tag)) return;
		if (tag === 'input' && target.type !== 'submit' && target.type !== 'button') return;

		var payload = {
			post_id:       postId,
			element_type:  getElementType(target),
			element_text:  getElementText(target),
			element_id:    target.id    || '',
			element_class: (target.className || '').toString().substring(0, 100),
			target_url:    target.href  || '',
			x_position:    Math.round(e.clientX),
			y_position:    Math.round(e.clientY),
			page_url:      pageUrl,
		};

		getSessionHash(function (hash) {
			payload.session_hash = hash;
			try {
				navigator.sendBeacon(restUrl, new Blob([JSON.stringify(payload)], { type: 'application/json' }));
			} catch (err) {
				fetch(restUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
					body: JSON.stringify(payload),
					keepalive: true,
				}).catch(function () {});
			}
		});
	}, true);

}());
