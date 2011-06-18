<?php
$action_url = empty($action_url) ? Controller::$area . '/' . Controller::$action . (empty(Controller::$parameters[0]) ? '' : '/' . Controller::$parameters[0]) : $action_url;
$action_name = empty($action_name) ? ucwords(Controller::$action) : $action_name;
?>
<form method="post" action="?q=<?php echo $action_url ?>" enctype="multipart/form-data">
	<div id="value_name_container">
		<label id="value_name_label">Name<br>
			<input id="value_name" name="name" type="text" class="text" value="<?php echo plain($obj_values['name']) ?>">
		</label>
	</div>
	<div id="value_surname_container">
		<label id="value_surname_label">Surname<br>
			<input id="value_surname" name="surname" type="text" class="text" value="<?php echo plain($obj_values['surname']) ?>">
		</label>
	</div>
	<div id="value_email_container">
		<label id="value_email_label">Email<br>
			<input id="value_email" name="email" type="text" class="text" value="<?php echo plain($obj_values['email']) ?>">
		</label>
	</div>
	<div id="value_website_container">
		<label id="value_website_label">Website<br>
			<input id="value_website" name="website" type="text" class="text" value="<?php echo plain($obj_values['website']) ?>">
		</label>
	</div>
	<div id="value_mobile_container">
		<label id="value_mobile_label">Mobile<br>
			<input id="value_mobile" name="mobile" type="text" class="text" value="<?php echo plain($obj_values['mobile']) ?>">
		</label>
	</div>
	<div id="value_username_container">
		<label id="value_username_label">Username<br>
			<input id="value_username" name="username" type="text" class="text" value="<?php echo plain($obj_values['username']) ?>">
		</label>
	</div>
	<?php if (Permission::check('manage', 'BackendUser')): ?>
		<div id="value_confirmed_container">
			<label id="value_confirmed_label">Confirmed<br>
				<select id="value_confirmed" name="confirmed" class="">
				<option value="0"<?php if (!is_null($obj_values['confirmed']) && empty($obj_values['confirmed'])): ?> selected="selected"<?php endif; ?>>No</option>
				<option value="1"<?php if ($obj_values['confirmed']): ?> selected="selected"<?php endif; ?>>Yes</option>
			</select>
			</label>
		</div>
		<div id="value_active_container">
			<label id="value_active_label">Active<br>
				<select id="value_active" name="active" class="">
				<option value="0"<?php if (!is_null($obj_values['active']) && empty($obj_values['active'])): ?> selected="selected"<?php endif; ?>>No</option>
				<option value="1"<?php if ($obj_values['active']): ?> selected="selected"<?php endif; ?>>Yes</option>
			</select>
			</label>
		</div>
	<?php endif; ?>
	<input type="submit" value="<?php echo $action_name ?> User" class=""/>
</form>
<br>
<fieldset>
	<legend>Change Password</legend>
	 {tpl:backend_user.change_password.tpl.php}
</fieldset>

