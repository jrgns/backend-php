<form method="get" action="#SITE_LINK#?q=contact/search">Search: <input type="text" name="term" value="<?php echo $term ?>"></form>
<?php if (isset($results)): ?>
	<p class="large loud">Your search for <strong><?php echo $term ?></strong> returned <strong><?php echo is_array($results) ? count($results) : 0 ?></strong> results.</p>
	<?php foreach($results as $content): ?>
		{tpl:content.preview.tpl.php}
	<?php endforeach; ?>
<?php endif; ?>
