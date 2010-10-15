	<?php if (Component::isActive('Style')): ?>
		<link rel="stylesheet" href="<?php echo SITE_LINK ?>?q=style/#Area#/#Action#.css" type="text/css">
	<?php endif; ?>
<?php if (!empty($Styles)): ?>
	<?php foreach($Styles as $style): ?>
		<link type="text/css" rel="stylesheet" href="<?php echo $style ?>">
	<?php endforeach; ?>
<?php endif; ?>