<?php

interface VcsInterface {
    public function __construct($config = array());

    /**
     * Writes a message to the log file.
     *
     * @param  array|string $message  The message(s) to write
     * @param  string $type     The type of log message (e.g. INFO, DEBUG, ERROR, etc.)
     */
    public function log($message, $type = 'INFO');

    /**
     * Calls exec and logs its input and output
     * @param string $description  The description of the command
     * @param string $command      The command to execute
     */
    public function exec_and_log($description, $command);

    /**
     * Executes the necessary commands to deploy the website.
     */
    public function execute();
}