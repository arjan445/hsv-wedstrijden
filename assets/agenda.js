/* HSV Wedstrijden – maandweergave: tik op een dag om alleen de activiteiten van die dag te zien. */
(function () {
	'use strict';

	document.querySelectorAll('[data-hsv-month]').forEach(function (month) {
		var list = month.querySelector('.hsv-month-list');
		if (!list) {
			return;
		}
		var head = list.querySelector('.hsv-day-head');
		var title = list.querySelector('.hsv-day-title');
		var empty = list.querySelector('.hsv-day-empty');
		var items = Array.prototype.slice.call(list.querySelectorAll('.hsv-day-item'));
		var fmt = new Intl.DateTimeFormat('nl-NL', { weekday: 'long', day: 'numeric', month: 'long' });

		function show(day) {
			month.querySelectorAll('.hsv-cell.is-selected').forEach(function (c) {
				c.classList.remove('is-selected');
			});
			var shown = 0;
			items.forEach(function (it) {
				var hit = !day || (' ' + it.getAttribute('data-days') + ' ').indexOf(' ' + day + ' ') !== -1;
				it.hidden = !hit;
				shown += hit ? 1 : 0;
			});
			list.classList.toggle('is-filtered', !!day);
			if (head) {
				head.hidden = !day;
			}
			if (empty) {
				empty.hidden = !day || shown > 0;
			}
			if (day) {
				var cell = month.querySelector('.hsv-cell[data-day="' + day + '"]');
				if (cell) {
					cell.classList.add('is-selected');
				}
				var d = fmt.format(new Date(day + 'T12:00:00'));
				title.textContent = d.charAt(0).toUpperCase() + d.slice(1);
				list.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		}

		month.addEventListener('click', function (ev) {
			var btn = ev.target.closest('.hsv-cell-day');
			if (btn && !btn.disabled) {
				var cell = btn.closest('.hsv-cell');
				show(cell.classList.contains('is-selected') ? '' : btn.getAttribute('data-day'));
				return;
			}
			// Op mobiel: tik op een dagvak (niet op een activiteit) = die dag tonen.
			var c = ev.target.closest('.hsv-cell.has-events');
			if (c && !ev.target.closest('a') && window.matchMedia('(max-width: 767px)').matches) {
				show(c.getAttribute('data-day'));
			}
		});

		var reset = list.querySelector('.hsv-day-reset');
		if (reset) {
			reset.addEventListener('click', function () {
				show('');
				month.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		}
	});
})();
