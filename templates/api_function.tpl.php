<h3><?php echo $class ?>::<?php echo $function ?></h3>
<div class="description"><?php echo $definition['description'] ?></div>
<dl>
	<dt>Return: <span class="large bottom"><code><strong><?php echo $definition['return']['type'] ?></strong></code></span></dt>
	<dd>
		<?php echo $definition['return']['description'] ?>
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
