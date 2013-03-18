<?php

/**
 * Description of workerPhotosetsGetPhotos
 *
 * @author tbilou
 */
require_once "aWorker.php";
require_once "Keys.php";

class workerPhotosetsGetPhotos extends aWorker {

    public function __construct() {

        parent::__construct();

        $this->incTotalInstances(Keys::PHOTOSETS_GETPHOTOS_INFO);

        $this->log->logInfo("[workerPhotosetsGetPhotos] [" . getmypid() . "] - STARTED ");
        $this->log->logInfo("[workerPhotosetsGetPhotos] [" . getmypid() . "] - Monitoring Queue " . Keys::PHOTOSETS_GETPHOTOS_QUEUE);

        while (1) {

            // Check if this process should die
            if ($this->redis->lPop(Keys::PHOTOSETS_GETPHOTOS_KILL_QUEUE)) {
                $this->decrTotalInstances(Keys::PHOTOSETS_GETPHOTOS_INFO);
                $this->log->logFatal("[workerPhotosetsGetPhotos] [" . getmypid() . "] - Got kill order...Bye Bye");
                exit();
            }

            try {
                $job = $this->redis->blPop(Keys::PHOTOSETS_GETPHOTOS_QUEUE, 30);
            } catch (Exception $e) {
                $this->log->logError("[workerPhotosetsGetPhotos] [" . getmypid() . "] - Error reading from queue " . Keys::PHOTOSETS_GETPHOTOS_QUEUE);
                continue;
            }

            if ($job) {
                $this->setStartTime(Keys::PHOTOSETS_GETPHOTOS_INFO);
                // Start working
                $json = $job[1];
                $this->run(json_decode($json));
            } else {
                $this->log->logInfo("[workerPhotosetsGetPhotos] [" . getmypid() . "] - No messages found on queue " . Keys::PHOTOSETS_GETPHOTOS_QUEUE . " ... Looping");
            }
        }
    }

    public function run($job) {
        // Log
        $this->log->logNotice("[workerPhotosetsGetPhotos] [" . getmypid() . "] - Got message on queue " . Keys::PHOTOSETS_GETPHOTOS_QUEUE);
        $this->log->logInfo("[workerPhotosetsGetPhotos] [" . getmypid() . "] - Parsing photos in set [" . $job->name . "] (page" . $job->page . ")");

        // Call flickr
        $this->phpFlickr->photosets_getPhotos($job->id, 'url_o', NULL, $job->perpage, $job->page);

        // Get everything into an associative array
        $photos = $this->phpFlickr->parsed_response['photoset']['photo'];

        $this->log->logInfo("[workerPhotosetsGetPhotos] [" . getmypid() . "] - Found " . count($photos) . " Photos");

        foreach ($photos as $photo) {

            $obj['id'] = $photo['id'];
            $obj['title'] = $photo['title'];
            $obj['url'] = $photo['url_o'];
            $obj['photoset'] = $job->name;

            $this->redis->rPush(Keys::DOWNLOADS_QUEUE, json_encode($obj));
            $this->incTotalMessages(Keys::DOWNLOADS_INFO);
        }
    }

}

$worker = new workerPhotosetsGetPhotos();
?>
