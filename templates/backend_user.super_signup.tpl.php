<form accept-charset="utf-8" method="post" action="?q=backend_user/super_signup/">
	<table> 
		<tbody> 
			<tr> 
				<td><label class="large">Username:</label></td><td><input type="text" class="text" name="username" value="admin"/></td> 
			</tr> 
			<tr> 
				<td><label class="large">Password:</label></td><td><input type="password" class="text" name="password" value=""/></td> 
			</tr> 
			<tr> 
				<td><label class="large">Confirm Password:</label></td><td><input type="password" class="text" name="confirm_password" value=""/></td> 
			</tr> 
			<tr> 
				<td><label class="large">Email:</label></td><td><input type="text" class="text" name="email" value="<?php echo ConfigValue::get('author.Email') ?>"/></td> 
			</tr> 
			<tr> 
				<td colspan="2" style="text-align: center"><input type="submit" value="Sign up!" />
			</td> 
		</tbody> 
	</table> 
</form>

