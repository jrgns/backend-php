<div class="description"><?php echo markdown($definition['description']) ?></div>
<dl>
	<?php if (!empty($definition['example'])): ?>
		<dt>Example</dt>
		<dd>
			<?php echo $definition['example'] ?>
		</dd>
	<?php endif; ?>
	<dt>Return: <span class="large bottom"><code><strong><?php echo $definition['return']['type'] ?></strong></code></span></dt>
	<dd>
		<?php echo $definition['return']['description'] ?>
	</dd>
	<dt>URL Parameters</dt>
	<dd>
		<?php if (!empty($definition['parameters'])): foreach($definition['parameters'] as $name => $parameter): ?>
			{tpl:api_parameter.tpl.php}
		<?php endforeach; else: ?>
			None
		<?php endif; ?>
	</dd>
	<dt>Required Parameters</dt>
	<dd>
		<?php if (!empty($definition['required'])): foreach($definition['required'] as $name => $parameter): ?>
			{tpl:api_parameter.tpl.php}
		<?php endforeach; else: ?>
			None
		<?php endif; ?>
	</dd>
	<dt>Optional Parameters</dt>
	<dd>
		<?php if (!empty($definition['optional'])): foreach($definition['optional'] as $name => $parameter): ?>
			{tpl:api_parameter.tpl.php}
		<?php endforeach; else: ?>
			None
		<?php endif; ?>
	</dd>
</dl>
<p>
	<a href="?q=<?php echo Controller::$area . '/define/' . Controller::$parameters[0] ?>">Up</a> | <a href="?q=<?php echo Controller::$area ?>">Top</a>
</p>
