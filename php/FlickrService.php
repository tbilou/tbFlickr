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
require "inc/tbPhpFlickr/tbPhpFlickr.php";

class FlickrService {

    protected $redis;
    protected $phpFlickr;

    const PER_PAGE = 500;

    public function __construct() {
        $this->redis = new Redis() or die("Can't load redis module.");
        $this->redis->connect(Main::REDIS_HOST, Main::REDIS_PORT);

        $this->phpFlickr = new tbPhpFlickr();
    }

    function __destruct() {
        $this->redis->close();
    }

}

?>
