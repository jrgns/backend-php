<div class="notice" id="backend_notice_container"<?php if (!isset($BackendNotices) || count($BackendNotices) == 0): ?> style="display: none;"<?php endif; ?>>
	<ul class="bottom loud large" id="backend_notice">
		<?php if (isset($BackendNotices) && count($BackendNotices)): ?>
			<?php foreach($BackendNotices as $notice): ?>
				<li><?php echo $notice ?></li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
</div>

