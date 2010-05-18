<div class="success span-23 last" id="backend_success_container"<?php if (!isset($BackendNotices) || count($BackendNotices) == 0): ?> style="display: none;"<?php endif; ?>>
	<ul class="bottom loud large" id="backend_success">
		<?php if (isset($BackendSuccess) && count($BackendSuccess)): ?>
			<?php foreach($BackendSuccess as $success): ?>
				<li><?php echo $success ?></li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
</div>

