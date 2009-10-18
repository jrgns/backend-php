		<?php if ($content_type = Controller::$view->mime_type): ?>
			<?php if (!empty(Controller::$view->charset)) { $content_type .= ';charset=' . Controller::$view->charset; } ?>
			 <meta http-equiv="Content-Type" content="<?php echo $content_type ?>">
		<?php endif; ?>
		<?php if ($author = Backend::getConfig('application.author')): ?>
			<meta name="author" content="<?php echo $author ?>">
		<?php endif; ?>
		<?php if ($description = Backend::getConfig('application.description')): ?>
			<meta name="description" content="<?php echo $description ?>">
		<?php endif; ?>
		<meta name="generator" content="backend-php.net">
		
