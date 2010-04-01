	<?php if ($list_count > count($list)): ?>
		<? if ($current_page > 1): ?><a><?php endif; ?>
			Previous
		<? if ($current_page > 1): ?></a><?php endif; ?>
		Page <?php echo $current_page ?> of <?php echo $pages ?>
		<? if ($current_page < $pages): ?><a href="?q=<?php echo $area . '/' . $action . '/' . ($list_start + $list_length) . '/' . $list_length ?>"><?php endif; ?>
			Next
		<? if ($current_page < $pages): ?></a><?php endif; ?>
	<?php else: ?>
		Page 1 of 1
	<?php endif; ?>