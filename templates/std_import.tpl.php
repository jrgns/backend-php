<?php if (!empty($Object)): ?>
	<form method="post" action="?q=<?php echo array_key_exists('q', $_REQUEST) ? $_REQUEST['q'] : '' ?>" enctype="multipart/form-data">
	<?php 
		$fields = $Object->getMeta('fields');
		?>
		<input type="file" name="import_file" class="text" />
		<input type="submit" value="Import <?php echo get_class($Object->getMeta('name')) ?>" class=""/>
	</form>
<?php else: Backend::addNotice('No Object to Import'); ?>
	No object
<?php endif; ?>