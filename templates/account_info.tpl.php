<form method="post" action="?q=<?php echo $_REQUEST['q'] ?>">
	<div  style="float: right;" id="gravatar_div">
		<h3>Gravatar</h3>
		<a href="http://en.gravatar.com/site/check/<?php echo $obj->email ?>" target="_blank">
			<img src="<?php echo $gravatar ?>" />
		</a>
	</div>
	<table>
		<tbody>
			<tr>
				<td><label class="large">Name:</label></td><td><input type="text" class="text" name="obj[name]" value="<?php echo $obj->name ?>"/></td>
			</tr>
			<tr>
				<td><label class="large">Surname:</label></td><td><input type="text" class="text" name="obj[surname]" value="<?php echo $obj->surname ?>" /></td>
			</tr>
			<tr>
				<td><label class="large">Username:</label></td><td><input type="text" class="text" name="obj[username]" value="<?php echo $obj->username ?>" /></td>
			</tr>
			<tr>
				<td><label class="large">Mobile:</label></td><td><input type="text" class="text" name="obj[mobile]" value="<?php echo $obj->mobile ?>" /></td>
			</tr>
			<tr>
				<td><label class="large">Email:</label></td><td><input type="text" class="text" name="obj[email]" value="<?php echo $obj->email ?>" /></td>
			</tr>
			<tr>
				<td colspan="2" style="text-align: center"><input type="submit" value="Update" /></td>
		</tbody>
	</table>
</form>
