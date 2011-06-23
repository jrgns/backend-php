<form method="post">
	<input type="hidden" name="q" value="backend_user/roles/<?php echo $user_id ?>">
	<?php foreach($system_roles as $role): ?>
		<input type="checkbox" id="role_<?php echo $role['name'] ?>"
			name="roles[]" value="<?php echo $role['name'] ?>"
			<?php if (in_array($role['name'], $user_roles)): ?> checked="checked"<?php endif; ?>
		>
		<label id="role_<?php echo $role['name'] ?>_label" id="role_<?php echo $role['name'] ?>">
			<?php echo $role['name'] ?>
		</label>
		<br>
	<?php endforeach; ?>
	<input type="submit" value="Update Roles">
</form>
