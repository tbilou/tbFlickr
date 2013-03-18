<?php

require_once "aWorker.php";
require_once "Keys.php";

class workerPhotosetsGetlist extends aWorker {

    public function __construct() {

        parent::__construct();

        $this->incTotalInstances(Keys::PHOTOSETS_GETLIST_INFO);

        // Make sure the Keys and variables we need are created

        $this->log->logInfo("[workerPhotosetsGetlist] [" . getmypid() . "] - STARTED ");
        $this->log->logInfo("[workerPhotosetsGetlist] [" . getmypid() . "] - Monitoring Queue " . Keys::PHOTOSETS_GETLIST_QUEUE);


        while (1) {

            // Check if this process should die
            if ($this->redis->lPop(Keys::PHOTOSETS_GETLIST_KILL_QUEUE)) {
                $this->decrTotalInstances(Keys::PHOTOSETS_GETLIST_INFO);
                $this->log->logFatal("[workerPhotosetsGetlist] [" . getmypid() . "] - Got kill order...Bye Bye");
                exit();
            }

            try {
                $job = $this->redis->blPop(Keys::PHOTOSETS_GETLIST_QUEUE, 30);
            } catch (Exception $e) {
                $this->log->logError("[workerPhotosetsGetlist] [" . getmypid() . "] - Error reading from queue " . Keys::PHOTOSETS_GETLIST_QUEUE);
                continue;
            }

            if ($job) {
                $this->setStartTime(Keys::PHOTOSETS_GETLIST_INFO);
                // Start working
                $this->run();
            } else {
                $this->log->logInfo("[workerPhotosetsGetlist] [" . getmypid() . "] - No messages found on queue " . Keys::PHOTOSETS_GETLIST_QUEUE . " ... Looping");
            }
        }
    }

    public function run() {
        $this->log->logNotice("[workerPhotosetsGetlist] [" . getmypid() . "] - Got message on queue " . Keys::PHOTOSETS_GETLIST_QUEUE);

        // If we don't specify the perpage limit, we get all the sets at once from flickr
        $this->phpFlickr->photosets_getList();

        // Get everything into an associative array
        $sets = $this->phpFlickr->parsed_response['photosets']['photoset'];

        $this->log->logInfo("[workerPhotosetsGetlist] [" . getmypid() . "] - Found " . count($sets) . " sets on Flickr");

        $this->incTotalMessages(Keys::PHOTOSETS_GETLIST_INFO);

        // Iterate over the sets
        foreach ($sets as $set) {
            // When the total photos of a set is greater than the perpage limit,
            // create multiple messages

            $photos = $set['photos'];
            $pages = ceil($photos / $this->PER_PAGE);

            //echo "Iterating over the pages: $pages"."</br>";
            for ($i = 1; $i <= $pages; $i++) {

                //$this->log->logInfo("[workerPhotosetsGetlist] [" . getmypid() . "] - Pages [" . $i . "|" . $pages . "] (" . $photos . ")");

                $obj['id'] = $set['id'];
                $obj['name'] = $set['title'];
                $obj['total'] = $set['photos'];
                $obj['page'] = $i;
                $obj['perpage'] = $this->PER_PAGE;

                // Send the message to the queue
                $this->log->logNotice("[workerPhotosetsGetlist] [" . getmypid() . "] - Photos for set " . $set['title'] . " Sent");

                $this->redis->rPush(Keys::PHOTOSETS_GETPHOTOS_QUEUE, json_encode($obj));
                $this->incTotalMessages(Keys::PHOTOSETS_GETPHOTOS_INFO);
            }
        }
    }

}

$worker = new workerPhotosetsGetlist();
?>
