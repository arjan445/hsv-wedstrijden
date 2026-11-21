/* HSV Wedstrijden – maandkalender met FullCalendar (zelfde opzet als de LAT-app).
 * Tik/klik op een dag: onder de kalender verschijnen de activiteiten van die dag. */
(function () {
	'use strict';
	if (!window.FullCalendar) {
		return;
	}

	function el(tag, cls, text) {
		var n = document.createElement(tag);
		if (cls) { n.className = cls; }
		if (text) { n.textContent = text; }
		return n;
	}
	function ymd(d) {
		return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
	}

	document.querySelectorAll('[data-hsv-fc]').forEach(function (box) {
		var cfg = JSON.parse(box.getAttribute('data-hsv-fc'));
		var target = box.querySelector('.hsv-fc-calendar');
		var panel = box.querySelector('.hsv-fc-day');
		var list = box.querySelector('.hsv-fc-day-list');
		var title = box.querySelector('.hsv-day-title');
		var mobile = window.matchMedia('(max-width: 767px)');
		var dayFmt = new Intl.DateTimeFormat('nl-NL', { weekday: 'long', day: 'numeric', month: 'long' });
		var timeFmt = new Intl.DateTimeFormat('nl-NL', { hour: '2-digit', minute: '2-digit' });
		var selected = null;

		function markSelected() {
			box.querySelectorAll('.fc-daygrid-day.is-selected').forEach(function (c) { c.classList.remove('is-selected'); });
			if (selected) {
				var c = box.querySelector('.fc-daygrid-day[data-date="' + selected + '"]');
				if (c) { c.classList.add('is-selected'); }
			}
		}

		function showDay(date) {
			var key = ymd(date);
			if (selected === key) { return hideDay(); }
			selected = key;
			var start = new Date(date.getFullYear(), date.getMonth(), date.getDate());
			var end = new Date(start.getTime() + 864e5);
			var events = calendar.getEvents().filter(function (ev) {
				var evEnd = ev.end || new Date(ev.start.getTime() + 1);
				return ev.start < end && evEnd > start;
			}).sort(function (a, b) {
				return (a.allDay === b.allDay ? a.start - b.start : (a.allDay ? -1 : 1));
			});

			var t = dayFmt.format(start);
			title.textContent = t.charAt(0).toUpperCase() + t.slice(1);
			list.innerHTML = '';
			if (!events.length) {
				list.appendChild(el('p', 'hsv-empty', 'Niets gepland op deze dag.'));
			}
			events.forEach(function (ev) {
				var p = ev.extendedProps;
				var row = el('a', 'hsv-fc-item');
				row.href = ev.url;
				row.style.borderLeftColor = ev.backgroundColor;
				row.appendChild(el('span', 'hsv-fc-item-time', ev.allDay ? 'hele dag' : (timeFmt.format(ev.start) + (ev.end ? ' – ' + timeFmt.format(ev.end) : ''))));
				var body = el('span', 'hsv-fc-item-body');
				if (p.kind) { body.appendChild(el('span', 'hsv-kind', p.kind)); }
				body.appendChild(el('span', 'hsv-fc-item-title', ev.title));
				var meta = [p.locaties, p.voor].filter(Boolean).join(' · ');
				if (meta) { body.appendChild(el('span', 'hsv-fc-item-meta', meta)); }
				if (p.now) { body.appendChild(el('span', 'hsv-status is-now', 'Nu bezig')); }
				else if (p.open) { body.appendChild(el('span', 'hsv-status is-open', 'Inschrijving open')); }
				row.appendChild(body);
				row.appendChild(el('span', 'hsv-fc-item-arrow', '›'));
				list.appendChild(row);
			});
			panel.hidden = false;
			markSelected();
			if (mobile.matches) {
				panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			}
		}

		function hideDay() {
			selected = null;
			panel.hidden = true;
			markSelected();
		}

		var calendar = new FullCalendar.Calendar(target, {
			locale: 'nl',
			firstDay: 1,
			initialView: 'dayGridMonth',
			initialDate: cfg.initialDate,
			contentHeight: 'auto',
			fixedWeekCount: false,
			headerToolbar: { left: 'prev', center: 'title', right: 'next' },
			footerToolbar: { left: '', right: 'today' },
			buttonText: { today: 'vandaag', month: 'maand', list: 'lijst' },
			buttonHints: { prev: 'Vorige maand', next: 'Volgende maand', today: 'Naar vandaag' },
			events: { url: cfg.events, extraParams: cfg.filters, failure: function () { target.classList.add('has-error'); } },
			eventDisplay: 'block',
			displayEventTime: !mobile.matches,
			eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
			dayMaxEvents: mobile.matches ? false : 4,
			noEventsText: 'Niets gepland in deze periode',
			dateClick: function (info) {
				if (info.view.type === 'dayGridMonth') { showDay(info.date); }
			},
			eventClick: function (info) {
				// Op mobiel eerst het dagoverzicht tonen; tweede tik opent de activiteit.
				if (mobile.matches && info.view.type === 'dayGridMonth') {
					var key = ymd(info.event.start);
					if (selected !== key) {
						info.jsEvent.preventDefault();
						showDay(info.event.start);
					}
				}
			},
			datesSet: function (info) {
				hideDay();
				// Maand in de URL en in de filterknoppen bijhouden.
				var mid = new Date((info.start.getTime() + info.end.getTime()) / 2);
				var ym = mid.getFullYear() + '-' + String(mid.getMonth() + 1).padStart(2, '0');
				var url = new URL(window.location.href);
				url.searchParams.set('maand', ym);
				window.history.replaceState(null, '', url);
				document.querySelectorAll('.hsv-agenda .hsv-filters a').forEach(function (a) {
					var u = new URL(a.href, window.location.href);
					u.searchParams.set('maand', ym);
					a.href = u.toString();
				});
			},
			eventsSet: markSelected,
			windowResize: function () {
				calendar.setOption('displayEventTime', !mobile.matches);
				calendar.setOption('dayMaxEvents', mobile.matches ? false : 4);
			}
		});
		calendar.render();
		box.classList.add('is-ready');

		box.querySelector('.hsv-day-reset').addEventListener('click', hideDay);
	});
})();
