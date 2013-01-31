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

App::import("Vendor", "GitCake.UnifiedDiff", array("file"=>"UnifiedDiff/Diff.php"));
App::uses("RepoTypes", "GitCake.Model");

/**
 * GitCakeAppModel class.
 * Parent class to Source objects containing common methods
 *
 * @extends AppModel
 */
class GitCakeAppModel extends AppModel {

    // The type of the current repo
    public $repoType = 1;

    // The repository connector
    public $engine = null;

    /**
     * commitDetails function.
     *
     * @access protected
     * @param mixed $hash
     * @param bool $extended (default: false)
     * @return void
     */
    protected function commitDetails($hash, $extended = false) {
        $fields = array(
            'hash',
            'subject',
            'date',
            'author',
        );

        if ($extended) {
            $fields[] = 'abbv';
            $fields[] = 'body';
            $fields[] = 'notes';
            $fields[] = 'parent';
        }

        $commit = $this->engine->getCommitMetadata($hash, $fields);

        if ($extended) {
            // For now we are ignoring multiple parents
            $parents = preg_split('/\s+/', $commit['parent']);
            if (isset($parents[0]) && $parents[0] != '') {
                $commit['parent'] = $parents[0];
            } else if ($parents[0] == '') {
                $commit['parent'] = null;
            }
            $commit['changeset'] = $this->engine->getChangedFiles($hash, $commit['parent']);
        }

        return $commit;
    }

    /**
     * exists function.
     *
     * @access public
     * @param mixed $blob (default: null)
     * @return void
     */
    public function exists($blob = null) {
        return $this->engine->exists($blob);
    }

    /**
     * history function.
     *
     * @access public
     * @param mixed $branch
     * @param mixed $number
     * @param mixed $offset
     * @param string $file (default: '')
     * @return void
     */
    public function history($branch, $number, $offset, $file = '') {
        return $this->engine->revisionList($branch, $number, $offset, $file);
    }

    /**
     * getBranches function.
     *
     * @access public
     * @return void
     */
    public function getBranches() {
        return $this->engine->getBranches();
    }

    /**
     * getType function.
     *
     * @access public
     * @return void
     */
    public function getType() {
        return $this->engine->getType();
    }

    /**
     * open function.
     *
     * @access public
     * @param mixed $repoType
     * @param mixed $location (default: null)
     * @return void
     */
    public function open($repoType, $location = null) {
        $this->repoType = $repoType;

        if ($this->repoType == RepoTypes::Git) {
            App::uses('SourceGit', 'GitCake.Model');
            $this->engine = new SourceGit();
        } else if ($this->repoType == RepoTypes::Subversion) {
            App::uses('SourceSubversion', 'GitCake.Model');
            $this->engine = new SourceSubversion();
        } else {
            throw new Exception("Repository Type Unknown");
        }

        $this->engine->open($location);
    }

}
