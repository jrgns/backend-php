<?php foreach($definition as $name => $one_def): ?>
	<h4>
		<a href="?q=a_p_i/define/<?php echo $class ?>/<?php echo substr($name, 7) ?>">
			<?php echo substr($name, 7) ?>
		</a>
	</h4>
	<p class="prepend-1"><?php echo $one_def['description'] ?></p>
<?php endforeach; ?>
<p>
	<a href="?q=<?php echo Controller::$area ?>">Top</a>
</p>