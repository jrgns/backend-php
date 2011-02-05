<?php
	$odd = false;
if (!empty($query) && $query instanceof SelectQuery): ?>
	<table>
	<?php
		$first = true;
		$count = 0;
		while($row = $query->fetchAssoc()):
			$count++;
			$odd = $odd ? false : true;
			if ($first):
				$first = false;
				?>
				<thead>
					<tr>
						<th><?php echo implode('</th><th>', array_keys($row)) ?></th>
					</tr>
				</thead>
			<?php endif; ?>
		<tr class="<?php echo $odd ? '' : 'even' ?>">
			<td><?php echo implode('</td><td>', $row) ?></td>
		</tr>
		<?php endwhile; ?>
	</table>
<?php elseif (!empty($data) && is_array($data)): ?>
	<table>
	<?php if (!empty($headers)): ?>
		<thead>
			<tr>
				<th><?php echo implode('</th><th>', $headers); ?></th>
			</tr>
		</thead>
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
