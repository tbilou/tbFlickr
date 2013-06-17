# tbFlickr : Backup all you flickr photos

![meme](http://dl.dropbox.com/u/288561/download_all_the_photos.jpg)

## About

I love taking photos and find flickr a great place to store and share all of my photos.
I trust flickr on keeping my photos safe but you never know... 

I originally used [flickrtouchr](https://github.com/tbilou/hivelogic-flickrtouchr) to backup all my photos and it worked great.
In 2011 I replaced my flickr backup hard drive and had to start the backup from scratch.
I launched python and after a couple of days it was only at 33% complete. Downloading over 100.000 photos in a single thread was taking forever.
So I decided play around with the flickr api and write my own backup system.

## Goals

 * Multiple downloads to use available bandwidth.
 * Use hardlinks to save space
 * Learn something new
 * Have fun doing it
 * Actually use it to backup my photos

## Implementations

My first implementation was in 2011 using Tibco BW. It's a strange tool to implement a backup script but I was learning how to use it and this seemed like a good project to do it.

Later in 2012, inspired by my implementation in Tibco BW and by [this post](http://www.justincarmony.com/blog/2012/01/10/php-workers-with-redis-solo/) I've implemented a multi-thread/worker solution based on [Redis](http://redis.io/) first in JAVA and later in PHP.

I'm currently using the PHP version in my 8 year old powerbook to backup my photos.
![splash](https://dl.dropboxusercontent.com/u/288561/tbFlickr/splash.jpg)
![splash](https://dl.dropboxusercontent.com/u/288561/tbFlickr/options.jpg)
![splash](https://dl.dropboxusercontent.com/u/288561/tbFlickr/status.jpg)

## Licence

The MIT License

Copyright (c) 2012-2013 Tiago Bilou <tiagobilou@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
