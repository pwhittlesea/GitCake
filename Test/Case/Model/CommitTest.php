<?php

class CommitTestCase extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Commit = ClassRegistry::init('GitCake.Commit');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Commit);
		parent::tearDown();
	}

/**
 * testCommitCreation function.
 * Check that the Commit can be created.
 *
 * @access public
 * @return void
 */
	public function testCommitCreation() {
		$sc = $this->getMock('SourceControl', array('open'));
		$sc->expects($this->once())
			->method('open')
			->with('location')
			->will($this->returnValue(true));

		$this->Commit->openEngine($sc, 'location');
	}

/**
 * testFetchCommit function.
 * Check that the fetch command returns all the coorect
 * information
 *
 * @access public
 * @return void
 */
	public function testFetchCommit() {
		$commitDetails = array(
			'hash' => 'b1bcad6cc5be9a89df080a810a03c81970ddcfb5',
			'subject' => 'Commit Subject',
			'date' => '2013-01-28 16:48:34 +0000',
			'author' => array(
				'name' => 'Author1',
				'email' => 'author@example.com',
			),
			'abbv' => 'b1bcad6',
			'body' => 'More chat about the commit',
			'notes' => '',
			'parent' => '3bf28d1fcf53f5b85e55d37e86d5954a738ac36c'
		);
		$changedFiles = array(
			'file1',
			'path1/path2/file2'
		);

		$this->Commit->engine = $this->getMock('SourceControl', array('getCommitMetadata', 'getChangedFiles'));
		$this->Commit->engine->expects($this->once())
			->method('getCommitMetadata')
			->with('b1bcad6cc5be9a89df080a810a03c81970ddcfb5', array_keys($commitDetails))
			->will($this->returnValue($commitDetails));
		$this->Commit->engine->expects($this->once())
			->method('getChangedFiles')
			->with('b1bcad6cc5be9a89df080a810a03c81970ddcfb5', '3bf28d1fcf53f5b85e55d37e86d5954a738ac36c')
			->will($this->returnValue($changedFiles));

		$commit = $this->Commit->fetch('b1bcad6cc5be9a89df080a810a03c81970ddcfb5');

		$commitDetails['changeset'] = $changedFiles;

		$this->assertEquals($commit, $commitDetails);
	}

/**
 * testFullDiff function.
 * Test that a diff on two commits returns the correct info
 *
 * @access public
 * @return void
 */
	public function testFullDiff() {
		$diffResponse = '
diff --git a/app/Console/Command/XmlShell.php b/app/Console/Command/XmlShell.php
index 419a2ab..c0aa714 100644
--- a/app/Console/Command/XmlShell.php
+++ b/app/Console/Command/XmlShell.php
@@ -27,4 +27,4 @@ class XmlShell extends AppShell {
 	public function import() {
-		$this->ImportXml->execute($this->_collectParameters());
+		$this->ImportXml->execute($this->__collectParameters());
 	}';

 		$expectedDiff = array(
			'app/Console/Command/XmlShell.php' => array(
				'hunks' => array(
					(int) 0 => array(
						(int) 0 => array(
							(int) 0 => ' ',
							(int) 1 => (int) 27,
							(int) 2 => (int) 27,
							(int) 3 => '	public function import() {'
						),
						(int) 1 => array(
							(int) 0 => '-',
							(int) 1 => (int) 28,
							(int) 2 => null,
							(int) 3 => '		$this-&gt;ImportXml-&gt;execute($this-&gt;_collectParameters());'
						),
						(int) 2 => array(
							(int) 0 => '+',
							(int) 1 => null,
							(int) 2 => (int) 28,
							(int) 3 => '		$this-&gt;ImportXml-&gt;execute($this-&gt;__collectParameters());'
						),
						(int) 3 => array(
							(int) 0 => ' ',
							(int) 1 => (int) 29,
							(int) 2 => (int) 29,
							(int) 3 => '	}'
						)
					)
				),
				'hunks_def' => array(
					(int) 0 => array(
						'-' => array(
							(int) 0 => '27',
							(int) 1 => '4'
						),
						'+' => array(
							(int) 0 => '27',
							(int) 1 => '4'
						),
						'heading' => 'class XmlShell extends AppShell {'
					)
				),
				'less' => (int) 1,
				'more' => (int) 1
			)
		);

		$this->Commit->engine = $this->getMock('SourceControl', array('getDiff'));
		$this->Commit->engine->expects($this->once())
			->method('getDiff')
			->with('b1bcad6cc5be9a89df080a810a03c81970ddcfb5', '3bf28d1fcf53f5b85e55d37e86d5954a738ac36c')
			->will($this->returnValue($diffResponse));

		$commit = $this->Commit->diff('b1bcad6cc5be9a89df080a810a03c81970ddcfb5', '3bf28d1fcf53f5b85e55d37e86d5954a738ac36c');

		$this->assertEquals($expectedDiff, $commit);
	}

/**
 * testFileDiff function.
 * Test that a diff on a file returns the correct info
 *
 * @access public
 * @return void
 */
	public function testFileDiff() {
		$diffResponse = '
diff --git a/app/Console/Command/XmlShell.php b/app/Console/Command/XmlShell.php
index 419a2ab..c0aa714 100644
--- a/app/Console/Command/XmlShell.php
+++ b/app/Console/Command/XmlShell.php
@@ -27,4 +27,4 @@ class XmlShell extends AppShell {
 	public function import() {
-		$this->ImportXml->execute($this->_collectParameters());
+		$this->ImportXml->execute($this->__collectParameters());
 	}';

 		$expectedDiff = array(
			'hunks' => array(
				(int) 0 => array(
					(int) 0 => array(
						(int) 0 => ' ',
						(int) 1 => (int) 27,
						(int) 2 => (int) 27,
						(int) 3 => '	public function import() {'
					),
					(int) 1 => array(
						(int) 0 => '-',
						(int) 1 => (int) 28,
						(int) 2 => null,
						(int) 3 => '		$this-&gt;ImportXml-&gt;execute($this-&gt;_collectParameters());'
					),
					(int) 2 => array(
						(int) 0 => '+',
						(int) 1 => null,
						(int) 2 => (int) 28,
						(int) 3 => '		$this-&gt;ImportXml-&gt;execute($this-&gt;__collectParameters());'
					),
					(int) 3 => array(
						(int) 0 => ' ',
						(int) 1 => (int) 29,
						(int) 2 => (int) 29,
						(int) 3 => '	}'
					)
				)
			),
			'hunks_def' => array(
				(int) 0 => array(
					'-' => array(
						(int) 0 => '27',
						(int) 1 => '4'
					),
					'+' => array(
						(int) 0 => '27',
						(int) 1 => '4'
					),
					'heading' => 'class XmlShell extends AppShell {'
				)
			),
			'less' => (int) 1,
			'more' => (int) 1
		);
		$this->Commit->engine = $this->getMock('SourceControl', array('getDiff'));
		$this->Commit->engine->expects($this->once())
			->method('getDiff')
			->with('b1bcad6cc5be9a89df080a810a03c81970ddcfb5', '3bf28d1fcf53f5b85e55d37e86d5954a738ac36c', 'app/Console/Command/XmlShell.php')
			->will($this->returnValue($diffResponse));

		$commit = $this->Commit->diff('b1bcad6cc5be9a89df080a810a03c81970ddcfb5', '3bf28d1fcf53f5b85e55d37e86d5954a738ac36c', 'app/Console/Command/XmlShell.php');

		$this->assertEquals($expectedDiff, $commit);
	}
}
