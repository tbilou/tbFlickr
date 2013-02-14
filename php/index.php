
<?php

/*
 * Connecting to Redis
 */

require_once "FlickrService.php";
require_once "workerPhotosetsGetList.php";
require_once "workerPhotosetsGetPhotos.php";
require_once "workerDownloadPhoto.php";

class Main {

    const REDIS_HOST = '127.0.0.1';
    const REDIS_PORT = 6379;
    const DOWNLOAD_PATH = '/home/tbilou/Pictures/';
}

$redis = new Redis() or die("Can't load redis module.");
$redis->connect(Main::REDIS_HOST, Main::REDIS_PORT);

// Create an instance of each object
$getlistWorker = new workerPhotosetsGetlist();
$getPhotosWorker = new workerPhotosetsGetPhotos();
$downloadPhotoWorker = new workerDownloadPhoto();

while (1) {
    $job = $redis->blPop(array('TB.flickr.download.photo.REQ',
        'TB.flickr.photosets.getList.REQ',
        'TB.flickr.photosets.getPhotos.REQ'), 60);

    if ($job) {

        $queue = $job[0];
        $json = $job[1];

        switch ($queue) {
            case "TB.flickr.download.photo.REQ":
                $downloadPhotoWorker->run(json_decode($json));
                break;
            case "TB.flickr.photosets.getList.REQ":
                $getlistWorker->run();
                break;
            case "TB.flickr.photosets.getPhotos.REQ":
                $getPhotosWorker->run(json_decode($json));
                break;
        }
    }
}
?>
