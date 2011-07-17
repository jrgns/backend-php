<div id="loginout" class="box">
<?php if (!BackendUser::check()): ?>
	<form method="post" action="#SITE_LINK#?q=backend_user/login">
		<div>
			<label>Username:</label> <input class="text" type="text" name="username"><br>
			<label>Password:</label> <input class="text" type="password" name="password"><br>
			<input type="submit" value="Login" name="do_login">
		</div>
	</form>
<?php else: ?>
	You are logged in as <a href="?q=backend_user/<?php echo $_SESSION['BackendUser']->id ?>">
		<?php echo $_SESSION['BackendUser']->username ?>
	</a>
	<form method="post" action="#SITE_LINK#?q=backend_user/logout">
		<div>
			<input type="submit" value="Logout" name="do_logout">
		</div>
	</form>
<?php endif; ?>
</div>

