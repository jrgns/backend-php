<?php if (isset($BackendNotices) && count($BackendNotices)): ?>
	<div class="notice span-23 last">
		<ul class="bottom loud large">
			<?php foreach($BackendNotices as $notice): ?>
				<li><?php echo $notice ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
