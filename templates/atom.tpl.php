<?php echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL; ?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title><?php echo htmlspecialchars($title) ?></title>
	<?php if (!empty($subtitle)): ?><subtitle><?php echo htmlspecialchars($sub_title) ?></subtitle><?php endif; ?>
	<link href="<?php echo $link ?>" rel="self" />
	<id><?php echo $link ?></id>
	<description><?php echo htmlspecialchars($description) ?></description>
	<updated><?php echo date('r') ?></updated>
	<?php if ($list) foreach($list as $item): ?>
		<entry>
			<title><?php echo htmlspecialchars($item['title']) ?></title>
			<link href="<?php echo $item['link'] ?>" />
			<summary type="html"><![CDATA[<?php echo Content::createPreview($item['body']) ?>]]></summary>
			<updated><?php echo gmdate('r', strtotime($item['modified'])) ?></updated>
			<id><?php echo $item['link'] ?></id>
		</entry>
	<?php endforeach; ?>
</feed>
