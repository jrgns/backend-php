<?php if (!empty($db_object)) {
	$fields = $db_object->getMeta('fields');
	$odd = false;
	foreach($fields as $name => $field) {
		$field = is_array($field) ? $field : array('type' => $field);
		if (
			in_array($field['type'], array('primarykey', 'dateadded'))
		) {
			continue;
		}
		$text  = '<?php echo empty($db_object->array[\'' . $name . '\']) ? \'&nbsp;\' : plain($db_object->array[\'' . $name . '\']) ?>';
		$label = humanize($name);
		$class = '';
		switch (true) {
		case $field['type'] == 'title' || $name == 'title';
			$class = 'large bottom';
			break;
		case $field['type'] == 'boolean':
			$text  = '<?php echo empty($db_object->array[\'' . $name . '\']) ? \'No\' : \'Yes\' ?>';
			break;
		case $field['type'] == 'lastmodified':
			$class = 'quiet';
			$value = 'date(\'H:i:s, d F Y\', strtotime($db_object->array[\'' . $name . '\']))';
			$text  = 'Last modified on <?php echo empty($db_object->array[\'' . $name . '\']) ? \'Unknown\' : ' . $value . ' ?>';
			break;
		case $name == 'description':
			$class = 'large';
			break;
		default:
			break;
		}
		$odd = $odd ? false : true;
		switch(true) {
			case $field['type'] == 'lastmodified'; ?><div class="<?php echo $class ?>">
	<?php echo $text ?>

</div>
<?php break;
case $field['type'] == 'title' || $name == 'title'; ?><div class="<?php echo $class ?>">
	<?php echo $text ?>

</div>
<?php break;
case $name == 'description'; ?><div class="<?php echo $class ?>">
	<?php echo $text ?>

</div>
<?php break;
			default: ?><div class="<?php echo $class ?>">
	<div class="span-3" style="text-align: right;">
		<label><?php echo $label ?>:</label>
	</div>
	<span><?php echo $text ?></span>
</div>
<?php break;
		}
	}
} else { ?>
	No object
<?php }
