<?php echo '<?xml version="1.0"?>' . PHP_EOL; ?>
<rss version="2.0">
	<channel>
		<title><?php echo $title ?></title>
		<link><?php echo $link ?></link>
		<description><?php echo $description ?></description>
		<language>en-us</language>
		<pubDate><?php echo date('r') ?></pubDate>

		<lastBuildDate><?php echo date('r') ?></lastBuildDate>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<generator>Backend-PHP</generator>
		<managingEditor>editor@example.com</managingEditor>
		<webMaster>webmaster@example.com</webMaster>
		
		<?php foreach($list as $item): ?>
			<item>
				<title><?php echo $item['title'] ?></title>
				<link><?php echo $item['link'] ?></link>
				<description><?php echo $item['body'] ?></description>
				<pubDate><?php echo date('r', strtotime($item['added'])) ?></pubDate>
				<guid><?php echo $item['link'] ?></guid>
			</item>
		<?php endforeach; ?>
	</channel>
</rss>
