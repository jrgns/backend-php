<?php
$action_url = empty($action_url) ? Controller::$area . '/' . Controller::$action . (empty(Controller::$parameters[0]) ? '' : '/' . Controller::$parameters[0]) : $action_url;
$action_name = empty($action_name) ? ucwords(Controller::$action) : $action_name;
?>
		<form method="post" action="?q=<?php echo $action_url ?>" enctype="multipart/form-data">
			<div id="obj_foreign_table_container">
				<label id="obj_foreign_table_label" for="obj_foreign_table">Foreign Table</label><br>
				<input id="obj_foreign_table" name="foreign_table" type="text" class="text" value="<?php echo plain($values['foreign_table']) ?>">
			</div>
			<div id="obj_name_container">
				<label id="obj_name_label" for="obj_name">Name</label><br>
				<input id="obj_name" name="name" type="text" class="text title" value="<?php echo plain($values['name']) ?>">
			</div>
			<div id="obj_description_container">
				<label id="obj_description_label" for="obj_description">Description</label><br>
				<textarea id="obj_description" name="description" class="textarea"><?php echo $values['description'] ?></textarea>
			</div>
			<div id="obj_active_container">
				<label id="obj_active_label" for="obj_active">Active</label><br>
				<select id="obj_active" name="active" class="">
					<option value="0"<?php if (empty($values['active'])): ?> selected="selected"<?php endif; ?>>No</option>
					<option value="1"<?php if ($values['active']): ?> selected="selected"<?php endif; ?>>Yes</option>
				</select>
			</div>
			<div id="obj_weight_container">
				<label id="obj_weight_label" for="obj_weight">Weight</label><br>
				<input id="obj_weight" name="weight" type="text" class="text" value="<?php echo plain($values['weight']) ?>">
			</div>
			<input type="submit" value="<?php echo $action_name ?> Tag" class=""/>
		</form>
