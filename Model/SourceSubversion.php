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

    public $repo = null;
    public $type = RepoTypes::Subversion;
    private $branches = array();

    /**
     * exec function.
     *
     * @access private
     * @param mixed $command
     * @return void
     */
    private function exec($command, $xml = false) {
        $return = array('output' => '', 'return' => 0);

        // debug(escapeshellcmd("svn $command"));
        $return['out'] = exec(escapeshellcmd("svn $command"), $return['output'], $return['return']);

        if ($return['return'] > 0) {
            throw new Exception('SVN call error');
        }

        $return['output'] = implode("\n", $return['output']);

        if ($xml) {
            return $this->parseXML($return['output']);
        }
        return $return;
    }

    /**
     * parseXML function.
     *
     * @access private
     * @param mixed $dom
     * @return void
     */
    private function parseXML($dom) {
        return simplexml_load_string($dom);
    }

    /**
     * create function.
     *
     * @access public
     * @static
     * @param mixed $base
     * @param mixed $mode
     * @param mixed $shared
     * @return void
     */
    public static function create($base, $mode, $shared) {
        if (file_exists($base)) {
            // Lets talk about logging as some point
            return false;
        }

        // Create the repo
        $return = 0;
        $output = array();
        exec("svnadmin create $base", $output, $return);

        if ($return != 0) {
            // Lets talk about logging as some point
            return false;
        }

        // Provide the users with a good repo layout
        $return = 0;
        $output = array();
        exec("svn mkdir file:///$base/trunk file:///$base/tags file:///$base/branches -m'Initial Commit'", $output, $return);

        if ($return != 0) {
            // Lets talk about logging as some point
            return false;
        }

        // Copy any hooks in here

        return true;
    }

    /**
     * exists function.
     *
     * @access public
     * @param mixed $branch
     * @return void
     */
    public function exists($branch) {
        if (ucwords($branch) == 'HEAD') return true;

        $out = $this->exec(sprintf('log %s@%s', escapeshellarg($this->repo), escapeshellarg($branch)));

        return ($out['return'] == 0);
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
     * @param mixed $branch
     * @param mixed $metadata
     * @return void
     */
    public function getCommitMetadata($branch, $metadata) {
        if ($branch != 'HEAD' and !preg_match('/^\d+$/', $branch)) {
            throw new NotFoundException("Revision type must be HEAD or a number");
        }

        $xml = $this->exec(sprintf('log --xml --limit 1 %s@%s', escapeshellarg($this->repo), escapeshellarg($branch)), true);
        $commit = array();

        $commit['date']    = (string) $xml->logentry->date;
        $commit['subject'] = (string) $xml->logentry->msg;
        $commit['hash']    = (string) $xml->logentry['revision'];
        $commit['author']  = array(
            'name'  => (string) $xml->logentry->author,
            'email' => null
        );

        // For Git compatability
        $commit['body']   = '';
        $commit['abbv']   = $branch;
        $commit['parent'] = ($commit['hash'] < 1) ? null : ($commit['hash'] - 1);

        return $commit;
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
        $result = $this->exec(sprintf('diff -r %s:%s %s --summarize', escapeshellarg($parent), escapeshellarg($hash), escapeshellarg($this->repo)));

        $changeset = explode("\n", $result['output']);
        foreach ($changeset as $a => $change) {
            preg_match('#^\w\W+(?P<file>.+)$#', $change, $result);
            $changeset[$a] = str_replace($this->repo, '', $result['file']);
        }
        return $changeset;
    }

    /**
     * getDiff function.
     *
     * @access public
     * @param mixed $hash
     * @param mixed $parent
     * @param mixed $file
     * @return void
     */
    public function getDiff($hash, $parent, $file) {
        // TODO: File is currently ignored, meaning that the full diff is returned for each file.
        // This is because SVN is stupid and just returns an error if you compare the revisions where
        // a file was created.
        $result = $this->exec(sprintf('diff -r %s:%s %s', escapeshellarg($parent), escapeshellarg($hash), escapeshellarg($this->repo)));
        return $result['output'];
    }

    /**
     * getPathDetails function.
     *
     * @access public
     * @param mixed $branch
     * @param mixed $path
     * @return void
     */
    public function getPathDetails($branch, $path) {
        // Check the last character isnt a / otherwise git will return the contents of the folder
        if ($path != '' && $path[strlen($path)-1] == '/') {
            $path = substr($path, 0, strlen($path)-1);
        }

        if ($path == '.' || $path == '') {
            return array(
                'hash' => $branch,
                'name' => $path,
                'type' => 'tree',
                'permissions' => '0'
            );
        }

        $xml = $this->exec(sprintf('info --xml %s@%s', escapeshellarg($this->repo.$path), escapeshellarg($branch)), true);

        $details = array(
            'hash' => "$path@".(string) $xml->entry['revision'],
            'name' => $path,
            'type' => (string) $xml->entry['kind'],
            'permissions' => ''
        );

        if ($details['type'] == 'dir') {
            $details['type'] = 'tree';
            $details['permissions'] = '100664';
        } else if ($details['type'] == 'file') {
            $details['type'] = 'blob';
            $details['permissions'] = '100400';
        }

        return $details;
    }

    /**
     * getType function.
     *
     * @access public
     * @return void
     */
    public function getType() {
        return $this->type;
    }

    /**
     * open function.
     *
     * @access public
     * @param mixed $location
     * @return void
     */
    public function open($location) {
        $this->repo = "file://$location";

        $this->branches[] = 'HEAD';

        return true;
    }

    /**
     * revisionList function.
     *
     * @access public
     * @param mixed $branch
     * @param mixed $number
     * @param mixed $offset
     * @param mixed $file
     * @return void
     */
    public function revisionList($branch, $number, $offset, $file) {
        $xml = $this->exec(sprintf('log --xml -v --limit %s %s@%s', escapeshellarg(($number+$offset)), escapeshellarg($this->repo.$file), escapeshellarg($branch)), true);

        $revisions = array();
        foreach ($xml->logentry as $entry) {
            $revisions[] = (string) $entry['revision'];
        }

        return $revisions;
    }

    /**
     * show function.
     *
     * @access public
     * @param mixed $hash
     * @return void
     */
    public function show($hash) {
        $hash = explode('@', $hash);
        $result = $this->exec(sprintf('cat %s@%s', escapeshellarg($this->repo.$hash[0]), escapeshellarg($hash[1])));

        return $result['output'];
    }

    /**
     * treeList function.
     *
     * @access public
     * @param mixed $branch
     * @param mixed $folderPath
     * @return void
     */
    public function treeList($branch, $folderPath) {
        $xml = $this->exec(sprintf('list --xml %s@%s', escapeshellarg($this->repo.$folderPath), escapeshellarg($branch)), true);

        $result = array();

        // Make the SVN output similar to the git output
        foreach ($xml->list->entry as $entry) {
            if ((string) $entry['kind'] == 'dir') {
                $_type = 'tree';
                $_permissions = '100644';
            } else {
                $_type = 'blob';
                $_permissions = '100400';
            }
            $result[] = array(
                'permissions' => $_permissions,
                'type'        => $_type,
                'hash'        => $branch,
                'path'        => $folderPath.((string) $entry->name),
                'name'        => (string) $entry->name
            );
        }

        return $result;
    }

}
