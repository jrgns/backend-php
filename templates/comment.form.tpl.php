<?php
$action_url = empty($action_url) ? Controller::$area . '/' . Controller::$action . (empty(Controller::$parameters[0]) ? '' : '/' . Controller::$parameters[0]) : $action_url;
$action_name = empty($action_name) ? 'Add' : $action_name;
?>
<hr>
<form method="post" action="?q=<?php echo $action_url ?>" enctype="multipart/form-data">
	<input id="obj_foreign_table" name="obj[foreign_table]" type="hidden" value="<?php echo plain($values['foreign_table']) ?>">
	<input id="obj_foreign_id"    name="obj[foreign_id]"    type="hidden" value="<?php echo plain($values['foreign_id']) ?>">
	<div id="obj_title_container">
		<input id="obj_title" name="obj[title]" type="text" class="text title" value="<?php echo plain($values['title']) ?>">
	</div>
	<div id="obj_content_container">
		<textarea id="obj_content" name="obj[content]" class="textarea"><?php echo $values['content'] ?></textarea>
	</div>
	<input type="submit" value="<?php echo $action_name ?> Comment" class=""/>
</form>
