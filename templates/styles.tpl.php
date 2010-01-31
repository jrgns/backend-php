		<link rel="stylesheet" href="<?php echo SITE_LINK ?>styles/blueprint/screen.css" type="text/css" media="screen, projection">
		<link rel="stylesheet" href="<?php echo SITE_LINK ?>styles/blueprint/print.css" type="text/css" media="print"> 
		<link rel="stylesheet" href="<?php echo SITE_LINK ?>styles/blueprint/tabs.css" type="text/css" media="screen, projection"> 
		<!--[if IE]>
		<link rel="stylesheet" href="styles/blueprint/ie.css" type="text/css" media="screen, projection">
		<![endif]-->
		<link rel="stylesheet" href="<?php echo SITE_LINK ?>styles/basic.css" type="text/css">
		<?php if (Component::isActive('Style')): ?>
			<link rel="stylesheet" href="<?php echo SITE_LINK ?>style/#Area#/#Action#.css" type="text/css">
		<?php endif; ?>
<?php if (!empty($Styles)): ?>
	<?php foreach($Styles as $style): ?>
		<link type="text/css" rel="stylesheet" href="<?php echo $style ?>">
	<?php endforeach; ?>
<?php endif; ?>