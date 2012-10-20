GitCake
=======

A CakePHP 2.0 Plugin for manipulating and viewing source code in a variety of SCMs.

Source Control Management systems supported
--------

GitCake (despite its name) supports the following SCMs:
* Git
* Subversion (in progress)

Features
--------

GitCake has the following functionality:
* Commit history
* Commit detail
* `Diff’ between hashes
* Display repository file tree
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
Thirdly, link a model to the GitCake provided models:
```php
<?php
/**
 * SourceModel
 * For a demo
 *
 */
class SourceModel extends AppModel {
  public $hasMany = array(
    'Blob' => array(
      'className' => 'GitCake.Blob'
    ),
    'Commit' => array(
      'className' => 'GitCake.Commit'
    )
  );
  ...
}
```
Finally, you can now use the GitCake Plugin in your controller. Firstly you must open a repository by calling open on the object you wish to query on:
Note: the open function must be passed the type of the repository.
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
      return $this->Blob->open(RepoTypes::Git, "/home/user/projects/$name.git");
  }
```

Usage
-----

After you've followed the installation steps, it will now be time to extract your data:

### 'Blobs' - Repository trees and file contents
In Git (the first supported SCM of this plugin) blobs can represent a folder or a file.
The following outlines the functions you can perform on a 'Blob'.

#### Blob::fetch($hash = 'master', $folderPath = '')
The fetch() function is designed to return the contents of a Blob. Passing a tree hash or folder location with a branch will enable you to find the contents of a sub folder in your repository:
```php
  $this->Blob->open(RepoTypes::Git, "/home/user/projects/example.git");
  $out = $this->Blob->fetch('master'); // Return the top level tree of a repo
```
Traditional Git output would show:
```bash
$ git ls-tree master ''
100644 blob 12656ced5f8b89d4ce2e3a48a80a2d0ea652a072	README.md
040000 tree ab0433981c68b7ebdd2f8339a18e2a89f19191b7	Model
```
Whereas $out will now contain:
```php
array(
  'type' => 'tree',
	'content' => array(
		(int) 0 => array(
			'permissions' => '100644',
			'type' => 'blob',
			'hash' => '12656ced5f8b89d4ce2e3a48a80a2d0ea652a072',
			'name' => 'README.md',
			'path' => 'README.md',
			'updated' => array(
				// latest commit affecting file...
			)
		),
		(int) 1 => array(
			'permissions' => '040000',
			'type' => 'tree',
			'hash' => 'ab0433981c68b7ebdd2f8339a18e2a89f19191b7',
			'name' => 'Model',
			'path' => 'Model',
			'updated' => array(
				// latest commit affecting folder...
			)
		)
	),
	'path' => '.',
	'commit' => array(
		// latest commit affecting folder...
	)
)
```