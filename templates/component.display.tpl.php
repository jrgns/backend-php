		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Name:</label>
			</div>
			<span><?php echo empty($Object->array['name']) ? '&nbsp;' : plain($Object->array['name']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Filename:</label>
			</div>
			<span><?php echo empty($Object->array['filename']) ? '&nbsp;' : plain($Object->array['filename']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Options:</label>
			</div>
			<span><?php echo empty($Object->array['options']) ? '&nbsp;' : plain($Object->array['options']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Active:</label>
			</div>
			<span><?php echo empty($Object->array['active']) ? '&nbsp;' : plain($Object->array['active']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Modified:</label>
			</div>
			<span><?php echo empty($Object->array['modified']) ? '&nbsp;' : plain($Object->array['modified']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Added:</label>
			</div>
			<span><?php echo empty($Object->array['added']) ? '&nbsp;' : plain($Object->array['added']) ?></span>
		</div>
		<p>
			<a href="?q=gate_manager/permissions/<?php echo class_for_url($Object->array['name']) ?>">Check Permissions</a>
		</p>