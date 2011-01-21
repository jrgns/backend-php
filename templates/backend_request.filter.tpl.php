<h3>Backend errors</h3>
<?php
	$link = '';
	if (isset($params) && is_array($params) && count($params))
	{
		foreach ($params as $key => $param)
		{
			$link .= "&params[$key]=$param";
		}
	}
	
?>
<form method="get">
<table>
	<tr>
		<td>User Id <input name="params[userId]" value="<?php echo $params['userId'] ?>" style="width:50px;" /> </td>
		<td>Query <input name="params[query]" value="<?php echo $params['query'] ?>" style="width:50px;" /> </td>
		<td>User Agent <input name="params[user_agent]" value="<?php echo $params['user_agent'] ?>" style="width:50px;" /> </td>
		<td><input type="submit" value="Filter" />
		<input type="hidden" name="q" value="backend_request/filter/" />
		<input type="hidden" name="sort[field]" value="<?php echo $sort['field'] ?>" />
		<input type="hidden" name="sort[order]" value="<?php echo $sort['order'] ?>" /></td>
	</tr>
</table>
</form>
<?php
if (is_array($data) && count($data))
{
	?>
<table>
	<thead>
	<tr>
		<th>
			Count<br />
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=id&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=id&sort[order]=DESC">DESC</a>
		</th>
		<th>
			User<br />
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=user_id&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=user_id&sort[order]=DESC">DESC</a>
		</th>
		<th>
			IP Address<br />
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=ip&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=ip&sort[order]=DESC">DESC</a>
		</th>
		<th>
			User Agent<br />
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=user_agent&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=user_agent&sort[order]=DESC">DESC</a>
		</th>
		<th>
			Mode<br />
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=mode&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=mode&sort[order]=DESC">DESC</a>
		</th>
		<th>
			Request<br />
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=request&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=request&sort[order]=DESC">DESC</a>
		</th>
		<th>
			Query<br />
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=query&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_request/filter/<?php echo $link; ?>&sort[field]=query&sort[order]=DESC">DESC</a>
		</th>
		<th>
			Last recorded
		</th>
	</tr>
	</thead>
	<tbody>
<?php	
	$odd = false;
	$framework = '';
	$process = '';
	$currentstep = '';
	foreach($data as $id => $row)
	{
		$odd = !$odd;
	?>
	<tr class="<?php echo $odd ? '' : 'even' ?>">
		<td>
			<?php echo $row['id']; ?>
		</td>
		<td>
			<?php echo $row['user_id']; ?>
		</td>
		<td>
			<?php echo $row['ip']; ?>
		</td>
		<td>
			<?php echo $row['user_agent']; ?>
		</td>
		<td>
			<?php echo $row['mode']; ?>
		</td>
		<td>
			<?php echo $row['request']; ?>
		</td>
		<td>
			<?php echo $row['query']; ?>
		</td>
		<td>
			<?php echo $row['added']; ?>
		</td>
	</tr>
		<?php
		//unset($stepDetails[$id]);
	}
?>
	</tbody>
	<tfoot>
	<?php
		if (isset($sort) && is_array($sort) && count($sort))
		{
			foreach ($sort as $key => $param)
			{
				$link .= "&sort[$key]=$param";
			}
		}
	?>
		<tr><td colspan="9">
			<?php if ($pager['itemTotal'] > $pager['itemCount']): ?>
		<? if ($pager['currentPage'] > 1): ?><a href="?q=backend_error/filter/<?php echo ($pager['currentPage'] - 1) . $link; ?>"><?php endif; ?>
			Previous</a>
		<? if ($pager['currentPage'] > 1): ?><?php endif; ?>
			Page <?php echo $pager['currentPage'] ?> of <?php echo $pager['totalPages'] ?>
		<? if ($pager['currentPage'] < $pager['totalPages']): ?><a href="?q=backend_error/filter/<?php echo ($pager['currentPage'] + 1) . $link; ?>"><?php endif; ?>
			Next</a>
		<? if ($pager['currentPage'] < $pager['totalPages']): ?><?php endif; ?>
		<?php else: ?>
			Page 1 of 1
		<?php endif; ?>
		</td></tr>
	</tfoot>
</table>
<?php
} else
{
	echo "No data for parameters given";
}
?>
