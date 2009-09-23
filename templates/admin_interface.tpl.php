<dl>
	<dt>Administrate</dt>
	<dd>
		<ul>
			<?php if (!Value::get('admin_installed', false) && in_array('superadmin', $user->roles)): ?>
				<li><a href="?q=admin/install">Install Application</a></li>
			<?php endif; ?>
			<li><a href="?q=admin/components">Manage Components</a></li>
			<li><a href="?q=admin/update">Update Application</a></li>
		</ul>
	</dd>
</dl>
