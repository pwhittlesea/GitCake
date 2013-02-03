<?php

class BlobTestCase extends CakeTestCase {

/**
 * __prepareSubmodules function.
 * Prepare for a submodule test by mocking the engine
 *
 * @access private
 * @param mixed $sample
 * @return void
 */
	private function __prepareSubmodules($sample) {
		$pathDetails = array(
			'hash' => '6bd106ca427102ce9cdca16aa8560681de69a868',
			'name' => '.gitmodules',
			'type' => 'blob',
			'permissions' => '100664'
		);

		$this->Blob->engine = $this->getMock('SourceControl', array('getPathDetails', 'getCommitMetadata', 'show', 'revisionList'));
		$this->Blob->engine->expects($this->once())
			->method('getPathDetails')
			->with('master', './.gitmodules')
			->will($this->returnValue($pathDetails));
		$this->Blob->engine->expects($this->once())
			->method('show')
			->with('6bd106ca427102ce9cdca16aa8560681de69a868')
			->will($this->returnValue($sample));
		$this->Blob->engine->expects($this->any())
			->method('getCommitMetadata')
			->will($this->returnValue('Test Commit'));
	}

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Blob = ClassRegistry::init('GitCake.Blob');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Blob);
		parent::tearDown();
	}

	public function testBlobCreation() {
		$sc = $this->getMock('SourceControl', array('open'));
		$sc->expects($this->once())
			->method('open')
			->with('location')
			->will($this->returnValue(true));

		$this->Blob->openEngine($sc, 'location');
	}

/**
 * testFetchFile function.
 * Test if we return invalid if a path does not exist
 *
 * @access public
 * @return void
 */

	public function testFetchInvalid() {
		$this->Blob->engine = $this->getMock('SourceControl', array('getPathDetails'));
		$this->Blob->engine->expects($this->once())
			->method('getPathDetails')
			->with('master', 'file.php')
			->will($this->returnValue(null));

		$blob = $this->Blob->fetch('master', 'file.php');
		$this->assertEquals($blob, array('type'=>'invalid'));
	}

/**
 * testFetchFile function.
 * Test if we can extract details on a file
 *
 * @access public
 * @return void
 */
	public function testFetchFile() {
		$pathDetails = array(
			'hash' => '6bd106ca427102ce9cdca16aa8560681de69a868',
			'name' => 'file.php',
			'type' => 'blob',
			'permissions' => '100664'
		);
		$expected = array(
			'path' => 'file.php',
			'type' => 'blob',
			'content' => 'Test Content',
			'updated' => 'Test Commit',
			'commit' => 'Test Commit'
		);

		$this->Blob->engine = $this->getMock('SourceControl', array('getPathDetails', 'getCommitMetadata', 'show', 'revisionList'));
		$this->Blob->engine->expects($this->once())
			->method('getPathDetails')
			->with('master', 'file.php')
			->will($this->returnValue($pathDetails));
		$this->Blob->engine->expects($this->once())
			->method('show')
			->with('6bd106ca427102ce9cdca16aa8560681de69a868')
			->will($this->returnValue('Test Content'));
		$this->Blob->engine->expects($this->any())
			->method('getCommitMetadata')
			->will($this->returnValue('Test Commit'));

		$blob = $this->Blob->fetch('master', 'file.php');

		$this->assertEquals($expected, $blob);
	}

/**
 * testFetchFile function.
 * Test if we can extract details on a folder
 *
 * @access public
 * @return void
 */
	public function testFetchFolder() {
		$pathDetails = array(
			'hash' => '6bd106ca427102ce9cdca16aa8560681de69a868',
			'name' => 'folder',
			'type' => 'tree',
			'permissions' => '040000'
		);
		$treeContents = array(
			array(
				'hash' => '6bd106ca427102ce9cdca16aa8560681de69a867',
				'path' => 'folder/subfolder1',
				'type' => 'tree',
				'permissions' => '040000'
			),
			array(
				'hash' => '6bd106ca427102ce9cdca16aa8560681de69a869',
				'path' => 'folder/subfolder2',
				'type' => 'tree',
				'permissions' => '040000'
			)
		);

		$this->Blob->engine = $this->getMock('SourceControl', array('getPathDetails', 'getCommitMetadata', 'treeList', 'revisionList'));
		$this->Blob->engine->expects($this->any())
			->method('getPathDetails')
			->with('master', 'folder')
			->will($this->returnValue($pathDetails));
		$this->Blob->engine->expects($this->any())
			->method('getCommitMetadata')
			->will($this->returnValue('Test Commit'));
		$this->Blob->engine->expects($this->any())
			->method('treeList')
			->with('master', 'folder/')
			->will($this->returnValue($treeContents));

		$treeContents[0]['updated'] = 'Test Commit';
		$treeContents[1]['updated'] = 'Test Commit';

		$expected = array(
			'path' => 'folder',
			'type' => 'tree',
			'content' => $treeContents,
			'commit' => 'Test Commit'
		);

		$blobA = $this->Blob->fetch('master', 'folder');
		$blobB = $this->Blob->fetch('master', 'folder/');

		$this->assertEquals($expected, $blobA);
		$this->assertEquals($expected, $blobB);
	}

