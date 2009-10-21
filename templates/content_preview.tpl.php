<div class="content_preview box">
	<h3><?php echo $content['title'] ?></h3>
	<div>
		<?php echo Content::createPreview($content['body'], 100) ?>
	</div>
	<div class="bottom">
		<a href="?q=content/<?php echo $content['id'] ?>">Read More</a>
	</div>
</div>

