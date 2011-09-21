<?php if ($methods && count($methods)): ?>
	<ul class="large">
		<?php foreach($methods as $method): ?>
			<li>
				<a href="?q=<?php echo Controller::$area ?>/<?php echo $method ?>"><?php echo humanize($method) ?></a>
				<?php if (method_exists(class_name(Controller::$area), 'define_' . $method)):
					$definition = call_user_func(array(class_name(Controller::$area), 'define_' . $method));
					if (!empty($definition['description'])): ?>
						 <span class="quiet">- <?php echo $definition['description'] ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php else: ?>
	<p class="large center">
		Nothing here yet
	</p>
<?php endif; ?>
