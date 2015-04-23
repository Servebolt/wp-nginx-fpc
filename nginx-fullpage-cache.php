<?php
class Nginx_Fullpage_Cache {
  static function init_plugin() {
    add_filter( 'posts_results', array( 'Nginx_Fullpage_Cache', 'set_headers' ) );
  }

  static function set_headers( $posts ) {
    // Only trigger this function once.
    remove_filter( 'posts_results', 'nginx_cache_headers' );

    // Cache all archives
    if ( !is_archive() ) {
      // Loop through selected posts
      foreach ( $posts as $post ) {
        // Check if cache is disabled for the post
        if ( 'no' === get_post_meta( $post->ID, 'cache-on', true ) ) {
          self::no_cache_headers();
          return $posts;
        }
      }
    }

    header_remove( 'Cache-Control' );
    header_remove( 'Pragma' );
    header_remove( 'Expires' );
    header( 'Pragma: public' );
    header( 'Cache-Plugin: active' );
    return $posts;
  }

  static function no_cache_headers() {
    // Set no-cache headers
    header( 'Cache-Control:max-age=0, no-cache' );
    header( 'Pragma: no-cache' );
    header( 'Cache-Plugin: pending' );
  }
}

Nginx_Fullpage_Cache::init_plugin();