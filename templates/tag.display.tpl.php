<?php if ($Object): ?>
	<?php if (!empty($Object->array['description'])): ?>
		<div>
			<?php echo $Object->array['description'] ?>
		</div>
		<hr/>
	<?php endif; ?>
	<?php if (!empty($Object->array['content_list']) && is_array($Object->array['content_list'])): ?>
		<?php foreach($Object->array['content_list'] as $content): ?>
			{tpl:content_preview.tpl.php}
		<?php endforeach; ?>
	<?php endif; ?>
<?php endif; ?>
