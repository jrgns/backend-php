function add_(what, to_add) {
	if ($('#teaser div.' + what).length == 0) {
		$('#teaser').prepend('<div class="' + what + ' span-23 prepend-1"><ul class="bottom loud large"><li>' + to_add + '</li></ul></div>');
	} else {
		$('#teaser div.' + what + ' ul').append('<li>' + to_add + '</li>');
	}
}
