<?php if (!empty($Object)): ?>
	<form method="post" action="?q=<?php echo array_key_exists('q', $_REQUEST) ? $_REQUEST['q'] : '' ?>" enctype="multipart/form-data">
	<?php 
		Hook::run('form', 'pre', array($Object));
		$fields = $Object->getMeta('fields');
		$odd = false;
		$row_width = 15;
		$title_width = 2;
		$input_width = $row_width - $title_width - 1;
		foreach($fields as $name => $field):
			if (in_array($field, array('primarykey', 'lastmodified', 'dateadded', 'hidden', 'serialized'))) {
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
				case $field == 'string':
				case $field == 'email':
				case $field == 'telnumber':
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="text" class="text" value="' . $value . '">';
					break;
				case $field == 'title':
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="text" class="text title" value="' . $value . '">';
					break;
				case $field == 'text':
					$field_str = '<textarea id="' . $input_id . '" name="' . $input_name . '" class="textarea">' . $value . '</textarea>';
					break;
				case $field == 'boolean':
					$field_str = '<select id="' . $input_id . '" name="' . $input_name . '" class="">';
					$field_str .= '<option value="1">Yes</option>';
					$field_str .= '<option value="0">No</option>';
					$field_str .= '</select>';
					break;
				case $field == 'blob':
					$field_str = '<input id="' . $input_id . '" name="' . $input_name . '" type="file" class="text" value="' . $value . '">';
					break;
				default:
					$field_str = '';
					break;
			}
			?>
			<div id="<?php echo $input_id ?>_container">
				<label id="<?php echo $input_id ?>_label" for="<?php echo $input_id ?>"><?php echo humanize($name) ?></label><br/>
				<?php echo $field_str ?>
			</div>
		<?php endforeach;
		Hook::run('form', 'post');
	?>
		<input type="submit" value="<?php echo ucwords(Controller::$action) ?> <?php echo $Object->getMeta('name') ?>" class=""/>
	</form>
<?php else: ?>
	No object
<?php endif; ?>
