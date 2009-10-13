<?php if (!empty($Object)): ?>
	<h3 class="loud bottom">
		<?php echo $Object->array['name']; ?>
	</h3>
	<div>
		<?php echo $Object->array['description']; ?>
	</div>
<?php else: ?>
	No Roles to display
<?php endif; ?>
