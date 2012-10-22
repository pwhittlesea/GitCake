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

class Blob extends GitCakeAppModel {

    /**
     * lastChange function.
     *
     * @access private
     * @param mixed $branch
     * @param mixed $file
     * @return void
     */
    private function lastChange($branch, $file) {
        $history = $this->history($branch, 1, 0, $file);
        return $this->commitDetails($history[0]);
    }

    /**
     * fetch function.
     *
     * @access public
     * @param mixed $branch
     * @param mixed $folderPath
     * @return void
     */
    public function fetch($branch, $folderPath) {
        // Check the last character isnt a / otherwise git will return the contents of the folder
        if ($folderPath != '' && $folderPath[strlen($folderPath)-1] == '/') {
            $folderPath = substr($folderPath, 0, strlen($folderPath)-1);
        }

        // Lets start from the base of the repo
        if ($folderPath == '') {
            $folderPath = '.';
        }

        $current = $this->engine->getPathDetails($branch, $folderPath);

        if ($current == null) {
            return array('type' => 'invalid');
        }

        // Init standard return array
        $return = array(
            'type'    => $current['type'],
            'content' => '',
            'path'    => $folderPath,
            'commit'  => $this->commitDetails($branch)
        );

        if ($current['type'] == 'blob') {
            $return['content'] = $this->engine->show($current['hash']);
            $return['updated'] = $this->lastChange($branch, $current['name']);
        } else if ($current['type'] == 'tree') {
            $return['content'] = $this->engine->treeList($branch, "$folderPath/");

            foreach ($return['content'] as $a => $file) {
                $return['content'][$a]['updated'] = $this->lastChange($branch, $file['path']);

                if ($file['type'] == 'commit') {
                    $submodules = (!isset($submodules)) ? $this->submodules($branch) : $submodules;
                    $return['content'][$a]['remote'] = (isset($submodules[$file['name']])) ? $submodules[$file['name']]['remote'] : '';
                }
            }
        }
        return $return;
    }

    /**
     * submodules function.
     *
     * @access public
     * @param mixed $branch
     * @return void
     */
    public function submodules($branch) {
        $resp = $this->fetch($branch, './.gitmodules');

        $sub = array();

        if (!isset($resp['content'])) {
            return $sub;
        }
        preg_match_all('#\[submodule\s+[\"\'](?P<name>\S*)[\"\']\]\s+path\s=\s(?P<path>\S+)\s+url\s=\s(?P<remote>\S+)#', $resp['content'], $matches);

        // Just incase there are no submodules
        if (empty($matches)) {
            return $sub;
        }

        foreach ($matches['name'] as $i => $name) {
            preg_match('#(?P<protocol>(git|http)://|git@)(?P<url>\S+)#', $matches['remote'][$i], $remote);
            if ($remote['protocol'] == 'git@') {
                $remote['url'] = str_replace(':', '/', $remote['url']);
            }

            $sub[$matches['path'][$i]] = array('name'=>$matches['name'][$i], 'remote'=>$remote['url']);
        }
        return $sub;
    }

}
