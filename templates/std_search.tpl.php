<form method="get" action="?">
	<input type="hidden" name="q" value="<?php echo Controller::$area ?>/search">
	<input type="text" class="text" name="term" id="term" value="<?php echo empty($term) ? '' : $term ?>">
	<input type="submit" value="Search">
</form>
