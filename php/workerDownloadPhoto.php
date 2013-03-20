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

        if (file_exists($path)) {
            $this->log->logNotice("[workerDownloads] [" . getmypid() . "] - This file already exists on the Filesystem [" . $path . "]");
            // If the file is smaller than 100kb it's probably an interrupted download
            $file_stats = stat($path);
            if ($file_stats['size'] < 102400) {
                $this->log->logError("[workerDownloads] [" . getmypid() . "] - Invalid size (<100kb) " . $file_stats['size'] / 1024 . "kb Downloading again");
                // Continue exection and download the file
            } else {

                // This is the default case. The file exists on the Filesystems and it's greater than 100kb
                // The file already exists on the Filesystem. Keep a reference for future hardlinks
                if (!$this->redis->hget(Keys::DOWNLOADED_PHOTOS, $job->id)) {
                    $this->redis->hset(Keys::DOWNLOADED_PHOTOS, $job->id, $path);
                }
                // Stop execution
                return;
            }
        }

        if (!file_exists($path)) {
            $this->log->logInfo("[workerDownloads] [" . getmypid() . "] - No file found on the Disk. Checking if it's a copy");

            // Check if file is already downloaded
            $existingFile = $this->redis->hget(Keys::DOWNLOADED_PHOTOS, $job->id);

            // Check if the file really exists
            if (file_exists($existingFile)) {

                $this->log->logNotice("[workerDownloads] [" . getmypid() . "] - Duplicate Image - Creating Hardlink");

                if (!link($existingFile, $path)) {
                    $this->log->logError("[workerDownloads] [" . getmypid() . "] - Error creating hardlink");
                    // Error creating hardlink. Make a simple copy
                    $this->log->logError("[workerDownloads] [" . getmypid() . "] - Making a file copy from " . $existingFile . " ");
                    if (!copy($existingFile, $path)) {
                        $this->log->logError("[workerDownloads] [" . getmypid() . "] - Error copying file on filesystem");
                        $this->log->logError("[workerDownloads] [" . getmypid() . "] - Sent Message to Failed Queue " . Keys::DOWNLOADED_PHOTOS_FAILED);
                        $this->redis->rPush(Keys::DOWNLOADED_PHOTOS_FAILED, $job);
                    }
                }
                return;
            }



            // No file found on cache. Download it
            $this->log->logInfo("[workerDownloads] [" . getmypid() . "] - No Duplicate found. Downloading it");

            if (!is_dir($dir)) {
                mkdir($dir);
            }

            $this->log->logNotice("[workerDownloads] [" . getmypid() . "] - Downloading file [" . $path . "]");

            if (!$this->downloadFile($job->url, $path)) {
                // There was a timeout downloading the file
                $this->log->logError("[workerDownloads] [" . getmypid() . "] - Timeout downloading file");
                $this->log->logError("[workerDownloads] [" . getmypid() . "] - Sent Message to Failed Queue " . Keys::DOWNLOADED_PHOTOS_FAILED);
                $this->redis->rPush(Keys::DOWNLOADED_PHOTOS_FAILED, $job);

                return;
            }

            $this->redis->hset(Keys::DOWNLOADED_PHOTOS, $job->id, $path);
        }
    }

    private function downloadFile($url, $path) {

        $f = fopen($path, 'w+') or die("Cannot write file");

        $handle = fopen($url, "rb");

        if (!$handle) {
            $this->log->logError("[workerDownloads] [" . getmypid() . "] - Network Error. Could not connect to flickr");
            return false;
        }

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
        chmod($path, 0777);

        return true;
    }

}

$worker = new workerDownloadPhoto();
?>
