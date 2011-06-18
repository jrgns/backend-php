<?php if (is_array($admin_links)): foreach($admin_links as $name => $links): ?>
	<dl>
		<dt><?php echo $name ?></dt>
		<dd>
			<ul>
				<?php foreach($links as $link): ?>
					<li><?php echo Links::render($link) ?></li>
				<?php endforeach; ?>
			</ul>
		</dd>
	</dl>
<?php endforeach; else: ?>
	No Administrative Links
<?php endif; ?>

