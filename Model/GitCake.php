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
* @since         GitCake v 1.1
* @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
*/

App::import("Vendor", "GitCake.Git", array("file"=>"Git/Git.php"));
App::import("Vendor", "GitCake.UnifiedDiff", array("file"=>"UnifiedDiff/Diff.php"));

class GitCake extends GitCakeAppModel {

    // Reference to our copy of the open git repo
    public $repo = null;

    // We dont need no table
    public $useTable = null;

    /*
     * loadRepo
     * Load the repo at a location
     *
     * @param $base string the path to load
     * @return boolean true if repo is loaded
     */
    public function loadRepo($base = null) {
        if ($base == null) return null;

        try {
            $this->repo = Git::open($base);
        } catch (Exception $e) {
            return null;
        }
        return $this->repo;
    }

    /*
     * createRepo
     * Create a repo at a location
     *
     * @param $base string the path to use
     * @param $mode string the permissions mode to set on the repository (supports chmod-style arguments e.g. g+rwX, 0750)
     * @param $shared string the 'shared' option as per git init - (false|true|umask|group|all|world|everybody|0xxx)
     * @return boolean true if repo is created
     */
    public function createRepo($base = null, $mode = null, $shared = false) {
        if ($base == null) return null;

        if(!preg_match('/^([0-9]+)|([ugoa]+[+-=][rwxX]+)$/', $mode)){
            $mode = null;
        }

        if (!file_exists($base)) {
            mkdir($base, 0777);
        }

        // Note that $shared is sanitised within the Git class
        $this->repo = Git::create($base, null, true, $shared);

        // Ensure the permissions are set correctly, e.g. so the git group can have write access.
        // Ugh.  No recursive chmod() in PHP.  Ahead fudge factor 3.
        // TODO could be replaced with a lot of extra code to recurse down
        // the directory tree, if there's a reason to (safe mode?)
        if($mode != null){
            system('chmod -R ' . escapeshellarg($mode) . ' ' . escapeshellarg($base));
        }

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
     * branch
     * Fetch repos branches
     *
     * @return array list of branches
     */
    public function branch() {
        if (!$this->repoLoaded()) return null;

        $resp = $this->repo->run('branch');
        $branches = array();

        foreach (explode("\n", $resp) as $value) {
            if (preg_match('/^\*? +(?P<name>\S+)/', $value, $matches)) {
                $branches[] = $matches['name'];
            }
        }

        return $branches;
    }

    /*
     * hasTree
     * Check if repo has a tree
     *
     * @param $hash string the tree to look up
     * @return boolean true if tree exists
     */
    public function hasTree($hash) {
        if (!$this->repoLoaded()) return null;

        try {
            $this->repo->run("ls-tree $hash");
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /*
     * tree
     * Return the contents of a tree
     *
     * @param $hash string the node to look up
     * @param $path string the path to examine
     */
    public function tree($hash = 'master', $folderPath = '') {
        if (!$this->repoLoaded()) return null;

        // Check the last character isnt a / otherwise git will return the contents of the folder
        if ($folderPath != '' && $folderPath[strlen($folderPath)-1] == '/') {
            $folderPath = substr($folderPath, 0, strlen($folderPath)-1);
        }

        // Lets start from the base of the repo
        if ($folderPath == '') {
            $folderPath = '.';
        }

        if ($folderPath == '.') {
            $current = "0 tree $hash $hash";
        } else {
            $current = $this->repo->run("ls-tree $hash -- $folderPath");
        }

        if (empty($current)) {
            return array('type' => 'invalid');
        }

        // Fetch the details of the path we are looking at and check it parses
        if (!preg_match('/^(?P<permissions>[0-9]+) (?P<type>[a-z]+) (?P<hash>[0-9a-z]+)\s(?P<name>.+)/', $current, $current)) {
            return array('type' => 'invalid');
        }

        // Init standard return array
        $return = array(
            'type' => $current['type'],
            'content' => '',
            'path' => $folderPath
        );

        // Handle blob case (I know its a tree function, but we might as well)
        if ($current['type'] == 'blob') {
            $return['content'] = $this->show($current['hash']);
        }

        // Handle tree case
        if ($current['type'] == 'tree') {
            $folder = explode("\n", trim($this->repo->run("ls-tree $hash $folderPath/")));

            // Iterate through tree contents
            foreach ($folder as $a => $file) {
                if (preg_match('/^(?P<permissions>[0-9]+) (?P<type>[a-z]+) (?P<hash>[0-9a-z]+)\s(?P<name>.+)/',$file,$matches)) {
                    $folder[$a] =  array(
                        'permissions' => $matches['permissions'],
                        'type' => $matches['type'],
                        'hash' => $matches['hash'],
                        'name' => str_replace("$folderPath/", "", $matches['name'])
                    );
                }
            }

            $return['content'] = $folder;
        }

        return $return;
    }

    /*
     * show
     * Return the details of a blob
     *
     * @param $hash blob to look up
     */
    public function show($hash) {
        if (!$this->repoLoaded()) return null;

        return $this->repo->run('show ' . $hash);
    }

    /*
     * log
     * Return a list of commits
     *
     * @param $branch string the branch to look up
     * @param $limit int a restriction on the number of commits to return
     * @param $offset int an offest for the number restriction
     * @param $filepath string files can be specified to limit log return
     */
    public function log($branch = 'master', $limit = 10, $offset = 0, $filepath = '') {
        if (!$this->repoLoaded()) return null;

        $commits = trim($this->repo->run("rev-list --all -n $limit --skip=$offset $branch -- $filepath"));
        $commits = explode("\n", $commits);

        foreach ($commits as $a => $commit) {
            $commits[$a] = $this->showCommit($commit, false);
        }
        return $commits;
    }

    /*
     * showCommit
     * Return a list of commits
     *
     * @param $hash string the hash to look up
     * @param $diff boolean do we want a diff?
     */
    public function showCommit($hash, $diff = true) {
        if (!$this->repoLoaded()) return null;

        $result['Commit'] = $this->_commitMetadata($hash);
        if ($diff) {
            $result['Commit']['diff'] = $this->diff($hash);
        }

        return $result;
    }

    /*
     * size
     * Return a list sizes returned by count-objects
     *
     */
    public function size() {
        if (!$this->repoLoaded()) return null;

        $stats = explode("\n", trim($this->repo->run('count-objects -v')));

        foreach ( $stats as $a => $stat ) {
            $temp = preg_split('/:\s+/', $stat);
            unset($stats[$a]);
            $stats[$temp[0]] = $temp[1];
        }
        return $stats;
    }

    /*
     * diff
     * Return the diff for all files altered in a hash
     *
     * @param $hash string commit to look up
     * @param $parent string the parent to compare against
     */
    private function diff($hash, $parent = null) {
        if (!$this->repoLoaded()) return null;

        // If no hash to compare against was provided then use the direct parent
        if ($parent == null) {
            $parent = $this->_commitParent($hash);

            // For now we are ignoring multiple parents
            if (isset($parent[0]) && $parent[0] != '') {
                $parent = $parent[0];
            } else {
                // Finding the parent failed. Calculate, is this the first commit?
                if ($parent[0] == '') {
                    $parent = '--root';
                } else {
                    $parent = 'HEAD';
                }
            }
        }

        // Obtain the diff
        $output = $this->repo->run("diff-tree --cc $parent $hash");

        $output = Diff::parse($output);

        foreach ($output as $file => $array) {
            // Lets request the number stats for the file
            $numstat = trim($this->repo->run("diff-tree --numstat $parent $hash -- $file"));

            $line = preg_split('/\s+/', $numstat);

            // Sometimes the hash is returned as the first element
            $off = (isset($line[3])) ? 1 : 0;

            // Gather additions and subtractions stats
            $output[$file]['less'] = $line[$off + 1];
            $output[$file]['more'] = $line[$off + 0];
        }

        return $output;
    }

    /**
     * blame
     *
     * @param $filepath string the path to blame
     */
    public function blame($branch, $filepath){
        if (!$this->repoLoaded()) return null;

        $resp = $this->repo->run("blame -l $branch -- $filepath");

        $blame = array();
        foreach ($out as $line) {
            if (preg_match('/^([0-9a-z^]+) \(([^ \)]+) +([^\)]+) +([\d]+)\) (.*)$/',$line,$matches)) {
                $blame[] = array('hash' => $matches[1],
                                 'commiter' => $matches[2],
                                 'date' => $matches[3],
                                 'line' => $matches[4],
                                 'code' => $matches[5]);
            }
        }
        return $blame;
    }

    /**
     * submodules
     * return a list of the submodules for the project
     *
     * @param $branch string the branch to extract the submodules from
     */
    public function submodules($branch) {
        $resp = $this->tree($branch, '.gitmodules');

        $sub = array();

        if (!isset($resp['content'])) {
            return $sub;
        }
        preg_match_all('#\[submodule\s+[\"\'](?P<name>\S*)[\"\']\]\s+path\s=\s(?P<path>\S+)\s+url\s=\s(?P<remote>\S+)#', $resp['content'], $matches);

        // Just incase there are no submodules
        if (empty($matches)) {
            return $sub;
        }

        foreach ($matches['name'] as $i => $name) {
            $sub[$matches['path'][$i]] = array('name'=>$matches['name'][$i], 'remote'=>$matches['remote'][$i]);
        }
        return $sub;
    }

    /**
     * exec
     * For those times when the built in functions arnt enough
     *
     * @param $command string the command to run
     */
    public function exec($command) {
        if (!$this->repoLoaded()) return null;

        return $this->repo->run($command);
    }

    /*
     * _commitParent
     * Return the immediate parent of a commit
     *
     * @param $hash string commit to look up
     */
    private function _commitParent($hash) {
        if (!$this->repoLoaded()) return null;

        return preg_split('/\s+/', trim($this->repo->run("--no-pager show -s --format=%P $hash")));
    }

    /*
     * _commitMetadata
     * Return the details for the commit in a hash
     *
     * @param $hash commit to look up
     */
    private function _commitMetadata($hash) {
        if (!$this->repoLoaded()) return null;

        // Magical Git to JSON format
        $pretty = '{"abbv":"%h","hash":"%H","subject":"%s","body":"%b","notes":"%N","date":"%ci","author":{"name":"%cn","email":"%ce"}}';
        $almostpretty = '{"abbv":"%h","hash":"%H","date":"%ci"}';

        $commit = json_decode($this->repo->run("--no-pager show -s --format='".$pretty."' ".$hash), true);
        if (!$commit) {
            // Our super fast JSON solution failed because someone used a " or a ', plan b!
            $commit = json_decode($this->repo->run("--no-pager show -s --format='".$almostpretty."' ".$hash), true);
            $commit['subject'] = trim($this->repo->run("--no-pager show -s --format='%s' ".$hash));
            $commit['body'] = trim($this->repo->run("--no-pager show -s --format='%b' ".$hash));
            $commit['notes'] = trim($this->repo->run("--no-pager show -s --format='%N' ".$hash));
            $commit['author']['name'] = trim($this->repo->run("--no-pager show -s --format='%cn' ".$hash));
            $commit['author']['email'] = trim($this->repo->run("--no-pager show -s --format='%ce' ".$hash));
        }
        $commit['parent'] = preg_split('/\s+/', trim($this->repo->run("--no-pager show -s --format='%P' $hash")));

        return $commit;
    }

}
