<hr class="space">
<h3>Post a comment</h3>
<form method="post" action="?q=comment/create" enctype="multipart/form-data">
	<?php if (!BackendUser::check()): ?>
		<label>Name:</label><span class="quiet"> Required</span><br><input type="text" class="text" name="user[name]"><br>
		<label>Email:</label><span class="quiet"> Required, won't be published</span><br><input type="text" class="text" name="user[email]"><br>
		<label>Website:</label><span class="quiet"> Optional</span><br><input type="text" class="text" name="user[website]"><br>
	<?php endif; ?>
	<input id="foreign_table" name="foreign_table" type="hidden" value="<?php echo $foreign_table ?>">
	<input id="foreign_id"    name="foreign_id"    type="hidden" value="<?php echo $foreign_id ?>">
	<textarea id="content" name="content" class="textarea"></textarea><br>
	<input type="submit" value="Add Comment" class=""/>
</form>
