<?php

class workerPhotosetsGetlist extends FlickrService {

    public function __construct() {
        parent::__construct();
    }

    public function run() {
        echo date(DATE_RFC822) . " [workerPhotosetsGetlist] - Got message on queue TB.flickr.photosets.getList.REQ";

        // If we don't specify the perpage limit, we get all the sets at once from flickr
        $this->phpFlickr->photosets_getList();

        // Get everything into an associative array
        $sets = $this->phpFlickr->parsed_response['photosets']['photoset'];

        echo date(DATE_RFC822) . " [workerPhotosetsGetlist] - Found " . count($sets) . " sets on Flickr";

        // Iterate over the sets
        foreach ($sets as $set) {

            // When the total photos of a set is greater than the perpage limit,
            // create multiple messages

            $photos = $set['photos'];
            $pages = ceil($photos / FlickrService::PER_PAGE);

            //echo "Iterating over the pages: $pages"."</br>";
            for ($i = 0; $i < $pages; $i++) {


                $obj['id'] = $set['id'];
                $obj['name'] = $set['title'];
                $obj['total'] = $set['photos'];
                $obj['page'] = $i;
                $obj['perpage'] = FlickrService::PER_PAGE;

                // Send the message to the queue
                $this->redis->rPush("TB.flickr.photosets.getPhotos.REQ", json_encode($obj));
            }
        }
    }

}

?>
