<?php if (!empty($tertiary_links)): ?>
<h3><?php echo empty($tertiary_links_title) ? 'Go...' : $tertiary_links_title ?></h3>
<ul>
	<?php foreach($tertiary_links as $link): ?>
		<li><a href="<?php echo $link['href'] ?>"><?php echo $link['text'] ?></a></li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>