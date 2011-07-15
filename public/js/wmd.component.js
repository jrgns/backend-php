wmd_options = {
	output: 'Markdown'
}

$(document).ready(function() {
	$('textarea.textarea:first').after('<div class="wmd-container"><label>Preview</label><br/><div class="wmd-preview box">WMD Preview</div></div>');
	$('div#obj_body_container').hide();
});
