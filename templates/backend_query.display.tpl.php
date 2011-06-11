		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Alias:</label>
			</div>
			<span><?php echo empty($db_object->array['alias']) ? '&nbsp;' : plain($db_object->array['alias']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Query:</label>
			</div>
			<span><?php echo empty($db_object->array['query']) ? '&nbsp;' : plain($db_object->array['query']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Active:</label>
			</div>
			<span><?php echo empty($db_object->array['active']) ? '&nbsp;' : plain($db_object->array['active']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Modified:</label>
			</div>
			<span><?php echo empty($db_object->array['modified']) ? '&nbsp;' : plain($db_object->array['modified']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Added:</label>
			</div>
			<span><?php echo empty($db_object->array['added']) ? '&nbsp;' : plain($db_object->array['added']) ?></span>
		</div>
