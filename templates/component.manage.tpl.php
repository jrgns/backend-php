<?php if ($result):
	$odd = true;
?>
	<table>
		<tr>
			<th>Name</th>
			<th>Active</th>
			<th>&nbsp;</th>
		</tr>
		<?php foreach($result as $component):
			$odd = $odd ? false : true;
			?>
			<tr class="<?php echo $odd ? '' : 'even' ?>">
				<td><?php echo $component['name'] ?></td>
				<td>
					<a id="component_<?php echo $component['id'] ?>" href="#" class="toggleActive">
						<?php echo $component['active'] ? 'Yes' : 'No' ?>
					</a>
				</td>
				<td>
					<a href="?q=gate_manager/permissions/<?php echo class_for_url($component['name']) ?>">Permissions</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>
