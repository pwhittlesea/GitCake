<?php
/**
 * SourceGit class.
 * Connector for Git
 *
 * @implements SourceControl
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

App::uses('SourceControl', 'GitCake.Model');
App::uses('RepoTypes', 'GitCake.Model');
App::import("Vendor", "GitCake.Git", array("file"=>"Git" . DS . "Git.php"));

class SourceGit extends SourceControl {

/**
 * ensureValidHash function.
 * Check that a hash provied is valid by the following criteria:
 *   - Is alphanumeric
 *   - Has length greated then 0
 * If the hash is not valid then throw a generic exception.
 *
 * @access public static
 * @param string $hash
 * @return boolean true if passes
 */
	public static function ensureValidHash($hash) {
		if (strlen($hash) < 1) {
			throw new Exception("The provided hash ($hash) is not valid. Reason: Hash is zero length");
		}
		if (!preg_match('/^[A-Za-z0-9]+$/', $hash)) {
			throw new Exception("The provided hash ($hash) is not valid. Reason: Hash is not alphanumeric");
		}
		return true;
	}

	public $repo = null;
	public $type = RepoTypes::GIT;
	private $branches = array();

	// This is security by obscurity for now
	private $md1 = '{@{';
	private $md2 = '}@}';
	private $mRx = '[\s\S]*';

	private $metaDataMappings = array(
		'hash' => '%H',
		'subject' => '%s',
		'date' => '%ci',
		'abbv' => '%h',
		'body' => '%b',
		'notes' => '%N',
		'parent' => '%P',
		'author' => array(
			'name' => '%cn',
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
		// debug("git $command");
		return trim($this->repo->run($command));
	}

/**
 * calculateBranches function.
 *
 * @access public (Due to test requirement TODO)
 * @param array $branches as returned from a git branch call
 * @return array of matching branches
 */
	public function calculateBranches($branches = array()) {
		$cleanedBranches = array();
		foreach ($branches as $branch) {
			if (preg_match('/^(\*\s+)?(?P<name>.+)/', $branch, $matches)) {
				$cleanedBranches[] = $matches['name'];
			}
		}
		return $cleanedBranches;
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
		// No recursive chmod() in PHP.	 Ahead fudge factor 3.
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
		SourceGit::ensureValidHash($hash);
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
		SourceGit::ensureValidHash($hash);

		if (!is_array($metadata)) {
			$metadata = array($metadata);
		}

		$metadataString = $this->constructMetadataString($metadata);
		$metadataRegex = $this->constructMetadataRegex($metadata);

		$gitResult = $this->exec("--no-pager show -s --format='{$metadataString}' $hash");

		preg_match($metadataRegex, $gitResult, $result);

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
		SourceGit::ensureValidHash($hash);
		if ($parent == null || $parent == '') {
			$parent = '--root';
		} else {
			SourceGit::ensureValidHash($parent);
		}

		$changes = $this->exec("diff-tree --name-only -r $parent $hash");

		$changes = str_replace("$hash\n", '', $changes);
		return preg_split('/[\r\n]+/', $changes);
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
		SourceGit::ensureValidHash($hash);
		$file = escapeshellarg($file);
		if ($parent == null || $parent == '') {
			$parent = '--root';
		} else {
			SourceGit::ensureValidHash($parent);
		}

		if ($file != '') {
			$fileName = "-- $file";
		} else {
			$fileName = '';
		}
		return $this->exec("diff-tree --cc $parent $hash $fileName");
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
		$branch = escapeshellarg($branch);
		$path = escapeshellarg($path);

		// Check the last character isnt a / otherwise git will return the contents of the folder
		if ($path != '' && $path[strlen($path)-1] == '/') {
			$path = substr($path, 0, strlen($path)-1);
		}

		if ($path == '.' || $path == '') {
			return array(
				'hash' => $branch,
				'name' => $branch,
				'type' => 'tree',
				'permissions' => '0'
			);
		}

		if (!preg_match('/^(?P<permissions>[0-9]+) (?P<type>[a-z]+) (?P<hash>[0-9a-zA-Z]+)\s(?P<name>.+)/', $this->exec("ls-tree -z $branch -- $path"), $details)) {
			return null;
		}
		return array(
			'hash' => $details['hash'],
			'name' => $details['name'],
			'type' => $details['type'],
			'permissions' => $details['permissions']
		);
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
		$this->repo = Git::open($location);

		$this->branches = $this->calculateBranches(explode("\n", $this->exec('branch')));

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
		$branch = escapeshellarg($branch);
		$number = escapeshellarg($number);
		$offset = escapeshellarg($offset);
		$file = escapeshellarg($file);

		return explode("\n", $this->exec("rev-list -n $number --skip=$offset $branch -- $file"));
	}

/**
 * show function.
 *
 * @access public
 * @param mixed $hash
 * @return void
 */
	public function show($hash) {
		SourceGit::ensureValidHash($hash);
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
		$branch = escapeshellarg($branch);
		$folderPath = escapeshellarg($folderPath);

		$contents = array();

		foreach (explode("\0", $this->exec("ls-tree -z $branch -- $folderPath")) as $a => $file) {
			if (preg_match('/^(?P<permissions>[0-9]+) (?P<type>[a-z]+) (?P<hash>[0-9a-zA-Z]+)\s(?P<name>.+)/', $file, $matches)) {
				$contents[$a] = array(
					'permissions' => $matches['permissions'],
					'type' => $matches['type'],
					'hash' => $matches['hash'],
					'path' => $matches['name'],
					'name' => str_replace("$folderPath", "", $matches['name'])
				);
			}
		}

		return $contents;
	}
}
