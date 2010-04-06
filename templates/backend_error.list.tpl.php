<?php if (!empty($Object) && !empty($Object->list)): ?>
	<table id="backend_error_container">
		<thead>
			<tr>
				<th>Count</th>
				<th>Error</th>
				<th>Location</th>
				<th>Mode</th>
				<th>Query</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($Object->list as $error): ?>
				<tr>
					<td><?php echo $error['count'] ?></td>
					<td><?php echo $error['string'] ?></td>
					<td><?php echo $error['file'] ?> line <?php echo $error['line'] ?></td>
					<td><?php echo $error['mode'] ?></td>
					<td><?php echo $error['query'] ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php else: ?>
	No Errors...
<?php endif; ?>