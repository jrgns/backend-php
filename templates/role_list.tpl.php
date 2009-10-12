<?php if (!empty($Object)): 
	$list = $Object->list;
	$odd = false;
?>
	<table>
		<thead>
			<tr>
				<th>Name</th>
				<th>Description</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach($list as $row):
			$odd = $odd ? false : true;
			?>
			<tr class="<?php echo $odd ? '' : 'even' ?>">
				<td><?php echo $row['name'] ?></td>
				<td><?php echo $row['description'] ?></td>
				<td><a href="?q=gate_manager/roles/<?php echo $row['id'] ?>"><img src="#SITE_LINK#images/icons/magnifier.png"></a>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php else: ?>
	No Roles to list
<?php endif; ?>
