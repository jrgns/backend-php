<?php if (!empty($Object)):
	if ($Object->array) {
		$obj_values = $Object->array;
	} else if ($Object->object) {
		$obj_values = $Object->object;
	} else {
		$obj_values = false;
	}
	if ($obj_values):
		$fields = $Object->getMeta('fields');
		$odd = false;
		foreach($fields as $name => $field):
			switch ($field) {
				case 'title';
					$class = 'large bottom';
					break;
				default:
					$class = '';
			}
			if (in_array($field, array('primarykey'))) {
				continue;
			}
			$odd = $odd ? false : true;
			$value = array_key_exists($name, $obj_values) ? plain($obj_values[$name]) : '';
			?>
			<div class="<?php echo $class ?>">
				<div class="span-3" style="text-align: right;">
					<label><?php echo humanize($name) ?>:</label>
				</div>
				<span><?php echo $value ?></span>
			</div>
		<?php endforeach;
	else: ?>
	The object is empty.
	<?php endif; ?>
<?php else: ?>
	No object
<?php endif; ?>
