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
				<td><label class="large">Name:</label></td><td><span><?php echo $obj->name ?></span>
			</tr>
			<tr>
				<td><label class="large">Surname:</label></td><td><span><?php echo $obj->surname ?></span>
			</tr>
			<tr>
				<td><label class="large">Username:</label></td><td><span><?php echo $obj->username ?></span>
			</tr>
			<tr>
				<td><label class="large">Mobile:</label></td><td><span><?php echo $obj->mobile ?></span>
			</tr>
			<tr>
				<td><label class="large">Email:</label></td><td><span><?php echo $obj->email ?></span>
			</tr>
			<tr>
				<td colspan="2" style="text-align: center"><a href="?q=account/update">Edit</a></td>
		</tbody>
	</table>
</form>
