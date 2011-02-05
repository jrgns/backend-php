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
		<td>Error Type <input name="params[number]" value="<?php echo $params['number'] ?>" style="width:50px;" /> </td>
		<td><input type="submit" value="Filter" />
		<input type="hidden" name="q" value="backend_error/filter/" />
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
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=id&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=id&sort[order]=DESC">DESC</a>
		</th>
		<th>
			User<br />
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=user_id&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=user_id&sort[order]=DESC">DESC</a>
		</th>
		<th>
			Error type<br />
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=number&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=number&sort[order]=DESC">DESC</a>
		</th>
		<th>
			Error<br />
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=string&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=string&sort[order]=DESC">DESC</a>
		</th>
		<th>
			Location<br />
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=file&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=file&sort[order]=DESC">DESC</a>
		</th>
		<th>
			Mode<br />
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=mode&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=mode&sort[order]=DESC">DESC</a>
		</th>
		<th>
			Query<br />
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=query&sort[order]=ASC">ASC</a> | 
			<a href="?q=backend_error/filter/<?php echo $link; ?>&sort[field]=query&sort[order]=DESC">DESC</a>
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
			<?php echo $row['occured']; ?>
		</td>
		<td>
			<?php echo $row['user_id']; ?>
		</td>
		<td>
			<?php echo $row['number']; ?>
		</td>
		<td>
			<?php echo $row['string']; ?>
		</td>
		<td>
			<?php echo $row['file']; ?>
		</td>
		<td>
			<?php echo $row['mode']; ?>
		</td>
		<td>
			<?php echo $row['query']; ?>
		</td>
		<td>
			<?php echo $row['last_occured']; ?>
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
