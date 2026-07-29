/**
 * RICHES aggregator: client-side filter + sort for the Aggregator page template.
 *
 * Operates entirely on the server-rendered cards (no AJAX). The control bar is
 * built per-page, so this script discovers whatever controls are present:
 *   - Any number of taxonomy filter dropdowns, each marked [data-filter-taxonomy].
 *   - A "Sort by" dropdown whose options carry data-sort-type (title | published |
 *     date_recorded | taxonomy) and, for taxonomy sorts, data-sort-taxonomy.
 *   - An optional search box and a direction dropdown.
 * Filtering/sorting reorder the DOM nodes. Vanilla ES5, no dependencies.
 */
(function () {
	'use strict';

	function initAggregator(root) {
		var deck = root.querySelector('.card-deck');
		if (!deck) {
			return;
		}

		var cards    = Array.prototype.slice.call(root.querySelectorAll('.riches-agg-card'));
		var search   = root.querySelector('.riches-aggregator__search');
		var filters  = Array.prototype.slice.call(root.querySelectorAll('[data-filter-taxonomy]'));
		var sortSel  = root.querySelector('.riches-aggregator__sort');
		var dirSel   = root.querySelector('.riches-aggregator__dir');
		var status   = root.querySelector('.riches-aggregator__status');

		// Which taxonomies are referenced by a filter or a taxonomy sort — so we
		// only read the data-* attributes we actually need.
		var taxSet = {};
		filters.forEach(function (sel) {
			taxSet[sel.getAttribute('data-filter-taxonomy')] = true;
		});
		if (sortSel) {
			Array.prototype.slice.call(sortSel.options).forEach(function (opt) {
				var t = opt.getAttribute('data-sort-taxonomy');
				if (t) { taxSet[t] = true; }
			});
		}
		var taxList = Object.keys(taxSet);

		// Current control state. sortType/sortDir seed from the rendered defaults.
		var firstOpt = sortSel && sortSel.options.length ? sortSel.options[sortSel.selectedIndex] : null;
		var state = {
			q: '',
			filters: {}, // taxonomy => selected slug
			sortType: firstOpt ? (firstOpt.getAttribute('data-sort-type') || 'title') : 'title',
			sortTax: firstOpt ? (firstOpt.getAttribute('data-sort-taxonomy') || '') : '',
			sortDir: dirSel ? dirSel.value : 'asc'
		};

		// Cache each card's data once.
		var model = cards.map(function (el) {
			var m = {
				el: el,
				title: (el.getAttribute('data-title') || '').toLowerCase(),
				search: (el.getAttribute('data-search') || ''),
				published: el.getAttribute('data-published') || '',
				date: el.getAttribute('data-date-recorded') || '',
				tax: {},     // taxonomy => array of term slugs
				taxsort: {}  // taxonomy => first term name (lowercased)
			};
			taxList.forEach(function (t) {
				var raw = el.getAttribute('data-tax-' + t) || '';
				m.tax[t] = raw ? raw.split(' ') : [];
				m.taxsort[t] = el.getAttribute('data-taxsort-' + t) || '';
			});
			return m;
		});

		function matches(m) {
			for (var tax in state.filters) {
				if (!state.filters.hasOwnProperty(tax)) { continue; }
				var val = state.filters[tax];
				if (val && m.tax[tax] && m.tax[tax].indexOf(val) === -1) { return false; }
			}
			if (state.q && m.search.indexOf(state.q) === -1) { return false; }
			return true;
		}

		function sortKey(m) {
			switch (state.sortType) {
				case 'title': return m.title;
				case 'published': return m.published; // 'Ymd' string: lexicographic == chronological
				case 'date_recorded': return m.date;  // 'Ymd'
				case 'taxonomy': return m.taxsort[state.sortTax] || '';
				default: return m.title;
			}
		}

		function apply() {
			var shown = 0;
			model.forEach(function (m) {
				var ok = matches(m);
				m.el.classList.toggle('riches-aggregator__card--hidden', !ok);
				if (ok) { shown++; }
			});

			// Only reorder when a Sort control exists; otherwise preserve the
			// server-rendered order (published date, descending).
			if (sortSel) {
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
			}

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

		filters.forEach(function (sel) {
			var tax = sel.getAttribute('data-filter-taxonomy');
			sel.addEventListener('change', function () {
				state.filters[tax] = sel.value;
				apply();
			});
		});

		if (sortSel) {
			sortSel.addEventListener('change', function () {
				var opt = sortSel.options[sortSel.selectedIndex];
				state.sortType = opt.getAttribute('data-sort-type') || 'title';
				state.sortTax = opt.getAttribute('data-sort-taxonomy') || '';
				apply();
			});
		}
		if (dirSel) {
			dirSel.addEventListener('change', function () { state.sortDir = dirSel.value; apply(); });
		}

		apply(); // boot: applies the rendered default sort immediately
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
