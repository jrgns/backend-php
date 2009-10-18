<?php
if (!empty($obj_tags)) {
	$obj_tags = implode(', ', array_flatten($obj_tags, 'id', 'name'));
} else {
	$obj_tags = '';
}
?>
<div class="tags_container">
	<label>Tags</label><br>
	<input type="text" class="text" name="tags" value="<?php echo $obj_tags ?>"/>
</div>
