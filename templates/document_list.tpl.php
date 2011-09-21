<?php if (!empty($db_object)):
	$fields = $db_object->getMeta('fields');
	$list = $db_object->list;
	$odd = false;
	$row_width = 3;
	$count = 0;
?>
	<table>
		<tbody>
			<tr class="<?php echo $odd ? '' : 'even' ?>">
			<?php foreach($list as $document):
				if (!($count % $row_width)): ?>
			</tr>
			<tr class="<?php 
				$odd = $odd ? false : true;
				echo $odd ? '' : 'even' ?>">
				<?php endif;
				$document = $db_object->process($document, 'out');
				$extension = empty($document['meta_info']['extension']) ? 'unknown' : $document['meta_info']['extension'];
				switch ($extension) {
				case 'xls':
				case 'ods':
				case 'csv':
					$file_icon = SITE_LINK . '/icons/page_white_excel.png';
					break;
				case 'pdf':
					$file_icon = SITE_LINK . '/icons/page_white_acrobat.png';
					break;
				case 'zip':
				case 'gz':
				case 'tar':
				case 'bzip2':
					$file_icon = SITE_LINK . '/icons/page_white_compressed.png';
					break;
				case 'unknown':
				default:
					$file_icon = SITE_LINK . '/icons/page_white.png';
					break;
				}
				?>
					<td style="text-align: center">
						<a href="?q=document/display/<?php echo $document['id'] ?>">
							<img src="<?php echo $file_icon ?>">&nbsp;<?php echo $document['name'] ?>
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
