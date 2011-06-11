		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Name:</label>
			</div>
			<span><?php echo empty($db_object->array['name']) ? '&nbsp;' : plain($db_object->array['name']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Filename:</label>
			</div>
			<span><?php echo empty($db_object->array['filename']) ? '&nbsp;' : plain($db_object->array['filename']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Options:</label>
			</div>
			<span><?php echo empty($db_object->array['options']) ? '&nbsp;' : plain($db_object->array['options']) ?></span>
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
		<p>
			<a href="?q=gate_manager/permissions/<?php echo class_for_url($db_object->array['name']) ?>">Check Permissions</a>
		</p>
