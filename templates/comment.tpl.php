<?php
$Comment = new CommentObj($comment['id']);
if ($Comment->array):
	$comment = $Comment->array;
	if (filter_var($comment['Account']['username'], FILTER_VALIDATE_EMAIL) && !$comment['Account']['confirmed']) {
		$posted_by = $comment['Account']['name'];
		if (!empty($comment['Account']['website'])) {
			$posted_by = '<a href="' . $comment['Account']['website'] . '">' . $posted_by . '</a>';
		}
	} else {
		$posted_by = $comment['Account']['username'];
		if ($comment['Account']['confirmed']) {
			$posted_by = '<a href="?q=account/' . $comment['user_id'] . '">' . $posted_by . '</a>';
		}
	}
?>
	<div>
		<img src="<?php echo Account::getGravatar($comment['Account']['email'], 30) ?>" style="float: left; margin-right: 5px;" width="30px" height="30px">
		<p class="quiet">
			posted by <?php echo $posted_by ?> <span title="<?php echo $comment['added'] ?>"><?php echo time_elapsed($comment['added']) ?></a>
		</p>
		<p>
			<?php echo $comment['content'] ?>
		</p>
		<hr>
	</div>
<?php endif; ?>
