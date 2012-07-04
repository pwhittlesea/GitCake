GitCake
=======

A CakePHP 2.0 Plugin for manipulating Git Repos (that doesnt use the exec function)

Features
--------

GitCake has the following functionality:
* `Commit History’ ( git log )
* `Commit’ detail ( git show )
* `Diff’ between hashes ( git diff )
* File blame ( git blame )
* Display repository file tree ( git ls-tree )
* Ability to read bare and live repos

Installation
-----

Firstly, check out GitCake into your 'APP/app/Plugin' directory and update the submodules:
```bash
$ cd app/Plugin/GitCake
$ git clone git@github.com/pwhittlesea/GitCake
$ cd GitCake
$ git submodule init
$ git submodule update
```
Secondly, add GitCake to your APP/app/Config/bootstrap.php config file:
```php
<?php
...
CakePlugin::load('GitCake');
...
```
Thirdly, import the Plugin in your controller:
```php
<?php
/**
 * DemoController
 * For a demo
 *
 */
class DemoController extends AppController {
  public $uses = array('GitCake.GitCake');
  ...
}
```
Finally, you can now use the GitCake Plugin in your controller:
```php
<?php
  ...

  /*
   * __loadRepo
   *
   * @param $name string Project name
   */
  private function __loadRepo($name) {
      // Load the repo into the GitCake Model
      return $this->GitCake->loadRepo("/home/user/projects/$name.git");
  }
```

Usage
-----

After you've followed the installation steps, it will now be time to extract your data:

### tree($hash = 'master', $folderPath = '')
The tree() function is designed to return the contents of a Git tree. Passing a tree hash or folder location with a branch will enable you to find the contents of a tree:
```php
  $this->GitCake->loadRepo("/home/user/projects/example.git");
  $out = $this->GitCake->tree(); // Return the top level tree of a repo
```
Traditional Git output would show:
```bash
$ git ls-tree master ''
100644 blob 12656ced5f8b89d4ce2e3a48a80a2d0ea652a072	README.md
040000 tree ab0433981c68b7ebdd2f8339a18e2a89f19191b7	Model
```
Whereas $out will now contain:
```php
array(2) {
  [0]=> 
  array(4) {
    [permissions]=>
    string(6) "100644"
    [type]=>
    string(4) "blob"
    [hash]=>
    string(40) "12656ced5f8b89d4ce2e3a48a80a2d0ea652a072"
    [name]=>
    string(9) "README.md"
  }
  [1]=> 
  array(4) {
    [permissions]=>
    string(6) "040000"
    [type]=>
    string(4) "tree"
    [hash]=>
    string(40) "ab0433981c68b7ebdd2f8339a18e2a89f19191b7"
    [name]=>
    string(5) "Model"
  }
}
```