$(document).ready(function() {
	$('.toggleActive').bind('click', toggleActive);
});

function toggleActive(evt) {
	var id = $(this).attr('id').replace(/^component_/, '');
	if (id) {
		$('a#component_' + id).html('<img src="images/loading1.gif" alt="Loading..."/>');
		try {
			$.getJSON('?q=component/toggle/' + id + '/active&mode=json', function(json) {
				if (json['active'] == 1) {
					$('a#component_' + json['id']).html('Yes');
				} else {
					$('a#component_' + json['id']).html('No');
				}
			});
		} catch (e) {
			$('a#component_' + id).html('JS Error');
		}
	}
	return false;
}
