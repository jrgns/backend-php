<?php
if (!empty($Object->array['list']) && is_array($Object->array['list'])):
	foreach($Object->array['list'] as $item): ?>
		<?php var_dump($item); ?>
	<?php endforeach;
endif;

