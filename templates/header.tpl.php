<div id="header" class="prepend-top append-bottom span-24 last">
	<div class="prepend-1 span-9">
		<a href="?q=">
			<?php if (file_exists(WEB_FOLDER . '/images/logo.png')): ?>
				<img src="#SITE_LINK#images/logo.png" alt="#Title#" title="#Title#">
			<?php else: ?>
				<h1 class="bottom">#Title#</h1>
			<?php endif; ?>
		</a>
	</div>
	<?php if (!empty($Moto)): ?>
		<div class="span-13 last prepend-top append-1" style="text-align: right;">
			<p class="large loud">#Moto#</p>
		</div>
	<?php endif; ?>
</div>
