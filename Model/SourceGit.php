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
App::import("Vendor", "GitCake.Git", array("file"=>"Git/Git.php"));

/**
 * SourceGit class.
 * Connector for Git
 *
 * @implements SourceControl
 */
class SourceGit implements SourceControl {

    public $repo = null;
    public $type = RepoTypes::Git;
    private $branches;

    // This is security by obscurity for now
    private $md1 = '{@{';
    private $md2 = '}@}';
    private $mRx = '[\s\S]*';

    private $metaDataMappings = array(
        'hash'    => '%H',
        'subject' => '%s',
        'date'    => '%ci',
        'abbv'    => '%h',
        'body'    => '%b',
        'notes'   => '%N',
        'parent'  => '%P',
        'author'  => array(
            'name'  => '%cn',
            'email' => '%ce',
        ),
    );

    /**
     * constructMetadataString function.
     *
     * @access private
     * @param mixed $fields
     * @return void
     */
    private function constructMetadataString($fields) {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        $str = "";
        foreach ($fields as $field) {
            $mapping = $this->metaDataMappings[$field];
            if (is_array($mapping)) {
                foreach ($mapping as $mp) {
                    $str .= "{$this->md1}{$mp}{$this->md2}";
                }
            } else {
                $str .= "{$this->md1}{$mapping}{$this->md2}";
            }
        }
        return $str;
    }

    /**
     * constructMetadataRegex function.
     *
     * @access private
     * @param mixed $fields
     * @return void
     */
    private function constructMetadataRegex($fields) {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        $reg = "";
        foreach ($fields as $field) {
            $mapping = $this->metaDataMappings[$field];
            if (is_array($mapping)) {
                foreach ($mapping as $name => $mp) {
                    $reg .= "{$this->md1}(?P<{$name}>{$this->mRx}){$this->md2}";
                }
            } else {
                $reg .= "{$this->md1}(?P<{$field}>{$this->mRx}){$this->md2}";
            }
        }
        return "#^$reg$#";
    }

    /**
     * exec function.
     *
     * @access private
     * @param mixed $command
     * @return void
     */
    private function exec($command) {
        // debug($command);
        return trim($this->repo->run($command));
    }

    /**
     * create function.
     * Create a repo at a location
     *
     * @access public
     * @static
     * @param mixed $base (default: null) the path to use
     * @param mixed $mode (default: null) the permissions mode to set on the repository (supports chmod-style arguments e.g. g+rwX, 0750)
     * @param bool $shared (default: false) the 'shared' option as per git init - (false|true|umask|group|all|world|everybody|0xxx)
     * @return void
     */
    public static function create($base = null, $mode = null, $shared = false) {
        if ($base == null) return null;

        if(!preg_match('/^([0-9]+)|([ugoa]+[+-=][rwxX]+)$/', $mode)){
            $mode = null;
        }

        if (!file_exists($base)) {
            mkdir($base, 0777);
        }

        // Note that $shared is sanitised within the Git class
        Git::create($base, null, true, $shared);

        // Ensure the permissions are set correctly, e.g. so the git group can have write access.
        // No recursive chmod() in PHP.  Ahead fudge factor 3.
        // TODO could be replaced with a lot of extra code to recurse down
        // the directory tree, if there's a reason to (safe mode?)
        if($mode != null){
            system('chmod -R ' . escapeshellarg($mode) . ' ' . escapeshellarg($base));
        }

        return true;
    }

    /**
     * exists function.
     *
     * @access public
     * @param mixed $blob (default: null)
     * @return void
     */
    public function exists($hash = null) {
        return $this->exec("rev-parse $hash");
    }

    /**
     * getBranches function.
     *
     * @access public
     * @return void
     */
    public function getBranches() {
        return $this->branches;
    }

    /**
     * getCommitMetadata function.
     *
     * @access public
     * @param mixed $hash
     * @param mixed $metadata
     * @return void
     */
    public function getCommitMetadata($hash, $metadata) {
        $metadataString = $this->constructMetadataString($metadata);
        $metadataRegex = $this->constructMetadataRegex($metadata);

        $result = $this->exec("--no-pager show -s --format='{$metadataString}' $hash");

        preg_match($metadataRegex, $result, $result);

        $return = array();

        foreach ($metadata as $field) {
            if (is_array($this->metaDataMappings[$field])) {
                $return[$field] = array();
                foreach($this->metaDataMappings[$field] as $k => $v) {
                    $return[$field][$k] = $result[$k];
                }
            } else {
                $return[$field] = $result[$field];
            }
        }

        return $return;
    }

    /**
     * getChangedFiles function.
     *
     * @access public
     * @param mixed $hash
     * @param mixed $parent
     * @return void
     */
    public function getChangedFiles($hash, $parent) {
        if ($parent == null || $parent == '') {
            $parent = '--root';
        }
        return $this->exec("diff-tree --name-only -r $parent $hash");
    }

    /**
     * getDiff function.
     *
     * @access public
     * @param mixed $hash
     * @param mixed $parent
     * @param string $file (default: '')
     * @return void
     */
    public function getDiff($hash, $parent, $file = '') {
        if ($parent == null || $parent == '') {
            $parent = '--root';
        }
        if ($file != '') {
            $fileName = "-- $file";
        } else {
            $fileName = '';
        }
        return $this->exec("diff-tree --cc $parent $hash $fileName");
    }

    /**
     * getDiffStats function.
     *
     * @access public
     * @param mixed $hash
     * @param mixed $parent
     * @param mixed $file
     * @return void
     */
    public function getDiffStats($hash, $parent, $file) {
        if ($parent == null || $parent == '') {
            $parent = '--root';
        }
        return $this->exec("diff-tree --numstat $parent $hash -- $file");

    }

    /**
     * open function.
     *
     * @access public
     * @param mixed $location
     * @return void
     */
    public function open($location) {
        $this->repo = Git::open($location);

        foreach (explode("\n", $this->exec('branch')) as $value) {
            if (preg_match('/^\*? +(?P<name>\S+)/', $value, $matches)) {
                $this->branches[] = $matches['name'];
            }
        }

        return $this->repo;
    }

    /**
     * revisionList function.
     *
     * @access public
     * @param mixed $branch
     * @param mixed $number
     * @param mixed $offset
     * @param string $file (default: '')
     * @return void
     */
    public function revisionList($branch, $number, $offset, $file = '') {
        return $this->exec("rev-list -n $number --skip=$offset $branch -- $file");
    }

    /**
     * show function.
     *
     * @access public
     * @param mixed $hash
     * @return void
     */
    public function show($hash) {
        return $this->exec("show $hash");
    }

    /**
     * treeList function.
     *
     * @access public
     * @param mixed $branch
     * @param string $folderPath (default: '')
     * @return void
     */
    public function treeList($branch, $folderPath = '') {
        return $this->exec("ls-tree $branch -- $folderPath");
    }
}
