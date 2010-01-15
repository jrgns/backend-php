<?php
$action_url = empty($action_url) ? Controller::$area . '/' . Controller::$action . (empty(Controller::$parameters[0]) ? '' : '/' . Controller::$parameters[0]) : $action_url;
?>
		<form method="post" action="?q=<?php echo $action_url ?>" enctype="multipart/form-data">
			<div id="obj_name_container">
				<label id="obj_name_label" for="obj_name">Name</label><br>
				<input id="obj_name" name="obj[name]" type="text" class="text" value="<?php echo plain($obj_values['name']) ?>">
			</div>
			<div id="obj_value_container">
				<label id="obj_value_label" for="obj_value">Value</label><br>
				<input id="obj_value" name="obj[value]" type="text" class="text" value="<?php echo $obj_values['value'] ?>">
			</div>
			<input type="submit" value="<?php echo ucwords(Controller::$action) ?> Value" class=""/>
		</form>