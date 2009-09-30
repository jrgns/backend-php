$(document).ready(function() {
	$('.toggleActive').bind('click', toggleActive);
});

function toggleActive(evt) {
	var id = $(this).attr('id').replace(/^component_/, '');
	if (id) {
		$.getJSON('?q=component/toggle/' + id + '/active&mode=json', function(json) {
			if (json.array['active'] == 1) {
				$('a#component_' + json.array['id']).html('Yes');
			} else {
				$('a#component_' + json.array['id']).html('No');
			}
		});
	}
	return false;
}
