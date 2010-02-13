<?php
$action_url = empty($action_url) ? Controller::$area . '/' . Controller::$action . (empty(Controller::$parameters[0]) ? '' : '/' . Controller::$parameters[0]) : $action_url;
$action_name = empty($action_name) ? ucwords(Controller::$action) : $action_name;
?>
		<form method="post" action="?q=<?php echo $action_url ?>" enctype="multipart/form-data">
			<div id="obj_username_container">
				<label id="obj_username_label" for="obj_username">Username</label><br>
				<?php if ($obj_values['username'] == $obj_values['email']): ?>
					<input id="obj_username" name="obj[username]" type="text" class="text title" value="<?php echo plain($obj_values['username']) ?>">
				<?php else: ?>
					<span id="obj_username" class="large loud"><?php echo plain($obj_values['username']) ?></span>
				<?php endif; ?>
			</div>
			<div id="obj_name_container">
				<label id="obj_name_label" for="obj_name">Name</label><br>
				<input id="obj_name" name="obj[name]" type="text" class="text" value="<?php echo plain($obj_values['name']) ?>">
			</div>
			<div id="obj_surname_container">
				<label id="obj_surname_label" for="obj_surname">Surname</label><br>
				<input id="obj_surname" name="obj[surname]" type="text" class="text" value="<?php echo plain($obj_values['surname']) ?>">
			</div>
			<div id="obj_email_container">
				<label id="obj_email_label" for="obj_email">Email</label><br>
				<input id="obj_email" name="obj[email]" type="text" class="text" value="<?php echo plain($obj_values['email']) ?>">
			</div>
			<div id="obj_website_container">
				<label id="obj_website_label" for="obj_website">Website</label><br>
				<input id="obj_website" name="obj[website]" type="text" class="text" value="<?php echo plain($obj_values['website']) ?>">
			</div>
			<div id="obj_mobile_container">
				<label id="obj_mobile_label" for="obj_mobile">Mobile</label><br>
				<input id="obj_mobile" name="obj[mobile]" type="text" class="text" value="<?php echo plain($obj_values['mobile']) ?>">
			</div>
			<div id="obj_confirmed_container">
				<label id="obj_confirmed_label" for="obj_confirmed">Confirmed</label><br>
				<select id="obj_confirmed" name="obj[confirmed]" class="">
					<option value="1"<?php if ($obj_values['confirmed']): ?> selected="selected"<?php endif; ?>>Yes</option>
					<option value="0"<?php if (empty($obj_values['confirmed'])): ?> selected="selected"<?php endif; ?>>No</option>
				</select>
			</div>
			<div id="obj_active_container">
				<label id="obj_active_label" for="obj_active">Active</label><br>
				<select id="obj_active" name="obj[active]" class="">
					<option value="1"<?php if ($obj_values['active']): ?> selected="selected"<?php endif; ?>>Yes</option>
					<option value="0"<?php if (empty($obj_values['active'])): ?> selected="selected"<?php endif; ?>>No</option>
				</select>
			</div>
			<input type="submit" value="<?php echo $action_name ?> User" class=""/>
		</form>
