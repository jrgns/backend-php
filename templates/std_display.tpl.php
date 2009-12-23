<?php if (!empty($Object)) {
	$fields = $Object->getMeta('fields');
	$odd = false;
	foreach($fields as $name => $field) {
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
?>
		<div class="<?php echo $class ?>">
			<div class="span-3" style="text-align: right;">
				<label><?php echo humanize($name) ?>:</label>
			</div>
			<span><?php echo '<?php echo empty($Object->array[\'' . $name . '\']) ? \'&nbsp;\' : plain($Object->array[\'' . $name . '\']) ?>' ?></span>
		</div>
<?php }
} else { ?>
	No object
<?php } ?>