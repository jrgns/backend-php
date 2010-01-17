	<div>
		<img src="http://www.gravatar.com/avatar.php?size=30&d=identicon&gravatar_id=<?php echo md5(strtolower($comment['email'])) ?>" style="float: right;">
		<p class="large loud">
			<?php echo $comment['username'] ?> at <?php echo $comment['added'] ?>
		</p>
		<p>
			<?php echo $comment['content'] ?>
		</p>
		<hr>
	</div>
