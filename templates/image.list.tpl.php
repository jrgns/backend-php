<?php if (!empty($db_object)):
	$fields = $db_object->getMeta('fields');
	$list = $db_object->list;
	$list_count   = $db_object->list_count;
	$area         = empty($area)        ? Controller::$area          : $area;
	$action       = empty($action)      ? Controller::$action        : $action;
	$list_start   = empty($list_start)  ? Controller::$parameters[0] : $list_start;
	$list_length  = empty($list_length) ? Controller::$parameters[1] : $list_length;
	$pages        = ceil($list_count / $list_length);
	$current_page = floor($list_start / $list_length) + 1;
	$odd = false;
	$row_width = 3;
	$std_width = 150;
	$count = 0;
?>
	<table>
		<tbody>
			<tr class="<?php echo $odd ? '' : 'even' ?>">
			<?php foreach($list as $image): 
				$image['meta_info'] = unserialize(base64_decode($image['meta_info']));
				if (!empty($image['meta_info']['mime'])) {
					$extension = explode('/', $image['meta_info']['mime']);
				} else {
					$extension = explode('/', $image['mime_type']);
				}
				$extension = end($extension);
				if (!($count % $row_width)): ?>
			</tr>
			<tr class="<?php 
				$odd = $odd ? false : true;
				echo $odd ? '' : 'even' ?>">
				<?php endif;
				$image = $db_object->process($image, 'out');
				$image_width = is_array($image['meta_info']) && array_key_exists('width', $image['meta_info']) ? $image['meta_info']['width'] : $std_width;
				$image_width = ($image_width < $std_width ? $image_width : $std_width) . 'px';
				?>
					<td class="image_container">
						<div class="image_controller">
							<a href="#SITE_LINK#?q=<?php echo class_for_url($db_object) ?>/update/<?php echo $image['id'] ?>"><img src="#SITE_LINK#images/icons/pencil.png"></a>
							<a href="#" class="delete_link" id="delete_<?php echo $image['id'] ?>"><img src="#SITE_LINK#images/icons/cross.png"></a>
						</div>
						<a class="image_link" href="?q=image/display/<?php echo $image['id'] ?>">
							<img width="<?php echo $image_width ?>" src="#SITE_LINK#?q=image/read/<?php echo $image['id'] ?>.<?php echo $extension ?>" 
								title="<?php echo $image['title'] ?>" alt="<?php echo $image['title'] ?>" />
						</a>
					</td>
			<?php 
				$count++;
			endforeach; ?>
			</tr>
		</tbody>
	</table>
	{tpl:list_paging.tpl.php}
<form class="inline" id="form_list_delete" method="post" action="?q=<?php echo class_for_url($db_object) ?>/delete">
	<input type="hidden" id="delete_id" name="delete_id" value="false" />
</form>
<?php else: ?>
	No object
<?php endif; ?>
