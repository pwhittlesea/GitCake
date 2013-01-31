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

App::uses('GitCakeAppModel', 'GitCake.Model');

class Commit extends GitCakeAppModel {

/**
 * fetch function.
 *
 * @access public
 * @param mixed $commit
 * @return void
 */
    public function fetch($commit) {
        return $this->commitDetails($commit, true);
    }

/**
 * diff function.
 *
 * @access public
 * @param mixed $hash
 * @param mixed $parent
 * @param string $file (default: '')
 * @return void
 */
    public function diff($hash, $parent, $file = '') {
        $output = $this->engine->getDiff($hash, $parent, $file);

        $output = Diff::parse($output);

        foreach ($output as $fileName => $array) {
            $output[$fileName]['less'] = 0;
            $output[$fileName]['more'] = 0;

            foreach ($array['hunks'] as $hunk) {
                foreach ($hunk as $line) {
                    if ($line[0] == '-') $output[$fileName]['less']++;
                    if ($line[0] == '+') $output[$fileName]['more']++;
                }
            }
        }

        if ($file != '') {
            if (isset($output[$file])) {
                return $output[$file];
            }

            // Special case where folders are stored as changes in SVN
            try {
                $this->engine->getPathDetails($hash, $file);
                $added = true;
            } catch (Exception $e) {
                $added = false;
            }

            return array(
                'less' => (int) ((!$added) ? 1 : 0),
                'more' => (int) (($added) ? 1 : 0),
                'folder' => true
            );
        }
        return $output;
    }
}
