/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package com.tbilou.flickr.vo;

import com.tbilou.flickr.Photosets;

/**
 *
 * @author tbilou
 */
public class GetPhotosVO {

    public String id;
    public String name;
    public int total;
    public int page = 1;
    public int perPage = Photosets.PER_PAGE; 
}
