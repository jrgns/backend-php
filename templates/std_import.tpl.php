<?php
$action_url = empty($action_url) ?
	Controller::$area . '/' . Controller::$action . (empty(Controller::$parameters[0]) ? '' : '/' . Controller::$parameters[0]) :
	$action_url;
$action_name = empty($action_name) ? ucwords(Controller::$action) : $action_name;
if (!empty($db_object)): ?>
	<form method="post" action="?q=<?php echo $action_url ?>" enctype="multipart/form-data">
	<?php 
		$fields = $db_object->getMeta('fields');
		?>
		<input type="file" name="import_file" class="text" />
		<input type="submit" value="<?php echo $action_name ?> <?php echo get_class($db_object->getMeta('name')) ?>" class=""/>
	</form>
<?php else: Backend::addNotice('No Object to Import'); ?>
	No object
<?php endif; ?>
