<div id="comments_container">
	<h3>Comments</h3>
	<div id="title_container">
		<label id="comment_title_label">Title:</label><input type="text" name="comment_title" id="comment_title" class="title">
	</div>
	<div id="content_container">
		<textarea name="comment_content" id="comment_content" class="comment"></textarea>
	</div>
	<?php if ($comment_list): var_dump($comment_list); die; ?>
		<?php foreach($comment_list as $comment): ?>
			{tpl:comment.tpl.php}
		<?php endforeach; ?>
	<?php else: ?>
		No comments yet... Be the first to add one!
	<?php endif; ?>
</div>
