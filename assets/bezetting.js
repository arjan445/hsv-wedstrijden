/* HSV Wedstrijden – live controle op dubbele boekingen van locaties */
(function ($) {
	'use strict';

	var $box = $('#hsv-conflicts');
	if (!$box.length) {
		return;
	}
	var timer = null;
	var last = '';

	function locaties() {
		// Blokeditor
		if (window.wp && wp.data && wp.data.select('core/editor')) {
			var ids = wp.data.select('core/editor').getEditedPostAttribute('hsv_locatie');
			if (Array.isArray(ids)) {
				return ids;
			}
		}
		// Klassieke editor
		return $('input[name="tax_input[hsv_locatie][]"]:checked').map(function () {
			return this.value;
		}).get();
	}

	function check() {
		var data = {
			action: 'hsv_conflicts',
			nonce: $box.data('nonce'),
			post: $box.data('post'),
			start: $('#_hsv_start').val() || '',
			end: $('#_hsv_end').val() || '',
			time_start: $('#_hsv_time_start').val() || '',
			time_end: $('#_hsv_time_end').val() || '',
			locaties: locaties()
		};
		var key = JSON.stringify(data);
		if (key === last) {
			return;
		}
		last = key;
		$.post(window.ajaxurl, data).done(function (r) {
			if (r && r.success) {
				$box.html(r.data.html);
			}
		});
	}

	function schedule() {
		clearTimeout(timer);
		timer = setTimeout(check, 400);
	}

	$(document).on('change input', '#_hsv_start, #_hsv_end, #_hsv_time_start, #_hsv_time_end, input[name="tax_input[hsv_locatie][]"]', schedule);
	if (window.wp && wp.data && wp.data.subscribe) {
		wp.data.subscribe(schedule);
	}
})(jQuery);
