<?php
$Role = property_exists($Result, 'role') ? $Result->role : false;
$Permissions = property_exists($Result, 'permissions') ? $Result->permissions : false;
if (!empty($Role)): ?>
	<h3 class="loud bottom">
		<?php echo $Role->array['name']; ?>
	</h3>
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
						<td><?php echo $permission['action'] == '*'   ? 'Anything' : $permission['action'] ?></td>
						<td><?php echo $permission['subject'] == '*'  ? 'Everything' : $permission['subject'] ?></td>
						<td><?php echo $permission['subject_id'] == 0 ? 'All' :  $permission['subject_id']?></td>
					</tr>
				<?php endforeach; ?>
			<tbody>
		</table>
	<?php endif; ?>
	<form>
		<h3>Add a Permission to <?php echo $Role->array['name'] ?></h3>
		
	</form>
<?php else: ?>
	No Role to display
<?php endif; ?>
