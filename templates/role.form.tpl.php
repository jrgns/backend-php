		<form method="post" action="?q=<?php echo Controller::$area . '/' . Controller::$action . (empty(Controller::$parameters[0]) ? '' : '/' . Controller::$parameters[0]) ?>" enctype="multipart/form-data">
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
					<option value="1"<?php if ($values['active']): ?> selected="selected"<?php endif; ?>>Yes</option>
					<option value="0"<?php if (empty($values['active'])): ?> selected="selected"<?php endif; ?>>No</option>
				</select>
			</div>
			<input type="submit" value="<?php echo ucwords(Controller::$action) ?> Role" class=""/>
		</form>
