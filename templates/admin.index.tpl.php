<dl>
	<dt>Administrate</dt>
	<dd>
		<ul>
			<?php if (!ConfigValue::get('AdminInstalled', false) && in_array('superadmin', $user->roles)): ?>
				<li><a href="?q=admin/install">Install Application</a></li>
			<?php endif; ?>
			<?php if (!BACKEND_WITH_DATABASE): ?>
				<li><a href="?q=admin/install_db">Install Database</a></li>
			<?php endif; ?>
		</ul>
	</dd>
	<?php if (is_array($admin_links)): foreach($admin_links as $name => $links): ?>
	<dt><?php echo $name ?></dt>
	<dd>
		<ul>
			<?php foreach($links as $link): ?>
				<li><?php echo Links::render($link) ?></li>
			<?php endforeach; ?>
		</ul>
	</dd>
	<?php endforeach; endif; ?>
</dl>
