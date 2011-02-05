<?php if ($methods && count($methods)): ?>
	<ul class="large">
		<?php foreach($methods as $method): ?>
			<li><a href="?q=<?php echo Controller::$area ?>/<?php echo $method ?>"><?php echo humanize($method) ?></a></li>
		<?php endforeach; ?>
	</ul>
<?php else: ?>
	<p class="large center">
		Nothing here yet
	</p>
<?php endif; ?>