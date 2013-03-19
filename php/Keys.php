<?php

class Keys {

    // The Queues for the messages
    const PHOTOSETS_GETLIST_QUEUE = "TB.Queue.flickr.photosets.getList";
    const PHOTOSETS_GETPHOTOS_QUEUE = "TB.Queue.flickr.photosets.getPhotos";
    const DOWNLOADS_QUEUE = "TB.Queue.flickr.download.photo";

    // Manage worker instances
    const PHOTOSETS_GETLIST_KILL_QUEUE = "TB.Queue.flickr.photosets.getList.kill";
    const PHOTOSETS_GETPHOTOS_KILL_QUEUE = "TB.Queue.flickr.photosets.getPhotos.kill";
    const DOWNLOADS_KILL_QUEUE = "TB.Queue.flickr.download.photo.kill";

    // Manage totals (counters)
    const PHOTOSETS_GETLIST_INFO = "TB.Hash.flickr.photosets.getList";
    const PHOTOSETS_GETPHOTOS_INFO = "TB.Hash.flickr.photosets.getPhotos";
    const DOWNLOADS_INFO = "TB.Hash.flickr.download.photo";
    
    const DOWNLOADED_PHOTOS = "TB.Hash.downloaded.photos";
    
    const REDIS_CACHE = "TB.Hash.Cache";

    // Configuration
    const CONFIG_INFO = "TB.Hash.flickr.config";
}
?>


