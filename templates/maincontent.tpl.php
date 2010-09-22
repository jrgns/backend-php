<?php $contents = array_filter(array_map('trim', Backend::getContent())); if (!empty($contents)): ?>
	<?php foreach($contents as $content): ?>
		<?php echo $content ?>
	<?php endforeach; ?>
<?php else: ?>
	<div>&nbsp;</div>
<?php endif; ?>
