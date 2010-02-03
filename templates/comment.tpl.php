	<div>
		<img src="http://www.gravatar.com/avatar.php?size=30&d=identicon&gravatar_id=<?php echo md5(strtolower($comment['email'])) ?>" style="float: left; margin-right: 5px;" width="30px" height="30px">
		<p class="quiet">
			posted by <?php echo $comment['username'] ?> <span title="<?php echo $comment['added'] ?>"><?php echo time_elapsed($comment['added']) ?></a>
		</p>
		<p>
			<?php echo $comment['content'] ?>
		</p>
		<hr>
	</div>
