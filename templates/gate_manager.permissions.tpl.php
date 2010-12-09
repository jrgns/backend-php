<form method="post"
	action="?q=gate_manager/permissions<?php if (!empty(Controller::$parameters[0])): echo '/' . Controller::$parameters[0]; endif; ?>">
	<table>
	<?php
		$last = false;
		$even = false;
		if ($base_perms) foreach($base_perms as $permission):
			$even = $even ? false : true;
			if (empty($permission['action'])) {
				continue;
			}
			if ($last != $permission['subject']): 
				$last = $permission['subject']; ?>
			<tr>
				<th><?php echo humanize($permission['subject']) ?></th>
				<?php if ($roles) foreach($roles as $role): if(!in_array($role['name'], array('superadmin', 'nobody'))): ?>
					<th><?php echo humanize($role['name']) ?></th>
				<?php endif; endforeach; ?>
			</tr>
			<?php endif; ?>
			<tr class="<?php echo $even ? 'even' : '' ?>">
				<td><?php echo $permission['action'] ?></td>
				<?php foreach($roles as $role): if (!in_array($role['name'], array('superadmin', 'nobody'))):
					$row_name = $permission['subject'] . '::' . $permission['action'];
					$checked = 	array_key_exists($row_name, $permissions) && 
								in_array($role['name'], $permissions[$row_name]);
				?>
					<td>
						<input type="checkbox" name="<?php echo $row_name . '[' . $role['name'] ?>]"<?php if ($checked): ?> checked="checked"<?php endif; ?>>
					</td>
				<?php endif; endforeach; ?>
			</tr>
	<?php endforeach; ?>
	</table>
	<input type="submit" value="Save Permissions" />
</form>
