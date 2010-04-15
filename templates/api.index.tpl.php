<?php if ($actions): ?>
	<?php foreach($actions as $component => $methods): ?>
		<h3><a href="?q=a_p_i/define/<?php echo $component ?>"><?php echo $component ?></a></h3>
		<div>
			<?php foreach($methods as $name => $definition): ?>
				<h4>
					<a href="?q=a_p_i/define/<?php echo $component ?>/<?php echo substr($name, 7) ?>">
						<?php echo substr($name, 7) ?>
					</a>
				</h4>
				<p class="prepend-1"><?php echo $definition['description'] ?></p>
			<?php endforeach; ?>
		</div>
		<hr>
	<?php endforeach; ?>
<?php else: ?>
	No defined API actions
<?php endif; ?>