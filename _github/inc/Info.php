<?php

class Info extends VcsAbstract {
    /**
     * Shows some info.
     */
    public function execute() {
        try {
            if (!$this->has_base_directory()) {
                $message = "Directory '{$this->_directory}' not found.";
                throw new Exception($message);
            }

            // Make sure we're in the right directory
            chdir($this->_directory);

            $this->exec_and_log(
                'Git status:',
                'git status');

            $this->exec_and_log(
                'Current revision:',
                'git log -1');

            $this->exec_and_log(
                'All files that are changed locally:',
                'git diff --name-only');

            /*
            $this->exec_and_log(
                'Commits you have in HEAD that are not in '.$this->_remote.'/'.$this->_branch,
                'git log '.$this->_remote.'/'.$this->_branch.'..');
            */
        }
        catch (Exception $e) {
            $this->log($e, 'ERROR');
        }
    }
}