<?php echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL; ?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title><?php echo htmlspecialchars($title) ?></title>
	<?php if (!empty($subtitle)): ?><subtitle><?php echo htmlspecialchars($sub_title) ?></subtitle><?php endif; ?>
	<link href="<?php echo $link ?>" rel="self" />
	<id><?php echo $link ?></id>
	<updated><?php echo date('r') ?></updated>
	<?php $author = Backend::getConfig('application.author'); ?>
	<?php if (is_string($author)): ?>
		<author><name><?php echo $author ?></name></author>
	<?php elseif (is_array($author)): ?>
		<author>
			<?php if (array_key_exists('name', $author)): ?>
				<name><?php echo $author['name'] ?></name>
			<?php endif; ?>
			<?php if (array_key_exists('email', $author)): ?>
				<email><?php echo $author['email'] ?></email>
			<?php endif; ?>
			<?php if (array_key_exists('website', $author)): ?>
				<uri><?php echo $author['website'] ?></uri>
			<?php endif; ?>
		</author>
	<?php endif; ?>
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
