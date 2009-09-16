<?php if (!empty($Object)): ?>
	<form method="post" action="?q=<?php echo array_key_exists('q', $_REQUEST) ? $_REQUEST['q'] : '' ?>" enctype="multipart/form-data">
	<?php 
		Hook::run('form', 'pre');
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
			switch (true) {
				case $field == 'integer':
				case $field == 'string':
				case $field == 'email':
				case $field == 'telnumber':
					$field_str = '<input id="obj_' . $name . '" name="obj[' . $name . ']" type="text" class="text" value="' . $value . '">';
					break;
				case $field == 'title':
					$field_str = '<input id="obj_' . $name . '" name="obj[' . $name . ']" type="text" class="text title" value="' . $value . '">';
					break;
				case $field == 'text':
					$field_str = '<textarea id="obj_' . $name . '" name="obj[' . $name . ']" class="textarea">' . $value . '</textarea>';
					break;
				case $field == 'boolean':
					$field_str = '<select id="obj_' . $name . '" name="obj[' . $name . ']" class="">';
					$field_str .= '<option value="1">Yes</option>';
					$field_str .= '<option value="0">No</option>';
					$field_str .= '</select>';
					break;
				case $field == 'blob':
					$field_str = '<input id="obj_' . $name . '" name="obj[' . $name . ']" type="file" class="text" value="' . $value . '">';
					break;
				default:
					$field_str = '';
					break;
			}
			?>
			<label><?php echo humanize($name) ?></label><br/>
			<?php echo $field_str ?><br/>
		<?php endforeach;
		Hook::run('form', 'post');
	?>
		<input type="submit" value="Add <?php echo $Object->getMeta('name') ?>" class=""/>
	</form>
<?php else: ?>
	No object
<?php endif; ?>
