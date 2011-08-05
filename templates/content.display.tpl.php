<?php echo CodeHighlight::execute($db_object->object->body); ?>
<?php if (!empty($tags)): ?>
    {tpl:tags.tpl.php}
<?php endif; ?>
