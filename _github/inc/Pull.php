<?php

class Pull extends VcsAbstract {
    /**
     * Executes the necessary commands to deploy the website.
     */
    public function execute() {
        try {
            if (!$this->has_base_directory()) {
                mkdir($this->_directory, 0777, true);
            }

            // Make sure we're in the right directory
            chdir($this->_directory);

            // only first run
            if (!is_dir('./.git')) {
                // The "." at the end specifies the current folder as the checkout folder!
                $this->exec_and_log('Cloning repo for the first time.', "git clone '" . $this->_url . "' .");
            }

            // normal run
            else {

                // 1. Move to default (usually "master") branch.
                $this->exec_and_log('Switching to default branch.', 'git checkout ' . $this->_branch);

                // 2. Update the local repository
                $this->exec_and_log('Pulling in changes.', 'git pull');

                // hidden feature: checkout special revision

                if (isset($_GET['hash']) && $this->is_sha1($_GET['hash'])) {
                    $hash = $_GET['hash'];
                    $this->exec_and_log('Checkout previous revision '. $hash.':', 'git checkout '. $hash);
                }
            }

            // Secure the .git directory
            $this->exec_and_log('Securing .git directory.', 'chmod -R og-rx .git');

            $this->log('PULL successful.');
        }
        catch (Exception $e) {
            $this->log($e, 'ERROR');
        }
    }
}