<p>Please choose a table</p>
<form method="post">
	<div>
		<label>Database<br>
			<select name="database">
				<option>--Choose One--</option>
				<?php foreach($databases as $database): ?>
					<option><?php echo $database ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</div>
	<div>
		<label>Table<br>
			<input class="text" type="text" name="table">
		</label>
	</div>
	<input type="submit" value="Create Scaffolding">
</form>
