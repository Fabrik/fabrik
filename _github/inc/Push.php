<?php

class Push extends VcsAbstract {
    /**
     * Executes the necessary commands to commit website changes.
     */
    public function execute() {
        try {
            if (!$this->has_base_directory()) {
                $message = "Commit directory '{$this->_directory}' not found.";
                throw new Exception($message);
            }

            // Make sure we're in the right directory
            chdir($this->_directory);

            // Add files to repository.
            $this->exec_and_log('Adding untracked files.', 'git add -A .');

            // Move changes to stash.
            $this->exec_and_log('Stashing changes.', 'git stash');

            // Create a new branch.
            $branchName = 'from_deployed_system_' . date('Y-m-d_H-i-s');
            $this->exec_and_log(
                "Creating new branch '{$branchName}'.",
                "git checkout -b {$branchName}"
            );

            // Unstash changes in new branch.
            $this->exec_and_log('Unstashing changes.', 'git stash pop');

            // Create commit.
            $commitMessage = 'HTTP triggered server commit; ' . date('Y-m-d H:i:s');
            $this->exec_and_log('Creating commit.', "git commit -am '{$commitMessage}'");

            // Push changes to remote repository.
            $this->exec_and_log('Pushing in changes.', 'git push ' . $this->_remote . ' ' . $branchName);

            $this->log('PUSH successful.');
        }
        catch (Exception $e) {
            $this->log($e, 'ERROR');
        }
    }
}