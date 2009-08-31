<div id="topnav_container">
	<?php if (!empty($links)) foreach($links as $link): ?>
		<a class="menu_item" href="<?php echo $link['href'] ?>"><?php echo $link['text'] ?></a>
	<?php endforeach; ?>
</div>
