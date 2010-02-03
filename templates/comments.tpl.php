<hr class="space">
<div id="comments_container">
	<h3>Comments</h3>
	<div class="span-15">
		<?php if ($comment_list): ?>
			<?php foreach($comment_list as $comment): ?>
				{tpl:comment.tpl.php}
			<?php endforeach; ?>
		<?php else: ?>
			No comments yet... Be the first to add one!
		<?php endif; ?>
	</div>
</div>
