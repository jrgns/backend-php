<?php if (!empty($Object)):
	Hook::run('form', 'pre', array($Object));
	$fields = $Object->getMeta('fields');
	$odd = false;
	$row_width = 15;
	$title_width = 2;
	$input_width = $row_width - $title_width - 1;
echo '<?php
$action_url = empty($action_url) ? Controller::$area . \'/\' . Controller::$action . (empty(Controller::$parameters[0]) ? \'\' : \'/\' . Controller::$parameters[0]) : $action_url;
$action_name = empty($action_name) ? ucwords(Controller::$action) : $action_name;
?>
';
?>
		<form method="post" action="?q=<?php echo '<?php echo $action_url ?>' ?>" enctype="multipart/form-data">
<?php 
		foreach($fields as $name => $field):
			if (is_array($field)) {
				$field = array_key_exists('type', $field) ? $field['type'] : 'string';
			}
			if (in_array($field, array('primarykey', 'lastmodified', 'dateadded', 'hidden', 'serialized', 'current_user'))) {
				continue;
			}
			$odd = $odd ? false : true;
			if ($field != 'text') {
				$value = array_key_exists($name, $obj_values) ? plain($obj_values[$name]) : '';
			} else {
				$value = array_key_exists($name, $obj_values) ? $obj_values[$name] : '';
			}
			$input_id = 'obj_' . $name;
			$input_name = 'obj[' . $name . ']';
			switch (true) {
				case $field == 'integer':
				case $field == 'number':
				case $field == 'string':
				case $field == 'large_string':
				case $field == 'small_string':
				case $field == 'email':
				case $field == 'telnumber':
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="text" class="text" value="<?php echo plain($obj_values[\'' . $name . '\']) ?>">';
					break;
				case $field == 'date':
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="text" class="text" value="<?php echo plain($obj_values[\'' . $name . '\']) ?>">';
					break;
				case $field == 'title':
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="text" class="text title" value="<?php echo plain($obj_values[\'' . $name . '\']) ?>">';
					break;
				case $field == 'text':
					$field_str = '<textarea id="' . $input_id . '" name="' . $input_name . '" class="textarea"><?php echo $obj_values[\'' . $name . '\'] ?></textarea>';
					break;
				case $field == 'long_blob':
				case $field == 'medium_blob':
				case $field == 'blob':
				case $field == 'tiny_blob':
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="file" class="text" value="<?php echo plain($obj_values[\'' . $name . '\']) ?>">';
					break;
				case $field == 'boolean':
					$field_str = '<select id="' . $input_id . '" name="' . $input_name . '" class="">' . PHP_EOL;
					$field_str .= "\t\t\t\t\t" . '<option value="1"<?php if ($obj_values[\'' . $name . '\']): ?> selected="selected"<?php endif; ?>>Yes</option>' . PHP_EOL;
					$field_str .= "\t\t\t\t\t" . '<option value="0"<?php if (empty($obj_values[\'' . $name . '\'])): ?> selected="selected"<?php endif; ?>>No</option>' . PHP_EOL;
					$field_str .= "\t\t\t\t" . '</select>';
					break;
				default:
					$field_str = '';
					break;
			}
?>
			<div id="<?php echo $input_id ?>_container">
				<label id="<?php echo $input_id ?>_label" for="<?php echo $input_id ?>"><?php echo humanize($name) ?></label><br>
				<?php echo $field_str ?>

			</div>
<?php endforeach;
		Hook::run('form', 'post', array($Object));
?>
			<input type="submit" value="<?php echo '<?php echo $action_name ?>' ?> <?php echo $Object->getMeta('name') ?>" class=""/>
		</form>
<?php else: ?>
	No object
<?php endif; ?>