<?php

/**
 * Description of workerPhotosetsGetPhotos
 *
 * @author tbilou
 */
require_once("vo/GetPhotosVO.php");

class workerPhotosetsGetPhotos extends FlickrService {

    public function __construct() {
        parent::__construct();
    }

    public function run($job) {
        echo date(DATE_RFC822) . " [workerPhotosetsGetPhotos] - Got message on queue TB.flickr.photosets.getPhotos.REQ";

        $this->phpFlickr->photosets_getPhotos($job->id, 'url_o', $job->perpage, $job->page);
        // Get everything into an associative array
        $photos = $this->phpFlickr->parsed_response['photoset']['photo'];

        echo date(DATE_RFC822) . " [workerPhotosetsGetPhotos] - Parsing photos in set [" . $job->name . "]";

        foreach ($photos as $photo) {

            $obj['id'] = $photo['id'];
            $obj['title'] = $photo['title'];
            $obj['url'] = $photo['url_o'];
            $obj['photoset'] = $job->name;

            $this->redis->rPush("TB.flickr.download.photo.REQ", json_encode($obj));
        }
    }

}

?>
