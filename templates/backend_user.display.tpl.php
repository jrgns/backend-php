		<div class="span-11">	
			<table style="float: left;">
				<tr>
					<th>
						Username:
					</th>
					<td>
						<?php echo empty($db_object->array['username']) ? '&nbsp;' : plain($db_object->array['username']) ?>
					</td>
				</tr>
				<tr>
					<th>
						Name:
					</th>
					<td>
						<?php echo plain(trim($db_object->array['name'] . ' ' . $db_object->array['surname'])) ?>
					</td>
				</tr>
				<tr>
					<th>
						Website:
					</th>
					<td>
						<?php echo empty($db_object->array['website']) ? 'None' : plain($db_object->array['website']) ?>
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: center;">
						<?php echo empty($db_object->array['bio']) ? '&nbsp;' : simple($db_object->array['bio']) ?>
					</tr>
				</tr>
			</table>
		</div>
		<div id="gravatar_div" class="span-4 last">
			<a href="http://en.gravatar.com/site/check/<?php echo $db_object->array['email'] ?>" target="_blank">
				<img src="<?php echo BackendUser::getGravatar($db_object->array['email']) ?>" alt="Gravatar" />
			</a>
		</div>
		<div class="clear">&nbsp;</div>
