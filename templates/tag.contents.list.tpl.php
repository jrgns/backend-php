<?php
if (!empty($Object->array['list']) && is_array($Object->array['list'])):
	foreach($Object->array['list'] as $item): ?>
		<h3><?php echo plain($item['title']) ?></h3>
		<p>
			<?php echo Content::createPreview($item['body']) ?>
		</p>
		<hr>
	<?php endforeach;
endif;

