<?php
$Role = property_exists($Result, 'role') ? $Result->role : false;
$Permissions = property_exists($Result, 'permissions') ? $Result->permissions : false;
if (!empty($Role)): ?>
	<div>
		<?php echo $Role->array['description']; ?>
	</div>
	<?php if (!empty($Permissions)): ?>
		<table>
			<thead>
				<tr>
					<th>Action</th>
					<th>Subject</th>
					<th>Target</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($Permissions as $permission): ?>
					<tr>
						<td><?php echo $permission['action'] == '*'   ? 'Anything'   : ucwords($permission['action']) ?></td>
						<td><?php echo $permission['subject'] == '*'  ? 'Everything' : ucwords($permission['subject']) ?></td>
						<td><?php echo $permission['subject_id'] == 0 ? 'All'        : $permission['subject_id'] ?></td>
					</tr>
				<?php endforeach; ?>
			<tbody>
		</table>
	<?php endif; ?>
	<hr>
	<form>
		<span class="large loud">Add a Permission for <?php echo $Role->array['name'] ?> users</span>
	
	</form>
<?php else: ?>
	No Role to display
<?php endif; ?>
<hr>
<p>
	<a href="?q=gate_manager/roles/">Back</a>
</p>
