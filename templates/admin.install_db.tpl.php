<p class="large">
	Please provide the following information
</p>
<form method="post" action="index.php">
	<input type="hidden" name="q" value="admin/install_db">
	<label>Database<br>
		<input type="text" class="text" name="database" value="<?php echo $database ?>">
	</label><br>
	<label>DB Username<br>
		<input type="text" class="text" name="username" value="<?php echo $username ?>">
	</label><br>
	<label>DB Password<br>
		<input type="text" class="text" name="password" value="<?php echo $password ?>">
	</label><br>
	<label>DB Host<br>
		<input type="text" class="text" name="hostname" value="<?php echo empty($hostname) ? '127.0.0.1' : $hostname ?>">
	</label><br>
	<input type="submit" value="Configure Database">
</form>

