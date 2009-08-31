<?php if (!empty($Assignments)):
	$list = $Assignments->list;
	$odd = false;
?>
	<table>
		<thead>
			<tr>
				<th>Type</th>
				<th>ID</th>
				<th>Role</th>
				<th>Active</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach($list as $row):
			$odd = $odd ? false : true;
			switch ($row['access_id']) {
				case '-1':
					$id_type = 'None';
					break;
				case '0':
					$id_type = 'All';
					break;
				default:
					$id_type = $row['access_id'];
					break;
			}
			?>
			<tr class="<?php echo $odd ? '' : 'even' ?>">
				<td><?php echo $row['access_type'] ?></td>
				<td><?php echo $id_type ?></td>
				<td><a href="?q=role/display/<?php echo $row['role_id'] ?>"><?php echo $row['role'] ?></a></td>
				<td><?php echo $row['active'] ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<a href="?q=assignment/create">Add</a> an assignment.
<?php else: ?>
	No object
<?php endif; ?>
