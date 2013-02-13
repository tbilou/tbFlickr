package com.tbilou.tbflickr.workers;

import com.tbilou.flickr.vo.DownloadPhotoVO;
import com.google.gson.Gson;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.URL;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.Date;
import java.util.List;
import redis.clients.jedis.Jedis;
import com.tbilou.tbflickr.Main;

/*
 * To change this template, choose Tools | Templates and open the template in
 * the editor.
 */
/**
 *
 * @author tbilou
 */
public class WorkerDownloadPhoto implements Runnable {

    private Jedis jedis;
    private Gson gson;
    public static int TIMEOUT = 120000; // 2 minute to download a photo

    public WorkerDownloadPhoto() {
        jedis = new Jedis(Main.REDIS_HOST, Main.REDIS_PORT);
        gson = new Gson();
    }

    @Override
    public void run() {

        System.out.println(new Date() + "[" + Thread.currentThread().getName() + "] Listening for messages on queue : TB.flickr.download.photo.REQ");

        // Listen for messages on the queue
        for (;;) {
            List<String> jobs = jedis.blpop(60, "TB.flickr.download.photo.REQ");

            if (jobs == null || jobs.isEmpty()) {
                // Timeout
                System.out.println(new Date() + "[WorkerDownloadPhoto] Download Thread Timeout : Looping");

                continue;
            }

            System.out.println(new Date() + "[WorkerDowloadPhoto] Got message on queue. Downloading");
            download(gson.fromJson(jobs.get(1), DownloadPhotoVO.class));

        }


    }

    private void download(DownloadPhotoVO job) {

        job.photoset = job.photoset.replaceAll("[\\/:\"*?<>|]", "_");
        File dir = new File(Main.DOWNLOAD_PATH + job.photoset.trim() + File.separator);
        File f = new File(dir, job.title + ".jpg");

        // The file is not a copy. Check if the file was downloaded in a previous session
        if (!(f.exists())) {

            // Check if file is already downloaded
            String path = jedis.get(job.id);

            // If the file exists a copy of the file was already downloaded
            // Make a hardlink to it
            if (path != null) {
                System.out.println(new Date() + "[WorkerDownloadPhoto] DUPLICATE Image. Making Hardlink ");
                // Make the hardlink
                Path newLink = f.toPath(); // Destination
                Path existingFile = new File(path).toPath(); // One of the existing copies
                try {
                    Files.createLink(newLink, existingFile);
                } catch (IOException | UnsupportedOperationException x) {
                    System.err.println(x);
                    try {
                        //Make a simple copy of the file
                        Files.copy(existingFile, newLink);
                    } catch (IOException ex) {
                        System.out.println(new Date() + "[WorkerDownloadPhoto] Error Copying File " + ex);
                    }
                    
                }
            } else {
                System.out.println(new Date() + "[WorkerDownloadPhoto] Downloading file : " + job.title + ".jpg");
                try {
                    dir.mkdirs();
                    saveImage(job.url, f.getAbsolutePath());
                    // Make a reference to it in Redis
                    jedis.set(job.id, f.getAbsolutePath());


                } catch (IOException ex) {
                    System.out.println(new Date() + "[WorkerDownloadPhoto] Error/Timeout Downloading/Writting File " + ex);
                }
            }

        } else {
            // Check if file exists in Redis
            String path = jedis.get(job.id);
            if (path == null) {
                // No link found. create one
                jedis.set(job.id, f.getAbsolutePath());
            }


            System.out.println(new Date() + "[WorkerDownloadPhoto] Skipping image " + job.title + ".jpg");
        }

    }

    private void saveImage(String imageUrl, String destinationFile) throws IOException {
        URL url = new URL(imageUrl);
        InputStream is = url.openStream();
        OutputStream os = new FileOutputStream(destinationFile);

        long startTime = System.currentTimeMillis();

        byte[] b = new byte[2048];
        int length;

        while ((length = is.read(b)) != -1) {
            // Check if the time is up (failsafe)
            long duration = System.currentTimeMillis() - startTime;

            if (duration > TIMEOUT) {
                System.out.println("[WorkerDownloadPhoto] TIMEOUT: Took more than 2 minutes to download the image. Aborting");
                return;
            }
            os.write(b, 0, length);
        }

        is.close();
        os.close();
    }
}
