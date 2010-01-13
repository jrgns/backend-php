<table>
	<thead>
		<tr>
			<th>Name</th><th>Value</th>
		</tr>
	</thead>
	<tbody>
		<?php if ($values) foreach($values as $value): 
			@$value_value = unserialize(base64_decode($value['value']));
			switch (true) {
			case $value_value === true:
				$value_value = 'True';
				break;
			case $value_value === false:
				$value_value = 'False';
				break;
			case is_null($value_value):
				$value_value = 'Null';
				break;
			}
		?>
			<tr>
				<td><?php echo $value['name'] ?></td>
				<td><?php echo $value_value ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

{tpl:value.form.tpl.php}

