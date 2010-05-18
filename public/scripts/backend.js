function add_(what, to_add) {
	if ($('#teaser div.' + what).length == 0) {
		$('#teaser').prepend('<div class="' + what + ' span-23 prepend-1"><ul class="bottom loud large"><li>' + to_add + '</li></ul></div>');
	} else {
		$('#teaser div.' + what + ' ul').append('<li>' + to_add + '</li>');
	}
}

function add_success(success) {
	if (typeof(success) == 'object' && success.length) {
		for(var i = 0; i < success.length; i++) {
			add_success(success[i]);
		}
	} else {
		$('#backend_success').append('<li>' + success + '</li>');
	}
	$('#backend_success_container').show();
}

function add_notice(notice) {
	if (typeof(notice) == 'object' && notice.length) {
		for(var i = 0; i < notice.length; i++) {
			add_notice(notice[i]);
		}
	} else {
		$('#backend_notice').append('<li>' + notice + '</li>');
	}
	$('#backend_notice_container').show();
}

function add_error(error) {
	if (typeof(error) == 'object' && error.length) {
		for(var i = 0; i < error.length; i++) {
			add_error(error[i]);
		}
	} else {
		$('#backend_error').append('<li>' + error + '</li>');
	}
	$('#backend_error_container').show();
}
