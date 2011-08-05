<div id="loginout" class="box left">
<?php if (!BackendUser::check()): ?>
    <form method="post" action="#SITE_LINK#?q=backend_user/login" class="span-5">
	    <label class="span-2">Username:</label><input class="text span-5" type="text" name="username">
	    <label class="span-2">Password:</label><input class="text span-5" type="password" name="password">
	    <input class="left" type="submit" value="Login" name="do_login">
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
