<?php
/**
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     GitCake Development Team 2012
 * @link          http://github.com/pwhittlesea/gitcake
 * @package       GitCake.Model
 * @since         GitCake v 1.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('SourceControl', 'GitCake.Model');

/**
 * SourceSubversion class.
 * Connector for Subversion
 *
 * @implements SourceControl
 */
class SourceSubversion implements SourceControl {

    public static function create($base, $mode, $shared) {
        return false;
    }
    public function exists($hash) {
        return '';
    }
    public function getBranches() {
        return '';
    }
    public function getCommitMetadata($hash, $metadata) {
        return '';
    }
    public function getChangedFiles($hash, $parent) {
        return '';
    }
    public function getDiff($hash, $parent, $file) {
        return '';
    }
    public function getDiffStats($hash, $parent, $file) {
        return '';
    }
    public function open($location) {
        return '';
    }
    public function revisionList($branch, $number, $offset, $file) {
        return '';
    }
    public function show($hash) {
        return '';
    }
    public function treeList($branch, $folderPath) {
        return '';
    }

}
