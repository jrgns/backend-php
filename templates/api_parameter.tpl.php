		<p class="large bottom">
			<strong><?php echo $name ?></strong> (<code><?php echo $parameter['type'] ?></code>)<br>
		</p>
		<p><?php echo $parameter['description'] ?>
			<?php if(!empty($parameter['range'])): ?>
				<br><strong>Range:</strong> <?php echo implode(', ', $parameter['range']) ?>
			<?php endif; ?>
		</p>