<?php
/**
 * SourceControl abstract class.
 *
 * Ensures that all source control classes
 * will be able to be probed in a standard way.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     GitCake Development Team 2012
 * @link          http://github.com/pwhittlesea/gitcake
 * @package       GitCake.Model
 * @since         GitCake v 1.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

abstract class SourceControl {
	abstract public static function create($base, $mode, $shared);

	abstract public function exists($hash);
	abstract public function getBranches();
	abstract public function getCommitMetadata($hash, $metadata);
	abstract public function getChangedFiles($hash, $parent);
	abstract public function getDiff($hash, $parent, $file);
	abstract public function getPathDetails($branch, $path);
	abstract public function getType();
	abstract public function open($location);
	abstract public function revisionList($branch, $number, $offset, $file);
	abstract public function show($hash);
	abstract public function treeList($branch, $folderPath);
}
