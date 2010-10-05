<div>
	<p class="large bottom">
		<strong><?php echo $name ?></strong> (<?php echo $parameter['type'] ?>)
		<?php if (!empty($parameter['optional'])): ?> - Optional<?php endif; ?><br>
	</p>
	<?php echo markdown($parameter['description']) ?>
	<?php if(!empty($parameter['range'])): ?>
		<br><strong>Range:</strong>
		<ul><li><?php echo implode('</li><li>', $parameter['range']) ?></li></ul>
	<?php endif; ?>
</div>