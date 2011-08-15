<div id="tags_container">
	<label id="tags_label" for="tags">Tags:</label><br/>
	<input type="text" class="text" id="tags" name="tags" value="<?php if ($tags): foreach($tags as $tag) echo $tag . ', '; endif; ?>">
</div>
