<?php

class SourceGitTestCase extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->SourceGit = ClassRegistry::init('GitCake.SourceGit');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->SourceGit);
		parent::tearDown();
	}

/**
 * testEnsureValidHash function.
 * Test that the ensureValidHash method correctly identifies a valid hash.
 *
 * @access public
 * @return void
 */
	public function testEnsureValidHash() {
		// Check happy path
		$this->assertTrue(SourceGit::ensureValidHash('b1bcad6cc5be9a89df080a810a03c81970ddcfb5'));

		// Check underscores are not allowed
		try {
			$this->assertTrue(SourceGit::ensureValidHash('b1bcad6cc5be9a89df080a810_03c81970ddcfb5'));
			$this->fail('b1bcad6cc5be9a89df080a810_03c81970ddcfb5 should have caused an exception.');
		} catch (Exception $e) {
			$this->assertContains('Hash is not alphanumeric', $e->getMessage());
		}

		// Check dashes are not allowed
		try {
			$this->assertTrue(SourceGit::ensureValidHash('b1bcad6cc5be9a89df080a810-03c81970ddcfb5'));
			$this->fail('b1bcad6cc5be9a89df080a810-03c81970ddcfb5 should have caused an exception.');
		} catch (Exception $e) {
			$this->assertContains('Hash is not alphanumeric', $e->getMessage());
		}

		// Check single quotes are not allowed
		try {
			$this->assertTrue(SourceGit::ensureValidHash("b1bcad6cc5be9a89df080a810'03c81970ddcfb5"));
			$this->fail("b1bcad6cc5be9a89df080a810'03c81970ddcfb5 should have caused an exception.");
		} catch (Exception $e) {
			$this->assertContains('Hash is not alphanumeric', $e->getMessage());
		}

		// Check double quotes are not allowed
		try {
			$this->assertTrue(SourceGit::ensureValidHash('b1bcad6cc5be9a89df080a810"03c81970ddcfb5'));
			$this->fail('b1bcad6cc5be9a89df080a810"03c81970ddcfb5 should have caused an exception.');
		} catch (Exception $e) {
			$this->assertContains('Hash is not alphanumeric', $e->getMessage());
		}

		// Check empty hashes are not allowed
		try {
			$this->assertTrue(SourceGit::ensureValidHash(''));
			$this->fail('No hash should have caused an exception.');
		} catch (Exception $e) {
			$this->assertContains('Hash is zero length', $e->getMessage());
		}
	}

/**
 * testOpen function.
 * Test that we can open the current repo
 *
 * @access public
 * @return void
 */
	public function testOpen() {
		$this->SourceGit->open(App::pluginPath('GitCake'));

		$branches = $this->SourceGit->getBranches();
		$this->assertTrue(!empty($branches));
	}

	public function testExists() {
		$this->SourceGit->open(App::pluginPath('GitCake'));
		$exists = $this->SourceGit->exists('5be38c7f065523379b30247f2aa67bcf3995b29b');

		$this->assertEquals($exists, '5be38c7f065523379b30247f2aa67bcf3995b29b');
	}

	public function testGetCommitMetadata1() {
		$this->SourceGit->open(App::pluginPath('GitCake'));
		$expectedOutput = array(
			'author' => array(
				'name' => 'Phillip Whittlesea',
				'email' => 'pw.github@thega.me.uk'
			)
		);
		$metadataA = $this->SourceGit->getCommitMetadata('5be38c7f065523379b30247f2aa67bcf3995b29b', array('author'));

		$this->assertEquals($metadataA, $expectedOutput);
	}

	public function testGetCommitMetadata2() {
		$this->SourceGit->open(App::pluginPath('GitCake'));
		$expectedOutput = array(
			'author' => array(
				'name' => 'Phillip Whittlesea',
				'email' => 'pw.github@thega.me.uk'
			)
		);
		$metadataB = $this->SourceGit->getCommitMetadata('5be38c7f065523379b30247f2aa67bcf3995b29b', 'author');

		$this->assertEquals($metadataB, $expectedOutput);
	}

	public function testGetCommitMetadata3() {
		$this->SourceGit->open(App::pluginPath('GitCake'));
		$expectedOutput = array(
			'hash' => '5be38c7f065523379b30247f2aa67bcf3995b29b'
		);
		$metadataB = $this->SourceGit->getCommitMetadata('5be38c7f065523379b30247f2aa67bcf3995b29b', 'hash');

		$this->assertEquals($metadataB, $expectedOutput);
	}

	public function testGetDiff() {
		$this->SourceGit->open(App::pluginPath('GitCake'));
		$show = $this->SourceGit->getDiff('5be38c7f065523379b30247f2aa67bcf3995b29b', '');
		if (strpos($show, 'diff --git a/README.md b/README.md') == false) {
			$this->fail('The show funciton did not contain the required string.');
		}
		if (strpos($show, 'diff --git a/.gitignore b/.gitignore') == false) {
			$this->fail('The show funciton did not contain the required string.');
		}
	}

	public function testShow() {
		$this->SourceGit->open(App::pluginPath('GitCake'));

		$show = $this->SourceGit->show('5be38c7f065523379b30247f2aa67bcf3995b29b');

		if (strpos($show, 'Date:   Tue Jun 19 20:04:53 2012 -0700') == false) {
			$this->fail('The show funciton did not contain the required string.');
		}
	}

	public function testBranchRegex() {
		$inBranches = array(
			'develop',
			'* master',
			'feature/branch1',
			'feature/branch with spaces',
			'folder with spaces/branch2'
		);
		$outBranches = $this->SourceGit->calculateBranches($inBranches);

		$this->assertTrue(in_array('develop', $outBranches));
		$this->assertTrue(in_array('master', $outBranches));
		$this->assertTrue(in_array('feature/branch1', $outBranches));
		$this->assertTrue(in_array('feature/branch with spaces', $outBranches));
		$this->assertTrue(in_array('folder with spaces/branch2', $outBranches));
		$this->assertFalse(in_array('* master', $outBranches));
	}

/**
 * testRevisionListWithQuote function.
 * Test that folders and files with quotes in the name are escaped properly with the revisionList function.
 *
 * @access public
 * @return void
 */
	public function testRevisionListWithQuote() {
		$this->SourceGit->open(App::pluginPath('GitCake'));
		$expectedOutput = array('47b0c27c9778e4c85af26ca09428d7af7a09f42a');
		$lastRevision = $this->SourceGit->revisionList('master', 1, 0, "Test/Sample/folder_example_quot'e/file_example_quot'e");

		$this->assertEquals($expectedOutput, $lastRevision);
	}

}
