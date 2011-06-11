<?php if ($db_object):
	if (!empty($db_object->array['description'])): ?>
		<div>
			<?php echo $db_object->array['description'] ?>
		</div>
		<hr/>
	<?php endif;
endif; ?>
