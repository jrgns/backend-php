<?php if (!empty($Object)):
	$fields = $Object->getMeta('fields');
	$list = $Object->list;
	$odd = false;
	$row_width = 3;
	$std_width = 150;
	$count = 0;
?>
	<table>
		<tbody>
			<tr class="<?php echo $odd ? '' : 'even' ?>">
			<?php foreach($list as $image):
				if (!($count % $row_width)): ?>
			</tr>
			<tr class="<?php 
				$odd = $odd ? false : true;
				echo $odd ? '' : 'even' ?>">
				<?php endif;
				$image = $Object->process($image, 'out');
				$image_width = is_array($image['meta_info']) && array_key_exists('width', $image['meta_info']) ? $image['meta_info']['width'] : $std_width;
				$image_width = ($image_width < $std_width ? $image_width : $std_width) . 'px';
				?>
					<td class="image_container">
						<div class="image_controller">
							<a href="?q=<?php echo class_for_url($Object) ?>/update/<?php echo $row['id'] ?>"><img src="<?php echo SITE_LINK ?>images/icons/pencil.png"></a>
							<a href="#" class="delete_link" id="delete_<?php echo $row['id'] ?>"><img src="<?php echo SITE_LINK ?>images/icons/cross.png"></a>
						</div>
						<a href="?q=image/display/<?php echo $image['id'] ?>">
							<img width="<?php echo $image_width ?>" src="?q=image/read/<?php echo $image['id'] ?>" 
								title="<?php echo $image['title'] ?>" alt="<?php echo $image['title'] ?>" />
						</a>
					</td>
			<?php 
				$count++;
			endforeach; ?>
			</tr>
		</tbody>
	</table>
<form class="inline" id="form_list_delete" method="post" action="?q=<?php echo class_for_url($Object) ?>/delete">
	<input type="hidden" id="delete_id" name="delete_id" value="false" />
</form>
<?php else: ?>
	No object
<?php endif; ?>
