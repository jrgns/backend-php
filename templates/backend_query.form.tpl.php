<?php
$action_url = empty($action_url) ? Controller::$area . '/' . Controller::$action . (empty(Controller::$parameters[0]) ? '' : '/' . Controller::$parameters[0]) : $action_url;
$action_name = empty($action_name) ? ucwords(Controller::$action) : $action_name;
?>
		<form method="post" action="?q=<?php echo $action_url ?>" enctype="multipart/form-data">
			<div id="obj_alias_container">
				<label id="obj_alias_label" for="obj_alias">Alias</label><br>
				<input id="obj_alias" name="obj[alias]" type="text" class="text" value="<?php echo plain($obj_values['alias']) ?>">
			</div>
			<div id="obj_query_container">
				<label id="obj_query_label" for="obj_query">Query</label><br>
				<input id="obj_query" name="obj[query]" type="text" class="text" value="<?php echo plain($obj_values['query']) ?>">
			</div>
			<div id="obj_active_container">
				<label id="obj_active_label" for="obj_active">Active</label><br>
				<select id="obj_active" name="obj[active]" class="">
					<option value="0"<?php if (empty($obj_values['active'])): ?> selected="selected"<?php endif; ?>>No</option>
					<option value="1"<?php if ($obj_values['active']): ?> selected="selected"<?php endif; ?>>Yes</option>
				</select>
			</div>
			<input type="submit" value="<?php echo $action_name ?> BackendQuery" class=""/>
		</form>
