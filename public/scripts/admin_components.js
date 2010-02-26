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
			} catch (e) {
				$('a#component_' + id).html('JS Error');
			}
		});
	}
	return false;
}
