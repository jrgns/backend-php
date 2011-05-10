<?php if ($can_install): ?>
	<p class="large loud">
		#Title# is now ready to be installed.
	</p>
	<form method="post" action="index.php">
		<input type="checkbox" id="add_database" name="add_database"><label for="add_database"> Add Database Settings</label><br>
		<input type="hidden" name="q" value="admin/install">
		<input type="submit" value="Install #Title#">
	</form>
	<hr class="space">
<?php else: ?>
	<p>
		Please check the issues raised before continuing
	</p>
<?php endif; ?>
