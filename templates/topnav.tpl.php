<div id="topnav" class="large span-24 last">
	<div id="topnav_container">
		<?php if (!empty($primary_links)) foreach($primary_links as $link): ?>
			<label class="nav_item">
				<a class="menu_item" href="<?php echo $link['href'] ?>"><?php echo $link['text'] ?></a>
			</label>
		<?php endforeach; ?>
	</div>
</div>

