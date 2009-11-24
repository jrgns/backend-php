<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>#Title#<?php if (!empty($Sub_Title)): ?> - #Sub Title#<?php endif; ?></title>
		{tpl:head.tpl.php}
		{tpl:styles.tpl.php}
		<link rel="shortcut icon" type="image/x-icon" href="<?php echo SITE_LINK ?>/favicon.ico">
	</head>
	<body>
		<div id="middleback">
			<div class="container">
				{tpl:header.tpl.php}
				<div id="topinfo" class="span-24 success hide">
					Top Info
				</div>
				<div id="topnav" class="span-24">
					{tpl:topnav.tpl.php}
				</div>
				<div id="teaser" class="span-24">
					{tpl:backend_errors.tpl.php}
					{tpl:backend_success.tpl.php}
					{tpl:backend_notices.tpl.php}
					<?php if (!empty($Teaser)): ?><p class="bottom">#Teaser#</p><?php endif; ?>
					<hr>
				</div>

				<div id="maincol" class="span-15 prepend-1 colborder">
					{tpl:tab_links.tpl.php}
					<?php if (!empty($Sub_Title)): ?><h2 class="quiet">#Sub Title#</h2><?php endif; ?>
					<div id="content">
						{tpl:maincontent.tpl.php}
					</div>
				</div>
				<div id="rightcol" class="span-6 last">
					<div class="box loud" id="helpbox">
						#HelpBoxContent#
					</div>
					{tpl:secondary_links.tpl.php}
					{tpl:loginout.tpl.php}
				</div>
				<?php if ($debug): ?>
					<div id="lastcontent" class="notice span-23 last">
						#Last Content#
					</div>
				<?php endif; ?>
				{tpl:footer.tpl.php}
			</div>
		</div>
		<div id="lowerback">
			&nbsp;
		</div>
		{tpl:scripts.tpl.php}
		<?php if (SITE_STATE == 'production'): ?>
			{tpl:tracking.tpl.php}
		<?php endif; ?>
	</body>
</html>
