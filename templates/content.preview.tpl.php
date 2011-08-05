<div class="content_preview box">
	<h3><a href="?q=content/<?php echo $content['name'] ?>">
	    <?php echo $content['title'] ?>
    </a></h3>
	<div>
		<?php echo Content::createPreview($content['body'], 100) ?>
	</div>
	<div class="bottom">
		<a href="?q=content/<?php echo $content['name'] ?>">Read More</a>
	</div>
</div>
