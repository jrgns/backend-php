		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Name:</label>
			</div>
			<span><?php echo empty($Object->array['name']) ? '&nbsp;' : plain($Object->array['name']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Value:</label>
			</div>
			<span><?php echo empty($Object->array['value']) ? '&nbsp;' : plain($Object->array['value']) ?></span>
		</div>
		<div class="">
			<div class="span-3" style="text-align: right;">
				<label>Modified:</label>
			</div>
			<span><?php echo empty($Object->array['modified']) ? '&nbsp;' : plain($Object->array['modified']) ?></span>
		</div>
