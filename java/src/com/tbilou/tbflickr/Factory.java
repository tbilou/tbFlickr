/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package com.tbilou.tbflickr;

import java.util.ArrayList;
import java.util.Date;
import com.tbilou.tbflickr.workers.WorkerDownloadPhoto;
import com.tbilou.tbflickr.workers.WorkerPhotosetsGetList;
import com.tbilou.tbflickr.workers.WorkerPhotosetsGetPhotos;

/**
 *
 * @author tbilou
 */
public class Factory {
    

    public Factory() {
    }
    
    public void start(){
        
        System.out.println(new Date() + "[Factory] Creating workers");

       // ArrayList downloadWorkers = new ArrayList();
        
        // Loop to create the workers
        for (int i=0; i<Main.DOWNLOAD_NTHREDS; i++){
            Thread worker = new Thread(new WorkerDownloadPhoto(), "downloadThread#"+i);
            worker.start();
        }
        
         // Loop to create the workers
        for (int i=0; i<Main.PHOTOSETS_GETLIST_NTHREDS; i++){
            Thread worker = new Thread(new WorkerPhotosetsGetList());
            worker.start();
        }
        
         // Loop to create the workers
        for (int i=0; i<Main.PHOTOSETS_GETPHOTOS_NTHREDS; i++){
            Thread worker = new Thread(new WorkerPhotosetsGetPhotos());
            worker.start();
        }
         
    }

   
}
