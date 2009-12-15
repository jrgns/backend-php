<?php
$Permission = property_exists($Result, 'permission') ? $Result->permission : false;
$Roles = property_exists($Result, 'roles') ? $Result->roles : false;
if (!empty($Permission)): ?>
	<div class="notice">
		<ul>
			<li>Show All roles with this permission</li>
			<li>Show All users with this permission??? Is this necessary? It might as well be checked in roles...</li>
		</ul>
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
