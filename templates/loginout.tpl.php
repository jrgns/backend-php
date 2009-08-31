<div id="loginout" class="box span-5">
<?php if (!Account::checkUser()): ?>
	<form method="post" action="<?php echo S_SITE_LINK ?>/?q=account/login">
		<label>Username:</label> <input class="text" type="text" name="username" /><br/>
		<label>Password:</label> <input class="text" type="password" name="password" /><br/>
		<input type="submit" value="Login" name="do_login" />
	</form>
	<p>Not a member yet?<br/><a href="?q=account/signup">Signup now!</a> It's free... ;)</p>
<?php else: ?>
	You are logged in as <?php echo $_SESSION['user']->username ?>
	<form method="post" action="<?php echo S_SITE_LINK ?>/?q=account/logout">
		<input type="submit" value="Logout" name="do_logout" />
	</form>
<?php endif; ?>
</div>

