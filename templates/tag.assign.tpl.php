			<div id="obj_tags_container">
				<label id="obj_tags_label" for="obj_tags">Tags:</label><br/>
				<input type="text" class="text" id="obj_tags" name="obj[tags]" value="<?php if ($tags) foreach($tags as $tag) echo $tag['name'] . ', ';  ?>">
			</div>

