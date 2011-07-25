<div class="info" id="backend_info_container"<?php if (!isset($BackendInfo) || count($BackendInfo) == 0): ?> style="display: none;"<?php endif; ?>>
	<ul class="bottom loud large" id="backend_info">
		<?php if (isset($BackendInfo) && count($BackendInfo)): ?>
			<?php foreach($BackendInfo as $info): ?>
				<li><?php echo $info ?></li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
</div>