/**
 * testFetchFolderRoot function.
 * Test if we can extract details on root
 *
 * @access public
 * @return void
 */
	public function testFetchFolderRoot() {
		$this->Blob->engine = $this->getMock('SourceControl', array('getPathDetails'));
		$this->Blob->engine->expects($this->once())
			->method('getPathDetails')
			->with('master', '.');
		$this->Blob->fetch('master', '');
	}

/**
 * testFetchFolderWithSubmodules function.
 * Test if we can extract details on a folder with submodules
 *
 * @access public
 * @return void
 */
	public function testFetchFolderWithSubmodules() {
		$pathDetails = array(
			'hash' => '6bd106ca427102ce9cdca16aa8560681de69a868',
			'name' => 'folder',
			'type' => 'tree',
			'permissions' => '040000'
		);
		$moduleDetails = array(
			'hash' => '6bd106ca427102ce9cdca16aa8560681de69a820',
			'name' => './.gitmodules',
			'type' => 'blob',
			'permissions' => '064400'
		);
		$moduleFile = '
			[submodule "folder/subproject1"]
				path = folder/subproject1
				url = git://github.com/cakephp/cakephp.git';
		$treeContents = array(
			array(
				'hash' => '6bd106ca427102ce9cdca16aa8560681de69a821',
				'path' => 'folder/subproject1',
				'name' => 'subproject1',
				'type' => 'commit',
				'permissions' => '040000'
			)
		);
		$map = array(
		    array('master', 'folder', $pathDetails),
		    array('master', './.gitmodules', $moduleDetails),
		);

		$this->Blob->engine = $this->getMock('SourceControl', array('getPathDetails', 'getCommitMetadata', 'treeList', 'revisionList', 'show'));
		$this->Blob->engine->expects($this->exactly(2))
			->method('getPathDetails')
			->will($this->returnValueMap($map));
		$this->Blob->engine->expects($this->any())
			->method('getCommitMetadata')
			->will($this->returnValue('Test Commit'));
		$this->Blob->engine->expects($this->any())
			->method('treeList')
			->with('master', 'folder/')
			->will($this->returnValue($treeContents));
		$this->Blob->engine->expects($this->once())
			->method('show')
			->with('6bd106ca427102ce9cdca16aa8560681de69a820')
			->will($this->returnValue($moduleFile));

		$treeContents[0]['updated'] = 'Test Commit';
		$treeContents[0]['remote'] = 'github.com/cakephp/cakephp.git';
		$expected = array(
			'path' => 'folder',
			'type' => 'tree',
			'content' => $treeContents,
			'commit' => 'Test Commit'
		);

		$blobA = $this->Blob->fetch('master', 'folder');

		$this->assertEquals($expected, $blobA);
	}

	public function testFetchSubmodulesSpacesNotIndented() {
		$sample = '
[submodule "cakephp"]
    path = cakephp
    url = git://github.com/cakephp/cakephp.git';
		$this->__prepareSubmodules($sample);
		$blob = $this->Blob->submodules('master');

		$expectedResult = array(
			'cakephp' => array(
				'name' => 'cakephp',
				'remote' => 'github.com/cakephp/cakephp.git'
			)
		);
		$this->assertEquals($expectedResult, $blob);
	}

	public function testFetchSubmodulesSpacesIndented() {
		$sample = '
            [submodule "cakephp"]
                path = cakephp
                url = git://github.com/cakephp/cakephp.git';
		$this->__prepareSubmodules($sample);
		$blob = $this->Blob->submodules('master');

		$expectedResult = array(
			'cakephp' => array(
				'name' => 'cakephp',
				'remote' => 'github.com/cakephp/cakephp.git'
			)
		);
		$this->assertEquals($expectedResult, $blob);
	}

	public function testFetchSubmodulesTabsNotIndented() {
		$sample = '
[submodule "cakephp"]
	path = cakephp
	url = git://github.com/cakephp/cakephp.git';
		$this->__prepareSubmodules($sample);
		$blob = $this->Blob->submodules('master');

		$expectedResult = array(
			'cakephp' => array(
				'name' => 'cakephp',
				'remote' => 'github.com/cakephp/cakephp.git'
			)
		);
		$this->assertEquals($expectedResult, $blob);
	}

	public function testFetchSubmodulesTabsIndented() {
		$sample = '
			[submodule "cakephp"]
				path = cakephp
				url = git://github.com/cakephp/cakephp.git';
		$this->__prepareSubmodules($sample);
		$blob = $this->Blob->submodules('master');

		$expectedResult = array(
			'cakephp' => array(
				'name' => 'cakephp',
				'remote' => 'github.com/cakephp/cakephp.git'
			)
		);
		$this->assertEquals($expectedResult, $blob);
	}

	public function testFetchSubmodulesMultiple() {
		$sample = '
			[submodule "cakephp"]
				path = cakephp
				url = git://github.com/cakephp/cakephp.git
			[submodule "app/Plugin/GitCake"]
				path = app/Plugin/GitCake
				url = git://github.com/pwhittlesea/GitCake.git';
		$this->__prepareSubmodules($sample);
		$blob = $this->Blob->submodules('master');

		$expectedResult = array(
			'cakephp' => array(
				'name' => 'cakephp',
				'remote' => 'github.com/cakephp/cakephp.git'
			),
			'app/Plugin/GitCake' => array(
				'name' => 'app/Plugin/GitCake',
				'remote' => 'github.com/pwhittlesea/GitCake.git'
			)
		);
		$this->assertEquals($expectedResult, $blob);
	}

	public function testFetchSubmodulesHttps() {
		$sample = '
			[submodule "app/Plugin/GitCake"]
				path = app/Plugin/GitCake
				url = https://github.com/pwhittlesea/GitCake.git';
		$this->__prepareSubmodules($sample);
		$blob = $this->Blob->submodules('master');

		$expectedResult = array(
			'app/Plugin/GitCake' => array(
				'name' => 'app/Plugin/GitCake',
				'remote' => 'github.com/pwhittlesea/GitCake.git'
			)
		);
		$this->assertEquals($expectedResult, $blob);
	}

	public function testFetchSubmodulesHttp() {
		$sample = '
			[submodule "app/Plugin/GitCake"]
				path = app/Plugin/GitCake
				url = http://github.com/pwhittlesea/GitCake.git';
		$this->__prepareSubmodules($sample);
		$blob = $this->Blob->submodules('master');

		$expectedResult = array(
			'app/Plugin/GitCake' => array(
				'name' => 'app/Plugin/GitCake',
				'remote' => 'github.com/pwhittlesea/GitCake.git'
			)
		);
		$this->assertEquals($expectedResult, $blob);
	}

	public function testFetchSubmodulesGit() {
		$sample = '
			[submodule "app/Plugin/GitCake"]
				path = app/Plugin/GitCake
				url = git@github.com:pwhittlesea/GitCake.git';
		$this->__prepareSubmodules($sample);
		$blob = $this->Blob->submodules('master');

		$expectedResult = array(
			'app/Plugin/GitCake' => array(
				'name' => 'app/Plugin/GitCake',
				'remote' => 'github.com/pwhittlesea/GitCake.git'
			)
		);
		$this->assertEquals($expectedResult, $blob);
	}

	public function testFetchSubmodulesNoFile() {
		$sample = null;
		$this->__prepareSubmodules($sample);
		$blob = $this->Blob->submodules('master');

		$expectedResult = array();
		$this->assertEquals($expectedResult, $blob);
	}

	public function testFetchSubmodulesEmpty() {
		$sample = '';
		$this->__prepareSubmodules($sample);
		$blob = $this->Blob->submodules('master');

		$expectedResult = array();
		$this->assertEquals($expectedResult, $blob);
	}
}
