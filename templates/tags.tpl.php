<?php
if (!empty($obj_tags)) {
	$obj_tags = array_flatten($obj_tags, 'id', 'name');
} else {
	$obj_tags = array();
}
?>
<div class="tags_container">
	<label>Tags</label><br/>
	<input type="text" class="text" name="tags" value="<?php echo implode(', ', $obj_tags) ?>"/>
</div>
