/* HSV Wedstrijden – bestand- en afbeeldingkiezer voor startlijsten en uitslagen */
(function ($) {
	'use strict';

	$(document).on('click', '.hsv-file-pick', function (e) {
		e.preventDefault();
		var $cell = $(this).closest('.hsv-file');
		var frame = wp.media({ title: 'Bestand kiezen', button: { text: 'Gebruik dit bestand' }, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			$cell.find('input').val(a.id);
			$cell.find('.hsv-file-name').text(a.filename || a.title);
			$cell.find('.hsv-file-clear').prop('hidden', false);
		});
		frame.open();
	});

	$(document).on('click', '.hsv-file-clear', function (e) {
		e.preventDefault();
		var $cell = $(this).closest('.hsv-file');
		$cell.find('input').val('');
		$cell.find('.hsv-file-name').text('Geen bestand gekozen');
		$(this).prop('hidden', true);
	});

	$(document).on('click', '.hsv-gallery-pick', function (e) {
		e.preventDefault();
		var $cell = $(this).closest('.hsv-gallery');
		var $input = $cell.find('input');
		var frame = wp.media({ title: 'Afbeeldingen kiezen', button: { text: 'Gebruik deze afbeeldingen' }, library: { type: 'image' }, multiple: 'add' });
		frame.on('open', function () {
			var sel = frame.state().get('selection');
			($input.val() || '').split(',').filter(Boolean).forEach(function (id) {
				var att = wp.media.attachment(id);
				att.fetch();
				sel.add(att);
			});
		});
		frame.on('select', function () {
			var ids = [];
			var $prev = $cell.find('.hsv-gallery-preview').empty();
			frame.state().get('selection').each(function (att) {
				var a = att.toJSON();
				ids.push(a.id);
				var src = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
				$('<img>', { src: src, alt: '' }).css({ width: 80, height: 80, objectFit: 'cover', borderRadius: 4 }).appendTo($prev);
			});
			$input.val(ids.join(','));
			$cell.find('.hsv-gallery-clear').prop('hidden', !ids.length);
		});
		frame.open();
	});

	$(document).on('click', '.hsv-gallery-clear', function (e) {
		e.preventDefault();
		var $cell = $(this).closest('.hsv-gallery');
		$cell.find('input').val('');
		$cell.find('.hsv-gallery-preview').empty();
		$(this).prop('hidden', true);
	});
})(jQuery);
