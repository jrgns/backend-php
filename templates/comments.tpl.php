<div id="comments_container">
	<h3>Comments</h3>
	<div id="name_container">
		<label id="_label" for="">Name:</label><input type="text" name="" id="" class="text"/>
	</div>
	<div id="comment_container">
		<input type="text" name="comment_title" id="comment_title" class="title"></textarea>
	</div>
	<div id="comment_container">
		<textarea name="comment_content" id="comment_content" class="comment"></textarea>
	</div>
	<?php if ($obj_comments): ?>
	<?php else: ?>
		No comments yet... Be the first to add one!
	<?php endif; ?>
</div>
