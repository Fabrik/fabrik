<?php

abstract class VcsAbstract implements VcsInterface {
    /**
     * The name of the file that will be used for logging deployments. Set to
     * FALSE to disable logging.
     *
     * @var string
     */
    private $_log = '../deployments.log';

    /**
     * All logged messages of the current run
     *
     * @var string
     */
    public $_log_messages = '';

    /**
     * The timestamp format used for logging.
     *
     * @link    http://www.php.net/manual/en/function.date.php
     * @var     string
     */
    private $_date_format = 'Y-m-d H:i:sP';

    /**
     * The name of the branch to pull from.
     *
     * @var string
     */
    protected $_branch = 'GN_deploy';

    /**
     * The name of the remote to pull from.
     *
     * @var string
     */
    protected $_remote = 'origin';

    /**
     * The directory where your website and git repository are located, can be
     * a relative or absolute path, will be relative to the inc/ folder
     *
     * @var string
     */
    protected $_directory;

    /**
     * Only used for first clone
     */
    protected $_url;

    /**
     * Sets up defaults.
     *
     * @param  array $config  Information about the deployment
     */
    public function __construct($config = array()) {
        $available_options = array('directory', 'log', 'date_format', 'branch', 'remote', 'url');

        foreach ($config as $option => $value) {
            if (in_array($option, $available_options)) {
                $this->{'_' . $option} = $value;
            }
        }
    }

    /**
     * Writes a message to the log file.
     *
     * @param  array|string $message  The message(s) to write
     * @param  string $type     The type of log message (e.g. INFO, DEBUG, ERROR, etc.)
     */
    public function log($message, $type = 'INFO') {
        if ($this->_log) {
            // Set the name of the log file
            $filename = $this->_log;

            if (!file_exists($filename)) {
                // Create the log file
                file_put_contents($filename, '');

                // Allow anyone to write to log files
                chmod($filename, 0666);
            }

            if (is_array($message)) {
                $message = implode("\n\t", $message);
            }

            $formatted = date($this->_date_format) . ' --- ' . $type . ': ' . $message . "\n";
            file_put_contents($filename, $formatted, FILE_APPEND);

            $this->_log_messages .= $formatted;
            $this->_log_messages .= "-------------------------------------------------------------------------------\n";
        }
    }

    /**
     * Calls exec and logs its input and output
     * @param string $description  The description of the command
     * @param string $command      The command to execute
     */
    public function exec_and_log($description, $command) {
        $output = array();
        exec($command . ' 2>&1', $output);

        $messages = array_merge(array($description, $command), $output);
        $this->log($messages);
    }

    /**
     * @return bool
     */
    protected function has_base_directory() {
        return file_exists($this->_directory);
    }

    protected function is_sha1($str) {
        return (bool) preg_match('/^[0-9a-f]{40}$/i', $str);
    }
}