<?php echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL; ?>
<rss version="2.0">
	<channel>
		<title><?php echo htmlspecialchars($title) ?></title>
		<link><?php echo $link ?></link>
		<description><?php echo htmlspecialchars($description) ?></description>
		<language>en-us</language>
		<pubDate><?php echo date('r') ?></pubDate>

		<lastBuildDate><?php echo date('r') ?></lastBuildDate>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<generator>Backend-PHP</generator>
		<managingEditor><?php echo ConfigValue::get('author.Email', ConfigValue::get('application.Email', 'info@' . SITE_DOMAIN)) ?></managingEditor>
		<webMaster><?php echo ConfigValue::get('author.Email', ConfigValue::get('application.Email', 'info@' . SITE_DOMAIN)) ?></webMaster>

		<?php if ($list) foreach($list as $item): ?>
			<item>
				<title><?php echo htmlspecialchars($item['title']) ?></title>
				<link><?php echo $item['link'] ?></link>
				<description><![CDATA[ <?php echo $item['body'] ?> ]]></description>
				<pubDate><?php echo date('r', strtotime($item['added'])) ?></pubDate>
				<guid><?php echo $item['link'] ?></guid>
			</item>
		<?php endforeach; ?>
	</channel>
</rss>
