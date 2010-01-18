		<div class="tweet">
			<a style="float: left;" href="http://www.twitter.com/ <?php echo $tweet->from_user ?>">
				<img src="<?php echo $tweet->profile_image_url ?>">
			</a>
			<h3><?php echo $tweet->from_user ?></h3>
			<p class="large loud">
				<?php echo $tweet->text ?>
			</p>
			<p class="small quiet">
				<?php echo time_elapsed($tweet->created_at) ?>
			</p>
		</div>

