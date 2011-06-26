<?php
$action_url = empty($action_url) ? Controller::$area . '/' . Controller::$action . (empty(Controller::$parameters[0]) ? '' : '/' . Controller::$parameters[0]) : $action_url;
$action_name = empty($action_name) ? ucwords(Controller::$action) : $action_name;
?>
		<form method="post" action="?q=<?php echo $action_url ?>" enctype="multipart/form-data">
			<div id="obj_name_container">
				<label id="obj_name_label" for="obj_name">Name</label><br>
				<input id="obj_name" name="name" type="text" class="text" value="<?php echo plain($values['name']) ?>">
			</div>
			<div id="obj_value_container">
				<label id="obj_value_label" for="obj_value">Value</label><br>
				<input id="obj_value" name="value" type="text" class="text" value="<?php echo $values['value'] ?>">
			</div>
			<input type="submit" value="<?php echo $action_name ?> Value" class=""/>
		</form>
