var faded_opacity = 0.85;
$(document).ready(function() {
	$('img').fadeTo(0, faded_opacity);
	$('img').bind('mouseover', focus_on_animation);
	$('img').bind('mouseout', focus_out_animation);
});

function focus_on_animation(evt) {
	$(this).fadeTo('fast', 1);
}

function focus_out_animation(evt) {
	$(this).fadeTo('slow', faded_opacity);
}
