<?php $contents = Backend::getContent(); if (!empty($contents)): ?>
	<?php foreach($contents as $content): ?>
		<?php echo $content ?>
	<?php endforeach; ?>
<?php else: ?>
	&nbsp;
<?php endif; ?>
