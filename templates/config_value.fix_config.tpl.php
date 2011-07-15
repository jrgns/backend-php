<h3>Config File</h3>
<p>
	The config file isn't writeable.
	<?php if ($file && $group): ?>
		On a linux / unix based system, this can be fixed by doing:
	<code><pre>
<?php if (!file_exists(dirname($file))): ?>
mkdir -p <?php echo dirname($file) ?>

<?php endif; ?>
<?php if (!file_exists($file)): ?>
touch <?php echo $file ?>

<?php endif; ?>
sudo chgrp <?php echo $group ?> <?php echo $file ?>

sudo chmod g+w <?php echo $file ?>
	</pre></code>
	Once the setup process is done, you should remove the writable flag by doing:
	<code><pre>
sudo chmod g-w <?php echo $file ?>
	</pre></code>
	<?php else: ?>
		Please check the permissions for the cache folder.
	<?php endif; ?>
</p>
