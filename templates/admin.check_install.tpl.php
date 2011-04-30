<?php if ($can_install): ?>
	<p class="large loud">
		#Title# is now ready to be installed.
	</p>
	<form method="post" action="index.php">
		<input type="hidden" name="q" value="admin/install">
		<input type="submit" value="Install #Title#">
	</form>
<?php else: ?>
	<p>
		Please check the issues raised before continuing
	</p>
<?php endif; ?>