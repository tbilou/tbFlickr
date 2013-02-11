/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package com.tbilou.tbflickr.workers;

import com.tbilou.flickr.Photosets;
import com.tbilou.flickr.vo.GetPhotosVO;
import com.google.gson.Gson;
import java.util.Date;
import java.util.List;
import redis.clients.jedis.Jedis;
import com.tbilou.tbflickr.Main;

/**
 *
 * @author tbilou
 */
public class WorkerPhotosetsGetPhotos implements Runnable{

    private Jedis jedis;
    private Photosets photosets;
    private Gson gson;

    public WorkerPhotosetsGetPhotos() {
        jedis = new Jedis(Main.REDIS_HOST, Main.REDIS_PORT);
        photosets = new Photosets();
        gson = new Gson();
    }

    @Override
    public void run() {

        // Listen for messages on the queue
        System.out.println(new Date() + "[WorkerPhotosetsGetPhotos] Listening for messages on queue : TB.flickr.photosets.getPhotos.REQ");
        for (;;) {
            List<String> jobs = jedis.blpop(60, "TB.flickr.photosets.getPhotos.REQ");

            if (jobs == null || jobs.isEmpty()) {
                // Timeout
                System.out.println(new Date() + "[WorkerPhotosetsGetPhotos] Download Thread Timeout : Looping");

                continue;
            }

            System.out.println(new Date() + "[WorkerPhotosetsGetPhotos] Got message on queue. Processing....");
            photosets.getPhotos(gson.fromJson(jobs.get(1), GetPhotosVO.class));

        }

    }
}
