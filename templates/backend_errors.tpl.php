<div class="error span-23 last" id="backend_error_container"<?php if (!isset($BackendErrors) || count($BackendErrors) == 0): ?> style="display: none;"<?php endif; ?>>
	<ul class="bottom loud large" id="backend_error">
		<?php if (isset($BackendErrors) && count($BackendErrors)): ?>
			<?php foreach($BackendErrors as $error): ?>
				<li><?php echo $error ?></li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
</div>
