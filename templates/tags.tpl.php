<div class="tags_container">
	Filed Under
	<?php foreach($obj_tags as $tag): ?>
		<label><a href="?q=tag/<?php echo $tag['tag_id'] ?>"><?php echo $tag['name'] ?></a></label>
	<?php endforeach; ?>
</div>
<hr>

