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