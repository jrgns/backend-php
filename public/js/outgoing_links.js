$(document).ready(function() {
	$('.outgoing_click').click(recordClick);
});

function recordClick(evt) {
	var end_point = $(this).attr('href');
	if (end_point) {
		var target = $(this).attr('target');
		var dest   = site_link + '/?q=outgoing_click/add&url=' + encodeURIComponent(end_point);
		if (target == '_blank') {
			window.open(dest);
		} else {
			window.location = dest;
		}
		return false;
	}
}

