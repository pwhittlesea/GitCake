<?php
/**
 * SourceControl interface.
 *
 * Ensures that all source control interfaces
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

interface SourceControl {
    public static function create($base, $mode, $shared);

    public function exists($hash);
    public function getBranches();
    public function getCommitMetadata($hash, $metadata);
    public function getChangedFiles($hash, $parent);
    public function getDiff($hash, $parent, $file);
    public function getPathDetails($branch, $path);
    public function getType();
    public function open($location);
    public function revisionList($branch, $number, $offset, $file);
    public function show($hash);
    public function treeList($branch, $folderPath);
}
