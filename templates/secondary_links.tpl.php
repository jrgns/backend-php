<?php if (!empty($secondary_links)): ?>
<h3><?php echo empty($secondary_links_title) ? 'Go...' : $secondary_links_title ?></h3>
<ul>
	<?php foreach($secondary_links as $link): ?>
		<li><a href="<?php echo $link['href'] ?>"><?php echo $link['text'] ?></a></li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>