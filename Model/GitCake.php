<?php
/**
*
* Git model for the GitCake plugin
* Performs the hard graft of fetching Git data
*
* Licensed under The MIT License
* Redistributions of files must retain the above copyright notice.
*
* @copyright     GitCake Development Team 2012
* @link          http://github.com/pwhittlesea/gitcake
* @package       GitCake.Model
* @since         GitCake v 0.1
* @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
*/

App::import("Vendor", "Git", array("file"=>"Git/Git.php"));

class GitCake extends GitCakeAppModel {

    // Reference to our copy of the open git repo
    public $repo = null;

    /*
     * loadRepo
     * Load the repo at a location
     *
     * @param $base string the path to load
     * @return boolean true if repo is loaded
     */
    public function loadRepo($base = null) {
        if ($base == null) return null;

        $this->repo = Git::open($base);
        return true;
    }

    /*
     * createRepo
     * Create a repo at a location
     *
     * @param $base string the path to use
     * @return boolean true if repo is created
     */
    public function createRepo($base = null) {
        if ($base == null) return null;

        if (!file_exists($base)) {
            mkdir($base, 0777);
        }

        $this->repo = Git::create($base, null, true);
        return true;
    }

    /*
     * repoLoaded
     * Check that a repo has been loaded
     *
     * @return boolean true if repo is loaded
     */
    public function repoLoaded() {
        return ($this->repo) ? true : false;
    }

    /*
     * getNodeAtPath
     * Return the details of the current node
     *
     * @param $branch string branch to examine
     * @param $path string the path to examine
     */
    public function getNodeAtPath($branch = 'master', $path = '') {
        if (!$this->repoLoaded()) return null;

        // If we are looking at the root of the project
        if ($path == '') {
            return array(
                'type' => 'tree',
                'hash' => $branch,
            );
        }

        $files = $this->repo->run("ls-tree $branch $path");
        $nodes = explode("\n", $files);

        return $this->_proccessNode($nodes[0]);
    }

    /*
     * _lsFolder
     * Return the contents of a tree
     *
     * @param $hash string the node to look up
     */
    public function lsFolder($hash) {
        if (!$this->repoLoaded()) return null;

        $files = $this->repo->run('ls-tree ' . $hash);
        $nodes = explode("\n", $files);

        unset($nodes[sizeof($nodes)-1]);

        foreach ( $nodes as $node ) {
            $return[] = $this->_proccessNode($node);
        }
        return $return;
    }

    /*
     * _lsFile
     * Return the contents of a blob
     *
     * @param $hash blob to look up
     */
    public function lsFile($hash) {
        if (!$this->repoLoaded()) return null;

        return $this->repo->run('show ' . $hash);
    }

    /*
     * _proccessNode
     * Return the details for the node in a linked list
     * Essentially converts git row output to array
     *
     * @param $node array the node details
     */
    private function _proccessNode($node) {
        $node = preg_split('/\s+/', $node);

        if ( !isset($node[0]) ||
             !isset($node[1]) ||
             !isset($node[2]) ||
             !isset($node[3]) ) {
            return null;
        }
        return array(
            'permissions' => $node[0],
            'type'        => $node[1],
            'hash'        => $node[2],
            'name'        => $node[3],
        );
    }

}
