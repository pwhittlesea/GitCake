<?php
/*
 * Custom test suite to execute all tests
 */

class AllTestsTest extends PHPUnit_Framework_TestSuite {

	public static function suite() {
		$path = App::pluginPath('GitCake') . 'Test' . DS . 'Case' . DS;

		$suite = new CakeTestSuite('All tests');
		$suite->addTestDirectoryRecursive($path . 'Model' . DS);
		return $suite;
	}
}
