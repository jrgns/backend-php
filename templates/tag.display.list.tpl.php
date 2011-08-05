<?php if (!empty($db_object)) {
	$list         = $db_object->array['list'];
	$list_count   = $db_object->array['list_count'];
	$area         = empty($area)        ? Controller::$area          : $area;
	$action       = empty($action)      ? Controller::$action . '/' . Controller::$parameters[0] : $action;
	$list_start   = empty($list_start)  ? Controller::$parameters[1] : $list_start;
	$list_length  = empty($list_length) ? Controller::$parameters[2] : $list_length;
	$pages        = ceil($list_count / $list_length);
	$current_page = floor($list_start / $list_length) + 1;
	//var_dump(count($list), $list_count, $area, $action, $list_start, $list_length, $pages, $current_page);
}
if (!empty($db_object->array['list']) && is_array($db_object->array['list'])):
	foreach($db_object->array['list'] as $item): ?>
	    <div class="tag_item_preview">
	        <?php if (array_key_exists('name', $item) && array_key_exists('title', $item)): ?>
	            <h3>
	            <a href="?q=<?php echo class_for_url($db_object->array['foreign_table']) ?>/<?php echo $item['name'] ?>">
	                <?php echo $item['title'] ?>
	            </a>
	            </h3>
	        <?php else: ?>
        		<?php var_dump($item); ?>
		    <?php endif; ?>
		    <?php if (!empty($item['description'])): ?>
    		    <p class="bottom"><?php echo $item['description'] ?></p>
		    <?php endif; ?>
	    </div>
	<?php endforeach; ?>
<?php else: ?>
    <p>No items to display</p>
<?php endif; ?>
