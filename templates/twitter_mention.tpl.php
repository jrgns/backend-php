		<div class="tweet">
			<a style="float: left;" href="http://www.twitter.com/ <?php echo $mention->user->screen_name ?>">
				<img src="<?php echo $mention->user->profile_image_url ?>">
			</a>
			<h3><?php echo $mention->user->screen_name ?></h3>
			<p class="large loud">
				<?php echo $mention->text ?>
			</p>
			<p class="small quiet">
				<?php echo time_elapsed($mention->created_at) ?>
			</p>
		</div>

