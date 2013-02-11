/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package com.tbilou.tbflickr.workers;

import com.tbilou.flickr.Photosets;
import com.google.gson.Gson;
import com.tbilou.flickr.Photos;
import com.tbilou.flickr.vo.GetNotInSetVO;
import java.util.Date;
import java.util.List;
import redis.clients.jedis.Jedis;
import com.tbilou.tbflickr.Main;

/**
 *
 * @author tbilou
 */
public class WorkerPhotosetsGetList implements Runnable {

    private Jedis jedis;
    private Photosets photosets;
    private Gson gson;
    private Photos photos;

    public WorkerPhotosetsGetList() {
        jedis = new Jedis(Main.REDIS_HOST, Main.REDIS_PORT);
        photosets = new Photosets();
        gson = new Gson();
        photos = new Photos();
        

    }

    @Override
    public void run() {

        // Listen for messages on the queue
        System.out.println(new Date() + "[WorkerPhotosetsGetList] Listening for messages on queue : TB.flickr.photosets.getList.REQ");

        for (;;) {
            List<String> jobs = jedis.blpop(60, "TB.flickr.photosets.getList.REQ");

            if (jobs == null || jobs.isEmpty()) {
                // Timeout
                System.out.println(new Date() + "[WorkerPhotosetsGetList] Download Thread Timeout : Looping");

                continue;
            }

            System.out.println(new Date() + "[WorkerPhotosetsGetList] Got message on queue. Processing....");
            photosets.getList();

            // Get the photos no in a set
            GetNotInSetVO vo = new GetNotInSetVO();
            vo.name = "Not In a Set";
            photos.getNotInSet(vo);

        }
    }
}
