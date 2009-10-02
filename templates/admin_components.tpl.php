<?php if ($result): ?>
	<table>
		<tr>
			<th>Name</th>
			<th>Active</th>
		</tr>
		<?php foreach($result as $component): ?>
			<tr>
				<td><?php echo $component['name'] ?></td>
				<td><a id="component_<?php echo $component['id'] ?>" href="#" class="toggleActive"><?php echo $component['active'] ? 'Yes' : 'No' ?></a></td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>
