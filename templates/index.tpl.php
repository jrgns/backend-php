<!DOCTYPE html>
<html>
	<head>
		<title><?php echo strip_tags($Title); if (!empty($Sub_Title)): ?> - <?php echo strip_tags($Sub_Title); endif; ?></title>
		{tpl:head.tpl.php}
		{tpl:styles.tpl.php}
		<link rel="shortcut icon" type="image/x-icon" href="#SITE_LINK#favicon.ico">
	</head>
	<body>
		<div class="container">
		    {tpl:header.tpl.php}
		    <?php if (!empty($primary_links)): ?>
			    {tpl:topnav.tpl.php}
		    <?php endif; ?>
		    <div id="sub_title">
			    <?php if (!empty($Sub_Title)): ?><h2 class="quiet">#Sub Title#</h2><?php endif; ?>
		    </div>
		    <div class="clear"></div>
			{tpl:backend_errors.tpl.php}
			{tpl:backend_success.tpl.php}
			{tpl:backend_notices.tpl.php}
			{tpl:backend_info.tpl.php}
			<div id="maincol" class="span-18">
				{tpl:tab_links.tpl.php}
				<div id="content">
					{tpl:maincontent.tpl.php}
				</div>
			</div>
			<div id="rightcol" class="span-6 last">
				{tpl:rightcol.tpl.php}
			</div>
			<?php if ($debug): ?>
				<div id="lastcontent" class="notice span-23 last">
					#Last Content#
				</div>
			<?php endif; ?>
    		{tpl:footer.tpl.php}
		</div>
		{tpl:scripts.tpl.php}
		<?php if (SITE_STATE == 'production'): ?>
			{tpl:tracking.tpl.php}
		<?php endif; ?>
		<script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>
	</body>
</html>
