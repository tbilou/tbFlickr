/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package com.tbilou.flickr;

import com.tbilou.flickr.vo.DownloadPhotoVO;
import com.tbilou.flickr.vo.GetNotInSetVO;
import com.tbilou.flickr.vo.GetPhotosVO;
import com.tbilou.tbflickr.Main;
import java.io.IOException;
import java.io.Reader;
import java.io.StringReader;
import java.util.Date;
import java.util.List;
import org.jdom2.Document;
import org.jdom2.Element;
import org.jdom2.JDOMException;
import org.jdom2.input.SAXBuilder;

/**
 *
 * @author tbilou
 */
public class Photos extends FlickrService {

    public static final int PER_PAGE = 500; // Maximum allowed by Flickr

    public Photos() {
        super();
    }

    public void getNotInSet(GetNotInSetVO pvo) {
        String url = String.format("%s%s&extras=%s&per_page=%s&page=%s", Main.FLICKR_ENDPOINT, "flickr.photos.getNotInSet", "url_o", pvo.perPage, pvo.page);
        System.out.println(new Date() + "\t[Photos] Calling flickr.photos.getNotInSet URL : "+url);
        String response = makeRequest(url, true);
        
        // Iterave over the sets
        SAXBuilder builder = new SAXBuilder();
        Reader in = new StringReader(response);

        try {
            Document document = (Document) builder.build(in);
            Element rootNode = document.getRootElement();
            List photosets = rootNode.getChild("photos").getChildren("photo");
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
