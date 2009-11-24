<?php
if (!empty($revisions)) foreach($revisions as $key => $revision): ?>
	<div class="content_revision">
		<h3><?php echo $revision['id'] ?>:</h3>
		<?php echo $revision['body'] ?>
	</div>
<?php endforeach; ?>
