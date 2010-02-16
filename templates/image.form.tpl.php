<?php
$action_url = empty($action_url) ? Controller::$area . '/' . Controller::$action . (empty(Controller::$parameters[0]) ? '' : '/' . Controller::$parameters[0]) : $action_url;
$action_name = empty($action_name) ? ucwords(Controller::$action) : $action_name;
?>
		<form method="post" action="?q=<?php echo $action_url ?>" enctype="multipart/form-data">
			<div id="obj_name_container">
				<label id="obj_name_label" for="obj_name">Name</label><br>
				<input id="obj_name" name="obj[name]" type="text" class="text" value="<?php echo plain($obj_values['name']) ?>">
			</div>
			<div id="obj_title_container">
				<label id="obj_title_label" for="obj_title">Title</label><br>
				<input id="obj_title" name="obj[title]" type="text" class="text" value="<?php echo plain($obj_values['title']) ?>">
			</div>
			<div id="obj_active_container">
				<label id="obj_active_label" for="obj_active">Active</label><br>
				<select id="obj_active" name="obj[active]" class="">
					<option value="1"<?php if ($obj_values['active']): ?> selected="selected"<?php endif; ?>>Yes</option>
					<option value="0"<?php if (empty($obj_values['active'])): ?> selected="selected"<?php endif; ?>>No</option>
				</select>
			</div>
			<div id="obj_from_db_container">
				<label id="obj_from_db_label" for="obj_from_db">From Db</label><br>
				<select id="obj_from_db" name="obj[from_db]" class="">
					<option value="1"<?php if ($obj_values['from_db']): ?> selected="selected"<?php endif; ?>>Yes</option>
					<option value="0"<?php if (empty($obj_values['from_db'])): ?> selected="selected"<?php endif; ?>>No</option>
				</select>
			</div>
			<div id="obj_content_container">
				<label id="obj_content_label" for="obj_content">Content</label><br>
				<input id="obj_content" name="obj[content]" type="file" class="text">
			</div>
			<input type="submit" value="<?php echo $action_name ?> Image" class=""/>
		</form>
