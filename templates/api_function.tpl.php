<div class="description"><?php echo markdown($definition['description']) ?></div>
<hr>
<h3>Example</h3>
<p>
	<?php if (!empty($definition['example'])): ?>
		<?php echo $definition['example'] ?>
	<?php else: ?>
		<code>
			#SITE_LINK#?q=<?php echo class_for_url($class) . '/' . $function ?><?php if (!empty($definition['parameters'])): ?><?php echo '/$' . implode('/$', array_keys($definition['parameters'])) ?><?php endif; ?>
		</code>(Generated)
	<?php endif; ?>
</p>
<hr>
<h3>Return</h3>
<p>
	<span class="large">
		<strong><?php echo $definition['return']['type'] ?></strong>
	</span>
	<br>
	<?php echo $definition['return']['description'] ?>
</p>
<hr>
<h3>URL Parameters</h3>
<?php if (!empty($definition['parameters'])): foreach($definition['parameters'] as $name => $parameter): ?>
	{tpl:api_parameter.tpl.php}
<?php endforeach; else: ?>
	<p>None</p>
<?php endif; ?>
<hr>
<h3>Required Parameters</h3>
<?php if (!empty($definition['required'])): foreach($definition['required'] as $name => $parameter): ?>
	{tpl:api_parameter.tpl.php}
<?php endforeach; else: ?>
	<p>None</p>
<?php endif; ?>
<hr>
<h3>Optional Parameters</h3>
<?php if (!empty($definition['optional'])): foreach($definition['optional'] as $name => $parameter): ?>
	{tpl:api_parameter.tpl.php}
<?php endforeach; else: ?>
	<p>None</p>
<?php endif; ?>
<p>
	<a href="?q=<?php echo Controller::$area . '/define/' . Controller::$parameters[0] ?>">Up</a> | <a href="?q=<?php echo Controller::$area ?>">Top</a>
</p>
