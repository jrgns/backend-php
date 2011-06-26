<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>#Title#<?php if (!empty($Sub_Title)): ?> - #Sub Title#<?php endif; ?></title>
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
			<?php if (!empty($BackendNotices) || !empty($BackendErrors) || !empty($BackendSuccess) || !empty($Teaser)): ?>
				<div id="teaser" class="span-24 last clear">
					{tpl:backend_errors.tpl.php}
					{tpl:backend_success.tpl.php}
					{tpl:backend_notices.tpl.php}
					<?php if (!empty($Teaser)): ?><p class="bottom">#Teaser#</p><?php endif; ?>
				</div>
			<?php endif; ?>
			<div id="maincol" class="clear">
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
	</body>
</html>
