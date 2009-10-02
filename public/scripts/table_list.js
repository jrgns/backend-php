$(document).ready(function() {
	$('a.delete_link').bind('click', doDelete);
});

function doDelete(evt) {
	if (confirm('Are you sure you want to delete this record')) {
		var delete_id = $(this).attr('id').replace('delete_', '');
		$('input#delete_id').val(delete_id);
		$('form#form_list_delete').submit();
	}
	return false;
}
