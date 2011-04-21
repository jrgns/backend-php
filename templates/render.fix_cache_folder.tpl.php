<h3>Cache Folder</h3>
<p>
	The cache folder doesn't exist, or is unwritable.
	<?php if ($folder && $group): ?>
		On a linux / unix based system, this can be fixed by doing:
	<code><pre>
<?php if (!file_exists($folder)): ?>
mkdir -p <?php echo $folder ?>

<?php endif; ?>
sudo chgrp <?php echo $group ?> <?php echo $folder ?>

sudo chmod g+w <?php echo $folder ?>
	</pre></code>
	<?php else: ?>
		Please check the permissions for the cache folder.
	<?php endif; ?>
</p>

