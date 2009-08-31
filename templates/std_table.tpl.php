<?php if (!empty($data)):
	$odd = false;
	$row_width = 15;
	$title_width = 2;
	$input_width = $row_width - $title_width - 1;
?>
	<table>
	<?php if (!empty($headers)): ?>
		<tr>
			<th><?php echo implode('</th><th>', $headers); ?></th>
		</tr>
	<?php endif; ?>
	<?php foreach($data as $row):
		$odd = $odd ? false : true;
		?>
		<tr class="<?php echo $odd ? '' : 'even' ?>">
			<td><?php echo implode('</td><td>', $row); ?></td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php else: ?>
	No data to display
<?php endif; ?>
