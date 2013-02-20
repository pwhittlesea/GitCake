<?php
/*
 * Custom test suite to execute all tests
 */

class AllTestsTest extends PHPUnit_Framework_TestSuite {

	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Tests');

		$path = App::pluginPath('GitCake') . 'Test' . DS . 'Case' . DS;

		$suite->addTestFile($path . 'Model' . DS . 'BlobTest.php');
		$suite->addTestFile($path . 'Model' . DS . 'CommitTest.php');
		$suite->addTestFile($path . 'Model' . DS . 'SourceGitTest.php');
		return $suite;
	}
}
