<h3>This application has not been installed yet.</h3>
<?php if ($can_install): ?>
	<p>
		If you are the administrator of this website, just click on this <a href="?q=admin/install">link</a> to install it
	</p>
<?php else: ?>
	<p>
		Please check the issues raised before continuing
	</p>
<?php endif; ?>