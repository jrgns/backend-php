		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Name:</label>
			</div>
			<span><?php echo empty($db_object->array['name']) ? '&nbsp;' : plain($db_object->array['name']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Value:</label>
			</div>
			<span><?php echo empty($db_object->array['value']) ? '&nbsp;' : plain($db_object->array['value']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Modified:</label>
			</div>
			<span><?php echo empty($db_object->array['modified']) ? '&nbsp;' : plain($db_object->array['modified']) ?></span>
		</div>
