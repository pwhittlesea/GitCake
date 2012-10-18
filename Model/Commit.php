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
            // Lets request the number stats for the file
            $numstat = $this->engine->getDiffStats($hash, $parent, $fileName);

            $line = preg_split('/\s+/', $numstat);

            // Sometimes the hash is returned as the first element
            $off = (isset($line[3])) ? 1 : 0;

            // Gather additions and subtractions stats
            $output[$fileName]['less'] = $line[$off + 1];
            $output[$fileName]['more'] = $line[$off + 0];
        }

        if ($file != '') {
            return $output[$file];
        }
        return $output;
    }
}
