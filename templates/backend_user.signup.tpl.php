<form method="post" action="?q=backend_user/signup">
	<table>
		<tbody>
			<tr>
				<td><label class="large">Name:</label></td><td><input type="text" class="text" name="name" value="<?php echo $values['name'] ?>"/></td>
			</tr>
			<tr>
				<td><label class="large">Surname:</label></td><td><input type="text" class="text" name="surname" value="<?php echo $values['surname'] ?>"/></td>
			</tr>
			<tr>
				<td><label class="large">Username:</label></td><td><input type="text" class="text" name="username" value="<?php echo $values['username'] ?>"/></td>
			</tr>
			<tr>
				<td><label class="large">Password:</label></td><td><input type="password" class="text" name="password" value="<?php echo $values['password'] ?>"/></td>
			</tr>
			<tr>
				<td><label class="large">Email:</label></td><td><input type="text" class="text" name="email" value="<?php echo $values['email'] ?>"/></td>
			</tr>
			<tr>
				<td colspan="2" style="text-align: center"><input type="submit" value="Sign up!" /></td>
		</tbody>
	</table>
</form>
