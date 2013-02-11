/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package com.tbilou.flickr;

import com.google.gson.Gson;
import org.apache.commons.codec.digest.DigestUtils;
import org.scribe.builder.ServiceBuilder;
import org.scribe.builder.api.FlickrApi;
import org.scribe.model.OAuthRequest;
import org.scribe.model.Response;
import org.scribe.model.Token;
import org.scribe.model.Verb;
import org.scribe.oauth.OAuthService;
import redis.clients.jedis.Jedis;
import com.tbilou.tbflickr.Main;

/**
 *
 * @author tbilou
 */
abstract class FlickrService {

    protected Jedis jedis;
    protected OAuthService service;
    protected Gson gson;
    private Token accessToken;

    protected FlickrService() {
        // Create a connection the Redis Server
        jedis = new Jedis(Main.REDIS_HOST, Main.REDIS_PORT);

        service = new ServiceBuilder()
                .provider(FlickrApi.class)
                .apiKey(Main.FLICKR_API_KEY)
                .apiSecret(Main.FLICKR_API_SECRET)
                .build();
        
        accessToken = new Token(Main.FLICKR_OAUTH_TOKEN, Main.FLICKR_OAUTH_TOKEN_SECRET);

        gson = new Gson();
    }

    protected String makeRequest(String url, Boolean cache) {

        String flickrResponse = null;
        String hash = null;
        try {
            if (cache) {
                hash = DigestUtils.md5Hex(url);
                flickrResponse = jedis.get(hash);
            }

            // FIXME: add a time to live

            if (flickrResponse == null) {
                // Call Flickr to get the photosets List
                OAuthRequest request = new OAuthRequest(Verb.POST, url);
                service.signRequest(accessToken, request);
                Response response = request.send();

                // Cache the response
                if (cache) {
                    flickrResponse = response.getBody();
                    jedis.set(hash, flickrResponse);
                }
            }

        } catch (Exception e) {
            System.out.println(e.getMessage());
        }

        return flickrResponse;
    }
}
