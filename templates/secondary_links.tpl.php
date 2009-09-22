<?php if (!empty($secondary_links)): ?>
<h3>Go...</h3>
<ul>
	<?php foreach($secondary_links as $link): ?>
		<li><a class="menu_item" href="<?php echo $link['href'] ?>"><?php echo $link['text'] ?></a></li>
	<?php endforeach; ?>
</ul>
<?php else: ?>
<hr/>
<?php endif; ?>
