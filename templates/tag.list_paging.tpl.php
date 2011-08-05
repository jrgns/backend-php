	<?php if ($list_count > count($list)): ?>
		<? if ($current_page > 1): ?><a href="?q=<?php echo $area . '/' . $action . '/' . $db_object->getMeta('id') . '/' . max(0, $list_start - $list_length) . '/' . $list_length . (empty($term) ? '' : '&term=' . $term) ?>"><?php endif; ?>
			Previous
		<? if ($current_page > 1): ?></a><?php endif; ?>
		Page <?php echo $current_page ?> of <?php echo $pages ?>
		<? if ($current_page < $pages): ?><a href="?q=<?php echo $area . '/' . $action . '/' . $db_object->getMeta('id') . '/' . ($list_start + $list_length) . '/' . $list_length . (empty($term) ? '' : '&term=' . $term) ?>"><?php endif; ?>
			Next
		<? if ($current_page < $pages): ?></a><?php endif; ?>
	<?php else: ?>
		Page 1 of 1
	<?php endif; ?>
