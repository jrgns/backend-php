<div class="success" id="backend_success_container"<?php if (!isset($BackendSuccess) || count($BackendSuccess) == 0): ?> style="display: none;"<?php endif; ?>>
	<ul class="bottom loud large" id="backend_success">
		<?php if (isset($BackendSuccess) && count($BackendSuccess)): ?>
			<?php foreach($BackendSuccess as $success): ?>
				<li><?php echo $success ?></li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
</div>

