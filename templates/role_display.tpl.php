<?php
$Role        = property_exists($Result, 'role') ? $Result->role : false;
$Permissions = property_exists($Result, 'permissions') ? $Result->permissions : false;
$Assignments = property_existS($Result, 'assignments') ? $Result->assignments : false;
if (!empty($Role)): ?>
	<div>
		<?php echo $Role->array['description']; ?>
	</div>
	<hr>
	<dl>
	<?php if (!empty($Assignments)): ?>
		<dt>Users</dt>
		<dd>
			<table>
				<thead>
					<tr>
						<th>User</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($Assignments as $assignment): ?>
						<tr>
							<td><?php echo $assignment['access_id'] == 0 ? 'All' : $assignment['username'] ?></td>
						</tr>
					<?php endforeach; ?>
				<tbody>
			</table>
		</dd>
	<?php endif; ?>

	<?php if (!empty($Permissions)): ?>
		<dt>Permissions</dt>
		<dd>
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
		</dd>
	<?php endif; ?>
	</dl>
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
