<?php
$account_name = BackendAccount::getName();
$Comment = new CommentObj($comment['id']);
if ($Comment->array):
	$comment = $Comment->array;
	if (filter_var($comment[$account_name]['username'], FILTER_VALIDATE_EMAIL) && !$comment[$account_name]['confirmed']) {
		$posted_by = $comment[$account_name]['name'];
		if (!empty($comment[$account_name]['website'])) {
			$posted_by = '<a href="' . $comment['Account']['website'] . '">' . $posted_by . '</a>';
		}
	} else {
		$posted_by = $comment[$account_name]['username'];
		if ($comment[$account_name]['confirmed']) {
			$posted_by = '<a href="?q=account/' . $comment['user_id'] . '">' . $posted_by . '</a>';
		}
	}
?>
	<div>
		<img src="<?php echo BackendAccount::getGravatar($comment[$account_name]['email'], 30) ?>" style="float: left; margin-right: 5px;" width="30px" height="30px">
		<p class="quiet">
			posted by <?php echo $posted_by ?> <span title="<?php echo $comment['added'] ?>"><?php echo time_elapsed($comment['added']) ?></a>
		</p>
		<p>
			<?php echo $comment['content'] ?>
		</p>
		<hr>
	</div>
<?php endif; ?>