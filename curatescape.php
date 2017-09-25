<?php

/*
Plugin Name: Curatescape for WordPress
Plugin URI: https://curatescape.org
Description: Publish location-based, media-rich, structured narratives compatible with Curatescape mobile apps. Designed for public historians, urbanists, and other humanities researchers. Adds Tour and Story post types, as well as custom taxonomies and metadata fields.
Version: 0.9
Author: CSU Center for Public History + Digital Humanities 
Author URI: http://csudigitalhumanities.org
License: GPL2
*/

/*  
Copyright 2017  CSU Center for Public History + Digital Humanities  (email : digitalhumanities@csuohio.edu)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'DEFAULT_COORDINATES', '[41.503240, -81.675249]' );
define( 'DEFAULT_ZOOM', 3 );
define( 'DISABLE_TOURS' , false );

/*
** CUSTOM POST TYPES AND TAXONOMIES
*/

include 'post_types.php';


/* 
** CUSTOM FIELDS
*/
include 'metaboxes.php';


/*
** CUSTOM JSON OUTPUT
*/	
include 'json.php';


/*
** DASHBOARD
*/
add_action( 'dashboard_glance_items' , 'curatescape_at_a_glance' );
function curatescape_at_a_glance(){
    $args = array(
        'public' => true ,
        '_builtin' => false
    );
    $post_types = get_post_types( $args , 'object' , 'and' );
    foreach( $post_types as $post_type ) {
        $count = wp_count_posts( $post_type->name );
        $num = number_format_i18n( $count->publish );
        $text = _n( $post_type->labels->singular_name, $post_type->labels->name , intval( $count->publish ) );
        if ( current_user_can( 'edit_posts' ) ) {
            $type_name = $post_type->name;
        }
        $html = '<li class="'.$type_name.'-count"><tr><a href="edit.php?post_type='.$type_name.'">';
        $html .= '<td class="first b b-' . $post_type->name . '"></td>' . $num . ' <td class="t ' . $post_type->name . '">' . $text . '</td>';
        $html .= '</a></tr></li>';
    }

}


/*
** STYLES AND SCRIPTS
*/

// Admin  
add_action( 'admin_enqueue_scripts', 'curatescape_admin_css' );
function curatescape_admin_css(){
	wp_register_style( 'curatescape_admin_css', plugin_dir_url( __FILE__ ) . 'styles/admin.css');
	wp_register_style( 'leafletcss', '//unpkg.com/leaflet@1.2.0/dist/leaflet.css');
	wp_register_script( 'leafletjs', '//unpkg.com/leaflet@1.2.0/dist/leaflet.js', '', '', false );
	wp_register_script( 'curatescape_admin_js', plugin_dir_url( __FILE__ ) . 'custom_ui/admin.js', '', '', true);

    global $pagenow;
    if ($pagenow != 'post.php' && $pagenow != 'post-new.php') {
        return;
    }   

	wp_enqueue_style( 'curatescape_admin_css' );
    wp_enqueue_style( 'leafletcss' );	    
    wp_enqueue_script( 'leafletjs' );   
    wp_enqueue_script( 'curatescape_admin_js' ); 
}

// Public  
add_action( 'wp_enqueue_scripts', 'curatescape_public_css' );
function curatescape_public_css(){
    wp_register_style( 'curatescape_public_css', plugin_dir_url( __FILE__ ) . 'styles/public.css');
	wp_enqueue_style( 'curatescape_public_css' );	
}