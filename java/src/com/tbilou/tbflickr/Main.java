package com.tbilou.tbflickr;

import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.Date;
import java.util.Properties;

/**
 *
 * @author tbilou
 */
public class Main {

    public static int DOWNLOAD_NTHREDS;
    public static int PHOTOSETS_GETLIST_NTHREDS;
    public static int PHOTOSETS_GETPHOTOS_NTHREDS;
    
    public static String REDIS_HOST;
    public static int REDIS_PORT;
    public static String FLICKR_ENDPOINT;
    public static String DOWNLOAD_PATH;
    public static String FLICKR_API_KEY;
    public static String FLICKR_API_SECRET;
    public static String FLICKR_OAUTH_TOKEN;
    public static String FLICKR_OAUTH_TOKEN_SECRET;

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) throws Exception {

        Properties prop = new Properties();
        try {

            //load a properties file
            System.out.println(new Date() + "[Main] loading config.properties");
            prop.load(new FileInputStream("config.properties"));

        } catch (IOException ex) {
            try {
                System.out.println("No Config file found. Writting default configs");

                prop.setProperty("REDIS_HOST", "127.0.0.1");
                prop.setProperty("REDIS_PORT", "6379");

                prop.setProperty("DOWNLOAD_NTHREDS", "10");
                prop.setProperty("PHOTOSETS_GETLIST_NTHREDS", "1");
                prop.setProperty("PHOTOSETS_GETPHOTOS_NTHREDS", "10");

                prop.setProperty("FLICKR_ENDPOINT", "http://api.flickr.com/services/rest?method=");
                prop.setProperty("DOWNLOAD_PATH", "/home/tbilou/Pictures/");
                prop.setProperty("FLICKR_API_KEY", "Insert your flickr API key here");
                prop.setProperty("FLICKR_API_SECRET", "Insert your flickr API secret here");
                prop.setProperty("FLICKR_OAUTH_TOKEN", "Insert your flickr OAuth token here");
                prop.setProperty("FLICKR_OAUTH_TOKEN_SECRET", "Insert your flickr OAuth token secret here");

                prop.store(new FileOutputStream("config.properties"), null);

            } catch (IOException ex1) {
                System.out.println(new Date() + "[Main] Error saving the config.properties file");
                throw new Exception(new Date() + "[Main] Error saving the config.properties file");
            }
        } finally {
            // Load the configs into the variables
            DOWNLOAD_NTHREDS = Integer.parseInt(prop.getProperty("DOWNLOAD_NTHREDS"));
            PHOTOSETS_GETLIST_NTHREDS = Integer.parseInt(prop.getProperty("PHOTOSETS_GETLIST_NTHREDS"));
            PHOTOSETS_GETPHOTOS_NTHREDS = Integer.parseInt(prop.getProperty("PHOTOSETS_GETPHOTOS_NTHREDS"));

            REDIS_HOST = prop.getProperty("REDIS_HOST");
            REDIS_PORT = Integer.parseInt(prop.getProperty("REDIS_PORT"));
            FLICKR_ENDPOINT = prop.getProperty("FLICKR_ENDPOINT");
            DOWNLOAD_PATH = prop.getProperty("DOWNLOAD_PATH");
            FLICKR_API_KEY = prop.getProperty("FLICKR_API_KEY");
            FLICKR_API_SECRET = prop.getProperty("FLICKR_API_SECRET");
            FLICKR_OAUTH_TOKEN = prop.getProperty("FLICKR_OAUTH_TOKEN");
            FLICKR_OAUTH_TOKEN_SECRET = prop.getProperty("FLICKR_OAUTH_TOKEN_SECRET");

            System.out.println(new Date() + "[Main] config.properties Loaded Successfully");
            System.out.println(new Date() + "[Main] Downloading files to : " + DOWNLOAD_PATH);
        }

        Factory factory = new Factory();
        factory.start();
    }
}
