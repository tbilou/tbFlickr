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
class workerDownloadPhoto extends FlickrService {

    const TIMEOUT = 120; // Seconds

    public function __construct() {
        parent::__construct();
    }

    public function run($job) {
        echo date(DATE_RFC822)." [workerDownloadPhoto] - Got message on queue TB.flickr.download.photo.REQ";
        
        // Replace forbidden chars in windows filenames
        $job->photoset = preg_replace('/[\\/:\"*?<>|]/i', "_", $job->photoset);

        $dir = Main::DOWNLOAD_PATH . trim($job->photoset) . DIRECTORY_SEPARATOR;
        $path = Main::DOWNLOAD_PATH . trim($job->photoset) . DIRECTORY_SEPARATOR . $job->title . ".jpg";

        if (!file_exists($path)) {
            // Check if file is already downloaded
            $existingFile = $this->redis->get($job->id);
            if ($existingFile) {
                echo date(DATE_RFC822)." [workerDownloadPhoto] - Duplicate Image - Creating Hardlink";
                if (!link($existingFile, $path)) {
                    // Error creating hardlink. Make a simple copy
                    if (!copy($existingFile, $path)) {
                        echo date(DATE_RFC822)." [workerDownloadPhoto] - ERROR: Error copying file on filesystem";
                    }
                }
            } else {
                // No file found on cache. Download it
                if (!is_dir($dir)) {
                    mkdir($dir);
                }
                echo date(DATE_RFC822)." [workerDownloadPhoto] - Downloading file [".$path."]";
                if (!$this->downloadFile($job->url, $path)) {
                    // There was a timeout downloading the file
                    echo date(DATE_RFC822)." [workerDownloadPhoto] - ERROR: Timeout downloading file. Pushing job to TB.flickr.download.failed.REQ";
                    $this->redis->rPush("TB.flickr.download.failed.REQ", $job);
                    return;
                }

                $this->redis->set($job->id, $path);
            }
        } else {
            // The file already exists on the Filesystem. Keep a reference for future hardlinks
            // TODO: Check the size of the file
            if (!$this->redis->get($job->id)) {
                $this->redis->put($job->id, $path);
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

?>
