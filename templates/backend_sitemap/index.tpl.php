<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach($sitemaps as $map): ?>
	<sitemap>
		<loc><?php echo $map['location'] ?></loc>
		<lastmod><?php echo empty($map['modified']) ? date('c') : date('c', strtotime($map['modified'])) ?></lastmod>
	</sitemap>
<?php endforeach ?>
</sitemapindex>
