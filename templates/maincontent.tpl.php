<?php if (!empty($Sub_Title)): ?><h2 class="quiet">#Sub Title#</h2><?php endif; ?>
<?php $contents = array_filter(array_map('trim', Backend::getContent())); if (!empty($contents)): ?>
	<?php foreach($contents as $content): ?>
		<?php echo $content ?>
	<?php endforeach; ?>
<?php else: ?>
	<div>&nbsp;</div>
<?php endif; ?>
