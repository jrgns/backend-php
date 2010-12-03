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
			if (!is_array($field)) {
				$field = array('type' => $field);
			}
			if (in_array($field['type'], array('primarykey', 'lastmodified', 'dateadded', 'hidden', 'serialized', 'current_user'))) {
				continue;
			}
			$odd = $odd ? false : true;
			$input_id    = 'obj_' . $name;
			$input_name  = 'obj[' . $name . ']';
			$raw_value = '$obj_values[\'' . $name . '\']';
			if (array_key_exists('default', $field)) {
				$value = 'empty(' . $raw_value . ') ? \'' . $field['default'] . '\' : ' . $raw_value;
			} else {
				$value = $raw_value;
			}
			$plain_value = '<?php echo plain(' . $value . ') ?>';
			switch (true) {
				case $field['type'] == 'integer':
				case $field['type'] == 'number':
				case $field['type'] == 'string':
				case $field['type'] == 'large_string':
				case $field['type'] == 'small_string':
				case $field['type']['type'] == 'email':
				case $field['type'] == 'website':
				case $field['type'] == 'telnumber':
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="text" class="text" value="' . $plain_value . '">';
					break;
				case $field['type'] == 'date':
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="text" class="text" value="' . $plain_value . '">';
					break;
				case $field['type'] == 'title':
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="text" class="text title" value="' . $plain_value . '">';
					break;
				case $field['type'] == 'text':
					$field_str = '<textarea id="' . $input_id . '" name="' . $input_name . '" class="textarea"><?php echo ' . $value . ' ?></textarea>';
					break;
				case $field['type'] == 'long_blob':
				case $field['type'] == 'medium_blob':
				case $field['type'] == 'blob':
				case $field['type'] == 'tiny_blob':
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="file" class="text" value="' . $plain_value . '">';
					break;
				case $field['type'] == 'boolean':
					$field_str = '<select id="' . $input_id . '" name="' . $input_name . '" class="">' . PHP_EOL;
					if (array_key_exists('default', $field)) {
						$field_str .= "\t\t\t\t\t" . '<option value="0"<?php if ((is_null(' . $raw_value . ') && '0' == \'' . $field['default'] . '\') || (!is_null(' . $raw_value . ') && empty(' . $raw_value . '))): ?> selected="selected"<?php endif; ?>>No</option>' . PHP_EOL;
						$field_str .= "\t\t\t\t\t" . '<option value="1"<?php if ((is_null(' . $raw_value . ') && '1' == \'' . $field['default'] . '\') || ' . $raw_value . '): ?> selected="selected"<?php endif; ?>>Yes</option>' . PHP_EOL;
					} else {
						$field_str .= "\t\t\t\t\t" . '<option value="0"<?php if (!is_null(' . $raw_value . ') && empty(' . $raw_value . ')): ?> selected="selected"<?php endif; ?>>No</option>' . PHP_EOL;
						$field_str .= "\t\t\t\t\t" . '<option value="1"<?php if (' . $raw_value . '): ?> selected="selected"<?php endif; ?>>Yes</option>' . PHP_EOL;
					}
					$field_str .= "\t\t\t\t" . '</select>';
					break;
				case $field['type'] == 'date':
				case $field['type'] == 'time':
				case $field['type'] == 'datetime':
					//TODO Maybe add some jquery stuph to this later
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="text" class="text" value="' . $plain_value . '">';
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