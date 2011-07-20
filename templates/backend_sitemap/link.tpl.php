	<url>
		<loc><?php echo $link['url'] ?></loc>
		<lastmod><?php echo date('c', strtotime($link['modified'])) ?></lastmod>
		<changefreq><?php echo empty($link['frequency']) ? 'weekly' : $link['frequency'] ?></changefreq>
		<priority><?php echo empty($link['priority']) ? '0.5' : $link['priority'] ?></priority>
	</url>
