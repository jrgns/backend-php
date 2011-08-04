<?php if(!empty($secondary_links)): ?>
	{tpl:secondary_links.tpl.php}
<?php endif; ?>
<?php if (!empty($HelpBoxContent)): ?>
	<div class="box loud clear" id="helpbox">
		#HelpBoxContent#
	</div>
<?php endif; ?>
<?php if (Component::isActive('BackendUser')): ?>
	{tpl:loginout.tpl.php}
<?php endif; ?>
