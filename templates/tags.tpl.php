<div class="tags_container">
	<?php foreach($obj_tags as $tag): ?>
		<label><a href="?q=tag/<?php echo $tag['id'] ?>"><?php echo $tag['name'] ?></a></label>
	<?php endforeach; ?>
</div>
