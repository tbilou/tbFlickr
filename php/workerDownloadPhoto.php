<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of workerDownloadPhoto
 *
 * @author tbilou
 */

require_once "aWorker.php";


class workerDownloadPhoto extends aWorker {

    const TIMEOUT = 120; // Seconds

    public function __construct() {

        parent::__construct();
        
        $this->incTotalInstances(Keys::DOWNLOADS_INFO);

        $this->log->logInfo("[workerDownloads] [" . getmypid() . "] - STARTED ");
        $this->log->logInfo("[workerDownloads] [" . getmypid() . "] - Monitoring Queue " . Keys::DOWNLOADS_QUEUE);

        while (1) {

            // Check if this process should die
            if ($this->redis->lPop(Keys::DOWNLOADS_KILL_QUEUE)) {
                $this->decrTotalInstances(Keys::DOWNLOADS_INFO);
                $this->log->logFatal("[workerDownloads] [" . getmypid() . "] - Got kill order...Bye Bye");
                exit();
            }
            
            try {
                $job = $this->redis->blPop(Keys::DOWNLOADS_QUEUE, 10);
            } catch (Exception $e) {
                $this->log->logError("[workerDownloads] [" . getmypid() . "] - Error reading from queue " . Keys::DOWNLOADS_QUEUE);
                continue;
            }

            if ($job) {
                $this->setStartTime(Keys::DOWNLOADS_INFO);
                // Start working
                $json = $job[1];

                $this->run(json_decode($json));
            } else {
                $this->log->logInfo("[workerDownloads] [" . getmypid() . "] - No messages found on queue " . Keys::DOWNLOADS_QUEUE . " ... Looping");
            }
        }
    }

    public function run($job) {
        $this->log->logNotice("[workerDownloads] [" . getmypid() . "] - Got message on queue " . Keys::DOWNLOADS_QUEUE);

        // Replace forbidden chars in windows filenames
        $job->photoset = preg_replace('/[\\/:\"*?<>|]/i', "_", $job->photoset);

        $dir = $this->DOWNLOAD_PATH . trim($job->photoset) . DIRECTORY_SEPARATOR;
        $path = $this->DOWNLOAD_PATH . trim($job->photoset) . DIRECTORY_SEPARATOR . $job->title . ".jpg";

        if (!file_exists($path)) {
            $this->log->logInfo("[workerDownloads] [" . getmypid() . "] - No file found on the Disk. Checking if it's a duplicate");
          
            // Check if file is already downloaded
            $existingFile = $this->redis->hget(Keys::DOWNLOADED_PHOTOS, $job->id);
          
            if ($existingFile) {
                
                $this->log->logNotice("[workerDownloads] [" . getmypid() . "] - Duplicate Image - Creating Hardlink");
                
                if (!link($existingFile, $path)) {
                    $this->log->logError("[workerDownloads] [" . getmypid() . "] - Error creating hardlink. Make a simple copy");
                    // Error creating hardlink. Make a simple copy
                    if (!copy($existingFile, $path)) {
                        $this->log->logError("[workerDownloads] [" . getmypid() . "] - Error copying file on filesystem");
                    }
                }
            } else {
                // No file found on cache. Download it
                $this->log->logInfo("[workerDownloads] [" . getmypid() . "] - No Duplicate found. Downloading it");
                
                if (!is_dir($dir)) {
                    mkdir($dir);
                }
                
                $this->log->logNotice("[workerDownloads] [" . getmypid() . "] - Downloading file [" . $path . "]");
                
                if (!$this->downloadFile($job->url, $path)) {
                    // There was a timeout downloading the file
                    $this->log->logError("[workerDownloads] [" . getmypid() . "] - Timeout downloading file. Pushing job to TB.flickr.download.failed");
                    $this->redis->rPush("TB.flickr.download.failed", $job);

                    return;
                }

                $this->redis->hset(Keys::DOWNLOADED_PHOTOS, $job->id, $path);
            }
        } else {
            // The file already exists on the Filesystem. Keep a reference for future hardlinks
            // TODO: Check the size of the file. 0kb are not valid files...
            if (!$this->redis->hget(Keys::DOWNLOADED_PHOTOS, $job->id)) {
                $this->redis->hset(Keys::DOWNLOADED_PHOTOS, $job->id, $path);
            }
        }
    }

    private function downloadFile($url, $path) {

        $f = fopen($path, 'w+') or die("Cannot write file");

        $handle = fopen($url, "rb");

        $start = time();

        while (!feof($handle)) {
            // Check timeouts
            if ((time() - $start) > workerDownloadPhoto::TIMEOUT) {
                fclose($handle);
                fclose($f);
                // Delete the file
                return false;
            }

            $contents = fread($handle, 8192);
            fwrite($f, $contents);
        }

        fclose($handle);
        fclose($f);

        return true;
    }

}

$worker = new workerDownloadPhoto();

?>
