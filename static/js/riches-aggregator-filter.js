/**
 * RICHES aggregator: client-side filter + sort for the Aggregator page template.
 *
 * Operates entirely on the server-rendered cards (no AJAX). Reads each card's
 * data-* attributes, then filters (collection / location / search) and sorts
 * (date recorded / name / collection / location / title, asc|desc) by reordering
 * the DOM nodes. Vanilla ES5, no dependencies. Mirrors static/js/riches-map.js.
 */
(function () {
	'use strict';

	function initAggregator(root) {
		var deck = root.querySelector('.card-deck');
		if (!deck) {
			return;
		}

		var cards   = Array.prototype.slice.call(root.querySelectorAll('.riches-agg-card'));
		var search  = root.querySelector('.riches-aggregator__search');
		var fColl   = root.querySelector('[data-filter="collection"]');
		var fLoc    = root.querySelector('[data-filter="location"]');
		var sortSel = root.querySelector('.riches-aggregator__sort');
		var dirSel  = root.querySelector('.riches-aggregator__dir');
		var status  = root.querySelector('.riches-aggregator__status');

		var state = { q: '', collection: '', location: '', sortField: 'date', sortDir: 'desc' };

		// Cache each card's data once.
		var model = cards.map(function (el) {
			return {
				el: el,
				name: (el.getAttribute('data-name') || '').toLowerCase(),
				title: (el.getAttribute('data-title') || '').toLowerCase(),
				collection: el.getAttribute('data-collection') || '',
				location: el.getAttribute('data-location-recorded') || '',
				date: el.getAttribute('data-date-recorded') || ''
			};
		});

		function matches(m) {
			if (state.collection && m.collection !== state.collection) { return false; }
			if (state.location && m.location !== state.location) { return false; }
			if (state.q && m.name.indexOf(state.q) === -1 && m.title.indexOf(state.q) === -1) { return false; }
			return true;
		}

		function sortKey(m) {
			switch (state.sortField) {
				case 'name': return m.name;
				case 'collection': return m.collection.toLowerCase();
				case 'location': return m.location.toLowerCase();
				case 'title': return m.title;
				default: return m.date; // 'Ymd' string: lexicographic == chronological
			}
		}

		function apply() {
			var shown = 0;
			model.forEach(function (m) {
				var ok = matches(m);
				m.el.classList.toggle('riches-aggregator__card--hidden', !ok);
				if (ok) { shown++; }
			});

			var dir = state.sortDir === 'asc' ? 1 : -1;
			var ordered = model.slice().sort(function (a, b) {
				var ka = sortKey(a), kb = sortKey(b);
				var ea = ka === '', eb = kb === '';
				if (ea && eb) { return 0; }
				if (ea) { return 1; }  // empties always last, regardless of direction
				if (eb) { return -1; }
				if (ka < kb) { return -1 * dir; }
				if (ka > kb) { return 1 * dir; }
				return 0;
			});
			ordered.forEach(function (m) { deck.appendChild(m.el); });

			root.classList.toggle('riches-aggregator--empty', shown === 0);
			if (status) {
				status.textContent = shown === 1 ? '1 entry shown' : shown + ' entries shown';
			}
		}

		var t;
		function onSearch() {
			clearTimeout(t);
			t = setTimeout(function () {
				state.q = search.value.trim().toLowerCase();
				apply();
			}, 180);
		}

		if (search) { search.addEventListener('input', onSearch); }
		if (fColl) { fColl.addEventListener('change', function () { state.collection = fColl.value; apply(); }); }
		if (fLoc) { fLoc.addEventListener('change', function () { state.location = fLoc.value; apply(); }); }
		if (sortSel) { sortSel.addEventListener('change', function () { state.sortField = sortSel.value; apply(); }); }
		if (dirSel) { dirSel.addEventListener('change', function () { state.sortDir = dirSel.value; apply(); }); }

		apply(); // boot: applies the default Date-recorded-desc sort immediately
	}

	function boot() {
		var roots = document.querySelectorAll('.riches-aggregator');
		for (var i = 0; i < roots.length; i++) {
			initAggregator(roots[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
