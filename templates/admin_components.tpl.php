<?php if ($result):
	$odd = true;
?>
	<table>
		<tr>
			<th>Name</th>
			<th>Active</th>
		</tr>
		<?php foreach($result as $component):
			$odd = $odd ? false : true;
			?>
			<tr class="<?php echo $odd ? '' : 'even' ?>">
				<td><?php echo $component['name'] ?></td>
				<td><a id="component_<?php echo $component['id'] ?>" href="#" class="toggleActive"><?php echo $component['active'] ? 'Yes' : 'No' ?></a></td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>
