/**
 * RICHES Leaflet map embed (reads .riches-map__config script element on .riches-map).
 */
(function () {
	'use strict';

	var HOVER_MEDIA = window.matchMedia('(hover: hover)');

	function parseConfig(el) {
		var scriptEl = el.querySelector('.riches-map__config');
		if (!scriptEl) {
			return null;
		}
		try {
			return JSON.parse(scriptEl.textContent);
		} catch (e) {
			return null;
		}
	}

	function createPinIcon(pin) {
		var slug = pin.category || 'other';
		var color = pin.color || '#6c757d';
		return L.divIcon({
			className: 'riches-map-pin-wrap',
			html:
				'<span class="riches-map-pin riches-map-pin--' +
				slug +
				'" style="--pin-fill:' +
				color +
				'" aria-hidden="true"><span class="riches-map-pin__shape"></span></span>',
			iconSize: [28, 40],
			iconAnchor: [14, 40],
			tooltipAnchor: [0, -38],
			popupAnchor: [0, -38],
		});
	}

	function initMap(root) {
		if (typeof L === 'undefined') {
			return;
		}

		var config = parseConfig(root);
		if (!config || !config.center) {
			return;
		}

		var canvas = root.querySelector('.riches-map__canvas');
		var panel = root.querySelector('.riches-map__panel');
		var panelBody = root.querySelector('.riches-map__panel-body');
		var panelClose = root.querySelector('.riches-map__panel-close');

		if (!canvas) {
			return;
		}

		var map = L.map(canvas, {
			scrollWheelZoom: false,
		}).setView(config.center, config.zoom || 12);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution:
				'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
		}).addTo(map);

		var pins = config.pins || [];
		var bounds = [];
		var activePinId = null;

		function closePanel() {
			if (!panel) {
				return;
			}
			panel.hidden = true;
			activePinId = null;
			root.classList.remove('riches-map--panel-open');
		}

		function openPanel(pin) {
			if (!panel || !panelBody) {
				return;
			}
			var html = '';
			if (pin.title) {
				html += '<h3 class="riches-map__panel-title">' + escapeHtml(pin.title) + '</h3>';
			}
			if (pin.detail) {
				html += '<div class="riches-map__panel-detail">' + pin.detail + '</div>';
			} else if (pin.teaser) {
				html += '<p class="riches-map__panel-teaser">' + escapeHtml(pin.teaser) + '</p>';
			}
			if (pin.url) {
				html +=
					'<p class="riches-map__panel-link"><a href="' +
					escapeAttr(pin.url) +
					'">Read more</a></p>';
			}
			panelBody.innerHTML = html;
			panel.hidden = false;
			activePinId = pin.id;
			root.classList.add('riches-map--panel-open');
			if (panelClose) {
				panelClose.focus();
			}
		}

		function togglePanel(pin) {
			if (activePinId === pin.id && panel && !panel.hidden) {
				closePanel();
			} else {
				openPanel(pin);
			}
		}

		pins.forEach(function (pin) {
			if (typeof pin.lat !== 'number' || typeof pin.lng !== 'number') {
				return;
			}

			var marker = L.marker([pin.lat, pin.lng], {
				icon: createPinIcon(pin),
				title: pin.title || '',
				keyboard: true,
			});

			if (pin.teaser) {
				marker.bindTooltip(escapeHtml(pin.teaser), {
					direction: 'auto',
					offset: [0, -8],
					className:
						'riches-map-tooltip riches-map-tooltip--' + (pin.category || 'other'),
					opacity: 0.95,
				});
				marker.on('tooltipopen', function () {
					var tip = marker.getTooltip();
					if (tip && tip.getElement && pin.color) {
						tip.getElement().style.setProperty('--pin-fill', pin.color);
					}
				});
			}

			marker.on('mouseover', function () {
				if (HOVER_MEDIA.matches && pin.teaser) {
					marker.openTooltip();
				}
			});

			marker.on('mouseout', function () {
				if (HOVER_MEDIA.matches) {
					marker.closeTooltip();
				}
			});

			marker.on('click', function (e) {
				L.DomEvent.stopPropagation(e);
				togglePanel(pin);
			});

			marker.addTo(map);
			bounds.push([pin.lat, pin.lng]);
		});

		map.on('click', closePanel);

		if (panelClose) {
			panelClose.addEventListener('click', function (e) {
				e.preventDefault();
				closePanel();
			});
		}

		root.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				closePanel();
			}
		});

		setTimeout(function () {
			map.invalidateSize();
			if (bounds.length > 1) {
				map.fitBounds(bounds, { padding: [40, 40], maxZoom: config.zoom || 12 });
			} else if (bounds.length === 1) {
				map.setView(bounds[0], config.zoom || 14);
			}
		}, 100);
	}

	function escapeHtml(str) {
		if (!str) {
			return '';
		}
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function escapeAttr(str) {
		return escapeHtml(str).replace(/'/g, '&#39;');
	}

	function boot() {
		var roots = document.querySelectorAll('.riches-map');
		for (var i = 0; i < roots.length; i++) {
			initMap(roots[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
