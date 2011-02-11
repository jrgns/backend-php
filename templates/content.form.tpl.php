<?php
$action_url = empty($action_url) ? Controller::$area . '/' . Controller::$action . (empty(Controller::$parameters[0]) ? '' : '/' . Controller::$parameters[0]) : $action_url;
?>
		<form method="post" action="?q=<?php echo $action_url ?>" enctype="multipart/form-data">
			<div id="obj_name_container">
				<label id="obj_name_label" for="obj_name">Name</label><br>
				<input id="obj_name" name="obj[name]" type="text" class="text" value="<?php echo plain($obj_values['name']) ?>">
			</div>
			<div id="obj_title_container">
				<label id="obj_title_label" for="obj_title">Title</label><br>
				<input id="obj_title" name="obj[title]" type="text" class="text title" value="<?php echo plain($obj_values['title']) ?>">
			</div>
			<div id="obj_markdown_container">
				<label id="obj_markdown_label" for="obj_markdown">Markdown</label><br>
				<textarea id="obj_markdown" name="obj[markdown]" class="textarea"><?php echo $obj_values['markdown'] ?></textarea>
			</div>
			<div id="obj_body_container">
				<label id="obj_body_label" for="obj_body">Body</label><br>
				<textarea id="obj_body" name="obj[body]" class="textarea"><?php echo $obj_values['body'] ?></textarea>
			</div>
			<!-- TODO Implement this
			<div id="obj_from_file_container">
				<label id="obj_from_file_label" for="obj_from_file">From File</label><br>
				<select id="obj_from_file" name="obj[from_file]" class="">
					<option value="1"<?php if ($obj_values['from_file']): ?> selected="selected"<?php endif; ?>>Yes</option>
					<option value="0"<?php if (empty($obj_values['from_file'])): ?> selected="selected"<?php endif; ?>>No</option>
				</select>
			</div>
			-->
			<div id="obj_active_container">
				<label id="obj_active_label" for="obj_active">Active</label><br>
				<select id="obj_active" name="obj[active]" class="">
					<option value="1"<?php if ($obj_values['active']): ?> selected="selected"<?php endif; ?>>Yes</option>
					<option value="0"<?php if (empty($obj_values['active'])): ?> selected="selected"<?php endif; ?>>No</option>
				</select>
			</div>
			<input type="submit" value="<?php echo ucwords(Controller::$action) ?> Content" class=""/>
		</form>
