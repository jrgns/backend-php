<?php if (isset($BackendErrors) && count($BackendErrors)): ?>
	<div class="error span-23 prepend-1">
		<ul class="bottom loud large">
			<?php foreach($BackendErrors as $error): ?>
				<li><?php echo $error ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
