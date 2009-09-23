var faded_opacity = 0.85;
$(document).ready(function() {
	$('img').fadeTo(0, faded_opacity);
	$('img').bind('mouseover', focus_on_animation);
	$('img').bind('mouseout', focus_out_animation);
	$('a.delete_link').bind('click', doDelete);
});

function focus_on_animation(evt) {
	$(this).fadeTo('fast', 1);
}

function focus_out_animation(evt) {
	$(this).fadeTo('slow', faded_opacity);
}

function doDelete(evt) {
	if (confirm('Are you sure you want to delete this record')) {
		var delete_id = $(this).attr('id').replace('delete_', '');
		$('input#delete_id').val(delete_id);
		$('form#form_list_delete').submit();
	}
	return false;
}

