		<div class="span-11">	
			<table style="float: left;">
				<tr>
					<th>
						Username:
					</th>
					<td>
						<?php echo empty($Object->array['username']) ? '&nbsp;' : plain($Object->array['username']) ?>
					</td>
				</tr>
				<tr>
					<th>
						Name:
					</th>
					<td>
						<?php echo plain(trim($Object->array['name'] . ' ' . $Object->array['surname'])) ?>
					</td>
				</tr>
				<tr>
					<th>
						Website:
					</th>
					<td>
						<?php echo empty($Object->array['website']) ? 'None' : plain($Object->array['website']) ?>
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: center;">
						<?php echo empty($Object->array['bio']) ? '&nbsp;' : simple($Object->array['bio']) ?>
					</tr>
				</tr>
			</table>
		</div>
		<div id="gravatar_div" class="span-4 last">
			<a href="http://en.gravatar.com/site/check/<?php echo $Object->array['email'] ?>" target="_blank">
				<img src="<?php echo BackendUser::getGravatar($Object->array['email']) ?>" alt="Gravatar" />
			</a>
		</div>
		<div class="clear">&nbsp;</div>
