<?php
$Comment = new CommentObj($comment['id']);
if ($Comment->array):
	$comment = $Comment->array;
	if (filter_var($comment['BackendUser']['username'], FILTER_VALIDATE_EMAIL) && !$comment['BackendUser']['confirmed']) {
		$posted_by = $comment['BackendUser']['name'];
		if (!empty($comment['BackendUser']['website'])) {
			$posted_by = '<a href="http://' . $comment['BackendUser']['website'] . '">' . $posted_by . '</a>';
		}
	} else {
		$posted_by = $comment['BackendUser']['username'];
		if ($comment['BackendUser']['confirmed']) {
			$posted_by = '<a href="?q=backend_user/' . $comment['user_id'] . '">' . $posted_by . '</a>';
		}
	}
?>
	<div id="comment_<?php echo $comment['id'] ?>">
		<img src="<?php echo BackendUser::getGravatar($comment['BackendUser']['email'], 30) ?>" style="float: left; margin-right: 5px;" width="30px" height="30px">
		<p class="quiet">
			posted by <?php echo $posted_by ?> <span title="<?php echo $comment['added'] ?>"><?php echo time_elapsed($comment['added']) ?></a>
		</p>
		<p>
			<?php echo $comment['content'] ?>
		</p>
		<hr>
	</div>
<?php endif; ?>
