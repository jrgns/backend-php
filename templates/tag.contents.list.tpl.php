<?php if (!empty($Object)) {
	$list         = $Object->array['list'];
	$list_count   = $Object->array['list_count'];
	$area         = empty($area)        ? Controller::$area          : $area;
	$action       = empty($action)      ? Controller::$action . '/' . Controller::$parameters[0] : $action;
	$list_start   = empty($list_start)  ? Controller::$parameters[1] : $list_start;
	$list_length  = empty($list_length) ? Controller::$parameters[2] : $list_length;
	$pages        = ceil($list_count / $list_length);
	$current_page = floor($list_start / $list_length) + 1;
	//var_dump(count($list), $list_count, $area, $action, $list_start, $list_length, $pages, $current_page);
}
if (!empty($Object->array['list']) && is_array($Object->array['list'])):
	foreach($Object->array['list'] as $item):
		$tags = Tag::getTags('contents', $item['id']);
		$link = '?q=content/' . $item['name'];
	?>
		<h3>
			<a href="<?php echo $link ?>"><?php echo plain($item['title']) ?></a>
		</h3>
		<p>
			<?php echo Content::createPreview($item['body'], false) ?>
		</p>
		<div class="clear">
			<div class="span-3">
				<a href="<?php echo $link ?>">Read more</a>
			</div>
			<div class="span-6">
				<?php if ($tags): foreach($tags as $tag): ?>
					<a  href="?q=tag/<?php echo $tag['id'] ?>"><?php echo $tag['name'] ?></a>
				<?php endforeach; endif; ?>
			</div>
			<div style="text-align: right;" class="small quiet">
				Last updated <?php echo time_elapsed($item['modified']) ?>
			</div>
		</div>
		<hr>
	<?php endforeach; ?>
	{tpl:list_paging.tpl.php}
<?php endif; ?>