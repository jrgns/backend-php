<?php if (!empty($Scripts)): ?>
	<?php foreach($Scripts as $script): ?>
		<script type="text/javascript" src="<?php echo $script ?>"></script>
	<?php endforeach; ?>
<?php endif; ?>
<?php if (!empty($ScriptContent)): ?>
<script>
<?php echo implode(PHP_EOL, $ScriptContent) ?>

</script>
<?php endif; ?>