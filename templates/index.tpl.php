<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>#Title# - #Sub Title#</title>
		{tpl:templates/styles.tpl.php}
		<link rel="shortcut icon" type="image/x-icon" href="<?php echo SITE_LINK ?>/favicon.ico">
	</head>
	<body>
		<div id="middleback">
			<div class="container">
				<div id="header" class="span-24">
					<h1 class="bottom"><a href="?q=">#Title#</a></h1>
					<p class="">&nbsp;#Moto#</p>
				</div>
				<div id="topinfo" class="span-24 success hide">
					Top Info
				</div>
				<div id="topnav" class="span-24">
					{tpl:templates/topnav.tpl.php}
				</div>
				<div id="teaser" class="span-24">
					{tpl:templates/backend_errors.tpl.php}
					{tpl:templates/backend_success.tpl.php}
					{tpl:templates/backend_notices.tpl.php}
					<?php if (!empty($Teaser)): ?><p class="bottom">#Teaser#</p><?php endif; ?>
					<hr/>
				</div>

				<div id="maincol" class="span-15 prepend-1 colborder">
					<h2 class="quiet">#Sub Title#</h2>
					<div id="content">
						{tpl:templates/tab_links.tpl.php}
						{tpl:templates/maincontent.tpl.php}
					</div>
				</div>
				<div id="rightcol" class="span-6 last">
					<div class="box loud">
						#HelpBoxContent#
					</div>
					<h3>Go...</h3>
					<ul>
						<li class="loud">Something</li>
						<li>Another</li>
					</ul>
					{tpl:templates/loginout.tpl.php}
				</div>
				<?php if ($debug): ?>
					<div id="lastcontent" class="notice span-23 last">
						#Last Content#
					</div>
				<?php endif; ?>
				<div id="footer" class="span-24 small quiet">
					<hr class="bottom"/>
					<p class="bottom">
						Let us know if you need anything else...
					</p>
					<p>
						&copy; <a href="http://www.jadeit.co.za">JadeIT</a> 2009
					</p>
				</div>
			</div>
		</div>
		<div id="lowerback">
			&nbsp;
		</div>
		{tpl:templates/scripts.tpl.php}
	<?php if (!$LocalSite): ?>
		<script type="text/javascript">
			var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
			document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
		</script>
		<script type="text/javascript">
			var pageTracker = _gat._getTracker("UA-3257244-1");
			pageTracker._initData();
			pageTracker._trackPageview();
		</script>
	<?php endif; ?>
	</body>
</html>
