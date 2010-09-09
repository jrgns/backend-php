<?php if (!empty($Object)) {
	$fields = $Object->getMeta('fields');
	$odd = false;
	foreach($fields as $name => $field) {
		if (in_array($field, array('primarykey'))) {
			continue;
		}
		$text  = '<?php echo empty($Object->array[\'' . $name . '\']) ? \'&nbsp;\' : plain($Object->array[\'' . $name . '\']) ?>';
		switch ($field) {
		case 'title';
			$class = 'large bottom';
			break;
		case 'boolean':
			$text  = '<?php echo empty($Object->array[\'' . $name . '\']) ? \'No\' : \'Yes\' ?>';
			break;
		default:
			$class = '';
			break;
		}
		$odd = $odd ? false : true;
?>
		<div class="<?php echo $class ?>">
			<div class="span-3" style="text-align: right;">
				<label><?php echo humanize($name) ?>:</label>
			</div>
			<span><?php echo $text ?></span>
		</div>
<?php }
} else { ?>
	No object
<?php } ?>