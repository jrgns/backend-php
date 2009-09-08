<form method="post" action="?q=account/signup">
	<table>
		<tbody>
			<tr>
				<td><label class="large">Username:</label></td><td><input type="text" class="text" name="obj[username]" value="<?php echo $obj_values['username'] ?>"/></td>
			</tr>
			<tr>
				<td><label class="large">Name:</label></td><td><input type="text" class="text" name="obj[name]" value="<?php echo $obj_values['name'] ?>"/></td>
			</tr>
			<tr>
				<td><label class="large">Surname:</label></td><td><input type="text" class="text" name="obj[surname]" value="<?php echo $obj_values['surname'] ?>"/></td>
			</tr>
			<tr>
				<td><label class="large">Password:</label></td><td><input type="password" class="text" name="obj[password]" value="<?php echo $obj_values['password'] ?>"/></td>
			</tr>
			<tr>
				<td><label class="large">Email:</label></td><td><input type="text" class="text" name="obj[email]" value="<?php echo $obj_values['email'] ?>"/></td>
			</tr>
			<tr>
				<td colspan="2" style="text-align: center"><input type="submit" value="Sign up!" /></td>
		</tbody>
	</table>
</form>
