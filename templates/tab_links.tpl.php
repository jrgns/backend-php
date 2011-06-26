<?php if (!empty($TabLinks)): ?>
	<ul class="tabs span-16 last">
	<?php foreach($TabLinks as $link): ?>
		<li><a href="<?php echo $link['link'] ?>"><?php echo $link['text'] ?></a></li>
	<?php endforeach; ?>
	</ul>
<?php endif; ?>
