<div id="footer" class="span-24 small quiet">					
	<hr class="bottom">
	<p class="bottom">
		Powered by <a href="http://backend-php.net" target="_top">backend-php.net</a>
	</p>
	<p>
		<?php if (!empty($CopyrightOwnerWebsite) || !empty($CopyrightOwner)): ?>
			&copy; <?php if (!empty($CopyrightOwnerWebsite)): ?><a href="http://#CopyrightOwnerWebsite#" target="_top"><?php endif; ?>
				<?php echo empty($CopyrightOwner) ? $CopyrightOwnerWebsite : $CopyrightOwner ?>
			<?php if (!empty($CopyrightOwnerWebsite)): ?></a><?php endif; ?>
			<?php echo date('Y') ?>
		<?php else: ?>
			&copy; <a href="http://www.jadeit.co.za">JadeIT</a> <?php echo date('Y') ?>
		<?php endif; ?>
	</p>
</div>

