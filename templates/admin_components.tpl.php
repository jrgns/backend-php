<?php if ($result): ?>
	<ul>
	<?php foreach($result as $component_file): 
		$component = preg_replace('/\.obj\.php$/', '', basename($component_file)); ?>
		<li><?php echo $component ?></li>
	<?php endforeach; ?>
	</ul>
<?php endif; ?>
