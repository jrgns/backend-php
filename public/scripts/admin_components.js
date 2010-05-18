$(document).ready(function() {
	$('.toggleActive').bind('click', toggleActive);
});

function toggleActive(evt) {
	var id = $(this).attr('id').replace(/^component_/, '');
	if (id) {
		$('a#component_' + id).html('<img src="images/loading1.gif" alt="Loading..."/>');
		$.getJSON('?q=component/toggle/' + id + '/active&mode=json', function(json) {
			try {
				if (json.result['active'] == 1) {
					$('a#component_' + json.result['id']).html('Yes');
				} else {
					$('a#component_' + json.result['id']).html('No');
				}
				if (json.error.length > 0) {
					alert('here');
					add_error(json.error);
				}
				if (json.notice.length > 0) {
					add_notice(json.notice);
				}
				if (json.success.length > 0) {
					add_success(json.success);
				}
			} catch (e) {
				$('a#component_' + id).html('JS Error');
			}
		});
	}
	return false;
}
