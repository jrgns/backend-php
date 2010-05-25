		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Alias:</label>
			</div>
			<span><?php echo empty($Object->array['alias']) ? '&nbsp;' : plain($Object->array['alias']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Query:</label>
			</div>
			<span><?php echo empty($Object->array['query']) ? '&nbsp;' : plain($Object->array['query']) ?></span>
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
