<?php
/**
 * The class file for BackendFile
 */
/**
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package Controllers
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */

/**
 * This is the controller for the table backend_files.
 *
 * This controller should only be run on sites meant as automatic update repositories for the backend framework.
 * @todo Add file type (Core, Standard, Third Party, Experimental
 * @todo Add location? If we're to "host" remote files as well
 */
class BackendFile extends TableCtl {
	/**
	 * @todo We need to find a way to tag files with it's revision
	 * @todo update overrides TableCtl::update
	 */
	function action_update() {
		$location = Backend::getConfig('application.file_provider.location', false);
		if (!$location) {
			Backend::addError('No File Provider Location');
			return false;
		}
		$files = Component::fromFolder();
		foreach($files as $file) {
			$rev_id = self::getRevisionFromFile($file);
			$result = curl_request($location, array('q' => 'backend_file/read/' . urlencode($file), 'mode' => 'json'));
			if ($result && $result = @json_decode($result, true)) {
				$info = $result['result'];
				if ($info['version'] != $rev_id) {
					//File is out old
				}
			} else {
				Backend::addError('Could not check status for ' . $file);
			}
		}
	}

	public static function getRevisionFromFile($file) {
		return 10;
	}

	public static function addRevisionToFile($file) {
		$rev_id = bzr_get_file_revision($file);

	}

	function action_read($file, $mode = 'array') {
		$file = urldecode($file);
		return parent::action_read($file, $mode);
	}

	function action_check() {
		if (!Backend::getConfig('application.file_provider', false)) {
			return false;
		}
		$files = Component::fromFolder();
		$count = 0;
		foreach($files as $file) {
			if ($rev_id = bzr_get_file_revision(BACKEND_FOLDER . '/' . $file)) {
				$name    = preg_replace('/\.obj\.php$/', '', basename($file));
				$be_file = BackendFile::retrieve($file, 'dbobject');
				if ($be_file->array) {
					if ($rev_id != $be_file->array['version']) {
						if ($be_file->update(array('version' => $rev_id))) {
							$count++;
							Backend::addSuccess($name . ' updated to ' . $rev_id);
						} else {
							Backend::addError('Could not update version for ' . $name);
						}
					}
				} else {
					$data = array(
						'name'    => $name,
						'file'    => $file,
						'version' => $rev_id,
						'active'  => 1,
					);
					if ($be_file->create($data)) {
						$count++;
						Backend::addSuccess($name . ' added');
					} else {
						Backend::addError('Could not add info for ' . $name);
					}
				}
			}
		}
		return $count;
	}
}
