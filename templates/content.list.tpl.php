<?php if (!empty($Object) && !empty($Object->list)): ?>
	<div id="content_container">
		<?php foreach($Object->list as $content): ?>
			{tpl:content_preview.tpl.php}
		<?php endforeach; ?>
	</div>
<?php else: ?>
	No Content Yet...
<?php endif; ?>