<?php
class CommentedController extends TableCtl {
	public function html_display($result) {
		$result = parent::html_display($result);
		if (Value::get(get_class($this) . '_commented', true) && Component::isActive('Comment')) {
			if ($result instanceof DBObject) {
				$comments = Comment::getComments($result->getMeta('table'), $result->getMeta('id'));
				Backend::addContent(Render::renderFile('comments.tpl.php', array('comment_list' => $comments)));
				if (Permission::check('create', 'comment')) {
					$values = array('foreign_table' => $result->getMeta('table'), 'foreign_id' => $result->getMeta('id'));
					Backend::addContent(Render::renderFile('comment.add.tpl.php', $values));
				}
			}
		}
		return $result;
	}
}
