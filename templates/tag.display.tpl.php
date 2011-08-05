<?php if ($db_object):
	if (!empty($db_object->array['description'])): ?>
		<div>
			<?php echo $db_object->array['description'] ?>
		</div>
		<hr/>
	<?php endif; ?>
<?php endif; ?>
<?php echo Render::file($tag_list_template, array('db_object' => $db_object)) ?>
