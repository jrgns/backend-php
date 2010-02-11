<?php echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL; ?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title><?php echo $title ?></title>
	<?php if (!empty($subtitle)): ?><subtitle><?php echo $sub_title ?></subtitle><?php endif; ?>
	<link><?php echo $link ?></link>
	<description><?php echo $description ?></description>
	<updated><?php echo date('r') ?></updated>
	<?php foreach($list as $item): ?>
		<entry>
			<title><?php echo $item['title'] ?></title>
			<link><?php echo $item['link'] ?></link>
			<summary type="html"><![CDATA[<?php echo $item['body'] ?>]]></summary>
			<updated><?php echo date('r', strtotime($item['added'])) ?></updated>
			<id><?php echo $item['link'] ?></id>
		</entry>
	<?php endforeach; ?>
</feed>
