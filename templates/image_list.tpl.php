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
<?php else: ?>
	No object
<?php endif; ?>
