/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package com.tbilou.flickr;

import com.tbilou.flickr.vo.DownloadPhotoVO;
import com.tbilou.flickr.vo.GetPhotosVO;
import java.io.IOException;
import java.io.Reader;
import java.io.StringReader;
import java.util.Date;
import java.util.List;
import org.jdom2.Document;
import org.jdom2.Element;
import org.jdom2.JDOMException;
import org.jdom2.input.SAXBuilder;
import com.tbilou.tbflickr.Main;

/**
 *
 * @author tbilou
 */
public class Photosets extends FlickrService {

    public static final int PER_PAGE = 500;

    public Photosets() {
        super();
    }

    public void getList() {
        String url = String.format("%s%s", Main.FLICKR_ENDPOINT, "flickr.photosets.getList");
        System.out.println(new Date() + "\t[Photosets] Calling flickr.photosets.getList URL : "+url);
        String response = makeRequest(url, true);

        try {
            // Iterave over the sets
            SAXBuilder builder = new SAXBuilder();
            Reader in = new StringReader(response);
            
            Document document = (Document) builder.build(in);
            Element rootNode = document.getRootElement();
            List photosets = rootNode.getChild("photosets").getChildren("photoset");
            for (int i = 0; i < photosets.size(); i++) {
                
                Element node = (Element) photosets.get(i);
                
                // Send a message to the queue
                int photos = Integer.parseInt(node.getAttributeValue("photos"));
                int pages = (int) Math.ceil((double) photos / (double) PER_PAGE);
                
                System.out.println(new Date() + "\t[Photosets] \tPhotoset : " + node.getChildText("title") + "("+ photos +")");

                System.out.println(new Date() + "\t[Photosets] \tSeding message(s): " + pages);
                for (int p = 1; p <= pages; p++) {
                    System.out.println(new Date() + "\t[Photosets] \t\tMessage: #" + p);
                    GetPhotosVO vo = new GetPhotosVO();

                    vo.id = node.getAttributeValue("id");
                    vo.name = node.getChildText("title");
                    vo.total = Integer.parseInt(node.getAttributeValue("photos"));
                    vo.page = p;

                    jedis.rpush("TB.flickr.photosets.getPhotos.REQ", gson.toJson(vo));
                }
            }
        } catch (IOException io) {
            System.out.println(io.getMessage());
        } catch (JDOMException jdomex) {
            System.out.println(jdomex.getMessage());
        }

    }

    public void getPhotos(GetPhotosVO pvo) {
        
        String url = String.format("%s%s&photoset_id=%s&extras=%s&per_page=%s&page=%s", Main.FLICKR_ENDPOINT, "flickr.photosets.getPhotos", pvo.id, "url_o", pvo.perPage, pvo.page);
        System.out.println(new Date() + "\t[Photosets] Calling flickr.photosets.getPhotos URL : "+url);
        String response = makeRequest(url, true);

        // Iterave over the sets
        SAXBuilder builder = new SAXBuilder();
        Reader in = new StringReader(response);

        try {
            Document document = (Document) builder.build(in);
            Element rootNode = document.getRootElement();
            List photosets = rootNode.getChild("photoset").getChildren("photo");
            for (int i = 0; i < photosets.size(); i++) {
                Element node = (Element) photosets.get(i);
                // Send a message to the queue

                DownloadPhotoVO vo = new DownloadPhotoVO();
                vo.id = node.getAttributeValue("id");
                vo.title = node.getAttributeValue("title");
                vo.url = node.getAttributeValue("url_o");
                vo.photoset = pvo.name;

                jedis.rpush("TB.flickr.download.photo.REQ", gson.toJson(vo));
            }

        } catch (IOException io) {
            System.out.println(io.getMessage());
        } catch (JDOMException jdomex) {
            System.out.println(jdomex.getMessage());
        }

    }
}
