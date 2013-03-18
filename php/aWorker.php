<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FlickrService
 *
 * @author tbilou
 */
require_once "tbPhpFlickr/tbPhpFlickr.php";
require_once "KLogger/src/KLogger.php";
require_once "Keys.php";

class aWorker {

    protected $redis;
    protected $phpFlickr;
    protected $log;
    protected $REDIS_HOST = '127.0.0.1';
    protected $REDIS_PORT = 6379;
    protected $DOWNLOAD_PATH = '/home/tbilou/Pictures/';
    protected $LOG_PATH = '/var/log/klogger';
    protected $PER_PAGE = 500;

    // Manage work for workers


    public function __construct() {
        $this->redis = new Redis() or die("Can't load redis module.");
        $this->redis->connect($this->REDIS_HOST, $this->REDIS_PORT);

        // Get the config
        $path = $this->redis->hget(Keys::CONFIG_INFO, "DOWNLOAD_PATH");
        $log_path = $this->redis->hget(Keys::CONFIG_INFO, "LOG_PATH");

        if ($path != null)
            $this->DOWNLOAD_PATH = $path;
        if ($log_path != null)
            $this->LOG_PATH = $log_path;
        
        $this->phpFlickr = new tbPhpFlickr();

        //$this->log = KLogger::instance(dirname(__FILE__), KLogger::DEBUG);
        $this->log = KLogger::instance($this->LOG_PATH, KLogger::DEBUG);
    }

    function __destruct() {
        $this->redis->close();
    }

    function setStartTime($key) {
        $this->redis->hSetNx($key, 'start', date_format(new DateTime('NOW'), DATE_RFC822));
    }

    function incTotalMessages($key) {
        $this->redis->hIncrBy($key, "messages", 1);
    }

    function incTotalInstances($key) {
        $this->redis->hIncrBy($key, "instances", 1);
    }

    function decrTotalInstances($key) {
        $this->redis->hIncrBy($key, "instances", -1);
    }

}

?>
